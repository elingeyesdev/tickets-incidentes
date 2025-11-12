# REPORTE: Vistas Implementables con Endpoints Existentes

**Fecha**: 2025-11-12
**Sistema**: HELPDESK - AdminLTE v3
**Rol Analizado**: PLATFORM_ADMIN (Administrador de Plataforma)

---

## 1. RESUMEN EJECUTIVO

### Resultados del Análisis
- **Total pantallas documentadas**: 4 pantallas principales
- **Pantallas 100% implementables**: 2 (Gestión de Solicitudes, Gestión de Usuarios)
- **Pantallas parcialmente implementables**: 2 (Dashboard 60%, Gestión de Empresas 60%)
- **Cobertura general**: **80% implementable** con endpoints existentes

### Endpoints Disponibles para PLATFORM_ADMIN
Se identificaron **9 endpoints principales** que soportan funcionalidades PLATFORM_ADMIN:

| Módulo | Endpoint | Método |
|--------|----------|--------|
| **Companies** | `/api/companies` | GET |
| **Companies** | `/api/companies` | POST |
| **Companies** | `/api/companies/{id}` | GET |
| **Companies** | `/api/companies/{id}` | PATCH |
| **Company Requests** | `/api/company-requests` | GET |
| **Company Requests** | `/api/company-requests/{id}/approve` | POST |
| **Company Requests** | `/api/company-requests/{id}/reject` | POST |
| **Users** | `/api/users` | GET |
| **Users** | `/api/users/{id}` | GET |
| **Users** | `/api/users/{id}` | DELETE |
| **Users** | `/api/users/{id}/status` | PUT |
| **Roles** | `/api/roles` | GET |
| **Roles** | `/api/users/{userId}/roles` | POST |
| **Roles** | `/api/users/roles/{roleId}` | DELETE |

### Funcionalidades Faltantes (Endpoints Requeridos)
**5 endpoints faltantes** impiden implementar el 20% restante:
1. Endpoint de estadísticas del dashboard
2. Endpoint de suspender/activar empresas
3. Endpoint de eliminar empresas
4. Endpoint de estadísticas detalladas de empresas
5. Endpoint global de tickets (listar todos)

---

## 2. ANÁLISIS POR PANTALLA DOCUMENTADA

### Pantalla 1: Dashboard (/admin/dashboard)

**Estado**: **PARCIALMENTE IMPLEMENTABLE (60%)**

#### Endpoints Necesarios vs Endpoints Existentes

| Funcionalidad | Endpoint Requerido | Existe | Estado |
|---------------|-------------------|--------|--------|
| **KPIs Globales** | | | |
| - Total Empresas | `GET /api/companies?count_only=true` | SI (parcial) | IMPLEMENTABLE |
| - Usuarios Activos | `GET /api/users?status=active&count_only=true` | SI (parcial) | IMPLEMENTABLE |
| - Solicitudes Pendientes | `GET /api/company-requests?status=pending` | SI | IMPLEMENTABLE |
| - Tickets Totales | `GET /api/tickets?count_only=true` | **NO** | **NO IMPLEMENTABLE** |
| **Gráficos y Tendencias** | | | |
| - Empresas por estado (donut) | `GET /api/companies` (calcular client-side) | SI | IMPLEMENTABLE |
| - Tendencia registros (12 meses) | `GET /api/admin/dashboard-stats` o similar | **NO** | **NO IMPLEMENTABLE** |
| - Tabla actividad sistema | `GET /api/admin/activity-log` | **NO** | **NO IMPLEMENTABLE** |
| **Acciones Rápidas** | | | |
| - Últimas 5 solicitudes pendientes | `GET /api/company-requests?status=pending&limit=5` | SI | IMPLEMENTABLE |
| - Botones [Aprobar]/[Rechazar] | `POST /api/company-requests/{id}/approve` | SI | IMPLEMENTABLE |

#### Funcionalidades QUE SÍ se pueden implementar
1. **4 KPI Cards**:
   - Total Empresas: Usar `GET /api/companies` y contar resultados
   - Usuarios Activos: Usar `GET /api/users?status=active` y contar
   - Solicitudes Pendientes: Usar `GET /api/company-requests?status=pending` y contar
   - ~~Tickets Totales~~ (NO disponible)

2. **Gráfico de Empresas por Estado (Donut)**:
   - Obtener `GET /api/companies`, agrupar por `status` en frontend y renderizar con Chart.js

3. **Lista de Últimas 5 Solicitudes**:
   - `GET /api/company-requests?status=pending&per_page=5&order=created_at&order_direction=desc`
   - Incluye botones [Ver Detalles], [Aprobar], [Rechazar] funcionales

4. **System Health Status**:
   - Implementar verificación client-side básica (API disponible, etc.)

