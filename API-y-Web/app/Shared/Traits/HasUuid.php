<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Automáticamente genera UUIDs como primary key para models.
 * Reemplaza auto-increment integers con UUIDs.
 *
 * @usage
 * ```php
 * class User extends Model
 * {
 *     use HasUuid;
 *
 *     protected $keyType = 'string';
 *     public $incrementing = false;
 * }
 * ```
 */
trait HasUuid
{
    /**
     * Boot del trait
     *
     * Se ejecuta automáticamente cuando el model se inicializa.
     * Genera UUID antes de crear el registro.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            // Si no tiene ID asignado, generar UUID
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Inicialización del trait
     *
     * Configura automáticamente las propiedades del model.
     */
    public function initializeHasUuid(): void
    {
        // Asegurar que el key type es string
        $this->keyType = 'string';

        // Desactivar auto-increment
        $this->incrementing = false;
    }

    /**
     * Obtiene el UUID del modelo como string
     */
    public function getUuid(): string
    {
        return (string) $this->getAttribute($this->getKeyName());
    }

    /**
     * Scope para buscar por UUID
     *
     * @usage User::whereUuid('550e8400-...')
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->getKeyName(), $uuid);
    }
}