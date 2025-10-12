<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para agregar el campo 'revocation_reason' a la tabla 'user_roles'
 *
 * Este campo almacena la razón por la cual un rol fue revocado,
 * útil para auditoría y cumplimiento legal (GDPR, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna revocation_reason
        DB::statement("
            ALTER TABLE auth.user_roles
            ADD COLUMN revocation_reason TEXT
        ");

        // Agregar comentario
        DB::statement("
            COMMENT ON COLUMN auth.user_roles.revocation_reason IS
            'Razón por la cual el rol fue revocado (para auditoría)'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE auth.user_roles
            DROP COLUMN IF EXISTS revocation_reason
        ");
    }
};