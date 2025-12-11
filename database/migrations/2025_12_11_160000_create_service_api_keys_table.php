<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para crear la tabla de API Keys de servicios externos.
 * 
 * Esta tabla almacena las API Keys que los proyectos externos usan
 * para autenticarse con el widget de Helpdesk.
 * 
 * Cada API Key está vinculada a una empresa específica.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relación con la empresa
            $table->foreignUuid('company_id')
                ->constrained('business.companies')
                ->cascadeOnDelete();
            
            // La API Key en sí (formato: sk_live_xxxxxxxx o sk_test_xxxxxxxx)
            $table->string('key', 64)->unique();
            
            // Nombre descriptivo para identificar la key
            $table->string('name'); // ej: "Widget Producción", "Widget Desarrollo"
            
            // Descripción opcional
            $table->text('description')->nullable();
            
            // Tipo de key (live o test)
            $table->enum('type', ['live', 'test'])->default('live');
            
            // Tracking de uso
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('usage_count')->default(0);
            
            // Expiración opcional (null = nunca expira)
            $table->timestamp('expires_at')->nullable();
            
            // Estado activo/inactivo
            $table->boolean('is_active')->default(true);
            
            // Quién creó la key
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('key');
            $table->index(['company_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_api_keys');
    }
};
