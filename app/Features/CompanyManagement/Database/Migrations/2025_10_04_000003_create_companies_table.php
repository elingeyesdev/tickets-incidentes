<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla companies en schema business
        DB::statement("
            CREATE TABLE business.companies (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                company_code VARCHAR(20) UNIQUE NOT NULL,

                -- Basic information
                name VARCHAR(200) NOT NULL,
                legal_name VARCHAR(250),
                support_email CITEXT,
                phone VARCHAR(20),
                website VARCHAR(200),

                -- Address
                contact_address TEXT,
                contact_city VARCHAR(100),
                contact_state VARCHAR(100),
                contact_country VARCHAR(100),
                contact_postal_code VARCHAR(20),

                -- Legal and tax information
                tax_id VARCHAR(50),
                legal_representative VARCHAR(200),

                -- Operational configuration (JSONB for flexibility)
                business_hours JSONB DEFAULT '{
                    \"monday\": {\"open\": \"09:00\", \"close\": \"18:00\"},
                    \"tuesday\": {\"open\": \"09:00\", \"close\": \"18:00\"},
                    \"wednesday\": {\"open\": \"09:00\", \"close\": \"18:00\"},
                    \"thursday\": {\"open\": \"09:00\", \"close\": \"18:00\"},
                    \"friday\": {\"open\": \"09:00\", \"close\": \"18:00\"}
                }'::JSONB,
                timezone VARCHAR(50) DEFAULT 'America/La_Paz',

                -- Branding
                logo_url VARCHAR(500),
                favicon_url VARCHAR(500),
                primary_color VARCHAR(7) DEFAULT '#007bff',
                secondary_color VARCHAR(7) DEFAULT '#6c757d',

                -- Additional settings (flexible JSONB)
                settings JSONB DEFAULT '{}'::JSONB,

                -- Status
                status VARCHAR(20) DEFAULT 'active' NOT NULL,

                -- Traceability
                created_from_request_id UUID REFERENCES business.company_requests(id),
                admin_user_id UUID NOT NULL REFERENCES auth.users(id),

                -- Audit
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                -- Constraints
                CONSTRAINT chk_status CHECK (status IN ('active', 'suspended'))
            )
        ");

        // Crear índices para rendimiento
        DB::statement('CREATE INDEX idx_companies_status ON business.companies(status)');
        DB::statement('CREATE INDEX idx_companies_admin_user_id ON business.companies(admin_user_id)');
        DB::statement('CREATE INDEX idx_companies_company_code ON business.companies(company_code)');
        DB::statement('CREATE INDEX idx_companies_created_at ON business.companies(created_at DESC)');

        // Crear trigger para updated_at
        DB::statement("
            CREATE TRIGGER trigger_update_companies_updated_at
            BEFORE UPDATE ON business.companies
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");

        // Agregar FK a company_requests.created_company_id (ahora que la tabla companies existe)
        DB::statement('
            ALTER TABLE business.company_requests
            ADD CONSTRAINT fk_company_requests_created_company
            FOREIGN KEY (created_company_id)
            REFERENCES business.companies(id)
            ON DELETE SET NULL
        ');

        // Agregar FK desde auth.user_roles.company_id a companies (estaba pendiente)
        // Verificar si la restricción ya existe antes de agregar
        $constraintExists = DB::select("
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_name = 'fk_user_roles_company'
            AND table_schema = 'auth'
            AND table_name = 'user_roles'
        ");

        if (empty($constraintExists)) {
            DB::statement('
                ALTER TABLE auth.user_roles
                ADD CONSTRAINT fk_user_roles_company
                FOREIGN KEY (company_id)
                REFERENCES business.companies(id)
                ON DELETE CASCADE
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar FK desde user_roles
        DB::statement('ALTER TABLE auth.user_roles DROP CONSTRAINT IF EXISTS fk_user_roles_company');

        // Eliminar FK desde company_requests
        DB::statement('ALTER TABLE business.company_requests DROP CONSTRAINT IF EXISTS fk_company_requests_created_company');

        // Eliminar tabla
        DB::statement('DROP TABLE IF EXISTS business.companies CASCADE');
    }
};
