<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_request_id',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
        'file_hash',
    ];

    /**
     * Tipos de documentos según normativa
     */
    public const TYPE_CERTIFICADO_MEDICO = 'certificado_medico';
    public const TYPE_CERTIFICADO_CONTROL_MEDICO = 'certificado_control_medico';
    public const TYPE_COPIA_CITACION = 'copia_citacion';
    public const TYPE_ACREDITACION_EDIL = 'acreditacion_edil';
    public const TYPE_RESOLUCION_NOMBRAMIENTO = 'resolucion_nombramiento';
    public const TYPE_HORARIO_VISADO = 'horario_visado';
    public const TYPE_HORARIO_RECUPERACION = 'horario_recuperacion';
    public const TYPE_PARTIDA_NACIMIENTO = 'partida_nacimiento';
    public const TYPE_DECLARACION_JURADA = 'declaracion_jurada_supervivencia';
    public const TYPE_OTROS = 'otros';

    /**
     * Relación con solicitud de permiso
     */
    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class);
    }

    /**
     * Obtiene la URL del archivo
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Obtiene el tamaño formateado del archivo
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Verifica si el archivo es una imagen
     */
    public function isImage(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ]);
    }

    /**
     * Verifica si el archivo es un PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Verifica si el archivo es un documento de Office
     */
    public function isOfficeDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    /**
     * Genera un hash del archivo
     */
    public static function generateFileHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Verifica la integridad del archivo
     */
    public function verifyIntegrity(): bool
    {
        if (!Storage::exists($this->file_path)) {
            return false;
        }
        
        $currentHash = self::generateFileHash(Storage::path($this->file_path));
        return $currentHash === $this->file_hash;
    }

    /**
     * Obtiene el nombre del tipo de documento
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return match($this->document_type) {
            self::TYPE_CERTIFICADO_MEDICO => 'Certificado Médico',
            self::TYPE_CERTIFICADO_CONTROL_MEDICO => 'Certificado de Control Médico',
            self::TYPE_COPIA_CITACION => 'Copia de Citación',
            self::TYPE_ACREDITACION_EDIL => 'Acreditación Edil',
            self::TYPE_RESOLUCION_NOMBRAMIENTO => 'Resolución de Nombramiento',
            self::TYPE_HORARIO_VISADO => 'Horario Visado',
            self::TYPE_HORARIO_RECUPERACION => 'Horario de Recuperación',
            self::TYPE_PARTIDA_NACIMIENTO => 'Partida de Nacimiento',
            self::TYPE_DECLARACION_JURADA => 'Declaración Jurada de Supervivencia',
            default => 'Otros'
        };
    }

    /**
     * Scope para documentos por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope para documentos válidos (que existen físicamente)
     */
    public function scopeValid($query)
    {
        return $query->whereNotNull('file_hash');
    }
}