<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;

/**
 * Servicio para detectar empresas duplicadas usando múltiples heurísticas.
 *
 * ARQUITECTURA NORMALIZADA:
 * - Ahora solo busca en la tabla `companies` (unificada)
 * - Las solicitudes pendientes son empresas con status='pending'
 * - Las empresas rechazadas son empresas con status='rejected'
 * - Las empresas activas son empresas con status='active'
 * 
 * ESTRATEGIA DE DETECCIÓN:
 * 1. Tax ID (NIT/RUC) - Si está presente, debe ser único (garantizado por BD)
 * 2. Admin Email + Nombre Similar - Previene misma persona creando empresa duplicada
 * 3. Website Domain + Nombre Similar - Previene usar mismo dominio corporativo
 * 4. Nombre muy similar (>85% match) - Advierte sobre posibles duplicados
 */
final class CompanyDuplicateDetectionService
{
    /**
     * Resultado de detección de duplicados.
     *
     * @return array{
     *     is_duplicate: bool,
     *     blocking_errors: array<string, string>,
     *     warnings: array<string, string>,
     *     matched_companies: array<Company>
     * }
     */
    public function detectDuplicates(
        string $companyName,
        string $adminEmail,
        ?string $taxId = null,
        ?string $website = null,
        ?string $industryId = null,
        ?string $excludeCompanyId = null
    ): array {
        $blockingErrors = [];
        $warnings = [];
        $matchedCompanies = [];

        // ========================================================================
        // 1. VALIDACIÓN: Tax ID duplicado (BLOQUEO ABSOLUTO)
        // ========================================================================
        if ($taxId !== null && trim($taxId) !== '') {
            // Buscar en todas las empresas (activas, pendientes, rechazadas)
            $duplicateCompany = Company::withAllStatuses()
                ->where('tax_id', $taxId)
                ->when($excludeCompanyId, fn($q) => $q->where('id', '!=', $excludeCompanyId))
                ->first();

            if ($duplicateCompany) {
                $statusLabel = match ($duplicateCompany->status) {
                    'pending' => 'solicitud pendiente',
                    'rejected' => 'solicitud rechazada',
                    'suspended' => 'empresa suspendida',
                    default => 'empresa registrada',
                };

                $blockingErrors['tax_id'] = sprintf(
                    'Ya existe una %s con el NIT/Tax ID "%s": "%s". Si deseas formar parte de esta empresa, contacta con el administrador de plataforma.',
                    $statusLabel,
                    $taxId,
                    $duplicateCompany->name
                );
                $matchedCompanies[] = $duplicateCompany;
            }
        }

        // ========================================================================
        // 2. VALIDACIÓN: Admin Email + Nombre Similar (BLOQUEO)
        // ========================================================================
        $normalizedName = $this->normalizeCompanyName($companyName);

        // Buscar empresas donde el email coincide (activas y pendientes)
        $companiesWithSameEmail = Company::withAllStatuses()
            ->where('support_email', $adminEmail)
            ->whereIn('status', ['active', 'pending']) // No incluir rechazadas
            ->when($excludeCompanyId, fn($q) => $q->where('id', '!=', $excludeCompanyId))
            ->get();

        foreach ($companiesWithSameEmail as $company) {
            $existingNormalized = $this->normalizeCompanyName($company->name);
            $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

            $isPending = $company->status === 'pending';
            $requestCode = $company->onboardingDetails?->request_code ?? $company->company_code;

            if ($similarity > 0.70) { // 70% de similitud con mismo email = muy sospechoso
                if ($isPending) {
                    $blockingErrors['admin_email'] = sprintf(
                        'Ya existe una solicitud pendiente con el email "%s" para una empresa de nombre similar: "%s" (código: %s).',
                        $adminEmail,
                        $company->name,
                        $requestCode
                    );
                } else {
                    $blockingErrors['admin_email'] = sprintf(
                        'El email "%s" ya es el email de soporte de la empresa "%s" (código: %s). Si deseas administrar esta empresa, contacta con el administrador de plataforma.',
                        $adminEmail,
                        $company->name,
                        $company->company_code
                    );
                }
                $matchedCompanies[] = $company;
            } elseif ($similarity > 0.30) { // Similar pero no tanto - advertir
                $warnings['admin_email'] = sprintf(
                    'ADVERTENCIA: El email "%s" ya está asociado a una %s "%s" que tiene un nombre similar. Verifica que no sean la misma empresa.',
                    $adminEmail,
                    $isPending ? 'solicitud' : 'empresa',
                    $company->name
                );
            }
        }

        // ========================================================================
        // 3. VALIDACIÓN: Website Domain + Nombre Similar (BLOQUEO)
        // ========================================================================
        if ($website !== null && trim($website) !== '') {
            $domain = $this->extractDomain($website);

            if ($domain !== null) {
                // Buscar empresas con mismo dominio
                $companiesWithSameDomain = Company::withAllStatuses()
                    ->whereNotNull('website')
                    ->whereIn('status', ['active', 'pending'])
                    ->when($excludeCompanyId, fn($q) => $q->where('id', '!=', $excludeCompanyId))
                    ->get()
                    ->filter(function ($company) use ($domain) {
                        $companyDomain = $this->extractDomain($company->website ?? '');

                        return $companyDomain === $domain;
                    });

                foreach ($companiesWithSameDomain as $company) {
                    $existingNormalized = $this->normalizeCompanyName($company->name);
                    $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

                    if ($similarity > 0.50) { // Mismo dominio + nombre similar
                        $blockingErrors['website'] = sprintf(
                            'Ya existe una %s con el mismo sitio web (dominio: %s): "%s". Si deseas formar parte de esta empresa, contacta con el administrador de plataforma.',
                            $company->status === 'pending' ? 'solicitud' : 'empresa',
                            $domain,
                            $company->name
                        );
                        $matchedCompanies[] = $company;
                    }
                }
            }
        }

        // ========================================================================
        // 4. VALIDACIÓN: Nombre muy similar (ADVERTENCIA)
        // ========================================================================
        // Solo si no hay errores de bloqueo previos
        if (empty($blockingErrors)) {
            // Buscar empresas con nombres muy similares (activas y pendientes)
            $similarCompanies = Company::withAllStatuses()
                ->whereIn('status', ['active', 'pending'])
                ->when($excludeCompanyId, fn($q) => $q->where('id', '!=', $excludeCompanyId))
                ->get()
                ->filter(function ($company) use ($normalizedName) {
                    $existingNormalized = $this->normalizeCompanyName($company->name);
                    $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

                    return $similarity > 0.85; // 85% de similitud
                });

            if ($similarCompanies->isNotEmpty()) {
                $company = $similarCompanies->first();
                $isPending = $company->status === 'pending';
                $code = $isPending
                    ? ($company->onboardingDetails?->request_code ?? $company->company_code)
                    : $company->company_code;

                $warnings['company_name'] = sprintf(
                    'ADVERTENCIA: Ya existe una %s con nombre muy similar: "%s" (código: %s). Si es la misma empresa, contacta con el administrador de plataforma.',
                    $isPending ? 'solicitud pendiente' : 'empresa',
                    $company->name,
                    $code
                );
                $matchedCompanies = array_merge($matchedCompanies, $similarCompanies->all());
            }
        }

        return [
            'is_duplicate' => !empty($blockingErrors),
            'blocking_errors' => $blockingErrors,
            'warnings' => $warnings,
            'matched_companies' => array_unique($matchedCompanies),
        ];
    }

