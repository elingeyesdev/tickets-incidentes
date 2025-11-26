<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SeedBolivianCompanyLogosSeeder extends Seeder
{
    /**
     * Logos y favicons de empresas bolivianas
     * Solo ACTUALIZA, no elimina nada
     */
    private array $bolivianCompanies = [
        'PIL Andina S.A.' => [
            'logoUrl' => 'https://www.pilandina.com.bo/wp-content/uploads/2023/01/logo-pil.png',
            'faviconUrl' => 'https://www.pilandina.com.bo/favicon.ico',
        ],
        'Banco Fassil S.A.' => [
            'logoUrl' => 'https://www.fassil.com.bo/wp-content/uploads/2022/12/logo-fassil.png',
            'faviconUrl' => 'https://www.fassil.com.bo/favicon.ico',
        ],
        'YPFB Corporación' => [
            'logoUrl' => 'https://www.ypfb.gob.bo/sites/default/files/ypfb-logo.png',
            'faviconUrl' => 'https://www.ypfb.gob.bo/favicon.ico',
        ],
        'Tigo Bolivia S.A.' => [
            'logoUrl' => 'https://www.tigo.com.bo/wp-content/themes/tigo/assets/images/logo.png',
            'faviconUrl' => 'https://www.tigo.com.bo/favicon.ico',
        ],
        'Cervecería Boliviana Nacional S.A.' => [
            'logoUrl' => 'https://www.cbn.bo/wp-content/uploads/2022/11/cbn-logo.png',
            'faviconUrl' => 'https://www.cbn.bo/favicon.ico',
        ],
    ];

    public function run(): void
    {
        echo "\n╔════════════════════════════════════════════════════════╗\n";
        echo "║  Actualizando Logos de Empresas Bolivianas             ║\n";
        echo "║  (SIN ELIMINAR DATOS EXISTENTES)                       ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        $updated = 0;
        $failed = 0;

        foreach ($this->bolivianCompanies as $companyName => $urls) {
            $company = Company::where('name', $companyName)
                ->where('status', 'active')
                ->first();

            if (!$company) {
                echo "⚠️  Empresa no encontrada: {$companyName}\n";
                $failed++;
                continue;
            }

            echo "📦 {$companyName}\n";
            echo "   ID: {$company->id}\n";

            // Descargar y guardar logo
            $logoUrl = $this->downloadAndStoreFile(
                $urls['logoUrl'],
                "company-logos/{$company->id}",
                'logo',
                $companyName
            );

            // Descargar y guardar favicon
            $faviconUrl = $this->downloadAndStoreFile(
                $urls['faviconUrl'],
                "favicons/{$company->id}",
                'favicon',
                $companyName
            );

            // Actualizar empresa SOLO si la descarga fue exitosa
            if ($logoUrl) {
                $company->update(['logo_url' => $logoUrl]);
                echo "   ✅ Logo: {$logoUrl}\n";
                $updated++;
            } else {
                echo "   ❌ Logo: No se pudo descargar\n";
            }

            if ($faviconUrl) {
                $company->update(['favicon_url' => $faviconUrl]);
                echo "   ✅ Favicon: {$faviconUrl}\n";
                $updated++;
            } else {
                echo "   ❌ Favicon: No se pudo descargar\n";
            }

            echo "\n";
        }

        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ ✅ COMPLETADO                                          ║\n";
        echo "║    Actualizados: {$updated}                                        ║\n";
        echo "║    Fallidos: {$failed}                                          ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";
    }

    /**
     * Descargar archivo desde URL y guardarlo en storage
     */
    private function downloadAndStoreFile(
        string $sourceUrl,
        string $storagePath,
        string $fileType,
        string $companyName
    ): ?string {
        try {
            echo "      ⏳ Descargando {$fileType}...\n";

            // Crear contexto con SSL disabled y timeout
            $context = stream_context_create([
                'http' => ['timeout' => 15],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);

            // Descargar con manejo de errores
            $response = @file_get_contents($sourceUrl, false, $context);

            if (!$response || strlen($response) < 100) {
                echo "      ❌ Descarga fallida o archivo muy pequeño\n";
                return null;
            }

            // Detectar MIME type
            $finfo = finfo_open(\FINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $response);
            finfo_close($finfo);

            // Obtener extensión
            $extension = $this->getMimeExtension($mimeType);
            if (!$extension) {
                $urlPath = parse_url($sourceUrl, PHP_URL_PATH);
                $extension = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'png';
            }

            // Crear directorio si no existe
            if (!Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->makeDirectory($storagePath);
            }

            // Generar nombre único
            $timestamp = now()->timestamp;
            $fileName = "{$timestamp}_{$fileType}.{$extension}";
            $fullPath = "{$storagePath}/{$fileName}";

            // Guardar archivo en storage
            Storage::disk('public')->put($fullPath, $response);

            // Retornar URL completa
            $url = asset("storage/{$fullPath}");
            echo "      ✅ Guardado como: {$fileName}\n";

            return $url;

        } catch (\Exception $e) {
            echo "      ❌ Error: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Mapear MIME type a extensión
     */
    private function getMimeExtension(string $mimeType): ?string
    {
        $mimeMap = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'application/x-icon' => 'ico',
        ];

        return $mimeMap[$mimeType] ?? null;
    }
}
