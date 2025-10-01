<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait Auditable
 *
 * Rastrea automáticamente quién creó/actualizó/eliminó un registro.
 * Guarda user_id del usuario autenticado en campos específicos.
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
            if (Auth::check() && $model->hasAttribute('created_by_id')) {
                $model->created_by_id = Auth::id();
            }
        });

        // Registrar quién actualizó el registro
        static::updating(function (Model $model) {
            if (Auth::check() && $model->hasAttribute('updated_by_id')) {
                $model->updated_by_id = Auth::id();
            }
        });

        // Registrar quién eliminó el registro (soft delete)
        static::deleting(function (Model $model) {
            if (Auth::check() && $model->hasAttribute('deleted_by_id')) {
                $model->deleted_by_id = Auth::id();
                $model->save();
            }
        });
    }

    /**
     * Verifica si el model tiene un atributo específico
     */
    protected function hasAttribute(string $attribute): bool
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