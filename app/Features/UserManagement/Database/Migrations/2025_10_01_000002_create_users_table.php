<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'users' en el schema 'auth'
 *
 * Tabla principal de usuarios del sistema.
 * Contiene información de autenticación y estado de la cuenta.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth.users', function (Blueprint $table) {
            // ===== PRIMARY KEY =====
            $table->uuid('id')->primary();

            // ===== IDENTIFICADORES ÚNICOS =====
            $table->string('user_code', 20)->unique()->comment('Código único: USR-2025-00123');

            // ===== AUTENTICACIÓN =====
            $table->string('email')->unique()->comment('Email único del usuario');
            $table->string('password_hash')->nullable()->comment('Hash bcrypt de la contraseña (NULL si usa OAuth)');
            $table->boolean('email_verified')->default(false)->comment('Si el email está verificado');
            $table->timestamp('email_verified_at')->nullable()->comment('Fecha de verificación del email');

            // ===== AUTENTICACIÓN OAUTH =====
            $table->string('auth_provider', 50)->default('local')
                ->comment('Proveedor de auth: local, google, microsoft');
            $table->string('external_auth_id', 255)->nullable()
                ->comment('Google ID, Microsoft ID, etc.');

            // ===== RECUPERACIÓN DE CONTRASEÑA =====
            $table->string('password_reset_token', 255)->nullable()
                ->comment('Token para reset password');
            $table->timestamp('password_reset_expires')->nullable()
                ->comment('Expiración del token de reset');

            // ===== ESTADO Y CONFIGURACIÓN =====
            $table->enum('status', ['active', 'suspended', 'deleted'])
                ->default('active')
                ->comment('Estado del usuario');

            // ===== ACTIVIDAD Y SEGURIDAD =====
            $table->timestamp('last_login_at')->nullable()->comment('Último acceso al sistema');
            $table->string('last_login_ip', 45)->nullable()->comment('IP del último acceso (IPv6 compatible)');
            $table->timestamp('last_activity_at')->nullable()->comment('Última actividad registrada');

            // ===== TÉRMINOS Y CONDICIONES =====
            $table->boolean('terms_accepted')->default(false)->comment('Términos aceptados');
            $table->timestamp('terms_accepted_at')->nullable()->comment('Fecha de aceptación');
            $table->string('terms_version', 10)->nullable()->comment('Versión de términos aceptada');

            // ===== AUDITORÍA =====
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at

            // ===== ÍNDICES =====
            $table->index('status', 'idx_users_status');
            $table->index('email_verified', 'idx_users_email_verified');
            $table->index('auth_provider', 'idx_users_auth_provider');
            $table->index('last_login_at', 'idx_users_last_login');
            $table->index('created_at', 'idx_users_created_at');
            $table->index(['status', 'email_verified'], 'idx_users_status_verified');

            // Comentario de la tabla
            $table->comment('Usuarios del sistema - Información de autenticación y estado');
        });

        // Crear índice para búsqueda full-text en email (PostgreSQL)
        DB::statement('CREATE INDEX idx_users_email_search ON auth.users USING gin(to_tsvector(\'english\', email))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth.users');
    }
};