# Análisis de Inconsistencias: Jerarquía de Contextos Company

Fecha: 31 de Octubre de 2025
Severidad: CRÍTICA

## 1. Estado de Base de Datos

### TABLA 6: COMPANY_REQUESTS
Campos existentes:
- business_description ✅ EXISTE
- industry_type ✅ EXISTE
- estimated_users
- request_code, company_name, legal_name, admin_email, website
- contact_address, contact_city, contact_country, contact_postal_code
- tax_id, status, reviewed_by, reviewed_at, rejection_reason, created_company_id

### TABLA 7: COMPANIES  
Campos existentes:
- company_code, name, legal_name, support_email, phone, website
- contact_address, contact_city, contact_state, contact_country, contact_postal_code
- tax_id, legal_representative, business_hours, timezone
- logo_url, favicon_url, primary_color, secondary_color, settings
- status, created_from_request_id, admin_user_id

CRÍTICO: business_description e industry_type NO EXISTEN en companies ❌

## 2. Análisis Modelo Company.php

$fillable (23 campos):
company_code, name, legal_name, support_email, phone, website, contact_address, contact_city, contact_state, contact_country, contact_postal_code, tax_id, legal_representative, business_hours, timezone, logo_url, favicon_url, primary_color, secondary_color, settings, status, created_from_request_id, admin_user_id

CONFIRMACIÓN: business_description e industry_type NO en $fillable ❌

## 3. Análisis de Resources REST

### CompanyMinimalResource - ✅ CORRECTO
Campos: id, companyCode, name, logoUrl (4 campos)
Solo usa campos que existen

### CompanyExploreResource - ❌ PROBLEMÁTICO
Campos: id, companyCode, name, logoUrl, description, industry, city, country, primaryColor, status, followersCount, isFollowedByMe (12 campos)

PROBLEMAS (líneas 32-33):
'description' => $this->business_description ?? null,  ← NO EXISTE
'industry' => $this->industry_type ?? null,             ← NO EXISTE

Resultado: Siempre retorna null

### CompanyResource - ✅ CORRECTO
35+ campos, todos válidos

## 4. Inconsistencias Identificadas

### INCONSISTENCIA 1: CompanyExploreResource retorna campos que no existen

Severidad: CRÍTICA
Localización: CompanyExploreResource.php líneas 32-33

Raíz:
- business_description existe SOLO en company_requests
- industry_type existe SOLO en company_requests
- Company model NO tiene estos campos
- Resource intenta acceder a atributos inexistentes

Consecuencia:
- Respuesta REST: "description": null, "industry": null
- Frontend recibe datos vacíos
- Comentario en CompanyController.php línea 287: "Note: Campo industry no existe en la BD"

### INCONSISTENCIA 2: Jerarquía de datos confusa

Severidad: ALTA

company_requests (solicitud histórica):
- Propósito: Almacenar solicitud onboarding
- Contiene: business_description, industry_type, estimated_users
- Estado: pending/approved/rejected
- Permanencia: Histórico

companies (entidad operativa):
- Propósito: Datos operativos
- Contiene: business_hours, settings, branding, admin_user_id
- Estado: active/suspended
- NO contiene: description, industry, estimated_users

Pregunta sin respuesta: ¿Qué pasa con business_description después de aprobación?
- No se copia a companies
- No se retorna en REST
- Datos quedan "perdidos"

## 5. Recomendaciones

### OPCIÓN RECOMENDADA: Mantener separación (V7.0)

ACCIÓN 1: Arreglar CompanyExploreResource

Remover líneas 32-33:
'description' => $this->business_description ?? null,
'industry' => $this->industry_type ?? null,

Nueva versión (10 campos):
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'companyCode' => $this->company_code,
        'name' => $this->name,
        'logoUrl' => $this->logo_url,
        'city' => $this->contact_city ?? null,
        'country' => $this->contact_country ?? null,
        'primaryColor' => $this->primary_color ?? null,
        'status' => $this->status ? strtoupper($this->status) : null,
        'followersCount' => $this->followers_count ?? 0,
        'isFollowedByMe' => $this->is_followed_by_me ?? false,
    ];
}

ACCIÓN 2: Actualizar OpenAPI Documentation
Archivo: CompanyController.php líneas 205-227
Remover propiedades description e industry

### OPCIÓN FUTURA: Agregar campos a companies (Sprint N+1)

Si necesitas description/industry en operaciones:

Migración:
ALTER TABLE business.companies 
ADD COLUMN description TEXT;
ADD COLUMN industry_type VARCHAR(100);
ADD COLUMN estimated_users INT;

Actualizar:
- Company.php $fillable
- CompanyExploreResource
- GraphQL schema
- OpenAPI docs

No es crítico ahora.

## 6. Resumen

¿Qué está mal?
CompanyExploreResource retorna campos que NO existen en companies:
- business_description (existe solo en company_requests)
- industry_type (existe solo en company_requests)

¿Por qué?
Confusión sobre jerarquía de datos:
- Asunción de que companies tendría description/industry
- Model Company NO los tiene (diseño correcto)
- Resource intenta acceder a atributos inexistentes
- Resultado: campos null en respuestas

¿Cuál es la solución?

Inmediata:
- Remover 2 líneas de CompanyExploreResource
- Actualizar OpenAPI docs
- Validar explore sin null campos

Impacto:
- Severidad: CRÍTICA
- Cambios: 2 líneas
- Migraciones SQL: 0
- Breaking change: BAJO (campos siempre null)

## Jerarquía Correcta Propuesta

business.company_requests (FormularioOnboarding - INMUTABLE)
- business_description, industry_type, estimated_users
- company_name, admin_email, website, contact_*
- status (pending/approved/rejected)
- reviewed_by, created_company_id

business.companies (EntidadOperativa - MUTABLE)
- company_code, name, legal_name
- support_email, phone, website
- contact_address, contact_city, contact_state, contact_country, contact_postal_code
- tax_id, legal_representative
- business_hours (JSONB), timezone
- logo_url, favicon_url, primary_color, secondary_color
- settings (JSONB)
- status (active/suspended)
- admin_user_id, created_from_request_id

REST Resources:
- CompanyMinimalResource (4 campos) → Selectores
- CompanyExploreResource (10 campos) → Cards públicas (DESPUÉS DE FIX)
- CompanyResource (35+ campos) → Administración

---

CONCLUSIÓN: La jerarquía de datos es correcta por diseño (V7.0), pero CompanyExploreResource intenta retornar campos que no existen. Solución: remover 2 líneas del Resource y actualizar OpenAPI.