#### Funcionalidades QUE NO se pueden implementar
1. **KPI de Tickets Totales**: No existe endpoint `/api/tickets` global para PLATFORM_ADMIN
2. **Gráfico de Tendencia (12 meses)**: No existe endpoint que retorne datos históricos agregados
3. **Tabla de Actividad del Sistema**: No existe endpoint `/api/admin/activity-log`

#### Nivel de Implementación
**60% IMPLEMENTABLE**
- 3 de 4 KPIs funcionan
- 1 de 2 gráficos funciona
- Acciones rápidas 100% funcionales

---

### Pantalla 2: Gestión de Empresas (/admin/companies)

**Estado**: **PARCIALMENTE IMPLEMENTABLE (60%)**

#### Endpoints Necesarios vs Endpoints Existentes

| Funcionalidad | Endpoint Requerido | Existe | Estado |
|---------------|-------------------|--------|--------|
| **Listar empresas** | `GET /api/companies` | SI | IMPLEMENTABLE |
| **Ver detalles** | `GET /api/companies/{id}` | SI | IMPLEMENTABLE |
| **Crear empresa** | `POST /api/companies` | SI | IMPLEMENTABLE |
| **Editar empresa** | `PATCH /api/companies/{id}` | SI | IMPLEMENTABLE |
| **Suspender empresa** | `POST /api/companies/{id}/suspend` | **NO** | **NO IMPLEMENTABLE** |
| **Activar empresa** | `POST /api/companies/{id}/activate` | **NO** | **NO IMPLEMENTABLE** |
| **Eliminar empresa** | `DELETE /api/companies/{id}` | **NO** | **NO IMPLEMENTABLE** |
| **Estadísticas empresa** | `GET /api/companies/{id}/stats` | **NO** | **NO IMPLEMENTABLE** |
| **Filtros avanzados** | `GET /api/companies?filters` | SI (parcial) | IMPLEMENTABLE |

#### Funcionalidades QUE SÍ se pueden implementar

1. **Vista Principal con DataTables**:
   ```
   GET /api/companies?search={term}&status={status}&industry_id={uuid}&sort_by={field}&sort_direction={asc|desc}&page={n}&per_page={n}
   ```
   - Filtros disponibles: búsqueda, estado, industria, ordenamiento, paginación
   - Tabla con columnas: código, nombre, logo, admin (email/nombre), industria, # usuarios, # tickets, estado, fecha registro
   - **NOTA**: Los campos calculados `active_agents_count` y `total_users_count` ya vienen del endpoint

2. **Modal de Detalles - Pestaña 1 (Información General)**:
   ```
   GET /api/companies/{id}
   ```
   - Retorna todos los campos: nombre, legal_name, support_email, phone, website, logo_url, dirección completa, tax_id, legal_representative, business_hours, timezone, settings
   - Incluye objeto `admin` con información del administrador
   - Incluye objeto `industry` con industria completa

3. **Modal de Creación (+ Nueva Empresa)**:
   ```
   POST /api/companies
   Body: {
     name, legal_name, support_email, phone, website, admin_user_id,
     contact_address, contact_city, contact_state, contact_country,
     contact_postal_code, tax_id, legal_representative, business_hours,
     timezone, settings
   }
   ```
   - Requiere `admin_user_id` (UUID del usuario que será COMPANY_ADMIN)
   - Asigna automáticamente rol COMPANY_ADMIN al usuario designado

4. **Modal de Edición**:
   ```
   PATCH /api/companies/{id}
   Body: { cualquier campo a actualizar (todos opcionales) }
   ```
   - Actualización parcial de campos

#### Funcionalidades QUE NO se pueden implementar

1. **Botón [Suspender/Activar]**:
   - No existe `POST /api/companies/{id}/suspend`
   - No existe `POST /api/companies/{id}/activate`
   - **Workaround parcial**: El endpoint `PATCH /api/companies/{id}` podría aceptar campo `status`, pero esto no está documentado explícitamente

2. **Botón [Eliminar]**:
   - No existe `DELETE /api/companies/{id}`
   - No se puede implementar eliminación lógica ni física

3. **Modal de Detalles - Pestaña 2 (Estadísticas)**:
   - No existe `GET /api/companies/{id}/stats` con métricas detalladas
   - No hay endpoint para obtener:
     - Gráficos de tickets por categoría
     - Tendencia de tickets
     - Métricas de agentes
     - Tiempo promedio de resolución
   - **Workaround limitado**: Los datos básicos (`total_users_count`, `active_agents_count`, `followers_count`) vienen en `GET /api/companies/{id}`

