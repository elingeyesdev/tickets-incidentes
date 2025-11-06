<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla ticketing.ticket_attachments
     * Archivos adjuntos en respuestas de tickets
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE ticketing.ticket_attachments (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
                response_id UUID REFERENCES ticketing.ticket_responses(id) ON DELETE SET NULL,

                uploaded_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

                file_name VARCHAR(255) NOT NULL,
                file_url VARCHAR(500) NOT NULL,
                file_type VARCHAR(100),
                file_size_bytes BIGINT,

                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.ticket_attachments IS 'Archivos adjuntos en tickets y respuestas. URLs apuntan a S3, GCS u otro storage externo.'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_attachments.ticket_id IS 'Ticket propietario del archivo'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_attachments.response_id IS 'Respuesta a la que pertenece el archivo (nullable si es subido sin respuesta)'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_attachments.uploaded_by_user_id IS 'Usuario que subió el archivo'");
        DB::statement("COMMENT ON COLUMN ticketing.ticket_attachments.file_url IS 'URL pública del archivo en storage externo'");

        // Índices
        DB::statement("CREATE INDEX idx_ticket_attachments_ticket_id ON ticketing.ticket_attachments(ticket_id)");
        DB::statement("CREATE INDEX idx_ticket_attachments_response_id ON ticketing.ticket_attachments(response_id)");
        DB::statement("CREATE INDEX idx_ticket_attachments_uploaded_by ON ticketing.ticket_attachments(uploaded_by_user_id)");
        DB::statement("CREATE INDEX idx_ticket_attachments_created_at ON ticketing.ticket_attachments(created_at DESC)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.ticket_attachments CASCADE');
    }
};