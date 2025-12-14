# 🔄 Análisis de Dependencias Circulares - Helpdesk

> **Fecha del Análisis:** 14 de Diciembre de 2025
> **Modelos Analizados:** 20+
> **Ciclos Detectados:** 4 principales

---

## 📊 Grafo de Dependencias Actual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          MAPA DE RELACIONES                                  │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌──────────┐
                    ┌────────►│   User   │◄────────┐
                    │         └────┬─────┘         │
                    │              │               │
              belongsTo      hasOne│hasMany    belongsTo
                    │              ▼               │
              ┌─────┴───┐    ┌──────────┐    ┌────┴─────┐
              │ Company │◄───│UserRole  │───►│  Role    │
              └────┬────┘    └──────────┘    └──────────┘
                   │              ▲
            hasMany│              │belongsTo
                   ▼              │
              ┌─────────┐   ┌─────┴─────┐
              │ Ticket  │   │UserProfile│
              └────┬────┘   └───────────┘
                   │
            hasMany│belongsTo
                   ▼
         ┌─────────────────┐
         │ TicketResponse  │──belongsTo──► User (CICLO!)
         └─────────────────┘
                   │
                   ▼
         ┌─────────────────┐
         │TicketAttachment │──belongsTo──► User (CICLO!)
         └─────────────────┘
```

---

## 🚨 CICLOS DETECTADOS

### Ciclo 1: User ↔ Company ↔ UserRole (CRÍTICO)
```
User ──hasMany──► UserRole ──belongsTo──► Company ──belongsTo──► User (admin)
                                               │
                                               └──hasMany──► UserRole ──belongsTo──► User
```

**Entidades involucradas:**
- `User.userRoles()` → hasMany UserRole
- `UserRole.company()` → belongsTo Company
- `Company.admin()` → belongsTo User
- `Company.userRoles()` → hasMany UserRole

**Riesgo:** ⚠️ **ALTO** - Al serializar un User con `->load('userRoles.company.admin')`, se puede crear un loop infinito.

---

### Ciclo 2: Company ↔ Ticket ↔ User (MODERADO)
```
Company ──hasMany──► Ticket ──belongsTo──► User (creator)
    ▲                                         │
    │                                         │
    └───────────belongsTo────────────────────┘
    (User sigue Companies = followers)
```

**Entidades involucradas:**
- `Company.tickets()` → hasMany Ticket
- `Ticket.creator()` → belongsTo User
- `User.followedCompanies()` → belongsToMany Company

**Riesgo:** ⚠️ **MODERADO** - Común en sistemas multi-tenant.

---

### Ciclo 3: Ticket ↔ TicketResponse ↔ User (MODERADO)
```
Ticket ──hasMany──► TicketResponse ──belongsTo──► User
   ▲                                               │
   │                                               │
   └──────────belongsTo (creator)──────────────────┘
```

**Entidades involucradas:**
- `Ticket.responses()` → hasMany TicketResponse
- `TicketResponse.author()` → belongsTo User
- `Ticket.creator()` → belongsTo User

**Riesgo:** ⚠️ **MODERADO** - Normal en sistemas de tickets.

---

### Ciclo 4: Company ↔ ServiceApiKey ↔ User (BAJO)
```
Company ──hasMany──► ServiceApiKey ──belongsTo──► User (creator)
    ▲                                                │
    │                                                │
    └─────────(User tiene roles en Company)──────────┘