4. **Modal de Detalles - Pestaña 3 (Configuración)**:
   - Campo `settings` viene en formato JSONB, pero no hay documentación específica de su estructura
   - No hay endpoints dedicados para gestionar categorías, macros, artículos de la empresa

#### Nivel de Implementación
**60% IMPLEMENTABLE**
- CRUD básico: 100% funcional (Listar, Ver, Crear, Editar)
- Acciones de estado: 0% funcional (Suspender, Activar, Eliminar)
- Modal detalles: Pestaña 1 (100%), Pestaña 2 (20%), Pestaña 3 (30%)

---

### Pantalla 3: Gestión de Solicitudes (/admin/requests)

**Estado**: **100% IMPLEMENTABLE**

#### Endpoints Necesarios vs Endpoints Existentes

| Funcionalidad | Endpoint Requerido | Existe | Estado |
|---------------|-------------------|--------|--------|
| **Listar solicitudes** | `GET /api/company-requests` | SI | IMPLEMENTABLE |
| **Filtrar por estado** | `GET /api/company-requests?status={status}` | SI | IMPLEMENTABLE |
| **Buscar por nombre** | `GET /api/company-requests?search={term}` | SI | IMPLEMENTABLE |
| **Ver detalles completos** | `GET /api/company-requests` (incluye todo) | SI | IMPLEMENTABLE |
| **Aprobar solicitud** | `POST /api/company-requests/{id}/approve` | SI | IMPLEMENTABLE |
| **Rechazar solicitud** | `POST /api/company-requests/{id}/reject` | SI | IMPLEMENTABLE |

#### Funcionalidades QUE SÍ se pueden implementar (TODAS)

1. **Vista Principal - Layout Card**:
   ```
   GET /api/company-requests?status={pending|approved|rejected}&search={term}&sort={field}&order={asc|desc}&per_page={n}&page={n}
   ```
   - Filtros: [Todas], [Pendientes], [Aprobadas], [Rechazadas]
   - Búsqueda por nombre de empresa
   - Ordenamiento y paginación

2. **Información por Card**:
   - `requestCode` (REQ-20251101-001)
   - `companyName`
   - `adminEmail`
   - `industryId` + objeto `industry` completo (code, name)
   - `estimatedUsers`
   - `status` (badge con colores)
   - `website`
   - `businessDescription`
   - `createdAt`, `updatedAt`

3. **Modal de Detalles (2 columnas)**:
   - **Información Empresa**:
     - Nombre comercial: `companyName`
     - Razón social: `legalName`
     - Industria: `industry.name`
     - Sitio web: `website`
     - Tax ID / RUT: `taxId`
   - **Información Contacto**:
     - Email administrador: `adminEmail`
     - Dirección: `contactAddress`
     - Ciudad: `contactCity`
     - País: `contactCountry`
     - Código postal: `contactPostalCode`
   - **Detalles Solicitud**:
     - Usuarios estimados: `estimatedUsers`
     - Descripción del negocio: `businessDescription`
     - Mensaje de solicitud: `requestMessage`
   - **Estado y Revisión**:
     - Estado actual: `status`
     - Fecha revisión: `reviewedAt`
     - Revisado por: objeto `reviewer` completo (id, user_code, email, name)
     - Motivo rechazo: `rejectionReason`
     - Empresa creada: objeto `createdCompany` (id, companyCode, name, logoUrl)

4. **Modal de Aprobación**:
   ```
   POST /api/company-requests/{id}/approve
   Body: { notes: "Notas adicionales (opcional)" }
   ```
   - **Proceso automático**:
     1. Crea empresa en `business.companies`
     2. Verifica si `admin_email` existe en `auth.users`
     3. Si existe: asigna rol `COMPANY_ADMIN`
     4. Si no existe: crea usuario + perfil + rol
     5. Genera password temporal (válido 7 días)
     6. Envía email con credenciales
     7. Actualiza solicitud: `status = 'APPROVED'`, `reviewed_at = now()`
   - **Respuesta incluye**:
     - Objeto `company` completo creado
     - Flag `newUserCreated` (boolean)
     - Email destino: `notificationSentTo`
     - Mensaje de éxito completo

5. **Modal de Rechazo**:
   ```
   POST /api/company-requests/{id}/reject
   Body: {
     reason: "Motivo del rechazo (obligatorio, mínimo 10 caracteres)",
     notes: "Notas adicionales (opcional)"
   }
   ```
   - **Validación**: campo `reason` es obligatorio (min 10 chars)
   - **Proceso automático**:
     1. Marca solicitud como `status = 'REJECTED'`
     2. Guarda `rejectionReason`
     3. Registra `reviewed_at = now()` y `reviewed_by`
     4. Envía email al solicitante con razón del rechazo
   - **Respuesta incluye**:
     - Mensaje de éxito
     - Razón del rechazo registrada
     - Email destino: `notificationSentTo`
     - Código de solicitud: `requestCode`

