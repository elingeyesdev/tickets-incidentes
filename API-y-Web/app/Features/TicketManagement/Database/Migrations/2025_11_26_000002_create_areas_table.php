<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla areas en schema business (NOT ticketing)
        Schema::create('business.areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key a companies
            $table->foreign('company_id')
                ->references('id')
                ->on('business.companies')
                ->onDelete('cascade');

            // Nombre único por empresa
            $table->unique(['company_id', 'name'], 'areas_company_name_unique');

            // Índices
            $table->index('company_id', 'idx_areas_company_id');
            $table->index('is_active', 'idx_areas_is_active');
        });

        // Agregar columna area_id a tickets (FK a business.areas, cross-schema)
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->uuid('area_id')
                ->nullable()
                ->after('category_id')
                ->comment('Área/departamento asignado (opcional)');

            $table->foreign('area_id')
                ->references('id')
                ->on('business.areas')
                ->onDelete('set null');

            $table->index('area_id', 'idx_tickets_area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropIndex('idx_tickets_area_id');
            $table->dropColumn('area_id');
        });

        Schema::dropIfExists('business.areas');
    }
};
