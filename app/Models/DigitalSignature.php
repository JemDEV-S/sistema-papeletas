<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DigitalSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_request_id',
        'user_id',
        'signature_type',
        'certificate_serial',
        'signature_hash',
        'signed_at',
        'certificate_data',
        'is_valid',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'certificate_data' => 'array',
        'is_valid' => 'boolean',
    ];

    /**
     * Tipos de firma digital
     */
    public const TYPE_REQUEST = 'request';
    public const TYPE_APPROVAL_LEVEL_1 = 'approval_level_1';
    public const TYPE_APPROVAL_LEVEL_2 = 'approval_level_2';

    /**
     * Relación con solicitud de permiso
     */
    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class);
    }

    /**
     * Relación con usuario firmante
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si es firma de solicitud
     */
    public function isRequestSignature(): bool
    {
        return $this->signature_type === self::TYPE_REQUEST;
    }

    /**
     * Verifica si es firma de aprobación nivel 1
     */
    public function isLevel1ApprovalSignature(): bool
    {
        return $this->signature_type === self::TYPE_APPROVAL_LEVEL_1;
    }

    /**
     * Verifica si es firma de aprobación nivel 2
     */
    public function isLevel2ApprovalSignature(): bool
    {
        return $this->signature_type === self::TYPE_APPROVAL_LEVEL_2;
    }

    /**
     * Obtiene el nombre del tipo de firma
     */
    public function getSignatureTypeNameAttribute(): string
    {
        return match($this->signature_type) {
            self::TYPE_REQUEST => 'Firma de Solicitud',
            self::TYPE_APPROVAL_LEVEL_1 => 'Firma de Aprobación - Nivel 1',
            self::TYPE_APPROVAL_LEVEL_2 => 'Firma de Aprobación - Nivel 2',
            default => 'Firma Desconocida'
        };
    }

    /**
     * Verifica si el certificado está vigente
     */
    public function isCertificateValid(): bool
    {
        if (!$this->certificate_data) {
            return false;
        }
        
        $certData = $this->certificate_data;
        
        // Verificar fecha de expiración
        if (isset($certData['valid_to'])) {
            $validTo = Carbon::parse($certData['valid_to']);
            if ($validTo->isPast()) {
                return false;
            }
        }
        
        // Verificar fecha de inicio
        if (isset($certData['valid_from'])) {
            $validFrom = Carbon::parse($certData['valid_from']);
            if ($validFrom->isFuture()) {
                return false;
            }
        }
        
        // Verificar estado de revocación (esto requeriría integración con OCSP)
        if (isset($certData['revoked']) && $certData['revoked']) {
            return false;
        }
        
        return true;
    }

    /**
     * Genera hash de firma
     */
    public static function generateSignatureHash(string $documentData, string $certificateSerial): string
    {
        return hash('sha256', $documentData . $certificateSerial . now()->toISOString());
    }

    /**
     * Verifica la integridad de la firma
     */
    public function verifySignatureIntegrity(string $originalDocumentData): bool
    {
        $expectedHash = self::generateSignatureHash($originalDocumentData, $this->certificate_serial);
        
        // En una implementación real, aquí se verificaría la firma digital usando la clave pública
        // Por ahora, verificamos que el hash coincida
        return $this->signature_hash === $expectedHash;
    }

    /**
     * Obtiene información del firmante desde el certificado
     */
    public function getSignerInfoAttribute(): ?array
    {
        if (!$this->certificate_data) {
            return null;
        }
        
        return [
            'name' => $this->certificate_data['subject']['CN'] ?? null,
            'dni' => $this->certificate_data['subject']['serialNumber'] ?? null,
            'organization' => $this->certificate_data['subject']['O'] ?? null,
            'country' => $this->certificate_data['subject']['C'] ?? null,
            'issuer' => $this->certificate_data['issuer']['CN'] ?? null,
        ];
    }

    /**
     * Verifica si la firma corresponde al usuario correcto
     */
    public function belongsToUser(): bool
    {
        $signerInfo = $this->signer_info;
        
        if (!$signerInfo || !isset($signerInfo['dni'])) {
            return false;
        }
        
        return $this->user->dni === $signerInfo['dni'];
    }

    /**
     * Obtiene el tiempo transcurrido desde la firma
     */
    public function getTimeElapsedAttribute(): string
    {
        return $this->signed_at->diffForHumans();
    }

    /**
     * Valida toda la firma (integridad, vigencia, usuario)
     */
    public function validateSignature(string $originalDocumentData = null): array
    {
        $errors = [];
        
        if (!$this->is_valid) {
            $errors[] = 'La firma ha sido marcada como inválida';
        }
        
        if (!$this->isCertificateValid()) {
            $errors[] = 'El certificado digital no es válido o ha expirado';
        }
        
        if (!$this->belongsToUser()) {
            $errors[] = 'La firma no corresponde al usuario indicado';
        }
        
        if ($originalDocumentData && !$this->verifySignatureIntegrity($originalDocumentData)) {
            $errors[] = 'La integridad de la firma no pudo ser verificada';
        }
        
        return $errors;
    }

    /**
     * Invalida la firma
     */
    public function invalidate(string $reason = null): void
    {
        $this->update([
            'is_valid' => false,
            'certificate_data' => array_merge($this->certificate_data ?? [], [
                'invalidated_at' => now()->toISOString(),
                'invalidation_reason' => $reason
            ])
        ]);
    }

    /**
     * Scopes
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('signature_type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('signed_at', '>=', now()->subDays($days));
    }

    public function scopeByCertificateSerial($query, string $serial)
    {
        return $query->where('certificate_serial', $serial);
    }
}