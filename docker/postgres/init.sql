
-- PostgreSQL initialization script for Helpdesk System
-- This script creates the multi-schema architecture for the helpdesk

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "citext";

-- Create schemas
CREATE SCHEMA IF NOT EXISTS auth;
CREATE SCHEMA IF NOT EXISTS business;
CREATE SCHEMA IF NOT EXISTS ticketing;
CREATE SCHEMA IF NOT EXISTS audit;

-- Set search path to include all schemas
ALTER DATABASE helpdesk SET search_path TO public, auth, business, ticketing, audit;

-- Grant permissions to helpdesk user
GRANT ALL PRIVILEGES ON SCHEMA auth TO helpdesk;
GRANT ALL PRIVILEGES ON SCHEMA business TO helpdesk;
GRANT ALL PRIVILEGES ON SCHEMA ticketing TO helpdesk;
GRANT ALL PRIVILEGES ON SCHEMA audit TO helpdesk;

-- Grant permissions on all tables in schemas (for future tables)
ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON TABLES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA business GRANT ALL ON TABLES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA ticketing GRANT ALL ON TABLES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA audit GRANT ALL ON TABLES TO helpdesk;

-- Grant permissions on sequences
ALTER DEFAULT PRIVILEGES IN SCHEMA auth GRANT ALL ON SEQUENCES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA business GRANT ALL ON SEQUENCES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA ticketing GRANT ALL ON SEQUENCES TO helpdesk;
ALTER DEFAULT PRIVILEGES IN SCHEMA audit GRANT ALL ON SEQUENCES TO helpdesk;

-- Create a comment for documentation
COMMENT ON SCHEMA auth IS 'Authentication schema - users, roles, sessions, permissions';
COMMENT ON SCHEMA business IS 'Business schema - companies, departments, users relationships';
COMMENT ON SCHEMA ticketing IS 'Ticketing schema - tickets, responses, attachments, ratings';
COMMENT ON SCHEMA audit IS 'Audit schema - logs, tracking, monitoring';

-- Create updated_at trigger function (used globally)
CREATE OR REPLACE FUNCTION public.update_updated_at_column() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create helper functions for UUID generation
CREATE OR REPLACE FUNCTION auth.generate_user_code() RETURNS text AS $$
BEGIN
    RETURN 'USR_' || UPPER(SUBSTRING(uuid_generate_v4()::text FROM 1 FOR 8));
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION business.generate_company_code() RETURNS text AS $$
BEGIN
    RETURN 'CMP_' || UPPER(SUBSTRING(uuid_generate_v4()::text FROM 1 FOR 8));
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION ticketing.generate_ticket_code() RETURNS text AS $$
BEGIN
    RETURN 'TKT_' || UPPER(SUBSTRING(uuid_generate_v4()::text FROM 1 FOR 8));
END;
$$ LANGUAGE plpgsql;

-- Create audit function for tracking changes
CREATE OR REPLACE FUNCTION audit.log_changes() RETURNS trigger AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit.activity_logs (
            table_name,
            operation,
            record_id,
            new_values,
            user_id,
            created_at
        ) VALUES (
            TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME,
            TG_OP,
            NEW.id,
            row_to_json(NEW),
            COALESCE(current_setting('app.current_user_id', true)::bigint, 0),
            NOW()
        );
        RETURN NEW;
    END IF;

    IF TG_OP = 'UPDATE' THEN
        INSERT INTO audit.activity_logs (
            table_name,
            operation,
            record_id,
            old_values,
            new_values,
            user_id,
            created_at
        ) VALUES (
            TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME,
            TG_OP,
            NEW.id,
            row_to_json(OLD),
            row_to_json(NEW),
            COALESCE(current_setting('app.current_user_id', true)::bigint, 0),
            NOW()
        );
        RETURN NEW;
    END IF;

    IF TG_OP = 'DELETE' THEN
        INSERT INTO audit.activity_logs (
            table_name,
            operation,
            record_id,
            old_values,
            user_id,
            created_at
        ) VALUES (
            TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME,
            TG_OP,
            OLD.id,
            row_to_json(OLD),
            COALESCE(current_setting('app.current_user_id', true)::bigint, 0),
            NOW()
        );
        RETURN OLD;
    END IF;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create basic audit table for activity logging
CREATE TABLE IF NOT EXISTS audit.activity_logs (
    id BIGSERIAL PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    operation VARCHAR(10) NOT NULL CHECK (operation IN ('INSERT', 'UPDATE', 'DELETE')),
    record_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    user_id BIGINT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    ip_address INET,
    user_agent TEXT
);

-- Create indexes for audit table
CREATE INDEX IF NOT EXISTS idx_activity_logs_table_name ON audit.activity_logs(table_name);
CREATE INDEX IF NOT EXISTS idx_activity_logs_operation ON audit.activity_logs(operation);
CREATE INDEX IF NOT EXISTS idx_activity_logs_record_id ON audit.activity_logs(record_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON audit.activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON audit.activity_logs(created_at);

-- Set permissions on audit table
GRANT ALL PRIVILEGES ON TABLE audit.activity_logs TO helpdesk;
GRANT ALL PRIVILEGES ON SEQUENCE audit.activity_logs_id_seq TO helpdesk;

-- Success message
\echo 'PostgreSQL multi-schema initialization completed successfully!'
\echo 'Schemas created: auth, business, ticketing, audit'
\echo 'Extensions enabled: uuid-ossp, citext'
\echo 'Helper functions and audit system ready'
