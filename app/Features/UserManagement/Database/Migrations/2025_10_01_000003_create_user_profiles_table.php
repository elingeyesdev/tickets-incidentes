<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'user_profiles' en el schema 'auth'
 *
 * Tabla de perfiles de usuarios (relación 1:1 con users).
 * Contiene información personal y preferencias del usuario.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth.user_profiles', function (Blueprint $table) {
            // ===== PRIMARY KEY =====
            $table->uuid('id')->primary();

            // ===== RELACIÓN CON USER (1:1) =====
            $table->uuid('user_id')->unique()->comment('FK a auth.users');
            $table->foreign('user_id')
                ->references('id')
                ->on('auth.users')
                ->onDelete('cascade');

            // ===== INFORMACIÓN PERSONAL =====
            $table->string('first_name', 100)->comment('Nombre del usuario');
            $table->string('last_name', 100)->comment('Apellido del usuario');
            $table->string('display_name', 200)->comment('Nombre completo calculado');
            $table->string('phone_number', 20)->nullable()->comment('Teléfono de contacto');
            $table->string('avatar_url', 500)->nullable()->comment('URL del avatar');

            // ===== PREFERENCIAS DE INTERFAZ =====
            $table->enum('theme', ['light', 'dark'])->default('light')->comment('Tema de la interfaz');
            $table->enum('language', ['es', 'en'])->default('es')->comment('Idioma preferido');
            $table->string('timezone', 50)->default('America/La_Paz')->comment('Zona horaria');

            // ===== PREFERENCIAS DE NOTIFICACIONES =====
            $table->boolean('push_web_notifications')->default(true)->comment('Notificaciones web push');
            $table->boolean('notifications_tickets')->default(true)->comment('Notificaciones de tickets');

            // ===== ACTIVIDAD =====
            $table->timestamp('last_activity_at')->nullable()->comment('Última actividad en el perfil');

            // ===== AUDITORÍA =====
            $table->timestamps(); // created_at, updated_at

            // ===== ÍNDICES =====
            $table->index('user_id', 'idx_user_profiles_user_id');
            $table->index(['first_name', 'last_name'], 'idx_user_profiles_name');
            $table->index('last_activity_at', 'idx_user_profiles_activity');

            // Comentario de la tabla
            $table->comment('Perfiles de usuarios - Información personal y preferencias');
        });

        // Crear índice para búsqueda full-text en nombres (PostgreSQL)
        DB::statement("CREATE INDEX idx_user_profiles_name_search ON auth.user_profiles USING gin(to_tsvector('spanish', first_name || ' ' || last_name))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth.user_profiles');
    }
};