    /**
     * Normalizar nombre de empresa para comparación.
     *
     * Proceso:
     * - Lowercase
     * - Elimina caracteres especiales, espacios, puntos
     * - Solo alfanuméricos
     *
     * Ejemplos:
     * - "UNITEL S.A." -> "unitelsa"
     * - "Viva-Bolivia" -> "vivabolivia"
     * - "Empresa Eléctrica" -> "empresaelectrica"
     */
    private function normalizeCompanyName(string $name): string
    {
        // Lowercase
        $normalized = mb_strtolower($name, 'UTF-8');

        // Eliminar acentos
        $normalized = $this->removeAccents($normalized);

        // Solo alfanuméricos (elimina espacios, puntos, guiones, etc.)
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return $normalized;
    }

    /**
     * Eliminar acentos de caracteres.
     */
    private function removeAccents(string $string): string
    {
        $unwanted_array = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
        ];

        return strtr($string, $unwanted_array);
    }

    /**
     * Calcular similitud entre dos strings normalizados.
     *
     * Usa algoritmo Levenshtein Distance normalizado (0-1).
     * - 0 = totalmente diferente
     * - 1 = idéntico
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));

        if ($maxLen === 0) {
            return 1.0; // Ambos vacíos = idénticos
        }

        $levenshtein = levenshtein($str1, $str2);

        return 1 - ($levenshtein / $maxLen);
    }

    /**
     * Extraer dominio de una URL.
     *
     * Ejemplos:
     * - "https://www.unitel.com.bo/contacto" -> "unitel.com.bo"
     * - "http://viva.bo" -> "viva.bo"
     * - "www.tigo.com.bo" -> "tigo.com.bo"
     */
    private function extractDomain(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        // Agregar http:// si no tiene protocolo
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }

        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['host'])) {
            return null;
        }

        $host = $parsedUrl['host'];

        // Eliminar www.
        $host = preg_replace('/^www\./', '', $host);

        return strtolower($host);
    }
}
