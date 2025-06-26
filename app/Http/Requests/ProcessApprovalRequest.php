<?php

namespace App\Http\Requests;

use App\Models\PermissionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permissionRequest = $this->route('permission');
        $user = auth()->user();

        // Verificar que el usuario puede aprobar esta solicitud
        if ($user->hasRole('jefe_inmediato')) {
            return $permissionRequest->status === PermissionRequest::STATUS_PENDING_SUPERVISOR &&
                   $user->subordinates()->where('id', $permissionRequest->user_id)->exists();
        }

        if ($user->hasRole('jefe_rrhh')) {
            return $permissionRequest->status === PermissionRequest::STATUS_PENDING_HR;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['approve', 'reject']),
            ],
            'comments' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(function () {
                    return $this->input('action') === 'reject';
                }),
            ],
            'digital_signature' => [
                'sometimes',
                'boolean',
            ],
            'signature_data' => [
                'nullable',
                'array',
            ],
            'signature_data.certificate_serial' => [
                'required_with:signature_data',
                'string',
                'max:255',
            ],
            'signature_data.certificate_data' => [
                'required_with:signature_data',
                'array',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Debe especificar una acción (aprobar o rechazar).',
            'action.in' => 'La acción debe ser "aprobar" o "rechazar".',
            'comments.required_if' => 'Los comentarios son obligatorios al rechazar una solicitud.',
            'comments.max' => 'Los comentarios no pueden exceder 1000 caracteres.',
            'signature_data.certificate_serial.required_with' => 'El número de serie del certificado es requerido.',
            'signature_data.certificate_data.required_with' => 'Los datos del certificado son requeridos.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateApprovalConditions($validator);
            $this->validateDigitalSignature($validator);
        });
    }

    /**
     * Validate approval specific conditions.
     */
    protected function validateApprovalConditions($validator): void
    {
        $permissionRequest = $this->route('permission');
        $user = auth()->user();

        // Verificar que la solicitud no haya sido procesada ya
        $existingApproval = $permissionRequest->approvals()
            ->where('approver_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->first();

        if ($existingApproval) {
            $validator->errors()->add(
                'permission',
                'Esta solicitud ya fue procesada por usted anteriormente.'
            );
        }

        // Verificar requisitos específicos para aprobación
        if ($this->input('action') === 'approve') {
            $this->validateApprovalRequirements($validator, $permissionRequest);
        }

        // Verificar que los comentarios sean apropiados para el rechazo
        if ($this->input('action') === 'reject') {
            $this->validateRejectionComments($validator);
        }
    }

    /**
     * Validate requirements for approval.
     */
    protected function validateApprovalRequirements($validator, PermissionRequest $permissionRequest): void
    {
        $permissionType = $permissionRequest->permissionType;

        // Verificar documentos requeridos
        if ($permissionType->requires_document) {
            $requiredDocs = $permissionType->getRequiredDocuments();
            $uploadedDocs = $permissionRequest->documents->pluck('document_type')->toArray();
            
            foreach ($requiredDocs as $requiredDoc) {
                if (!in_array($requiredDoc, $uploadedDocs)) {
                    $validator->errors()->add(
                        'documents',
                        "No se puede aprobar: falta el documento requerido '{$requiredDoc}'."
                    );
                }
            }
        }

        // Verificar integridad de documentos
        foreach ($permissionRequest->documents as $document) {
            if (!$document->verifyIntegrity()) {
                $validator->errors()->add(
                    'documents',
                    "No se puede aprobar: el documento '{$document->original_name}' está corrupto o ha sido modificado."
                );
            }
        }

        // Validaciones específicas por tipo de permiso
        $this->validateSpecificApprovalRules($validator, $permissionRequest);
    }

    /**
     * Validate specific approval rules by permission type.
     */
    protected function validateSpecificApprovalRules($validator, PermissionRequest $permissionRequest): void
    {
        $permissionType = $permissionRequest->permissionType;
        $user = auth()->user();

        switch ($permissionType->code) {
            case 'REPRESENTACION_CULTURAL':
                // Requiere aprobación expresa del jefe inmediato
                if ($user->hasRole('jefe_inmediato') && !$this->hasExplicitApproval()) {
                    $validator->errors()->add(
                        'approval',
                        'Este tipo de permiso requiere aprobación expresa del jefe inmediato.'
                    );
                }
                break;

            case 'COMISION_SERVICIOS':
                // Verificar que tenga autorización oficial
                if (!$this->hasOfficialAuthorization($permissionRequest)) {
                    $validator->errors()->add(
                        'authorization',
                        'Las comisiones de servicio requieren autorización oficial previa.'
                    );
                }
                break;

            case 'DOCENCIA_UNIVERSITARIA':
                // Verificar límite de horas semanales
                if (!$this->validateWeeklyHoursLimit($permissionRequest)) {
                    $validator->errors()->add(
                        'hours',
                        'Se excede el límite de 6 horas semanales para docencia universitaria.'
                    );
                }
                break;
        }
    }

    /**
     * Validate rejection comments.
     */
    protected function validateRejectionComments($validator): void
    {
        $comments = $this->input('comments');
        
        if (empty(trim($comments))) {
            $validator->errors()->add(
                'comments',
                'Debe proporcionar una razón detallada para el rechazo.'
            );
            return;
        }

        if (strlen(trim($comments)) < 10) {
            $validator->errors()->add(
                'comments',
                'Los comentarios de rechazo deben tener al menos 10 caracteres.'
            );
        }

        // Verificar que no contenga palabras inapropiadas (lista básica)
        $inappropriateWords = ['idiota', 'estúpido', 'tonto']; // Expandir según necesidad
        $lowerComments = strtolower($comments);
        
        foreach ($inappropriateWords as $word) {
            if (strpos($lowerComments, $word) !== false) {
                $validator->errors()->add(
                    'comments',
                    'Los comentarios deben mantener un tono profesional y respetuoso.'
                );
                break;
            }
        }
    }

    /**
     * Validate digital signature if provided.
     */
    protected function validateDigitalSignature($validator): void
    {
        if (!$this->filled('signature_data')) {
            return;
        }

        $signatureData = $this->input('signature_data');
        
        // Validar formato del certificado
        if (!isset($signatureData['certificate_data']['subject'])) {
            $validator->errors()->add(
                'signature_data',
                'Los datos del certificado digital no tienen el formato correcto.'
            );
        }

        // Validar que el certificado pertenezca al usuario
        $user = auth()->user();
        if (isset($signatureData['certificate_data']['subject']['serialNumber'])) {
            $certDni = $signatureData['certificate_data']['subject']['serialNumber'];
            if ($certDni !== $user->dni) {
                $validator->errors()->add(
                    'signature_data',
                    'El certificado digital no corresponde al usuario actual.'
                );
            }
        }

        // Validar vigencia del certificado
        if (isset($signatureData['certificate_data']['valid_to'])) {
            $validTo = \Carbon\Carbon::parse($signatureData['certificate_data']['valid_to']);
            if ($validTo->isPast()) {
                $validator->errors()->add(
                    'signature_data',
                    'El certificado digital ha expirado.'
                );
            }
        }
    }

    // Métodos auxiliares para validaciones específicas

    private function hasExplicitApproval(): bool
    {
        return $this->filled('explicit_approval') && $this->input('explicit_approval') === true;
    }

    private function hasOfficialAuthorization(PermissionRequest $request): bool
    {
        // Verificar si tiene documentos de autorización oficial
        return $request->documents()
            ->where('document_type', 'autorizacion_oficial')
            ->exists();
    }

    private function validateWeeklyHoursLimit(PermissionRequest $request): bool
    {
        $startDate = \Carbon\Carbon::parse($request->start_datetime);
        $weekStart = $startDate->copy()->startOfWeek();
        $weekEnd = $startDate->copy()->endOfWeek();

        // Calcular horas ya aprobadas en la semana
        $weeklyHours = $request->user->permissionRequests()
            ->whereHas('permissionType', function ($q) {
                $q->where('code', 'DOCENCIA_UNIVERSITARIA');
            })
            ->where('status', 'approved')
            ->whereBetween('start_datetime', [$weekStart, $weekEnd])
            ->sum('requested_hours');

        return ($weeklyHours + $request->requested_hours) <= 6;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar comentarios
        if ($this->comments) {
            $this->merge([
                'comments' => trim($this->comments),
            ]);
        }

        // Asegurar que action esté en minúsculas
        if ($this->action) {
            $this->merge([
                'action' => strtolower(trim($this->action)),
            ]);
        }
    }
}