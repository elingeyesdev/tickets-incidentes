<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agregar:
     * 1. Función return_pending_to_open_on_user_response (retorna tickets PENDING a OPEN cuando cliente responde)
     * 2. Trigger trigger_return_pending_to_open (ejecuta la función después de insertar respuesta)
     * 3. Índices compuestos para queries eficientes (status+owner, last_response_author)
     *
     * Orden de ejecución de triggers en ticket_responses:
     * - trigger_assign_ticket_owner (000002): Asigna owner y cambia OPEN -> PENDING
     * - trigger_return_pending_to_open (000009): Evalúa si debe volver PENDING -> OPEN
     */
    public function up(): void
    {
        // FUNCIÓN: Retornar automáticamente a OPEN cuando cliente responde a ticket PENDING
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.return_pending_to_open_on_user_response()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Si es respuesta de usuario Y el ticket está PENDING, cambiar a OPEN
                IF NEW.author_type = 'user' THEN
                    UPDATE ticketing.tickets
                    SET status = 'open'::ticketing.ticket_status
                    WHERE id = NEW.ticket_id
                    AND status = 'pending'::ticketing.ticket_status;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // COMENTARIO: Documentar propósito de la función
        DB::statement("COMMENT ON FUNCTION ticketing.return_pending_to_open_on_user_response() IS 'Retorna automáticamente un ticket a status OPEN cuando el cliente responde a un ticket PENDING. Se ejecuta después de insertar una nueva respuesta.'");

        // TRIGGER: Ejecutar la función después de insertar respuesta
        DB::statement("
            CREATE TRIGGER trigger_return_pending_to_open
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.return_pending_to_open_on_user_response();
        ");

        // ÍNDICE 1: Optimizar queries que filtran por status + owner_agent_id
        // Casos de uso: "tickets pendientes asignados a agente X", "tickets abiertos sin owner"
        DB::statement("CREATE INDEX idx_tickets_status_owner ON ticketing.tickets(status, owner_agent_id)");

        // ÍNDICE 2: Optimizar queries que filtran por último autor de respuesta
        // Casos de uso: "tickets esperando respuesta del cliente", "tickets esperando respuesta del agente"
        DB::statement("CREATE INDEX idx_tickets_last_response_author ON ticketing.tickets(last_response_author_type)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar en orden inverso: índices, trigger, función
        DB::statement('DROP INDEX IF EXISTS idx_tickets_last_response_author CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_tickets_status_owner CASCADE');
        DB::statement('DROP TRIGGER IF EXISTS trigger_return_pending_to_open ON ticketing.ticket_responses CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.return_pending_to_open_on_user_response() CASCADE');
    }
};
