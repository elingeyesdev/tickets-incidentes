<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla company_requests en schema business
        DB::statement("
            CREATE TABLE business.company_requests (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                request_code VARCHAR(20) UNIQUE NOT NULL,

                -- Form data
                company_name VARCHAR(200) NOT NULL,
                legal_name VARCHAR(250),
                admin_email CITEXT NOT NULL,
                business_description TEXT NOT NULL,
                website VARCHAR(200),
                industry_type VARCHAR(100) NOT NULL,
                estimated_users INT,
                contact_address TEXT,
                contact_city VARCHAR(100),
                contact_country VARCHAR(100),
                contact_postal_code VARCHAR(20),
                tax_id VARCHAR(50),

                status business.request_status DEFAULT 'pending' NOT NULL,

                -- Review process
                reviewed_by UUID REFERENCES auth.users(id),
                reviewed_at TIMESTAMPTZ,
                rejection_reason TEXT,

                -- Link to created company (if approved)
                created_company_id UUID,

                -- Audit
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Crear índices
        DB::statement('CREATE INDEX idx_company_requests_status ON business.company_requests(status)');
        DB::statement('CREATE INDEX idx_company_requests_admin_email ON business.company_requests(admin_email)');
        DB::statement('CREATE INDEX idx_company_requests_created_at ON business.company_requests(created_at DESC)');

        // Crear trigger para updated_at
        DB::statement("
            CREATE TRIGGER trigger_update_company_requests_updated_at
            BEFORE UPDATE ON business.company_requests
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS business.company_requests CASCADE');
    }
};
