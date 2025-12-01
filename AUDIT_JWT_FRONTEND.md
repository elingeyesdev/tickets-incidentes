# Auditoría del Sistema Frontend JWT y Autenticación Laravel

## 1. Resumen Ejecutivo

El sistema utiliza una arquitectura **Híbrida Stateless/Stateful** robusta y profesional.
- **API (Stateless):** Utiliza `Authorization: Bearer` con tokens JWT.
- **Frontend Web (Stateful/Session-like):** Utiliza Cookies (`jwt_token` y `refresh_token`) para persistir la sesión entre recargas de página, permitiendo que Laravel (Blade) renderice las vistas protegidas.

Esta arquitectura es correcta y segura, ya que protege el `refresh_token` en una cookie `HttpOnly` (inaccesible para JavaScript), mitigando riesgos de XSS.

Sin embargo, se han identificado **3 áreas de mejora crítica** relacionadas con la experiencia de usuario (UX) y el manejo de redirecciones, que es exactamente lo que has solicitado solucionar.

---

## 2. Hallazgos y Diagnóstico

### A. Pantalla de Bienvenida (`/`) no detecta sesión activa
**Estado Actual:**
La ruta `/` en `routes/web.php` devuelve incondicionalmente la vista `public.welcome`.
```php
Route::get('/', function () {
    return view('public.welcome');
})->name('welcome');
```
**Problema:**
Si un usuario ya está logueado (tiene cookies válidas), al entrar a `localhost/` ve la pantalla de bienvenida en lugar de su dashboard. Esto rompe la fluidez de la experiencia.

### B. Manejo de Roles Incorrecto (Error 403 vs Redirección)
**Estado Actual:**
El middleware `EnsureUserHasRole.php` devuelve una respuesta JSON 403 cuando el usuario no tiene el rol requerido.
```php
return response()->json([
    'success' => false,
    'message' => 'Insufficient permissions',
    // ...
], 403);
```
**Problema:**
Si un usuario con rol `USER` intenta acceder a una ruta de `AGENT` (por ejemplo, escribiendo la URL manualmente o por un link incorrecto), recibe un JSON de error o una página de error genérica.
**Comportamiento Esperado:**
El sistema debería detectar que es una petición Web (no API) y redirigir al usuario a **su propio dashboard** o a una página segura, en lugar de mostrar un error de permisos.

### C. Sincronización Frontend-Backend
**Estado Actual:**
El login (`login.blade.php`) guarda el token en `localStorage` y luego redirige a `/auth/prepare-web` para establecer las cookies.
**Observación:**
Este flujo es correcto. Sin embargo, si la sesión expira en el backend (cookies borradas), el frontend podría seguir teniendo el token en `localStorage`. El sistema ya maneja esto parcialmente con el parámetro `reason=session_expired` en el login, lo cual es una buena práctica.

---

## 3. Plan de Implementación y Mejoras

A continuación, se detallan los cambios exactos necesarios para solucionar los problemas detectados.

### Paso 1: Redirección Inteligente en Home (`/`)

Debemos modificar la ruta `/` para verificar si existe la cookie de autenticación.

**Archivo:** `routes/web.php`

```php
// Antes
Route::get('/', function () {
    return view('public.welcome');
})->name('welcome');

// Después (Propuesta)
Route::get('/', function () {
    // Verificar si existe cookie de sesión
    if (request()->hasCookie('jwt_token')) {
        return redirect()->route('dashboard');
    }
    return view('public.welcome');
})->name('welcome');
```

### Paso 2: Middleware de Roles con Redirección Automática

Modificar el middleware para que distinga entre peticiones API (JSON) y peticiones Web. Si es Web y no tiene permisos, redirigir al dashboard general (que ya sabe a dónde enviar al usuario según su rol real).

**Archivo:** `app/Http/Middleware/EnsureUserHasRole.php`

```php
// En el método handle, reemplazar el retorno final 403 por:

// Si es una petición API (espera JSON), devolver error 403
if ($request->expectsJson()) {
    return response()->json([
        'success' => false,
        'message' => 'Insufficient permissions',
        'code' => 'INSUFFICIENT_PERMISSIONS'
    ], 403);
}

// Si es petición Web, redirigir al dashboard del usuario
// Usamos 'with' para mostrar un mensaje amigable (Toast)
return redirect()->route('dashboard')
    ->with('warning', 'No tienes permisos para acceder a esa sección. Has sido redirigido a tu panel.');
```

### Paso 3: Middleware de Autenticación (Refinamiento)

El middleware `RequireJWTAuthentication.php` ya maneja bien la redirección al login si el token es inválido. No requiere cambios mayores, pero confirmamos que su lógica de "Server-Side Auto-Refresh" es excelente para mantener la sesión viva sin intervención del usuario.

---

## 4. Conclusión

Tu sistema tiene una base **muy sólida y profesional**. Los problemas que experimentas son de "capa de presentación" y manejo de flujo, no de seguridad ni de arquitectura.

Implementando los cambios propuestos en el **Paso 1** y **Paso 2**, lograrás:
1.  Que al entrar a `localhost/` te lleve directo al dashboard si ya estás logueado.
2.  Que si intentas entrar a una zona prohibida (ej. Agente siendo Usuario), el sistema te "rebotará" amigablemente a tu dashboard en lugar de mostrarte un error.

¿Deseas que proceda a aplicar estos cambios automáticamente?
