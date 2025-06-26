<?php

namespace App\Http\Requests;

use App\Models\PermissionType;
use App\Rules\WorkingHoursRule;
use App\Rules\WorkingDayRule;
use App\Rules\FutureDateRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('create_permission_request');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
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
            'priority.between' => 'La prioridad debe estar entre 1 y 3.',
            'additional_info.max' => 'La información adicional no puede exceder 1000 caracteres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePermissionTypeRules($validator);
            $this->validateTimeRange($validator);
            $this->validateConflictingRequests($validator);
        });
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

        // Validar horas máximas por día
        if ($permissionType->max_hours_per_day && $hours > $permissionType->max_hours_per_day) {
            $validator->errors()->add(
                'end_datetime',
                "Las horas solicitadas ({$hours}) exceden el máximo permitido por día ({$permissionType->max_hours_per_day})."
            );
        }

        // Validar reglas específicas del tipo de permiso
        $this->validateSpecificPermissionRules($validator, $permissionType, $startDate, $endDate, $hours);
    }

    /**
     * Validate specific permission type rules.
     */
    protected function validateSpecificPermissionRules($validator, PermissionType $permissionType, Carbon $startDate, Carbon $endDate, float $hours): void
    {
        switch ($permissionType->code) {
            case PermissionType::LACTANCIA:
                if ($hours > 1) {
                    $validator->errors()->add('end_datetime', 'Los permisos por lactancia no pueden exceder 1 hora diaria.');
                }
                break;

            case PermissionType::ASUNTOS_PARTICULARES:
                if ($hours > 2) {
                    $validator->errors()->add('end_datetime', 'Los permisos por asuntos particulares no pueden exceder 2 horas por día.');
                }
                break;

            case PermissionType::DOCENCIA_UNIVERSITARIA:
                if ($hours > 6) {
                    $validator->errors()->add('end_datetime', 'Los permisos por docencia universitaria no pueden exceder 6 horas.');
                }
                break;

            case PermissionType::ENFERMEDAD:
                if ($hours > 4) {
                    $validator->errors()->add('end_datetime', 'Los permisos por enfermedad no pueden exceder 4 horas, salvo justificación especial.');
                }
                break;
        }
    }

    /**
     * Validate time range consistency.
     */
    protected function validateTimeRange($validator): void
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return;
        }

        $startDate = Carbon::parse($this->start_datetime);
        $endDate = Carbon::parse($this->end_datetime);

        // Verificar que las fechas estén en el mismo día
        if (!$startDate->isSameDay($endDate)) {
            $validator->errors()->add(
                'end_datetime',
                'Los permisos deben solicitarse dentro del mismo día laboral.'
            );
        }

        // Verificar duración mínima (15 minutos)
        if ($startDate->diffInMinutes($endDate) < 15) {
            $validator->errors()->add(
                'end_datetime',
                'La duración mínima del permiso es de 15 minutos.'
            );
        }

        // Verificar duración máxima (8 horas)
        if ($startDate->diffInHours($endDate) > 8) {
            $validator->errors()->add(
                'end_datetime',
                'La duración máxima del permiso es de 8 horas.'
            );
        }
    }

    /**
     * Validate conflicting requests.
     */
    protected function validateConflictingRequests($validator): void
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return;
        }

        $user = auth()->user();
        $startDate = Carbon::parse($this->start_datetime);
        $endDate = Carbon::parse($this->end_datetime);

        // Verificar solicitudes superpuestas
        $conflictingRequests = $user->permissionRequests()
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
                'Ya tiene una solicitud de permiso en el período seleccionado.'
            );
        }
    }

    /**
     * Get validated data with additional processing.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Set default values
        $data['priority'] = $data['priority'] ?? 2;
        $data['is_urgent'] = $data['is_urgent'] ?? false;

        return $data;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir fechas a formato estándar si es necesario
        if ($this->start_datetime) {
            $this->merge([
                'start_datetime' => Carbon::parse($this->start_datetime)->format('Y-m-d H:i:s'),
            ]);
        }

        if ($this->end_datetime) {
            $this->merge([
                'end_datetime' => Carbon::parse($this->end_datetime)->format('Y-m-d H:i:s'),
            ]);
        }

        // Limpiar campos de texto
        if ($this->reason) {
            $this->merge([
                'reason' => trim($this->reason),
            ]);
        }

        if ($this->additional_info) {
            $this->merge([
                'additional_info' => trim($this->additional_info),
            ]);
        }
    }
}