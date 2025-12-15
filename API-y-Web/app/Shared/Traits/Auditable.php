<?php

namespace App\Shared\Traits;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Auditable
 *
 * Rastrea automáticamente quién creó/actualizó/eliminó un registro.
 * Guarda user_id del usuario autenticado en campos específicos.
 * Uses JWT authentication via JWTHelper.
 *
 * @usage
 * ```php
 * class Ticket extends Model
 * {
 *     use Auditable;
 *
 *     // Campos opcionales en la migración:
 *     // - created_by_id (UUID)
 *     // - updated_by_id (UUID)
 *     // - deleted_by_id (UUID)
 * }
 * ```
 */
trait Auditable
{
    /**
     * Boot del trait
     */
    protected static function bootAuditable(): void
    {
        // Registrar quién creó el registro
        static::creating(function (Model $model) {
            if ($model->hasAttribute('created_by_id')) {
                try {
                    $userId = JWTHelper::getUserId();
                    $model->created_by_id = $userId;
                } catch (\Exception $e) {
                    // Allow creation without authenticated user (e.g., seeders, console commands)
                }
            }
        });

        // Registrar quién actualizó el registro
        static::updating(function (Model $model) {
            if ($model->hasAttribute('updated_by_id')) {
                try {
                    $userId = JWTHelper::getUserId();
                    $model->updated_by_id = $userId;
                } catch (\Exception $e) {
                    // Allow updates without authenticated user
                }
            }
        });

        // Registrar quién eliminó el registro (soft delete)
        static::deleting(function (Model $model) {
            if ($model->hasAttribute('deleted_by_id')) {
                try {
                    $userId = JWTHelper::getUserId();
                    $model->deleted_by_id = $userId;
                    $model->save();
                } catch (\Exception $e) {
                    // Allow deletes without authenticated user
                }
            }
        });
    }

    /**
     * Verifica si el model tiene un atributo específico
     */
    public function hasAttribute($attribute): bool
    {
        return array_key_exists($attribute, $this->attributes)
            || $this->isFillable($attribute);
    }

    /**
     * Relación con el usuario que creó el registro
     */
    public function createdBy()
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Features\UserManagement\Models\User::class),
            'created_by_id'
        );
    }

    /**
     * Relación con el usuario que actualizó el registro
     */
    public function updatedBy()
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Features\UserManagement\Models\User::class),
            'updated_by_id'
        );
    }

    /**
     * Relación con el usuario que eliminó el registro
     */
    public function deletedBy()
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Features\UserManagement\Models\User::class),
            'deleted_by_id'
        );
    }
}