6. **Validaciones y Protecciones**:
   - Solo PLATFORM_ADMIN puede aprobar/rechazar (middleware `role:PLATFORM_ADMIN`)
   - No se puede aprobar/rechazar solicitudes ya procesadas (retorna 409 Conflict)
   - Todos los endpoints incluyen validación de estado

#### Funcionalidades QUE NO se pueden implementar
**NINGUNA** - Esta pantalla es 100% implementable con los endpoints actuales.

#### Nivel de Implementación
**100% IMPLEMENTABLE**
- Vista principal: 100%
- Filtros y búsqueda: 100%
- Modal de detalles: 100%
- Proceso de aprobación: 100% (automatizado backend)
- Proceso de rechazo: 100% (automatizado backend)

---

### Pantalla 4: Gestión de Usuarios (/admin/users)

**Estado**: **100% IMPLEMENTABLE**

#### Endpoints Necesarios vs Endpoints Existentes

| Funcionalidad | Endpoint Requerido | Existe | Estado |
|---------------|-------------------|--------|--------|
| **Listar usuarios** | `GET /api/users` | SI | IMPLEMENTABLE |
| **Ver perfil usuario** | `GET /api/users/{id}` | SI | IMPLEMENTABLE |
| **Filtros avanzados** | `GET /api/users?filters` | SI | IMPLEMENTABLE |
| **Suspender usuario** | `PUT /api/users/{id}/status` | SI | IMPLEMENTABLE |
| **Eliminar usuario** | `DELETE /api/users/{id}` | SI | IMPLEMENTABLE |
| **Listar roles** | `GET /api/roles` | SI | IMPLEMENTABLE |
| **Asignar rol** | `POST /api/users/{userId}/roles` | SI | IMPLEMENTABLE |
| **Remover rol** | `DELETE /api/users/roles/{roleId}` | SI | IMPLEMENTABLE |

#### Funcionalidades QUE SÍ se pueden implementar (TODAS)

1. **Vista Principal con DataTables**:
   ```
   GET /api/users?search={term}&status={status}&emailVerified={bool}&role={roleCode}&companyId={uuid}&recentActivity={bool}&createdAfter={datetime}&createdBefore={datetime}&order_by={field}&order_direction={asc|desc}&page={n}&per_page={n}
   ```
   - **Filtros Avanzados Disponibles**:
     - Búsqueda: por email, user_code, o nombre de perfil
     - Estado: activos/suspendidos/eliminados
     - Rol: USER/COMPANY_ADMIN/AGENT/PLATFORM_ADMIN
     - Empresa: filtro por `companyId` (UUID)
     - Verificación email: true/false
     - Actividad reciente: últimos 7 días
     - Rango de fechas: `createdAfter`, `createdBefore`
   - **Ordenamiento**: por cualquier campo, dirección asc/desc
   - **Paginación**: `per_page` (máx 50), `page`

2. **Tabla de Usuarios con Columnas**:
   - Usuario: `userCode`, `email`, `profile.avatar_url`
   - Nombre completo: `profile.first_name + profile.last_name`
   - Roles activos: `roleContexts` (array con roleCode, roleName, company)
   - Estado: `status` (ACTIVE/SUSPENDED/DELETED)
   - Verificación email: `emailVerified` (boolean)
   - Último acceso: `lastLoginAt`
   - Empresa principal: a través de `roleContexts[].company`
   - Fecha registro: `createdAt`
   - Estadísticas: `ticketsCount`, `resolvedTicketsCount`, `averageRating`

3. **Modal de Perfil Completo**:
   ```
   GET /api/users/{id}
   ```
   - **Pestaña 1: Información Personal** (UserResource con 15 campos):
     - `id`, `userCode`, `email`, `emailVerified`
     - `status`, `authProvider`
     - Objeto `profile` completo (ProfileResource con 12 campos):
       - `first_name`, `last_name`, `phone`, `date_of_birth`
       - `gender`, `bio`, `avatar_url`, `location`
       - `timezone`, `language`, `preferences`, `social_links`
     - `lastLoginAt`, `lastActivityAt`
     - `createdAt`, `updatedAt`

   - **Pestaña 2: Roles y Permisos**:
     - Array `roleContexts`:
       - `roleCode`, `roleName`, `dashboardPath`
       - `company` (id, companyCode, name, logoUrl)
     - Información completa de cada rol activo

   - **Pestaña 3: Actividad**:
     - `ticketsCount` (total de tickets)
     - `resolvedTicketsCount` (tickets resueltos)
     - `averageRating` (calificación promedio)
     - `lastLoginAt` (último login)
     - `lastActivityAt` (última actividad)

