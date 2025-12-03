<?php declare(strict_types=1);

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
        Schema::table('auth.refresh_tokens', function (Blueprint $table) {
            // Agregar columna para guardar el user agent raw (sin normalizar)
            // para auditoría y parsing futuro mejorado
            $table->text('user_agent_raw')->nullable()->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth.refresh_tokens', function (Blueprint $table) {
            $table->dropColumn('user_agent_raw');
        });
    }
};
