<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'user_roles' en el schema 'auth'
 *
 * Tabla pivot entre users y roles con contexto de empresa.
 * Permite que un usuario tenga diferentes roles en diferentes empresas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth.user_roles', function (Blueprint $table) {
            // ===== PRIMARY KEY =====
            $table->uuid('id')->primary();

            // ===== RELACIONES =====
            $table->uuid('user_id')->comment('FK a auth.users');
            $table->foreign('user_id')
                ->references('id')
                ->on('auth.users')
                ->onDelete('cascade');

            $table->uuid('role_id')->comment('FK a auth.roles');
            $table->foreign('role_id')
                ->references('id')
                ->on('auth.roles')
                ->onDelete('restrict'); // No permitir eliminar roles en uso

            // ===== CONTEXTO DE EMPRESA =====
            $table->uuid('company_id')->nullable()->comment('FK a business.companies (NULL para roles globales)');
            // NOTA: FK a business.companies se agregará cuando se cree esa tabla en CompanyManagement feature

            // ===== ESTADO =====
            $table->boolean('is_active')->default(true)->comment('Si el rol está activo');

            // ===== FECHAS =====
            $table->timestamp('assigned_at')->useCurrent()->comment('Fecha de asignación del rol');
            $table->timestamp('revoked_at')->nullable()->comment('Fecha de revocación del rol');

            // ===== AUDITORÍA =====
            $table->uuid('assigned_by_id')->nullable()->comment('Usuario que asignó el rol');
            $table->uuid('revoked_by_id')->nullable()->comment('Usuario que revocó el rol');
            $table->timestamps(); // created_at, updated_at

            // ===== ÍNDICES =====
            $table->index('user_id', 'idx_user_roles_user_id');
            $table->index('role_id', 'idx_user_roles_role_id');
            $table->index('company_id', 'idx_user_roles_company_id');
            $table->index('is_active', 'idx_user_roles_is_active');
            $table->index(['user_id', 'role_id', 'company_id'], 'idx_user_roles_composite');
            $table->index(['user_id', 'is_active'], 'idx_user_roles_user_active');

            // ===== UNIQUE CONSTRAINT =====
            // Un usuario no puede tener el mismo rol dos veces en la misma empresa (o globalmente)
            $table->unique(['user_id', 'role_id', 'company_id'], 'unq_user_role_company');

            // Comentario de la tabla
            $table->comment('Roles asignados a usuarios con contexto de empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth.user_roles');
    }
};