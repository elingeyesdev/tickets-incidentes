<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para normalizar las tablas de empresas.
 * 
 * OBJETIVO:
 * - Unificar company_requests y companies en una sola tabla (companies)
 * - Crear tabla company_onboarding_details para metadata del proceso
 * - Eliminar duplicación de datos
 * 
 * ESTRATEGIA:
 * 1. Modificar companies para aceptar status 'pending' y 'rejected'
 * 2. Crear tabla company_onboarding_details
 * 3. Migrar datos de company_requests a companies + onboarding_details
 * 4. Eliminar tabla company_requests
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================================================
        // PASO 1: Modificar constraint de status en companies
        // ========================================================================
        // Ahora acepta: 'pending', 'active', 'rejected', 'suspended'
        
        DB::statement("
            ALTER TABLE business.companies
            DROP CONSTRAINT IF EXISTS chk_status
        ");
        
        DB::statement("
            ALTER TABLE business.companies
            ADD CONSTRAINT chk_status CHECK (status IN ('pending', 'active', 'rejected', 'suspended'))
        ");

        // ========================================================================
        // PASO 2: Hacer admin_user_id nullable temporalmente
        // ========================================================================
        // Necesario para empresas pendientes que aún no tienen admin
        
        DB::statement("
            ALTER TABLE business.companies
            ALTER COLUMN admin_user_id DROP NOT NULL
        ");

        // ========================================================================
        // PASO 3: Crear tabla company_onboarding_details
        // ========================================================================
        // Esta tabla almacena la metadata del proceso de solicitud
        
        DB::statement("
            CREATE TABLE business.company_onboarding_details (
                company_id UUID PRIMARY KEY REFERENCES business.companies(id) ON DELETE CASCADE,
                
                -- Código único de la solicitud (REQ-2024-00001)
                request_code VARCHAR(20) UNIQUE NOT NULL,
                
                -- Mensaje original del solicitante
                request_message TEXT NOT NULL,
                
                -- Datos estadísticos de la solicitud
                estimated_users INT,
                
                -- Email original del solicitante (puede diferir del support_email actual)
                submitter_email CITEXT NOT NULL,
                
                -- Proceso de revisión
                reviewed_by UUID REFERENCES auth.users(id),
                reviewed_at TIMESTAMPTZ,
                rejection_reason TEXT,
                
                -- Audit
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Crear índices para company_onboarding_details
        DB::statement('CREATE INDEX idx_onboarding_details_request_code ON business.company_onboarding_details(request_code)');
        DB::statement('CREATE INDEX idx_onboarding_details_submitter_email ON business.company_onboarding_details(submitter_email)');
        
        // Trigger para updated_at
        DB::statement("
            CREATE TRIGGER trigger_update_onboarding_details_updated_at
            BEFORE UPDATE ON business.company_onboarding_details
            FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column()
        ");

        // ========================================================================
        // PASO 4: Migrar datos de empresas APROBADAS
        // ========================================================================
        // Crear onboarding_details desde company_requests para empresas existentes
        
        DB::statement("
            INSERT INTO business.company_onboarding_details (
                company_id,
                request_code,
                request_message,
                estimated_users,
                submitter_email,
                reviewed_by,
                reviewed_at,
                created_at,
                updated_at
            )
            SELECT 
                c.id,
                cr.request_code,
                cr.request_message,
                cr.estimated_users,
                cr.admin_email,
                cr.reviewed_by,
                cr.reviewed_at,
                cr.created_at,
                cr.updated_at
            FROM business.companies c
            INNER JOIN business.company_requests cr ON c.created_from_request_id = cr.id
            WHERE cr.status = 'approved'
        ");

        // ========================================================================
        // PASO 5: Migrar solicitudes PENDIENTES a companies
        // ========================================================================
        // Crear empresas con status 'pending' desde solicitudes pendientes
        
        DB::statement("
            INSERT INTO business.companies (
                id,
                company_code,
                name,
                legal_name,
                description,
                support_email,
                website,
                industry_id,
                contact_address,
                contact_city,
                contact_country,
                contact_postal_code,
                tax_id,
                status,
                admin_user_id,
                created_from_request_id,
                created_at,
                updated_at
            )
            SELECT 
                uuid_generate_v4(),
                'CMP-' || EXTRACT(YEAR FROM cr.created_at)::TEXT || '-' || LPAD(
                    (ROW_NUMBER() OVER (ORDER BY cr.created_at) + 
                     (SELECT COALESCE(MAX(CAST(SUBSTRING(company_code FROM 10) AS INTEGER)), 0) FROM business.companies WHERE company_code LIKE 'CMP-%'))::TEXT, 
                    5, '0'
                ),
                cr.company_name,
                cr.legal_name,
                cr.company_description,
                cr.admin_email,
                cr.website,
                cr.industry_id,
                cr.contact_address,
                cr.contact_city,
                cr.contact_country,
                cr.contact_postal_code,
                cr.tax_id,
                'pending',
                NULL,
                cr.id,
                cr.created_at,
                cr.updated_at
            FROM business.company_requests cr
            WHERE cr.status = 'pending'
        ");

        // Crear onboarding_details para las solicitudes pendientes migradas
        DB::statement("
            INSERT INTO business.company_onboarding_details (
                company_id,
                request_code,
                request_message,
                estimated_users,
                submitter_email,
                created_at,
                updated_at
            )
            SELECT 
                c.id,
                cr.request_code,
                cr.request_message,
                cr.estimated_users,
                cr.admin_email,
                cr.created_at,
                cr.updated_at
            FROM business.companies c
            INNER JOIN business.company_requests cr ON c.created_from_request_id = cr.id
            WHERE c.status = 'pending'
            AND NOT EXISTS (
                SELECT 1 FROM business.company_onboarding_details od WHERE od.company_id = c.id
            )
        ");

        // ========================================================================
        // PASO 6: Migrar solicitudes RECHAZADAS a companies
        // ========================================================================
        
        DB::statement("
            INSERT INTO business.companies (
                id,
                company_code,
                name,
                legal_name,
                description,
                support_email,
                website,
                industry_id,
                contact_address,
                contact_city,
                contact_country,
                contact_postal_code,
                tax_id,
                status,
                admin_user_id,
                created_from_request_id,
                created_at,
                updated_at
            )
            SELECT 
                uuid_generate_v4(),
                'CMP-' || EXTRACT(YEAR FROM cr.created_at)::TEXT || '-' || LPAD(
                    (ROW_NUMBER() OVER (ORDER BY cr.created_at) + 
                     (SELECT COALESCE(MAX(CAST(SUBSTRING(company_code FROM 10) AS INTEGER)), 0) FROM business.companies WHERE company_code LIKE 'CMP-%'))::TEXT, 
                    5, '0'
                ),
                cr.company_name,
                cr.legal_name,
                cr.company_description,
                cr.admin_email,
                cr.website,
                cr.industry_id,
                cr.contact_address,
                cr.contact_city,
                cr.contact_country,
                cr.contact_postal_code,
                cr.tax_id,
                'rejected',
                NULL,
                cr.id,
                cr.created_at,
                cr.updated_at
            FROM business.company_requests cr
            WHERE cr.status = 'rejected'
        ");

        // Crear onboarding_details para las solicitudes rechazadas migradas
        DB::statement("
            INSERT INTO business.company_onboarding_details (
                company_id,
                request_code,
                request_message,
                estimated_users,
                submitter_email,
                reviewed_by,
                reviewed_at,
                rejection_reason,
                created_at,
                updated_at
            )
            SELECT 
                c.id,
                cr.request_code,
                cr.request_message,
                cr.estimated_users,
                cr.admin_email,
                cr.reviewed_by,
                cr.reviewed_at,
                cr.rejection_reason,
                cr.created_at,
                cr.updated_at
            FROM business.companies c
            INNER JOIN business.company_requests cr ON c.created_from_request_id = cr.id
            WHERE c.status = 'rejected'
            AND NOT EXISTS (
                SELECT 1 FROM business.company_onboarding_details od WHERE od.company_id = c.id
            )
        ");

        // ========================================================================
        // PASO 7: Limpiar índices viejos de company_requests
        // ========================================================================
        
        DB::statement('DROP INDEX IF EXISTS business.idx_company_requests_tax_id_unique');
        DB::statement('DROP INDEX IF EXISTS business.idx_company_requests_name_normalized');

        // ========================================================================
        // PASO 8: Eliminar FK y tabla company_requests
        // ========================================================================
        
        // Primero eliminar la FK desde companies hacia company_requests
        DB::statement('ALTER TABLE business.companies DROP CONSTRAINT IF EXISTS companies_created_from_request_id_fkey');
        
        // Eliminar la columna created_from_request_id (ya no la necesitamos)
        DB::statement('ALTER TABLE business.companies DROP COLUMN IF EXISTS created_from_request_id');
        
        // Finalmente eliminar la tabla company_requests
        DB::statement('DROP TABLE IF EXISTS business.company_requests CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ========================================================================
        // REVERTIR: Recrear tabla company_requests
        // ========================================================================
        
        // Primero agregar la columna created_from_request_id de vuelta
        DB::statement('ALTER TABLE business.companies ADD COLUMN IF NOT EXISTS created_from_request_id UUID');
        
        // Recrear la tabla company_requests
        DB::statement("
            CREATE TABLE IF NOT EXISTS business.company_requests (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                request_code VARCHAR(20) UNIQUE NOT NULL,
                company_name VARCHAR(200) NOT NULL,
                legal_name VARCHAR(250),
                admin_email CITEXT NOT NULL,
                company_description TEXT NOT NULL,
                request_message TEXT NOT NULL,
                website VARCHAR(200),
                industry_id UUID NOT NULL REFERENCES business.company_industries(id),
                estimated_users INT,
                contact_address TEXT,
                contact_city VARCHAR(100),
                contact_country VARCHAR(100),
                contact_postal_code VARCHAR(20),
                tax_id VARCHAR(50),
                status VARCHAR(20) DEFAULT 'pending' NOT NULL,
                reviewed_by UUID REFERENCES auth.users(id),
                reviewed_at TIMESTAMPTZ,
                rejection_reason TEXT,
                created_company_id UUID,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Recrear FK
        DB::statement("
            ALTER TABLE business.companies
            ADD CONSTRAINT companies_created_from_request_id_fkey
            FOREIGN KEY (created_from_request_id)
            REFERENCES business.company_requests(id)
            ON DELETE SET NULL
        ");

        // Eliminar tabla company_onboarding_details
        DB::statement('DROP TABLE IF EXISTS business.company_onboarding_details CASCADE');

        // Restaurar constraint original de status
        DB::statement("
            ALTER TABLE business.companies
            DROP CONSTRAINT IF EXISTS chk_status
        ");
        
        DB::statement("
            ALTER TABLE business.companies
            ADD CONSTRAINT chk_status CHECK (status IN ('active', 'suspended'))
        ");

        // Restaurar NOT NULL en admin_user_id
        DB::statement("
            ALTER TABLE business.companies
            ALTER COLUMN admin_user_id SET NOT NULL
        ");
    }
};
