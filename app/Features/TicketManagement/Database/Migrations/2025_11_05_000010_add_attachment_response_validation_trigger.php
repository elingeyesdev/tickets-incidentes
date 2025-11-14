<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agregar trigger para validar que response_id pertenezca al mismo ticket_id
     * Previene attachments con response_id de otros tickets
     */
    public function up(): void
    {
        // FUNCIÓN: Validar que response pertenece al mismo ticket
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.validate_attachment_response_ticket()
            RETURNS TRIGGER AS \$\$
            DECLARE
                response_ticket_id UUID;
            BEGIN
                -- Si response_id no es NULL, verificar que pertenece al mismo ticket
                IF NEW.response_id IS NOT NULL THEN
                    SELECT ticket_id INTO response_ticket_id
                    FROM ticketing.ticket_responses
                    WHERE id = NEW.response_id;

                    -- Si la response no existe, PostgreSQL ya lo rechazará por FK
                    -- Aquí solo validamos que el ticket_id coincida
                    IF response_ticket_id IS NOT NULL AND response_ticket_id != NEW.ticket_id THEN
                        RAISE EXCEPTION 'Response % does not belong to ticket %. Response belongs to ticket %.',
                            NEW.response_id, NEW.ticket_id, response_ticket_id;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement("COMMENT ON FUNCTION ticketing.validate_attachment_response_ticket() IS 'Valida que si un attachment tiene response_id, la response debe pertenecer al mismo ticket_id del attachment.'");

        // TRIGGER: Ejecutar validación antes de INSERT o UPDATE
        DB::statement("
            CREATE TRIGGER trigger_validate_attachment_response_ticket
            BEFORE INSERT OR UPDATE ON ticketing.ticket_attachments
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.validate_attachment_response_ticket();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trigger_validate_attachment_response_ticket ON ticketing.ticket_attachments CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.validate_attachment_response_ticket() CASCADE');
    }
};
