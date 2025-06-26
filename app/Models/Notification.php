<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Tipos de notificación
     */
    public const TYPE_EMAIL = 'email';
    public const TYPE_SMS = 'sms';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_PUSH = 'push';

    /**
     * Categorías de notificaciones
     */
    public const CATEGORY_PERMISSION_REQUEST = 'permission_request';
    public const CATEGORY_APPROVAL = 'approval';
    public const CATEGORY_REJECTION = 'rejection';
    public const CATEGORY_REMINDER = 'reminder';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_BIOMETRIC = 'biometric';

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marca la notificación como leída
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Marca la notificación como no leída
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Verifica si la notificación fue enviada
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Marca la notificación como enviada
     */
    public function markAsSent(): void
    {
        if (!$this->isSent()) {
            $this->update(['sent_at' => now()]);
        }
    }

    /**
     * Obtiene el tiempo transcurrido desde el envío
     */
    public function getTimeAgoAttribute(): string
    {
        if (!$this->sent_at) {
            return 'No enviada';
        }
        
        return $this->sent_at->diffForHumans();
    }

    /**
     * Obtiene la categoría desde los datos
     */
    public function getCategoryAttribute(): ?string
    {
        return $this->data['category'] ?? null;
    }

    /**
     * Obtiene el ID de la solicitud relacionada
     */
    public function getRelatedRequestIdAttribute(): ?int
    {
        return $this->data['permission_request_id'] ?? null;
    }

    /**
     * Obtiene la URL de acción si existe
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Verifica si la notificación tiene una acción asociada
     */
    public function hasAction(): bool
    {
        return !is_null($this->action_url);
    }

    /**
     * Obtiene el ícono para mostrar en la UI
     */
    public function getIconAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_PERMISSION_REQUEST => 'file-text',
            self::CATEGORY_APPROVAL => 'check-circle',
            self::CATEGORY_REJECTION => 'x-circle',
            self::CATEGORY_REMINDER => 'clock',
            self::CATEGORY_BIOMETRIC => 'fingerprint',
            self::CATEGORY_SYSTEM => 'settings',
            default => 'bell'
        };
    }

    /**
     * Obtiene el color para mostrar en la UI
     */
    public function getColorAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_PERMISSION_REQUEST => 'blue',
            self::CATEGORY_APPROVAL => 'green',
            self::CATEGORY_REJECTION => 'red',
            self::CATEGORY_REMINDER => 'yellow',
            self::CATEGORY_BIOMETRIC => 'purple',
            self::CATEGORY_SYSTEM => 'gray',
            default => 'blue'
        };
    }

    /**
     * Crea una notificación de solicitud de permiso
     */
    public static function createPermissionRequestNotification(
        User $user, 
        PermissionRequest $request,
        string $type = self::TYPE_SYSTEM
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => 'Nueva solicitud de permiso pendiente',
            'message' => "Tienes una nueva solicitud de permiso de {$request->user->full_name} por revisar.",
            'data' => [
                'category' => self::CATEGORY_PERMISSION_REQUEST,
                'permission_request_id' => $request->id,
                'action_url' => route('permissions.show', $request->id),
            ],
        ]);
    }

    /**
     * Crea una notificación de aprobación
     */
    public static function createApprovalNotification(
        User $user,
        PermissionRequest $request,
        string $approverName,
        string $type = self::TYPE_SYSTEM
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => 'Solicitud de permiso aprobada',
            'message' => "Tu solicitud de permiso #{$request->request_number} ha sido aprobada por {$approverName}.",
            'data' => [
                'category' => self::CATEGORY_APPROVAL,
                'permission_request_id' => $request->id,
                'action_url' => route('permissions.show', $request->id),
            ],
        ]);
    }

    /**
     * Crea una notificación de rechazo
     */
    public static function createRejectionNotification(
        User $user,
        PermissionRequest $request,
        string $approverName,
        string $reason,
        string $type = self::TYPE_SYSTEM
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => 'Solicitud de permiso rechazada',
            'message' => "Tu solicitud de permiso #{$request->request_number} ha sido rechazada por {$approverName}. Motivo: {$reason}",
            'data' => [
                'category' => self::CATEGORY_REJECTION,
                'permission_request_id' => $request->id,
                'action_url' => route('permissions.show', $request->id),
                'rejection_reason' => $reason,
            ],
        ]);
    }

    /**
     * Crea una notificación de recordatorio
     */
    public static function createReminderNotification(
        User $user,
        string $title,
        string $message,
        array $data = [],
        string $type = self::TYPE_SYSTEM
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => array_merge($data, [
                'category' => self::CATEGORY_REMINDER,
            ]),
        ]);
    }

    /**
     * Elimina notificaciones antiguas
     */
    public static function cleanupOldNotifications(int $daysToKeep = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return self::where('created_at', '<', $cutoffDate)
                   ->where('is_read', true)
                   ->delete();
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->whereJsonContains('data->category', $category);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}