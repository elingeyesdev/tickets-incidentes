<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix unique constraint on company_invitations table.
 * 
 * PROBLEMA: El constraint actual (company_id, user_id, status) previene:
 * - Re-invitar a un ex-agente (ya tiene una invitación ACCEPTED)
 * - Múltiples invitaciones históricas con el mismo status
 * 
 * SOLUCIÓN: Cambiar a un índice parcial que solo aplica a PENDING.
 * Esto permite:
 * - Múltiples invitaciones ACCEPTED/REJECTED (historial)
 * - Solo UNA invitación PENDING a la vez (evita spam)
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Eliminar el constraint actual (es un UNIQUE constraint, no solo index)
        DB::statement('ALTER TABLE business.company_invitations DROP CONSTRAINT IF EXISTS unique_pending_invitation');
        
        // 2. Crear índice parcial que solo aplica a PENDING
        // PostgreSQL partial unique index
        DB::statement("
            CREATE UNIQUE INDEX unique_pending_invitation 
            ON business.company_invitations (company_id, user_id) 
            WHERE status = 'PENDING'
        ");
    }

    public function down(): void
    {
        // Revertir al constraint original
        DB::statement('DROP INDEX IF EXISTS unique_pending_invitation');
        
        // Nota: No podemos recrear el constraint original si hay duplicados
        // Solo recreamos si no hay conflictos
        DB::statement("
            CREATE UNIQUE INDEX unique_pending_invitation 
            ON business.company_invitations (company_id, user_id, status)
        ");
    }
};
