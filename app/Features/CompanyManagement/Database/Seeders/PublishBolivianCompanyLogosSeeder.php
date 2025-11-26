<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeder para publicar logos de empresas bolivianas
 *
 * Lee logos desde: app/Features/CompanyManagement/resources/logos/
 * Los publica a: storage/app/public/company-logos/
 * Actualiza URLs en la BD
 *
 * Uso:
 * php artisan db:seed --class="App\Features\CompanyManagement\Database\Seeders\PublishBolivianCompanyLogosSeeder"
 */
class PublishBolivianCompanyLogosSeeder extends Seeder
{
    /**
     * Mapeo de archivos a empresas
     */
    private array $logoMap = [
        'pil-andina-logo.png' => 'b6916ab4-9c64-4305-8054-958c97131ea3',
        'fassil-logo.png' => 'ec198568-b237-432b-81ae-76d263596471',
        'ypfb-logo.png' => '4dfda053-4bfa-42a0-ae58-cc8e96bebf0c',
        'tigo-logo.png' => 'd5e69850-ba31-4e28-bb99-f97468bd72c5',
        'cbn-logo.png' => 'dea3f6fe-d906-414c-b4b3-d72f729492f8',
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════╗\n";
        echo "║  Publicando Logos - Empresas Bolivianas                ║\n";
        echo "║  (Lee desde feature resources, publica a storage)      ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        $updated = 0;
        $failed = 0;
        $baseDir = app_path('Features/CompanyManagement/resources/logos');

        // Verificar que el directorio existe
        if (!is_dir($baseDir)) {
            echo "❌ Error: Directorio de logos no encontrado en: {$baseDir}\n";
            echo "   Crea la carpeta y agrega los logos ahí.\n\n";
            return;
        }

        foreach ($this->logoMap as $fileName => $companyId) {
            $sourceFile = "{$baseDir}/{$fileName}";
            $company = Company::find($companyId);

            if (!$company) {
                echo "⚠️  Empresa no encontrada (ID: {$companyId})\n";
                $failed++;
                continue;
            }

            echo "📦 {$company->name}\n";
            echo "   ID: {$company->id}\n";
            echo "   Archivo: {$fileName}\n";

            // Verificar que el archivo existe
            if (!file_exists($sourceFile)) {
                echo "   ❌ Error: Archivo no encontrado en {$sourceFile}\n\n";
                $failed++;
                continue;
            }

            try {
                // Leer contenido del archivo
                $fileContent = file_get_contents($sourceFile);
                $fileSize = strlen($fileContent);

                // Crear directorio si no existe
                $storagePath = "company-logos/{$company->id}";
                if (!Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->makeDirectory($storagePath);
                }

                // Generar nombre único con timestamp
                $timestamp = now()->timestamp;
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                $finalFileName = "{$timestamp}_{$nameWithoutExt}.{$extension}";

                // Guardar en storage público
                $fullPath = "{$storagePath}/{$finalFileName}";
                Storage::disk('public')->put($fullPath, $fileContent);

                // Generar URL completa
                $logoUrl = asset("storage/{$fullPath}");

                // Actualizar empresa
                $company->update(['logo_url' => $logoUrl]);

                echo "   ✅ Publicado: {$finalFileName}\n";
                echo "   📊 Tamaño: " . $this->formatBytes($fileSize) . "\n";
                echo "   🌐 URL: {$logoUrl}\n\n";
                $updated++;

            } catch (\Exception $e) {
                echo "   ❌ Error: " . $e->getMessage() . "\n\n";
                $failed++;
            }
        }

        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ ✅ COMPLETADO                                          ║\n";
        echo "║    Publicados: {$updated}                                      ║\n";
        echo "║    Fallidos: {$failed}                                          ║\n";
        echo "║                                                        ║\n";
        echo "║ 📁 Origen:  app/Features/CompanyManagement/           ║\n";
        echo "║            resources/logos/                            ║\n";
        echo "║ 📍 Destino: storage/app/public/company-logos/         ║\n";
        echo "║ 🔗 URLs:    En table companies.logo_url               ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
