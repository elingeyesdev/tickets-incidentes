<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para agregar campos de onboarding a la tabla 'users'
 *
 * Campos agregados:
 * - onboarding_completed: Flag que indica si completó el flujo de onboarding
 * - onboarding_completed_at: Timestamp de cuándo completó el onboarding
 *
 * IMPORTANTE: Email verification NO es prerequisito ni parte del onboarding
 * Email verification es OPCIONAL y puede hacerse en cualquier momento
 *
 * Flujo completo:
 * 1. Register → email_verified = false, onboarding_completed = false
 * 2. Complete Profile → llena first_name, last_name (ONBOARDING PASO 1, puede hacerse SIN verificar email)
 * 3. Configure Preferences → llena theme, language (ONBOARDING PASO 2)
 * 4. Sistema marca → onboarding_completed = true
 * 5. Verify Email → OPCIONAL, puede hacerse en cualquier momento
 *
 * Este flag es crítico para la protección de rutas:
 * - Zona PUBLIC: Accesible sin autenticación
 * - Zona ONBOARDING: autenticado Y onboarding_completed = false
 * - Zona AUTHENTICATED: autenticado Y onboarding_completed = true
 *
 * Relacionado con: Sistema de 3 zonas en frontend
 * Fecha: 2025-10-16
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar campo de onboarding (timestamp)
        // NOTA: onboarding_completed es un accessor calculado en el modelo User
        // que devuelve (onboarding_completed_at !== null)
        DB::statement("
            ALTER TABLE auth.users
            ADD COLUMN onboarding_completed_at TIMESTAMPTZ
        ");

        // Comentario de columna
        DB::statement("
            COMMENT ON COLUMN auth.users.onboarding_completed_at IS
            'Timestamp de cuándo completó el onboarding (NULL si aún no lo completa). Email verification NO es prerequisito del onboarding.'
        ");

        // Índice para queries de usuarios que no completaron onboarding
        DB::statement("
            CREATE INDEX idx_users_onboarding_pending
            ON auth.users(onboarding_completed_at)
            WHERE onboarding_completed_at IS NULL
        ");

        // Índice compuesto para queries de onboarding + verificación
        DB::statement("
            CREATE INDEX idx_users_onboarding_status
            ON auth.users(onboarding_completed_at, email_verified, status)
        ");

        // IMPORTANTE: Los usuarios existentes mantienen onboarding_completed = FALSE
        // Esto es intencional para forzar que completen el onboarding si se implementa
        // en un sistema existente. Si quieres marcar usuarios existentes como completos:
        //
        // DB::statement("
        //     UPDATE auth.users
        //     SET onboarding_completed = TRUE,
        //         onboarding_completed_at = created_at
        //     WHERE email_verified = TRUE
        //     AND created_at < NOW()
        // ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices
        DB::statement('DROP INDEX IF EXISTS auth.idx_users_onboarding_pending');
        DB::statement('DROP INDEX IF EXISTS auth.idx_users_onboarding_status');

        // Eliminar columna
        DB::statement('
            ALTER TABLE auth.users
            DROP COLUMN IF EXISTS onboarding_completed_at
        ');
    }
};