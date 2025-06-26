<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BiometricRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_request_id',
        'record_type',
        'timestamp',
        'biometric_data_hash',
        'device_id',
        'latitude',
        'longitude',
        'is_valid',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_valid' => 'boolean',
    ];

    /**
     * Tipos de registro biométrico
     */
    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_PERMISSION_START = 'permission_start';
    public const TYPE_PERMISSION_END = 'permission_end';

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con solicitud de permiso
     */
    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class);
    }

    /**
     * Verifica si es una entrada
     */
    public function isEntry(): bool
    {
        return $this->record_type === self::TYPE_ENTRY;
    }

    /**
     * Verifica si es una salida
     */
    public function isExit(): bool
    {
        return $this->record_type === self::TYPE_EXIT;
    }

    /**
     * Verifica si es inicio de permiso
     */
    public function isPermissionStart(): bool
    {
        return $this->record_type === self::TYPE_PERMISSION_START;
    }

    /**
     * Verifica si es fin de permiso
     */
    public function isPermissionEnd(): bool
    {
        return $this->record_type === self::TYPE_PERMISSION_END;
    }

    /**
     * Obtiene el nombre del tipo de registro
     */
    public function getRecordTypeNameAttribute(): string
    {
        return match($this->record_type) {
            self::TYPE_ENTRY => 'Entrada',
            self::TYPE_EXIT => 'Salida',
            self::TYPE_PERMISSION_START => 'Inicio de Permiso',
            self::TYPE_PERMISSION_END => 'Fin de Permiso',
            default => 'Desconocido'
        };
    }

    /**
     * Verifica si el registro tiene coordenadas GPS
     */
    public function hasLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Obtiene las coordenadas como array
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasLocation()) {
            return null;
        }
        
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude
        ];
    }

    /**
     * Genera hash de datos biométricos
     */
    public static function generateBiometricHash(string $biometricData): string
    {
        return hash('sha256', $biometricData . config('app.key'));
    }

    /**
     * Verifica si el registro está dentro del horario laboral
     */
    public function isWithinWorkingHours(): bool
    {
        $workStart = SystemConfiguration::get('working_hours_start', '08:00');
        $workEnd = SystemConfiguration::get('working_hours_end', '17:00');
        
        $recordTime = $this->timestamp->format('H:i');
        
        return $recordTime >= $workStart && $recordTime <= $workEnd;
    }

    /**
     * Obtiene el último registro del usuario para el día
     */
    public static function getLastRecordForUserToday(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereDate('timestamp', Carbon::today())
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    /**
     * Verifica si hay un permiso activo para el momento del registro
     */
    public function hasActivePermission(): bool
    {
        if (!$this->permission_request_id) {
            return false;
        }
        
        $permission = $this->permissionRequest;
        
        return $permission && 
               $permission->isApproved() && 
               $this->timestamp->between(
                   $permission->start_datetime, 
                   $permission->end_datetime
               );
    }

    /**
     * Calcula las horas trabajadas entre dos registros
     */
    public static function calculateWorkingHours(self $entryRecord, self $exitRecord): float
    {
        if ($entryRecord->user_id !== $exitRecord->user_id) {
            return 0;
        }
        
        if (!$entryRecord->isEntry() || !$exitRecord->isExit()) {
            return 0;
        }
        
        return $entryRecord->timestamp->diffInHours($exitRecord->timestamp, true);
    }

    /**
     * Valida la secuencia de registros del día
     */
    public function validateDailySequence(): array
    {
        $errors = [];
        $userRecords = self::where('user_id', $this->user_id)
            ->whereDate('timestamp', $this->timestamp->toDateString())
            ->orderBy('timestamp')
            ->get();
        
        $expectedNext = self::TYPE_ENTRY;
        
        foreach ($userRecords as $record) {
            if ($record->record_type !== $expectedNext) {
                $errors[] = "Secuencia incorrecta: se esperaba {$expectedNext} pero se registró {$record->record_type}";
            }
            
            $expectedNext = match($record->record_type) {
                self::TYPE_ENTRY => self::TYPE_EXIT,
                self::TYPE_EXIT => self::TYPE_ENTRY,
                self::TYPE_PERMISSION_START => self::TYPE_PERMISSION_END,
                self::TYPE_PERMISSION_END => self::TYPE_ENTRY,
                default => self::TYPE_ENTRY
            };
        }
        
        return $errors;
    }

    /**
     * Scopes
     */
    public function scopeToday($query)
    {
        return $query->whereDate('timestamp', Carbon::today());
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    public function scopeWithPermission($query)
    {
        return $query->whereNotNull('permission_request_id');
    }

    public function scopeByDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}