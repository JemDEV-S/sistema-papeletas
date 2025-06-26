<?php

namespace App\Http\Requests;

use App\Models\PermissionType;
use App\Rules\WorkingHoursRule;
use App\Rules\WorkingDayRule;
use App\Rules\FutureDateRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permissionRequest = $this->route('permission');
        
        return auth()->check() && 
               (auth()->user()->id === $permissionRequest->user_id || 
                auth()->user()->hasPermission('manage_all_requests'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $permissionRequest = $this->route('permission');
        
        return [
            'permission_type_id' => [
                'required',
                'exists:permission_types,id',
                Rule::exists('permission_types', 'id')->where('is_active', true),
            ],
            'start_datetime' => [
                'required',
                'date',
                'after:now',
                new FutureDateRule(),
                new WorkingDayRule(),
                new WorkingHoursRule(),
            ],
            'end_datetime' => [
                'required',
                'date',
                'after:start_datetime',
                new WorkingDayRule(),
                new WorkingHoursRule(),
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
            'priority' => [
                'sometimes',
                'integer',
                'between:1,3',
            ],
            'is_urgent' => [
                'sometimes',
                'boolean',
            ],
            'additional_info' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'permission_type_id.required' => 'Debe seleccionar un tipo de permiso.',
            'permission_type_id.exists' => 'El tipo de permiso seleccionado no es válido.',
            'start_datetime.required' => 'La fecha y hora de inicio son requeridas.',
            'start_datetime.after' => 'La fecha de inicio debe ser posterior al momento actual.',
            'end_datetime.required' => 'La fecha y hora de fin son requeridas.',
            'end_datetime.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'reason.required' => 'Debe especificar el motivo de la solicitud.',
            'reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePermissionStatus($validator);
            $this->validatePermissionTypeRules($validator);
            $this->validateConflictingRequests($validator);
        });
    }

    /**
     * Validate that permission can be updated.
     */
    protected function validatePermissionStatus($validator): void
    {
        $permissionRequest = $this->route('permission');
        
        if ($permissionRequest->status !== 'draft') {
            $validator->errors()->add(
                'permission',
                'Solo se pueden editar solicitudes en estado borrador.'
            );
        }
    }

    /**
     * Validate conflicting requests excluding current request.
     */
    protected function validateConflictingRequests($validator): void
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return;
        }

        $user = auth()->user();
        $permissionRequest = $this->route('permission');
        $startDate = Carbon::parse($this->start_datetime);
        $endDate = Carbon::parse($this->end_datetime);

        $conflictingRequests = $user->permissionRequests()
            ->where('id', '!=', $permissionRequest->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_datetime', [$startDate, $endDate])
                    ->orWhereBetween('end_datetime', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_datetime', '<=', $startDate)
                          ->where('end_datetime', '>=', $endDate);
                    });
            })
            ->exists();

        if ($conflictingRequests) {
            $validator->errors()->add(
                'start_datetime',
                'Ya tiene otra solicitud de permiso en el período seleccionado.'
            );
        }
    }

    /**
     * Validate permission type specific rules.
     */
    protected function validatePermissionTypeRules($validator): void
    {
        if (!$this->permission_type_id || !$this->start_datetime || !$this->end_datetime) {
            return;
        }

        $permissionType = PermissionType::find($this->permission_type_id);
        if (!$permissionType) {
            return;
        }

        $startDate = Carbon::parse($this->start_datetime);
        $endDate = Carbon::parse($this->end_datetime);
        $hours = $startDate->diffInHours($endDate, true);

        if ($permissionType->max_hours_per_day && $hours > $permissionType->max_hours_per_day) {
            $validator->errors()->add(
                'end_datetime',
                "Las horas solicitadas ({$hours}) exceden el máximo permitido por día ({$permissionType->max_hours_per_day})."
            );
        }
    }
}