4. **Modal de Asignar Rol**:
   ```
   GET /api/roles (obtener lista de roles disponibles)
   POST /api/users/{userId}/roles
   Body: {
     roleCode: "AGENT|COMPANY_ADMIN|USER|PLATFORM_ADMIN",
     companyId: "uuid (requerido si roleCode es AGENT o COMPANY_ADMIN)"
   }
   ```
   - **Validaciones automáticas**:
     - `companyId` REQUERIDO si roleCode es AGENT o COMPANY_ADMIN
     - `companyId` DEBE ser null si roleCode es USER o PLATFORM_ADMIN
   - **Respuestas**:
     - 201: Rol asignado (nuevo)
     - 200: Rol reactivado (existía pero estaba inactivo)
   - **Throttling**: 100 requests/hora por usuario autenticado
   - **Retorna**: objeto con id, roleCode, roleName, company, isActive, assignedAt, assignedBy

5. **Modal de Suspender/Activar Usuario**:
   ```
   PUT /api/users/{id}/status
   Body: {
     status: "active|suspended",
     reason: "Motivo (requerido si status=suspended)"
   }
   ```
   - **Validación**: campo `reason` obligatorio cuando se suspende
   - Solo PLATFORM_ADMIN puede ejecutar esta acción
   - Retorna: userId, status, updatedAt

6. **Modal de Eliminar Usuario (Soft Delete)**:
   ```
   DELETE /api/users/{id}?reason={motivo_opcional}
   ```
   - **Protecciones**:
     - Solo PLATFORM_ADMIN puede eliminar usuarios
     - Usuario no puede eliminarse a sí mismo (retorna 422)
   - **Efecto**: establece `status = 'deleted'` y `deleted_at = now()`
   - Query param `reason` es opcional

7. **Remover Rol de Usuario**:
   ```
   DELETE /api/users/roles/{roleId}?reason={motivo_opcional}
   ```
   - **Nota**: `roleId` es el UUID de la asignación UserRole (no el Role)
   - Soft delete de la asignación
   - COMPANY_ADMIN solo puede remover roles de su empresa
   - PLATFORM_ADMIN puede remover cualquier rol

#### Funcionalidades QUE NO se pueden implementar
**NINGUNA** - Esta pantalla es 100% implementable con los endpoints actuales.

#### Observaciones Adicionales
- El endpoint `GET /api/users` ya aplica scoping automático:
  - **PLATFORM_ADMIN**: ve todos los usuarios del sistema
  - **COMPANY_ADMIN**: ve solo usuarios de su empresa
- Los filtros son extremadamente completos (9 tipos de filtros disponibles)
- La gestión de roles es robusta con validaciones de negocio

#### Nivel de Implementación
**100% IMPLEMENTABLE**
- Vista principal con filtros: 100%
- DataTables con todas las columnas: 100%
- Modal de perfil (3 pestañas): 100%
- Asignar rol: 100%
- Suspender/Activar: 100%
- Eliminar usuario: 100%
- Remover rol: 100%

---

## 3. MAPEO DETALLADO: FUNCIONALIDAD → ENDPOINT

### Dashboard Principal (/admin/dashboard)

| Funcionalidad | Implementable | Endpoint(s) | Notas |
|---------------|---------------|-------------|-------|
| **KPI: Total Empresas** | SÍ | `GET /api/companies` | Contar resultados o usar `meta.total` |
| **KPI: Usuarios Activos** | SÍ | `GET /api/users?status=active` | Contar con `meta.total` |
| **KPI: Solicitudes Pendientes** | SÍ | `GET /api/company-requests?status=pending` | Contar con `meta.total` |
| **KPI: Tickets Totales** | NO | ❌ No existe `/api/tickets` global | Endpoint faltante |
| **Gráfico: Empresas por Estado** | SÍ | `GET /api/companies` | Agrupar por `status` en frontend |
| **Gráfico: Tendencia 12 meses** | NO | ❌ No existe endpoint de métricas históricas | Endpoint faltante |
| **Tabla: Actividad Sistema** | NO | ❌ No existe `/api/admin/activity-log` | Endpoint faltante |
| **Lista: Últimas 5 Solicitudes** | SÍ | `GET /api/company-requests?status=pending&per_page=5&order_by=created_at&order_direction=desc` | Funcional |
| **Botón: Aprobar Solicitud** | SÍ | `POST /api/company-requests/{id}/approve` | Funcional |
| **Botón: Rechazar Solicitud** | SÍ | `POST /api/company-requests/{id}/reject` | Funcional |

---

### Gestión de Empresas (/admin/companies)