```

**Entidades involucradas:**
- `Company` (implícito via ServiceApiKey)
- `ServiceApiKey.creator()` → belongsTo User
- `ServiceApiKey.company()` → belongsTo Company

**Riesgo:** ⚠️ **BAJO** - Solo si cargas toda la cadena de relaciones.

---

## 📋 TABLA COMPLETA DE RELACIONES

| Modelo | Relación | Tipo | Apunta a | ¿Puede crear ciclo? |
|--------|----------|------|----------|---------------------|
| **User** | profile | hasOne | UserProfile | No |
| **User** | userRoles | hasMany | UserRole | ⚠️ Sí (via Company) |
| **User** | followedCompanies | belongsToMany | Company | ⚠️ Sí |
| **UserProfile** | user | belongsTo | User | Inverso (OK) |
| **UserRole** | user | belongsTo | User | Inverso (OK) |
| **UserRole** | company | belongsTo | Company | ⚠️ Sí |
| **UserRole** | role | belongsTo | Role | No |
| **UserRole** | assignedBy | belongsTo | User | ⚠️ Sí |
| **Company** | admin | belongsTo | User | ⚠️ Sí |
| **Company** | industry | belongsTo | CompanyIndustry | No |
| **Company** | onboardingDetails | hasOne | CompanyOnboardingDetails | No |
| **Company** | userRoles | hasMany | UserRole | ⚠️ Sí |
| **Company** | followers | belongsToMany | User | ⚠️ Sí |
| **Company** | tickets | hasMany | Ticket | ⚠️ Sí |
| **CompanyOnboardingDetails** | company | belongsTo | Company | Inverso (OK) |
| **CompanyOnboardingDetails** | reviewer | belongsTo | User | Potencial |
| **CompanyInvitation** | company | belongsTo | Company | Potencial |
| **CompanyInvitation** | user | belongsTo | User | Potencial |
| **CompanyInvitation** | invitedBy | belongsTo | User | Potencial |
| **Ticket** | creator | belongsTo | User | ⚠️ Sí |
| **Ticket** | company | belongsTo | Company | ⚠️ Sí |
| **Ticket** | category | belongsTo | Category | No |
| **Ticket** | area | belongsTo | Area | No |
| **Ticket** | ownerAgent | belongsTo | User | ⚠️ Sí |
| **Ticket** | responses | hasMany | TicketResponse | ⚠️ Sí |
| **Ticket** | internalNotes | hasMany | TicketInternalNote | Potencial |
| **Ticket** | attachments | hasMany | TicketAttachment | Potencial |
| **Ticket** | rating | hasOne | TicketRating | No |
| **TicketResponse** | ticket | belongsTo | Ticket | Inverso (OK) |
| **TicketResponse** | author | belongsTo | User | ⚠️ Sí |
| **TicketResponse** | attachments | hasMany | TicketAttachment | Potencial |
| **TicketAttachment** | ticket | belongsTo | Ticket | Inverso (OK) |
| **TicketAttachment** | response | belongsTo | TicketResponse | Inverso (OK) |
| **TicketAttachment** | uploader | belongsTo | User | ⚠️ Sí |
| **TicketRating** | ticket | belongsTo | Ticket | Inverso (OK) |
| **TicketRating** | customer | belongsTo | User | ⚠️ Sí |
| **TicketRating** | ratedAgent | belongsTo | User | ⚠️ Sí |
| **Category** | company | belongsTo | Company | Potencial |
| **Category** | tickets | hasMany | Ticket | ⚠️ Sí |
| **Area** | company | belongsTo | Company | Potencial |
| **Area** | tickets | hasMany | Ticket | ⚠️ Sí |
| **HelpCenterArticle** | company | belongsTo | Company | Potencial |
| **HelpCenterArticle** | category | belongsTo | ArticleCategory | No |
| **HelpCenterArticle** | author | belongsTo | User | ⚠️ Sí |
| **ServiceApiKey** | company | belongsTo | Company | ⚠️ Sí |
| **ServiceApiKey** | creator | belongsTo | User | ⚠️ Sí |

---

## 🎯 PRIORIDAD DE CORRECCIÓN

### 🔴 ALTA PRIORIDAD (Afectan serialización y APIs)

1. **User ↔ UserRole ↔ Company**
   - Problema: Al cargar `User::with('userRoles.company.admin')`, el admin es un User que tiene userRoles... loop infinito.
   - Solución: Usar `$hidden` o API Resources con campos explícitos.

2. **Company ↔ UserRole ↔ User**
   - Problema: `Company::with('userRoles.user.userRoles')` crea loop.
   - Solución: Limitar profundidad de carga.

### 🟡 MEDIA PRIORIDAD (Afectan reportes)

3. **Ticket ↔ User ↔ Company**
   - Problema: Al generar reportesde tickets con usuarios y empresas.
   - Solución: Cargar solo campos necesarios con `->select()`.

### 🟢 BAJA PRIORIDAD (Rara vez se cargan juntos)

4. **ServiceApiKey ↔ Company/User**
   - Problema: Solo si haces eager loading profundo.
   - Solución: Ya está controlado por API Resources.

---

## 🔧 SOLUCIONES RECOMENDADAS

### Solución 1: Usar API Resources (YA LO TIENES)
```php
// En lugar de retornar el modelo directamente:
return $user; // ❌ Peligroso

// Usar Resource que controla qué campos se exponen:
return new UserResource($user); // ✅ Seguro
```

### Solución 2: Definir $hidden en modelos
```php
class User extends Model
{
    protected $hidden = [
        'password_hash',
        // Agregar relaciones que no deben serializarse automáticamente:
        'userRoles', // Evita que se serialice automáticamente
    ];
}
```

### Solución 3: Usar whenLoaded() en Resources
```php
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            // Solo incluir si fue cargado explícitamente:
            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}
```

### Solución 4: Limitar profundidad de eager loading
```php
// ❌ Peligroso - carga infinita potencial
$user = User::with('userRoles.company.userRoles.user')->first();

// ✅ Seguro - profundidad limitada
$user = User::with(['userRoles.company' => function ($q) {
    $q->select('id', 'name'); // Solo campos necesarios
}])->first();
```

### Solución 5: Usar Laravel Fractal o spatie/laravel-query-builder
Estas librerías permiten controlar exactamente qué relaciones incluir basándose en parámetros de la request.

---

## 📊 DIFICULTAD DE CORRECCIÓN

| Nivel | Descripción | Tiempo Estimado |
|-------|-------------|-----------------|
| 🟢 Fácil | Agregar `$hidden` a modelos | 1-2 horas |
| 🟡 Medio | Revisar y ajustar API Resources | 4-8 horas |
| 🟠 Moderado | Refactorizar eager loading en controladores | 1-2 días |
| 🔴 Difícil | Reestructurar relaciones de modelos | 1-2 semanas |

---

## ✅ RECOMENDACIÓN FINAL

**Tu sistema NO está roto.** Las dependencias circulares son **normales** en sistemas complejos multi-tenant. Lo importante es:

1. **Ya usas API Resources** - Esto evita la mayoría de problemas de serialización.
2. **Revisa tus controladores** - Asegúrate de no hacer `->with()` con profundidad > 2.
3. **Agrega `->select()` a subqueries** - Para limitar los datos cargados.

Si quieres, puedo ayudarte a auditar tus Controllers y Resources para asegurar que no hay ningún lugar donde se cargue toda la cadena.

---

*Análisis generado el 14 de Diciembre de 2025*
