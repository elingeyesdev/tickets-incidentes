<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create the audit.log_changes() trigger function
 *
 * This function is used by triggers to automatically log changes to audited tables.
 * It can be attached to any table that needs audit tracking.
 *
 * Usage:
 * CREATE TRIGGER audit_[table_name]_changes
 * AFTER INSERT OR UPDATE OR DELETE ON [schema].[table]
 * FOR EACH ROW EXECUTE FUNCTION audit.log_changes();
 *
 * According to: Modelado V7.0 líneas 539-573
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the audit log function
        // According to Modelado V7.0 líneas 539-573
        DB::statement("
            CREATE OR REPLACE FUNCTION audit.log_changes()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO audit.audit_logs (
                    user_id,
                    action,
                    table_name,
                    record_id,
                    old_values,
                    new_values
                )
                VALUES (
                    COALESCE(
                        current_setting('app.current_user_id', true)::UUID,
                        NULL
                    ),
                    TG_OP::audit.action_type,
                    TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME,
                    CASE
                        WHEN TG_OP = 'DELETE' THEN OLD.id
                        ELSE NEW.id
                    END,
                    CASE
                        WHEN TG_OP IN ('UPDATE', 'DELETE') THEN to_jsonb(OLD)
                        ELSE NULL
                    END,
                    CASE
                        WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW)
                        ELSE NULL
                    END
                );

                RETURN CASE
                    WHEN TG_OP = 'DELETE' THEN OLD
                    ELSE NEW
                END;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Add function description
        DB::statement("
            COMMENT ON FUNCTION audit.log_changes() IS
            'Trigger function to automatically log INSERT/UPDATE/DELETE operations to audit.audit_logs table'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS audit.log_changes() CASCADE');
    }
};
