<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega ON DELETE CASCADE a la FK response_id en ticket_attachments
     * Cuando se elimina una response, todos sus attachments también se eliminan
     */
    public function up(): void
    {
        // Obtener nombre real de la constraint
        $constraintName = DB::selectOne("
            SELECT con.conname
            FROM pg_constraint con
            JOIN pg_class rel ON rel.oid = con.conrelid
            WHERE rel.relname = 'ticket_attachments'
            AND con.contype = 'f'
            AND EXISTS (
                SELECT 1 FROM pg_attribute
                WHERE attrelid = rel.oid
                AND attnum = ANY(con.conkey)
                AND attname = 'response_id'
            )
        ")->conname;

        // Eliminar constraint existente
        DB::statement("
            ALTER TABLE ticketing.ticket_attachments
            DROP CONSTRAINT {$constraintName}
        ");

        // Recrear constraint con ON DELETE CASCADE
        DB::statement("
            ALTER TABLE ticketing.ticket_attachments
            ADD CONSTRAINT {$constraintName}
            FOREIGN KEY (response_id)
            REFERENCES ticketing.ticket_responses(id)
            ON DELETE CASCADE
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar constraint con CASCADE
        DB::statement("
            ALTER TABLE ticketing.ticket_attachments
            DROP CONSTRAINT IF EXISTS ticket_attachments_response_id_foreign
        ");

        // Recrear constraint SIN CASCADE (comportamiento original)
        DB::statement("
            ALTER TABLE ticketing.ticket_attachments
            ADD CONSTRAINT ticket_attachments_response_id_foreign
            FOREIGN KEY (response_id)
            REFERENCES ticketing.ticket_responses(id)
        ");
    }
};
