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
        // Create business schema for company management
        DB::statement('CREATE SCHEMA IF NOT EXISTS business');

        // Create enums in business schema
        // Drop first to make migration idempotent (can run multiple times)
        DB::statement("DROP TYPE IF EXISTS business.request_status CASCADE");
        DB::statement("
            CREATE TYPE business.request_status AS ENUM (
                'pending',
                'approved',
                'rejected'
            )
        ");

        DB::statement("DROP TYPE IF EXISTS business.publication_status CASCADE");
        DB::statement("
            CREATE TYPE business.publication_status AS ENUM (
                'draft',
                'published',
                'archived'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop enums
        DB::statement('DROP TYPE IF EXISTS business.publication_status');
        DB::statement('DROP TYPE IF EXISTS business.request_status');

        // Drop schema (CASCADE drops all objects within it)
        DB::statement('DROP SCHEMA IF EXISTS business CASCADE');
    }
};
