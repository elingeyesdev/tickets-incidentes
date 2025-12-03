<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración para agregar constraints de prevención de duplicados en tablas de empresas.
 *
 * OBJETIVO: Prevenir empresas duplicadas mediante validaciones en base de datos.
 *
 * CONSTRAINTS AGREGADOS:
 * 1. tax_id UNIQUE (cuando NO es NULL) - Evita duplicados de NIT/RUC
 * 2. Índices para búsqueda eficiente de nombres normalizados
 *
 * NOTA: La validación de lógica de negocio compleja (nombres similares, emails duplicados)
 * se maneja en el backend por ser demasiado compleja para constraints de BD.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================================================
        // 1. UNIQUE INDEX en tax_id (solo cuando NO es NULL)
        // ========================================================================
        // Permite múltiples NULL pero garantiza que tax_id NO-NULL sea único
        // Esto es crítico porque el NIT/Tax ID es único por empresa en Bolivia

        DB::statement("
            CREATE UNIQUE INDEX idx_company_requests_tax_id_unique
            ON business.company_requests(tax_id)
            WHERE tax_id IS NOT NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX idx_companies_tax_id_unique
            ON business.companies(tax_id)
            WHERE tax_id IS NOT NULL
        ");

        // ========================================================================
        // 2. ÍNDICES para búsqueda de nombres normalizados (PERFORMANCE)
        // ========================================================================
        // Estos índices aceleran las búsquedas de nombres similares en el backend
        // Normalizan: lowercase + solo alfanuméricos (sin espacios, puntos, etc.)
        // Ejemplo: "UNITEL S.A." -> "unitelsa"

        DB::statement("
            CREATE INDEX idx_company_requests_name_normalized
            ON business.company_requests(
                LOWER(REGEXP_REPLACE(company_name, '[^a-zA-Z0-9]', '', 'g'))
            )
        ");

        DB::statement("
            CREATE INDEX idx_companies_name_normalized
            ON business.companies(
                LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]', '', 'g'))
            )
        ");

        // ========================================================================
        // 3. ÍNDICE en support_email (companies) para búsquedas rápidas
        // ========================================================================
        // Aunque admin_email en requests ya tiene índice, support_email en companies no
        // Este índice acelera la validación de emails duplicados

        DB::statement("
            CREATE INDEX idx_companies_support_email
            ON business.companies(support_email)
            WHERE support_email IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices en orden inverso
        DB::statement('DROP INDEX IF EXISTS business.idx_companies_support_email');
        DB::statement('DROP INDEX IF EXISTS business.idx_companies_name_normalized');
        DB::statement('DROP INDEX IF EXISTS business.idx_company_requests_name_normalized');
        DB::statement('DROP INDEX IF EXISTS business.idx_companies_tax_id_unique');
        DB::statement('DROP INDEX IF EXISTS business.idx_company_requests_tax_id_unique');
    }
};
