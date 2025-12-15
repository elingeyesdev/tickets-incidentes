<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create the audit schema for PostgreSQL
 *
 * This migration creates:
 * - Schema: audit
 * - ENUM: action_type
 * - Table: activity_logs (used by @audit directive)
 *
 * The audit system logs all important changes in the system.
 *
 * According to: Modelado V7.0 líneas 22-25, 36, 437-459
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create audit schema
        DB::statement('CREATE SCHEMA IF NOT EXISTS audit');

        // Add schema description
        DB::statement("
            COMMENT ON SCHEMA audit IS
            'Schema for system audit logs and tracking'
        ");

        // Create ENUM for action types
        // According to Modelado V7.0 línea 36
        // Drop first to make migration idempotent
        DB::statement("DROP TYPE IF EXISTS audit.action_type CASCADE");
        DB::statement("
            CREATE TYPE audit.action_type AS ENUM (
                'create',
                'update',
                'delete',
                'login',
                'logout'
            )
        ");

        DB::statement("
            COMMENT ON TYPE audit.action_type IS
            'Types of auditable actions in the system'
        ");

        // Create audit_logs table
        // According to Modelado V7.0 líneas 437-459
        DB::statement("
            CREATE TABLE audit.audit_logs (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

                -- Who and when
                user_id UUID REFERENCES auth.users(id),
                action audit.action_type NOT NULL,
                performed_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                -- What was modified
                table_name VARCHAR(100),
                record_id UUID,

                -- Change data
                old_values JSONB,
                new_values JSONB,

                -- Context
                ip_address INET,
                user_agent TEXT,

                -- Efficient search
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add table description
        DB::statement("
            COMMENT ON TABLE audit.audit_logs IS
            'System audit log - tracks all important changes in the database'
        ");

        // Create indexes for efficient querying
        // According to Modelado V7.0 líneas 496-498
        DB::statement('CREATE INDEX idx_audit_logs_user_id ON audit.audit_logs(user_id)');
        DB::statement('CREATE INDEX idx_audit_logs_table_record ON audit.audit_logs(table_name, record_id)');
        DB::statement('CREATE INDEX idx_audit_logs_created_at ON audit.audit_logs(created_at DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        DB::statement('DROP INDEX IF EXISTS audit.idx_audit_logs_created_at');
        DB::statement('DROP INDEX IF EXISTS audit.idx_audit_logs_table_record');
        DB::statement('DROP INDEX IF EXISTS audit.idx_audit_logs_user_id');

        // Drop table
        DB::statement('DROP TABLE IF EXISTS audit.audit_logs CASCADE');

        // Drop ENUM
        DB::statement('DROP TYPE IF EXISTS audit.action_type CASCADE');

        // Drop schema
        DB::statement('DROP SCHEMA IF EXISTS audit CASCADE');
    }
};
