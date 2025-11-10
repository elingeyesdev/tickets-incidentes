<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea:
     * 1. Función assign_ticket_owner_function (necesaria para triggers posteriores)
     * 2. Tabla ticketing.categories (categorías de tickets personalizadas por empresa)
     */
    public function up(): void
    {
        // FUNCIÓN: Asignar automáticamente owner_agent_id al primer agente que responde y actualizar last_response_author_type
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Si el que responde es un agente
                IF NEW.author_type = 'agent' THEN
                    -- Asignar owner_agent_id solo si el ticket no tiene owner
                    UPDATE ticketing.tickets
                    SET
                        owner_agent_id = NEW.author_id,
                        last_response_author_type = 'agent',
                        first_response_at = CASE
                            WHEN first_response_at IS NULL THEN NOW()
                            ELSE first_response_at
                        END,
                        status = CASE
                            WHEN status = 'open' THEN 'pending'::ticketing.ticket_status
                            ELSE status
                        END
                    WHERE id = NEW.ticket_id
                    AND owner_agent_id IS NULL;

                    -- Si el ticket ya tiene owner, solo actualizar last_response_author_type
                    UPDATE ticketing.tickets
                    SET
                        last_response_author_type = 'agent'
                    WHERE id = NEW.ticket_id
                    AND owner_agent_id IS NOT NULL;

                ELSIF NEW.author_type = 'user' THEN
                    -- Si responde un usuario, solo actualizar last_response_author_type
                    UPDATE ticketing.tickets
                    SET
                        last_response_author_type = 'user'
                    WHERE id = NEW.ticket_id;
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement("COMMENT ON FUNCTION ticketing.assign_ticket_owner_function() IS 'Asigna automáticamente el agente propietario al primer agente que responde un ticket y actualiza last_response_author_type (agent/user).'");

        // TABLA: Categories
        DB::statement("
            CREATE TABLE ticketing.categories (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT uq_company_category_name UNIQUE (company_id, name)
            )
        ");

        // Comentarios de tabla
        DB::statement("COMMENT ON TABLE ticketing.categories IS 'Categorías de tickets personalizadas por empresa. Ej: Soporte Técnico, Facturación, etc.'");
        DB::statement("COMMENT ON COLUMN ticketing.categories.id IS 'Identificador único de categoría'");
        DB::statement("COMMENT ON COLUMN ticketing.categories.company_id IS 'Referencia a la empresa propietaria'");
        DB::statement("COMMENT ON COLUMN ticketing.categories.name IS 'Nombre de la categoría (único por empresa)'");
        DB::statement("COMMENT ON COLUMN ticketing.categories.description IS 'Descripción detallada de la categoría'");
        DB::statement("COMMENT ON COLUMN ticketing.categories.is_active IS 'Indica si la categoría está activa'");

        // Índices
        DB::statement("CREATE INDEX idx_categories_company_id ON ticketing.categories(company_id)");
        DB::statement("CREATE INDEX idx_categories_is_active ON ticketing.categories(is_active) WHERE is_active = true");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS ticketing.categories CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.assign_ticket_owner_function() CASCADE');
    }
};