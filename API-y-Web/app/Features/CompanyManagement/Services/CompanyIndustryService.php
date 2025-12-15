<?php

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\CompanyIndustry;
use Illuminate\Database\Eloquent\Collection;

class CompanyIndustryService
{
    /**
     * Obtener todas las industrias ordenadas alfabéticamente.
     *
     * @return Collection<CompanyIndustry>
     */
    public function index(): Collection
    {
        return CompanyIndustry::alphabetical()->get();
    }

    /**
     * Obtener industria por código.
     *
     * @param string $code Código de la industria (ej: 'technology', 'healthcare')
     * @return CompanyIndustry|null
     */
    public function getByCode(string $code): ?CompanyIndustry
    {
        return CompanyIndustry::where('code', $code)->first();
    }

    /**
     * Obtener industria por ID.
     *
     * @param string $id UUID de la industria
     * @return CompanyIndustry|null
     */
    public function findById(string $id): ?CompanyIndustry
    {
        return CompanyIndustry::find($id);
    }

    /**
     * Obtener industrias con empresas activas.
     *
     * Retorna solo las industrias que tienen al menos una empresa activa,
     * junto con el conteo de empresas activas por industria.
     *
     * @return Collection<CompanyIndustry>
     */
    public function getActiveIndustries(): Collection
    {
        return CompanyIndustry::whereHas('companies', function ($query) {
            $query->where('status', 'active');
        })
        ->withCount(['companies as active_companies_count' => function ($query) {
            $query->where('status', 'active');
        }])
        ->alphabetical()
        ->get();
    }

    /**
     * Obtener conteo de empresas por industria.
     *
     * @param CompanyIndustry $industry
     * @param string $status Estado de la empresa (opcional, por defecto 'active')
     * @return int
     */
    public function getCompaniesCount(CompanyIndustry $industry, string $status = 'active'): int
    {
        return $industry->companies()
            ->where('status', $status)
            ->count();
    }

    /**
     * Obtener todas las industrias con conteo de empresas.
     *
     * @param string $status Estado de las empresas a contar (opcional, por defecto 'active')
     * @return Collection<CompanyIndustry>
     */
    public function getAllWithCompaniesCount(string $status = 'active'): Collection
    {
        return CompanyIndustry::withCount(['companies as companies_count' => function ($query) use ($status) {
            $query->where('status', $status);
        }])
        ->alphabetical()
        ->get();
    }
}
