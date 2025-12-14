# 🗺️ Mapeo de Datos: Normalización de Empresas

> **Objetivo:** Eliminar la duplicidad de datos entre `company_requests` y `companies` mediante Partición Vertical.
> **Estrategia:** Separar datos operativos (Company) de datos de proceso (Onboarding Details).

---

## 🏗️ 1. Estructura Destino (La Nueva Verdad)

### Tabla A: `business.companies` (Datos Operativos)
*Contiene la información real y activa de la empresa.*

| Campo Destino | Fuente Original (Mapeo) | Notas |
| :--- | :--- | :--- |
| `id` | `companies.id` | PK UUID |
| `status` | `companies.status` | **Nuevo:** Absorbe estados 'pending', 'rejected' |
| `company_code` | `companies.company_code` | Generado automáticamente |
| `name` | `company_requests.company_name` | **Dato Maestro** |
| `legal_name` | `company_requests.legal_name` | |
| `tax_id` | `company_requests.tax_id` | **UNIQUE** (Indexado) |
| `description` | `company_requests.company_description` | |
| `industry_id` | `company_requests.industry_id` | FK |
| `website` | `company_requests.website` | |
| `support_email` | `company_requests.admin_email` | Email de contacto público |
| `contact_address` | `company_requests.contact_address` | |
| `contact_city` | `company_requests.contact_city` | |
| `contact_country` | `company_requests.contact_country` | |
| `contact_postal_code`| `company_requests.contact_postal_code`| |
| `admin_user_id` | `companies.admin_user_id` | FK al usuario dueño |
| `settings` | `companies.settings` | JSONB (incl. areas_enabled) |
| `business_hours` | `companies.business_hours` | JSONB |
| `branding_*` | `companies.logo_url`, etc. | (url, colors, favicon) |

---

### Tabla B: `business.company_onboarding_details` (Datos de Proceso)
*Contiene la metadata histórica de la solicitud original. Relación 1:1 con Companies.*

| Campo Nuevo | Fuente Original (Mapeo) | Notas |
| :--- | :--- | :--- |
| `company_id` | `company_requests.created_company_id` | **PK, FK** (Relación 1 a 1) |
| `request_code` | `company_requests.request_code` | Código único de trámite "REQ-..." |
| `request_message` | `company_requests.request_message` | "Por qué quiero unirme..." |
| `estimated_users` | `company_requests.estimated_users` | Dato estadístico inicial |
| `submitter_email` | `company_requests.admin_email` | Email original de quien solicitó |
| `rejection_reason` | `company_requests.rejection_reason` | Solo si fue rechazada |
| `reviewed_by` | `company_requests.reviewed_by` | FK Auditor/Admin |
| `reviewed_at` | `company_requests.reviewed_at` | Fecha de decisión |

---

## 🗑️ 2. Datos Eliminados (Duplicados)
*Estas columnas DEJAN DE EXISTIR en la estructura de solicitud/detalles, ahorrando espacio y evitando inconsistencias.*

1.  ❌ `company_name` (Vive en `companies.name`)
2.  ❌ `legal_name` (Vive en `companies.legal_name`)
3.  ❌ `company_description` (Vive en `companies.description`)
4.  ❌ `website` (Vive en `companies.website`)
5.  ❌ `industry_id` (Vive en `companies.industry_id`)
6.  ❌ `contact_address` (Vive en `companies.contact_*`)
7.  ❌ `contact_city`
8.  ❌ `contact_country`
9.  ❌ `contact_postal_code`
10. ❌ `tax_id` (Vive en `companies.tax_id` con constraint UNIQUE)

---

## 🔄 3. Estrategia de Migración de Datos

### Paso 1: Empresas YA Activas
*Empresas que ya fueron aprobadas.*
- **Company:** Se mantiene igual.
- **Details:** Se crea registro en `company_onboarding_details` sacando la info del `company_requests` antiguo (usando `created_from_request_id`).

### Paso 2: Solicitudes Pendientes
*Solicitudes que aún no son empresas.*
- **Company:** Se crea un registro nuevo en `companies` con status = `PENDING`.
- **Details:** Se crea registro en `company_onboarding_details` con `request_message`, etc.
- **Usuario Admin:** Aún no existe en `companies` (campo `admin_user_id` nullable temporalmente o placeholder).

### Paso 3: Solicitudes Rechazadas
*Solicitudes viejas denegadas.*
- **Company:** Se crea registro en `companies` con status = `REJECTED`.
- **Details:** Se guardan los motivos de rechazo.

---

## ✅ Resultado Final
- **Integridad:** 100% (Single Source of Truth)
- **Normalización:** 3NF (Tercera Forma Normal)
- **Defendibilidad:** Alta (Partición Vertical justificada por dominios de datos diferentes: Operativo vs Onboarding).
