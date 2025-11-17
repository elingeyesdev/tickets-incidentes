# Soluciones para Manejo de Múltiples Roles en Tickets

**Fecha:** 2025-11-17
**Problema:** Un usuario puede tener múltiples roles simultáneamente (ej: AGENT + USER)
**Contexto:** Cuando un usuario tiene 2+ roles, ¿qué tickets debería ver en la API?

---

## El Problema

Un usuario puede ser:
- **AGENT** en empresa X
- **USER** (crea tickets normales)

Cuando llama `GET /api/tickets`, ¿debería ver?
- ✅ Sus propios tickets (como USER)
- ✅ Todos los tickets de empresa X (como AGENT)
- O solo UNO de los dos?

---

## Solución 1: Separar Endpoints por Rol

### Implementación
```
GET /api/user/tickets          → Ve solo sus tickets creados
GET /api/agent/tickets         → Ve tickets de su empresa
GET /api/admin/tickets         → Ve tickets de su empresa (COMPANY_ADMIN)
```

### Ventajas
- ✅ Frontend sabe exactamente qué está pidiendo
- ✅ Lógica clara: cada rol tiene su endpoint
- ✅ Fácil de testear (separación clara)
- ✅ Permisos explícitos por endpoint

### Desventajas
- ❌ Muchos endpoints (+3 a +5)
- ❌ Duplicación de código base
- ❌ Si usuario es AGENT+USER, debe hacer 2 llamadas
- ❌ Frontend debe saber qué endpoints usar

### Ejemplo de Implementación
```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::get('/user/tickets', [TicketController::class, 'userTickets']);
    Route::get('/agent/tickets', [TicketController::class, 'agentTickets']);
    Route::get('/admin/tickets', [TicketController::class, 'adminTickets']);
});

// Cada método filtra por su rol específico
```

---

## Solución 2: Query Parameter para Filtrar por Rol

### Implementación
```
GET /api/tickets?role=user    → Ve solo sus tickets
GET /api/tickets?role=agent   → Ve tickets de su empresa
GET /api/tickets              → (default) Ve todo según roles
```

### Ventajas
- ✅ Un endpoint que se adapta
- ✅ Frontend puede elegir qué rol usar sin cambiar URL
- ✅ Compatible con versiones anteriores (sin ?role = todos)
- ✅ Flexible para futuras expansiones

### Desventajas
- ⚠️ Requiere validación: usuario debe tener ese rol
- ⚠️ Error si pide role=admin pero es solo USER
- ❌ Menos explícito que endpoints separados

### Ejemplo de Implementación
```php
// TicketService.php
public function list(array $filters, User $user): LengthAwarePaginator
{
    $requestedRole = $filters['role'] ?? null; // ?role=agent

    if ($requestedRole) {
        // Validar que el usuario TIENE ese rol
        if (!JWTHelper::hasRoleFromJWT($requestedRole)) {
            throw new AuthorizationException("No tienes rol {$requestedRole}");
        }
        $userRole = $requestedRole;
    } else {
        // Sin ?role, muestra todo según sus roles
        $userRole = $this->getUserRole($user);
    }

    // Aplicar filtros según $userRole
    $this->applyVisibilityFilters($query, $user->id, $userRole);
}
```

---

## Solución 3: Rol Seleccionado en el JWT

### Implementación
JWT contiene un campo `selected_role`:
```json
{
  "user_id": "abc123",
  "roles": ["USER", "AGENT"],
  "selected_role": "AGENT",  // ← El rol ACTIVO
  "company_id": "company-xyz"
}
```

### Ventajas
- ✅ Frontend elige QUÉ rol usar al login
- ✅ Backend siempre sabe cuál es el rol activo
- ✅ Un endpoint, una lógica clara
- ✅ Usuario puede cambiar de rol sin logout (refresh token)
- ✅ Más seguro: selected_role se valida en JWT

### Desventajas
- ❌ Refactor en login (generar JWT con selected_role)
- ❌ Refactor en todo el código que usa `getUserRole()`
- ❌ Si usuario quiere cambiar rol, debe re-autenticar
- ⚠️ JWT más grande (pequeño impacto)

### Ejemplo de Implementación
```php
// AuthController.php (login)
public function login(Request $request)
{
    $user = User::where('email', $request->email)->firstOrFail();

    // Obtener rol principal (default: el primero)
    $selectedRole = $request->input('role') ?? $user->roles()->first()->name;

    // Validar que el usuario tiene ese rol
    if (!$user->hasRole($selectedRole)) {
        throw new UnauthorizedHttpException('Invalid role');
    }

    $payload = [
        'user_id' => $user->id,
        'roles' => $user->getAllRolesForJWT(),
        'selected_role' => $selectedRole,  // ← Nueva línea
        'company_id' => $user->getCompanyId($selectedRole),
    ];

    return ['token' => JWT::encode($payload, ...)];
}

// TicketService.php (simplificado)
public function list(array $filters, User $user): LengthAwarePaginator
{
    $userRole = JWTHelper::getSelectedRoleFromJWT();  // ← Desde JWT
    $this->applyVisibilityFilters($query, $user->id, $userRole);
}

// Cambiar rol (opcional endpoint)
POST /api/auth/switch-role
{
    "role": "AGENT"
}
Response: { "token": "nuevo_jwt_con_selected_role_AGENT" }
```

---

## Comparativa Rápida

| Aspecto | Solución 1 | Solución 2 | Solución 3 |
|--------|-----------|-----------|-----------|
| **Endpoints** | Múltiples | 1 | 1 |
| **Complejidad** | Media | Media-Alta | Alta |
| **Frontend** | Conoce qué endpoint | Controla con ?role | Controla en login |
| **Validación** | En la ruta | En TicketService | En JWTHelper |
| **Performance** | Similar | Similar | Mejor (1 JWT check) |
| **Escalabilidad** | Difícil (N endpoints) | Buena | Buena |
| **Recomendado para** | APIs simples | APIs escalables | Aplicaciones complejas |

---

## Recomendación

**SOLUCIÓN 3** es la más robusta para tu caso porque:
1. Un endpoint, sin duplicación
2. User puede tener AGENT+USER sin conflictos
3. Frontend controla explícitamente qué rol usa
4. Backend siempre sabe cuál es el rol activo
5. Escalable a más roles en el futuro

**Costo:** Refactor medio en:
- `AuthController::login()`
- `TicketService::list()` (simplificación)
- JWT generation (agregar `selected_role`)

---

## Próximos Pasos

1. Decidir cuál solución implementar
2. Revisar dónde se genera el JWT (AuthController, LoginAction, etc)
3. Actualizar tests para nueva lógica
4. Actualizar frontend para usar `selected_role` si corresponde

