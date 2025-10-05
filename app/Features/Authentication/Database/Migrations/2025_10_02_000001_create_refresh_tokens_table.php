<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'refresh_tokens' en el schema 'auth'
 *
 * Tabla para almacenar refresh tokens JWT.
 * Los refresh tokens permiten renovar access tokens sin requerir login.
 *
 * Seguridad:
 * - Tokens almacenados como hash SHA-256 (nunca plain text)
 * - Expiración automática (30 días default)
 * - Revocación manual posible
 * - Asociados a dispositivo específico
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth.refresh_tokens', function (Blueprint $table) {
            // ===== PRIMARY KEY =====
            $table->uuid('id')->primary();

            // ===== RELACIÓN CON USER =====
            $table->uuid('user_id')->comment('FK a auth.users');
            $table->foreign('user_id')
                ->references('id')
                ->on('auth.users')
                ->onDelete('cascade');

            // ===== TOKEN (HASH SHA-256) =====
            $table->string('token_hash', 64)->unique()->comment('Hash SHA-256 del refresh token');

            // ===== INFORMACIÓN DEL DISPOSITIVO =====
            $table->string('device_name', 255)->nullable()->comment('Nombre del dispositivo (Chrome on Windows)');
            $table->string('ip_address', 45)->nullable()->comment('IP del dispositivo (IPv6 compatible)');
            $table->text('user_agent')->nullable()->comment('User agent completo del navegador');

            // ===== EXPIRACIÓN Y USO =====
            $table->timestamp('expires_at')->comment('Fecha de expiración del token');
            $table->timestamp('last_used_at')->nullable()->comment('Última vez que se usó para refresh');

            // ===== REVOCACIÓN =====
            $table->boolean('is_revoked')->default(false)->comment('Si el token fue revocado manualmente');
            $table->timestamp('revoked_at')->nullable()->comment('Fecha de revocación');
            $table->uuid('revoked_by_id')->nullable()->comment('Usuario que revocó el token');
            $table->foreign('revoked_by_id')
                ->references('id')
                ->on('auth.users')
                ->onDelete('set null');

            // ===== AUDITORÍA =====
            $table->timestamps(); // created_at, updated_at

            // ===== ÍNDICES PARA PERFORMANCE =====
            $table->index('user_id', 'idx_refresh_tokens_user_id');
            $table->index('token_hash', 'idx_refresh_tokens_token_hash');
            $table->index('expires_at', 'idx_refresh_tokens_expires_at');
            $table->index('is_revoked', 'idx_refresh_tokens_is_revoked');
            $table->index(['user_id', 'is_revoked'], 'idx_refresh_tokens_user_active');

            // Comentario de la tabla
            $table->comment('Refresh tokens JWT para renovación de access tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth.refresh_tokens');
    }
};