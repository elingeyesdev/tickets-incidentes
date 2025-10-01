<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla 'roles' en el schema 'auth'
 *
 * Tabla catálogo de roles del sistema.
 * Contiene los 4 roles base: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth.roles', function (Blueprint $table) {
            // ===== PRIMARY KEY =====
            $table->uuid('id')->primary();

            // ===== INFORMACIÓN DEL ROL =====
            $table->string('name', 50)->unique()->comment('Nombre del rol: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN');
            $table->string('display_name', 100)->comment('Nombre legible: Usuario, Agente, Administrador de Empresa, etc.');
            $table->text('description')->nullable()->comment('Descripción del rol');

            // ===== PERMISOS =====
            $table->json('permissions')->comment('Array de permisos del rol');

            // ===== CONTEXTO =====
            $table->boolean('requires_company')->default(false)->comment('Si el rol requiere contexto de empresa');

            // ===== CONFIGURACIÓN =====
            $table->string('default_dashboard', 100)->comment('Ruta del dashboard por defecto');
            $table->integer('priority')->default(0)->comment('Prioridad del rol (mayor número = mayor prioridad)');

            // ===== AUDITORÍA =====
            $table->timestamps(); // created_at, updated_at

            // ===== ÍNDICES =====
            $table->index('name', 'idx_roles_name');
            $table->index('requires_company', 'idx_roles_requires_company');
            $table->index('priority', 'idx_roles_priority');

            // Comentario de la tabla
            $table->comment('Catálogo de roles del sistema');
        });

        // Insertar roles del sistema
        $roles = [
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'USER',
                'display_name' => 'Usuario',
                'description' => 'Usuario final que puede crear tickets y ver su historial',
                'permissions' => json_encode(['tickets.create', 'tickets.view_own', 'profile.edit']),
                'requires_company' => false,
                'default_dashboard' => '/dashboard/user',
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'AGENT',
                'display_name' => 'Agente',
                'description' => 'Agente de soporte que responde tickets de una empresa',
                'permissions' => json_encode(['tickets.*', 'users.view', 'macros.use', 'company.view']),
                'requires_company' => true,
                'default_dashboard' => '/dashboard/agent',
                'priority' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'COMPANY_ADMIN',
                'display_name' => 'Administrador de Empresa',
                'description' => 'Administrador de una empresa, gestiona agentes y configuración',
                'permissions' => json_encode(['company.*', 'users.manage', 'agents.manage', 'tickets.*', 'macros.*', 'categories.*']),
                'requires_company' => true,
                'default_dashboard' => '/dashboard/company-admin',
                'priority' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'PLATFORM_ADMIN',
                'display_name' => 'Administrador de Plataforma',
                'description' => 'Administrador global con acceso total al sistema',
                'permissions' => json_encode(['*']),
                'requires_company' => false,
                'default_dashboard' => '/dashboard/admin',
                'priority' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('auth.roles')->insert($roles);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth.roles');
    }
};