| Funcionalidad | Implementable | Endpoint(s) | Notas |
|---------------|---------------|-------------|-------|
| **Listar empresas en tabla** | SÍ | `GET /api/companies` | Filtros: search, status, industry_id, sort_by, sort_direction |
| **Ver detalles empresa** | SÍ | `GET /api/companies/{id}` | Incluye admin, industry, contadores |
| **Crear empresa (admin panel)** | SÍ | `POST /api/companies` | Requiere admin_user_id, asigna rol automáticamente |
| **Editar empresa** | SÍ | `PATCH /api/companies/{id}` | Actualización parcial de campos |
| **Suspender empresa** | NO | ❌ No existe `POST /api/companies/{id}/suspend` | Endpoint faltante |
| **Activar empresa** | NO | ❌ No existe `POST /api/companies/{id}/activate` | Endpoint faltante |
| **Eliminar empresa** | NO | ❌ No existe `DELETE /api/companies/{id}` | Endpoint faltante |
| **Ver estadísticas detalladas** | PARCIAL | `GET /api/companies/{id}` retorna contadores básicos | Falta endpoint `/api/companies/{id}/stats` para gráficos |
| **Filtrar por estado** | SÍ | `GET /api/companies?status={status}` | Funcional |
| **Filtrar por industria** | SÍ | `GET /api/companies?industry_id={uuid}` | Funcional |
| **Buscar por nombre** | SÍ | `GET /api/companies?search={term}` | Funcional |

---

### Gestión de Solicitudes (/admin/requests)

| Funcionalidad | Implementable | Endpoint(s) | Notas |
|---------------|---------------|-------------|-------|
| **Listar solicitudes** | SÍ | `GET /api/company-requests` | Filtros: status, search, sort, order |
| **Filtrar: Todas** | SÍ | `GET /api/company-requests` | Sin filtro de status |
| **Filtrar: Pendientes** | SÍ | `GET /api/company-requests?status=pending` | Funcional |
| **Filtrar: Aprobadas** | SÍ | `GET /api/company-requests?status=approved` | Funcional |
| **Filtrar: Rechazadas** | SÍ | `GET /api/company-requests?status=rejected` | Funcional |
| **Ver detalles completos** | SÍ | El endpoint list ya incluye todos los datos | Eager loading de reviewer, createdCompany |
| **Aprobar solicitud** | SÍ | `POST /api/company-requests/{id}/approve` | Proceso completo automatizado |
| **Rechazar solicitud** | SÍ | `POST /api/company-requests/{id}/reject` | Requiere campo `reason` obligatorio |
| **Ver empresa creada** | SÍ | Objeto `createdCompany` incluido en response | id, companyCode, name, logoUrl |
| **Ver revisor** | SÍ | Objeto `reviewer` incluido en response | id, user_code, email, name |

---

### Gestión de Usuarios (/admin/users)

| Funcionalidad | Implementable | Endpoint(s) | Notas |
|---------------|---------------|-------------|-------|
| **Listar usuarios** | SÍ | `GET /api/users` | 9 filtros disponibles |
| **Buscar por email/nombre/código** | SÍ | `GET /api/users?search={term}` | Busca en email, user_code, first_name, last_name |
| **Filtrar por estado** | SÍ | `GET /api/users?status={status}` | active/suspended/deleted |
| **Filtrar por rol** | SÍ | `GET /api/users?role={roleCode}` | USER/COMPANY_ADMIN/AGENT/PLATFORM_ADMIN |
| **Filtrar por empresa** | SÍ | `GET /api/users?companyId={uuid}` | UUID de empresa |
| **Filtrar por verificación email** | SÍ | `GET /api/users?emailVerified={bool}` | true/false |
| **Filtrar por actividad reciente** | SÍ | `GET /api/users?recentActivity=true` | Últimos 7 días |
| **Filtrar por rango fechas** | SÍ | `GET /api/users?createdAfter={dt}&createdBefore={dt}` | ISO8601 datetime |
| **Ver perfil completo** | SÍ | `GET /api/users/{id}` | 15 campos + profile (12 campos) + roleContexts |
| **Ver roles activos** | SÍ | Campo `roleContexts` en response | Array con todos los roles y companies |
| **Ver estadísticas usuario** | SÍ | Campos en response | ticketsCount, resolvedTicketsCount, averageRating |
| **Suspender usuario** | SÍ | `PUT /api/users/{id}/status` con `status=suspended` | Requiere campo `reason` |
| **Activar usuario** | SÍ | `PUT /api/users/{id}/status` con `status=active` | Funcional |
| **Eliminar usuario (soft)** | SÍ | `DELETE /api/users/{id}?reason={opt}` | Solo PLATFORM_ADMIN, no puede eliminarse a sí mismo |
| **Listar roles disponibles** | SÍ | `GET /api/roles` | Lista completa con requiresCompany, defaultDashboard |
| **Asignar rol a usuario** | SÍ | `POST /api/users/{userId}/roles` | Validación automática de companyId según roleCode |
| **Remover rol de usuario** | SÍ | `DELETE /api/users/roles/{roleId}` | roleId = UUID de UserRole assignment |
| **Ver sesiones activas** | SÍ | Mencionado en doc pero sin detalle | Endpoint existe según doc |

