<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;

/**
 * Servicio para detectar empresas duplicadas usando múltiples heurísticas.
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
     *     matched_companies: array<Company>,
     *     matched_requests: array<CompanyRequest>
     * }
     */
    public function detectDuplicates(
        string $companyName,
        string $adminEmail,
        ?string $taxId = null,
        ?string $website = null,
        ?string $industryId = null,
        ?string $excludeRequestId = null
    ): array {
        $blockingErrors = [];
        $warnings = [];
        $matchedCompanies = [];
        $matchedRequests = [];

        // ========================================================================
        // 1. VALIDACIÓN: Tax ID duplicado (BLOQUEO ABSOLUTO)
        // ========================================================================
        if ($taxId !== null && trim($taxId) !== '') {
            // Buscar en solicitudes pendientes
            $duplicateRequest = CompanyRequest::where('tax_id', $taxId)
                ->where('status', 'pending')
                ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
                ->first();

            if ($duplicateRequest) {
                $blockingErrors['tax_id'] = sprintf(
                    'Ya existe una solicitud pendiente con el NIT/Tax ID "%s" para la empresa "%s".',
                    $taxId,
                    $duplicateRequest->company_name
                );
                $matchedRequests[] = $duplicateRequest;
            }

            // Buscar en empresas ya creadas
            $duplicateCompany = Company::where('tax_id', $taxId)->first();

            if ($duplicateCompany) {
                $blockingErrors['tax_id'] = sprintf(
                    'Ya existe una empresa registrada con el NIT/Tax ID "%s": "%s". Si deseas formar parte de esta empresa, contacta con el administrador de plataforma.',
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

        // Buscar empresas donde el admin_email es el support_email
        $companiesWithSameEmail = Company::where('support_email', $adminEmail)
            ->get();

        foreach ($companiesWithSameEmail as $company) {
            $existingNormalized = $this->normalizeCompanyName($company->name);
            $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

            if ($similarity > 0.70) { // 70% de similitud con mismo email = muy sospechoso
                $blockingErrors['admin_email'] = sprintf(
                    'El email "%s" ya es el email de soporte de la empresa "%s" (código: %s). Si deseas administrar esta empresa, contacta con el administrador de plataforma.',
                    $adminEmail,
                    $company->name,
                    $company->company_code
                );
                $matchedCompanies[] = $company;
            } elseif ($similarity > 0.30) { // Similar pero no tanto - advertir
                $warnings['admin_email'] = sprintf(
                    'ADVERTENCIA: El email "%s" ya está asociado a la empresa "%s" que tiene un nombre similar. Verifica que no sean la misma empresa.',
                    $adminEmail,
                    $company->name
                );
            }
        }

        // Buscar solicitudes pendientes con mismo email
        $requestsWithSameEmail = CompanyRequest::where('admin_email', $adminEmail)
            ->where('status', 'pending')
            ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
            ->get();

        foreach ($requestsWithSameEmail as $request) {
            $existingNormalized = $this->normalizeCompanyName($request->company_name);
            $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

            if ($similarity > 0.70) { // Mismo email + nombre muy similar
                $blockingErrors['admin_email'] = sprintf(
                    'Ya existe una solicitud pendiente con el email "%s" para una empresa de nombre similar: "%s" (código: %s).',
                    $adminEmail,
                    $request->company_name,
                    $request->request_code
                );
                $matchedRequests[] = $request;
            }
        }

        // ========================================================================
        // 3. VALIDACIÓN: Website Domain + Nombre Similar (BLOQUEO)
        // ========================================================================
        if ($website !== null && trim($website) !== '') {
            $domain = $this->extractDomain($website);

            if ($domain !== null) {
                // Buscar empresas con mismo dominio
                $companiesWithSameDomain = Company::whereNotNull('website')
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
                            'Ya existe una empresa con el mismo sitio web (dominio: %s): "%s". Si deseas formar parte de esta empresa, contacta con el administrador de plataforma.',
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
            // Buscar empresas con nombres muy similares
            $similarCompanies = Company::all()->filter(function ($company) use ($normalizedName) {
                $existingNormalized = $this->normalizeCompanyName($company->name);
                $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

                return $similarity > 0.85; // 85% de similitud
            });

            if ($similarCompanies->isNotEmpty()) {
                $company = $similarCompanies->first();
                $warnings['company_name'] = sprintf(
                    'ADVERTENCIA: Ya existe una empresa con nombre muy similar: "%s" (código: %s). Si es la misma empresa, contacta con el administrador de plataforma.',
                    $company->name,
                    $company->company_code
                );
                $matchedCompanies = array_merge($matchedCompanies, $similarCompanies->all());
            }

            // Buscar solicitudes pendientes con nombres muy similares
            $similarRequests = CompanyRequest::where('status', 'pending')
                ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
                ->get()
                ->filter(function ($request) use ($normalizedName) {
                    $existingNormalized = $this->normalizeCompanyName($request->company_name);
                    $similarity = $this->calculateSimilarity($normalizedName, $existingNormalized);

                    return $similarity > 0.85;
                });

            if ($similarRequests->isNotEmpty() && empty($warnings['company_name'])) {
                $request = $similarRequests->first();
                $warnings['company_name'] = sprintf(
                    'ADVERTENCIA: Ya existe una solicitud pendiente con nombre muy similar: "%s" (código: %s).',
                    $request->company_name,
                    $request->request_code
                );
                $matchedRequests = array_merge($matchedRequests, $similarRequests->all());
            }
        }

        return [
            'is_duplicate' => ! empty($blockingErrors),
            'blocking_errors' => $blockingErrors,
            'warnings' => $warnings,
            'matched_companies' => array_unique($matchedCompanies),
            'matched_requests' => array_unique($matchedRequests),
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
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
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
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'http://'.$url;
        }

        $parsedUrl = parse_url($url);

        if (! isset($parsedUrl['host'])) {
            return null;
        }

        $host = $parsedUrl['host'];

        // Eliminar www.
        $host = preg_replace('/^www\./', '', $host);

        return strtolower($host);
    }
}
