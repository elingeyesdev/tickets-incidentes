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
        // Crear tabla user_company_followers en schema business
        DB::statement("
            CREATE TABLE business.user_company_followers (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
                company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,

                followed_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT uq_user_company_follow UNIQUE (user_id, company_id)
            )
        ");

        // Crear índices para rendimiento
        DB::statement('CREATE INDEX idx_user_company_followers_user_id ON business.user_company_followers(user_id)');
        DB::statement('CREATE INDEX idx_user_company_followers_company_id ON business.user_company_followers(company_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS business.user_company_followers CASCADE');
    }
};