---

## 4. VISTAS PRIORIZADAS PARA IMPLEMENTAR

### Orden de Prioridad (Mayor a Menor)

#### 1. Gestión de Solicitudes (/admin/requests)
- **Completitud**: 100% implementable
- **Criticidad**: CRÍTICA - Es el flujo principal de onboarding de empresas
- **Dependencias**: Ninguna
- **Estimación**: 2-3 días
- **Razón**: Flujo de negocio crítico completamente funcional con backend robusto

#### 2. Gestión de Usuarios (/admin/users)
- **Completitud**: 100% implementable
- **Criticidad**: ALTA - Gestión completa del sistema
- **Dependencias**: Ninguna
- **Estimación**: 3-4 días (por complejidad de filtros)
- **Razón**: Segunda funcionalidad más importante, backend completamente implementado

#### 3. Dashboard (/admin/dashboard)
- **Completitud**: 60% implementable
- **Criticidad**: MEDIA-ALTA - Vista de entrada pero no crítica para operación
- **Dependencias**: Puede mejorarse cuando se agreguen endpoints faltantes
- **Estimación**: 1-2 días
- **Razón**: Buena primera impresión, suficiente con datos básicos

#### 4. Gestión de Empresas (/admin/companies)
- **Completitud**: 60% implementable
- **Criticidad**: MEDIA - CRUD básico funciona, acciones críticas faltan
- **Dependencias**: Requiere endpoints adicionales para funcionalidad completa
- **Estimación**: 2-3 días (funcionalidad actual), +1 día cuando se agreguen endpoints
- **Razón**: CRUD básico suficiente para MVP, acciones de estado pueden agregarse después

---

## 5. ENDPOINTS FALTANTES

### Endpoints Críticos (Bloquean Funcionalidades Importantes)

#### 1. Suspender/Activar Empresa
```
POST /api/companies/{id}/suspend
POST /api/companies/{id}/activate
```
**Impacto**: Impide gestión de estado de empresas desde panel admin
**Workaround**: Posible usar `PATCH /api/companies/{id}` con campo `status`, pero no está documentado
**Prioridad**: ALTA

#### 2. Eliminar Empresa
```
DELETE /api/companies/{id}
```
**Validación requerida**: Solo permitir si `tickets_count = 0`
**Impacto**: No se pueden eliminar empresas problemáticas o de prueba
**Prioridad**: MEDIA

---

### Endpoints Deseables (Mejoran Experiencia pero No Críticos)

#### 3. Estadísticas Detalladas de Empresa
```
GET /api/companies/{id}/stats
Response: {
  tickets: {
    total: int,
    open: int,
    in_progress: int,
    resolved: int,
    closed: int,
    by_category: [{category, count}],
    by_month: [{month, count}],
    avg_resolution_time: int (hours)
  },
  agents: {
    total: int,
    active: int,
    avg_rating: float,
    top_performers: [{agent, resolved_tickets, avg_rating}]
  },
  users: {
    total: int,
    active: int,
    followers: int,
    growth_rate: float
  }
}
```
**Impacto**: Modal de detalles de empresa solo muestra info básica
**Prioridad**: MEDIA

#### 4. Tickets Globales (Vista Admin)
```
GET /api/tickets
Query params: status, company_id, assigned_to, priority, created_after, created_before, sort_by, order, per_page, page
```
**Impacto**:
- Falta KPI de "Tickets Totales" en dashboard
- No hay vista global de tickets del sistema
**Prioridad**: MEDIA

#### 5. Dashboard Stats Consolidadas
```
GET /api/admin/dashboard-stats
Response: {
  kpis: {
    total_companies: int,
    total_users: int,
    total_tickets: int,
    pending_requests: int
  },
  growth: {
    companies_this_month: int,
    users_this_month: int,
    companies_trend_12m: [{month, count}],
    users_trend_12m: [{month, count}]
  },
  companies_by_status: [{status, count}],
  recent_activity: [{type, description, timestamp, user, company}]
}
```
**Impacto**: Dashboard requiere múltiples llamadas API y cálculos en frontend
**Prioridad**: BAJA (nice-to-have)

