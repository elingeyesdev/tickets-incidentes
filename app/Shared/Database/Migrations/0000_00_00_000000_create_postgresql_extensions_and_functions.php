<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create PostgreSQL extensions and functions
 *
 * This migration MUST run FIRST before any other migrations.
 * It creates:
 * - Extensions: uuid-ossp, pgcrypto, citext
 * - Functions: update_updated_at_column()
 *
 * These are required by all other migrations in the system.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ====================================================================
        // CREATE EXTENSIONS
        // ====================================================================

        // uuid-ossp: Provides uuid_generate_v4() and other UUID functions
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        // pgcrypto: Provides gen_random_uuid() and encryption functions
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

        // citext: Case-insensitive text type (used for emails)
        DB::statement('CREATE EXTENSION IF NOT EXISTS "citext"');

        // ====================================================================
        // CREATE FUNCTIONS
        // ====================================================================

        // Function to automatically update updated_at column on UPDATE
        DB::statement("
            CREATE OR REPLACE FUNCTION public.update_updated_at_column()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Add comment to function
        DB::statement("
            COMMENT ON FUNCTION public.update_updated_at_column() IS
            'Automatically updates the updated_at column to current timestamp on UPDATE'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop function
        DB::statement('DROP FUNCTION IF EXISTS public.update_updated_at_column() CASCADE');

        // Drop extensions (CAUTION: This will fail if other databases/objects depend on them)
        DB::statement('DROP EXTENSION IF EXISTS "citext" CASCADE');
        DB::statement('DROP EXTENSION IF EXISTS "pgcrypto" CASCADE');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp" CASCADE');
    }
};