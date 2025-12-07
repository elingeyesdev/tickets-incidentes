<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration to create the activity_logs table
 *
 * This table stores all user activity in the system for auditing purposes.
 * Uses a simpler structure than the original audit_logs for better performance.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create activity_logs table in audit schema
        DB::statement("
            CREATE TABLE IF NOT EXISTS audit.activity_logs (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                
                -- Who performed the action (null for anonymous actions like failed login)
                user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
                
                -- What action was performed
                action VARCHAR(50) NOT NULL,
                
                -- What entity was affected (optional)
                entity_type VARCHAR(50),
                entity_id UUID,
                
                -- Change data (JSONB for flexibility)
                old_values JSONB,
                new_values JSONB,
                metadata JSONB,
                
                -- Request context
                ip_address INET,
                user_agent TEXT,
                
                -- Timestamp
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Add comments
        DB::statement("
            COMMENT ON TABLE audit.activity_logs IS 
            'Activity log for tracking user actions in the system'
        ");

        DB::statement("
            COMMENT ON COLUMN audit.activity_logs.action IS 
            'Action type: login, logout, ticket_created, ticket_resolved, etc.'
        ");

        DB::statement("
            COMMENT ON COLUMN audit.activity_logs.entity_type IS 
            'Type of entity affected: ticket, user, company, etc.'
        ");

        // Create indexes for efficient querying
        DB::statement('CREATE INDEX idx_activity_logs_user_id ON audit.activity_logs(user_id)');
        DB::statement('CREATE INDEX idx_activity_logs_action ON audit.activity_logs(action)');
        DB::statement('CREATE INDEX idx_activity_logs_entity ON audit.activity_logs(entity_type, entity_id)');
        DB::statement('CREATE INDEX idx_activity_logs_created_at ON audit.activity_logs(created_at DESC)');
        
        // Composite index for common queries
        DB::statement('CREATE INDEX idx_activity_logs_user_action ON audit.activity_logs(user_id, action, created_at DESC)');
        
        // Partial index for authentication actions (commonly queried)
        DB::statement("
            CREATE INDEX idx_activity_logs_auth_actions ON audit.activity_logs(user_id, created_at DESC) 
            WHERE action IN ('login', 'logout', 'login_failed', 'register')
        ");
        
        // Partial index for ticket actions
        DB::statement("
            CREATE INDEX idx_activity_logs_ticket_actions ON audit.activity_logs(entity_id, created_at DESC) 
            WHERE entity_type = 'ticket'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_ticket_actions');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_auth_actions');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_user_action');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_created_at');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_entity');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_action');
        DB::statement('DROP INDEX IF EXISTS audit.idx_activity_logs_user_id');

        // Drop table
        DB::statement('DROP TABLE IF EXISTS audit.activity_logs CASCADE');
    }
};
