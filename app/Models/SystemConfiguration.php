<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'data_type',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Obtiene el valor parseado según el tipo de dato
     */
    public function getParsedValueAttribute()
    {
        return match($this->data_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value
        };
    }

    /**
     * Establece el valor según el tipo de dato
     */
    public function setParsedValue($value): void
    {
        $this->value = match($this->data_type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value
        };
    }

    /**
     * Obtiene una configuración por clave
     */
    public static function get(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();
        
        return $config ? $config->parsed_value : $default;
    }

    /**
     * Establece una configuración
     */
    public static function set(string $key, $value, string $dataType = 'string'): self
    {
        $config = static::updateOrCreate(
            ['key' => $key],
            [
                'data_type' => $dataType,
                'is_public' => false,
            ]
        );
        
        $config->setParsedValue($value);
        $config->save();
        
        return $config;
    }

    /**
     * Obtiene todas las configuraciones públicas
     */
    public static function getPublicConfigurations(): array
    {
        return static::where('is_public', true)
            ->get()
            ->mapWithKeys(function ($config) {
                return [$config->key => $config->parsed_value];
            })
            ->toArray();
    }

    /**
     * Scope para configuraciones públicas
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope para configuraciones privadas
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }
}