#### 6. Activity Log del Sistema
```
GET /api/admin/activity-log
Query params: type, user_id, company_id, action, created_after, created_before, per_page, page
Response: Paginación de eventos del sistema
```
**Impacto**: No hay tabla de actividad en dashboard
**Prioridad**: BAJA (puede agregarse en futuro)

---

## 6. RECOMENDACIONES DE IMPLEMENTACIÓN

### Fase 1: Implementar Vistas 100% Funcionales (PRIORIDAD MÁXIMA)
**Duración estimada**: 5-7 días

1. **Gestión de Solicitudes** (2-3 días)
   - Vista tipo Card con filtros
   - Modal de detalles (2 columnas)
   - Modal de aprobación (con checkbox enviar email)
   - Modal de rechazo (con campo motivo obligatorio)
   - Integración con toast notifications

2. **Gestión de Usuarios** (3-4 días)
   - DataTables con 9 filtros avanzados
   - Modal de perfil (3 pestañas)
   - Modal asignar rol (con validación dinámica de companyId)
   - Modal suspender/activar (con campo reason)
   - Modal eliminar (con confirmación)
   - Botones remover rol

---

### Fase 2: Implementar Vistas Parciales (PRIORIDAD ALTA)
**Duración estimada**: 3-4 días

3. **Dashboard Básico** (1-2 días)
   - 3 KPIs funcionales (empresas, usuarios, solicitudes)
   - Gráfico donut de empresas por estado
   - Lista de últimas 5 solicitudes con acciones
   - System Health básico (verificación client-side)
   - **Dejar comentario TODO**: "KPI Tickets y gráficos avanzados requieren endpoints adicionales"

4. **Gestión de Empresas** (2-3 días)
   - DataTables con filtros
   - Modal detalles (Pestaña 1: Info General completa)
   - Modal crear empresa
   - Modal editar empresa
   - **Deshabilitar botones**: [Suspender], [Activar], [Eliminar] con tooltip "Requiere endpoint adicional"
   - **Dejar comentario TODO**: "Pestañas Estadísticas y Configuración requieren endpoints adicionales"

---

### Fase 3: Mejoras Post-Endpoints (FUTURO)
**Cuando se implementen endpoints faltantes**

- Habilitar acciones de suspender/activar/eliminar empresas
- Agregar Pestaña 2 (Estadísticas) en modal de empresa
- Agregar KPI de Tickets en dashboard
- Agregar gráficos avanzados en dashboard
- Agregar tabla de actividad del sistema

---

## 7. CONCLUSIONES FINALES

### Viabilidad del Proyecto
El proyecto de implementación de vistas PLATFORM_ADMIN es **ALTAMENTE VIABLE** con el backend actual:

- **80% de funcionalidades son implementables** con endpoints existentes
- **2 de 4 pantallas son 100% implementables** (Solicitudes y Usuarios)
- **2 de 4 pantallas son 60% implementables** (Dashboard y Empresas)

### Fortalezas del Backend Actual
1. **Gestión de Solicitudes**: Flujo completo y robusto con automatización
2. **Gestión de Usuarios**: Endpoints extremadamente completos con 9 filtros
3. **Gestión de Roles**: Sistema sólido con validaciones de negocio
4. **CRUD de Empresas**: Base funcional para operaciones básicas

### Brechas Identificadas
1. **Gestión de Estado de Empresas**: Falta suspender/activar/eliminar
2. **Estadísticas Detalladas**: No hay endpoints de métricas agregadas
3. **Tickets Globales**: No hay visibilidad del módulo ticketing para admin
4. **Activity Log**: No hay auditoría de acciones del sistema

### Estrategia Recomendada
1. **Implementar primero** las 2 vistas 100% funcionales (Solicitudes + Usuarios)
2. **Implementar después** las 2 vistas parciales (Dashboard + Empresas) con funcionalidad disponible
3. **Documentar claramente** en código qué funcionalidades esperan endpoints futuros
4. **Diseñar UI con estados deshabilitados** para botones que requieren endpoints faltantes
5. **Priorizar con backend** los 2 endpoints críticos de gestión de empresas

### Tiempo Total Estimado
- **Fase 1 (100% funcional)**: 5-7 días
- **Fase 2 (parcial)**: 3-4 días
- **TOTAL MVP**: 8-11 días de desarrollo frontend

### ROI de Implementación
- **Inversión**: 8-11 días de desarrollo
- **Retorno**: 80% de funcionalidades operativas de inmediato
- **Funcionalidades críticas de negocio**: 100% implementadas (onboarding de empresas + gestión de usuarios)
- **MVP completamente funcional** sin bloqueos operacionales

---

**FIN DEL REPORTE**

Generado el: 2025-11-12
Ruta del archivo: `C:\Users\lukem\Helpdesk\REPORTE_vistas_implementables.md`
