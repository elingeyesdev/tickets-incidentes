# ⚠️ DEUDA TÉCNICA: Gestión del Contexto de Rol Activo

## Problema

Actualmente, el backend no tiene un mecanismo para determinar el "rol activo" con el que un usuario está interactuando en la interfaz de usuario. Si un usuario tiene múltiples roles (ej. `USER` y `COMPANY_ADMIN`), el middleware de autorización (`JWTRoleMiddleware`) solo verifica si el usuario *posee* el rol requerido para una ruta, no si está *actuando* con ese rol específico en la sesión actual.

Esto presenta una **vulnerabilidad de seguridad** y una **inconsistencia lógica**:

-   **Escalada de Privilegios:** Un usuario podría seleccionar un rol de menor privilegio en la UI, pero manipular las peticiones HTTP para acceder a endpoints protegidos por un rol de mayor privilegio que posee, pero que no está "activo" en la UI.
-   **Incoherencia UX/Backend:** La lógica de autorización del backend no refleja la intención del usuario expresada en el frontend.

## Solución Propuesta (Implementación Futura)

La solución profesional y con buenas prácticas para un sistema JWT stateless y multi-rol implica la siguiente implementación:

### 1. Backend: Nuevo Middleware `ActiveRoleContextMiddleware`

-   **Propósito:** Leer el rol activo enviado por el frontend y establecerlo como el contexto de rol para la petición actual.
-   **Ubicación:** Se ejecutaría después de `JWTAuthenticationMiddleware` y antes de `JWTRoleMiddleware`.
-   **Funcionamiento:**
    1.  **Leer Header:** Esperaría un header HTTP personalizado del frontend, por ejemplo: `X-Active-Role-Context: ROLE_CODE[@COMPANY_UUID]` (ej. `COMPANY_ADMIN@uuid-de-empresa`).
    2.  **Validar Posesión:** Verificaría que el usuario autenticado (obtenido del JWT) realmente posee el `ROLE_CODE` especificado en el header y, si se incluye, que lo posee para el `COMPANY_UUID` indicado.
    3.  **Establecer Contexto:** Si la validación es exitosa, crearía un objeto `ActiveRoleContext` (un DTO) y lo guardaría en los atributos de la petición (ej. `$request->attributes->set('active_role_context', $activeRoleContext);`).
    4.  **Manejo de Errores:** Si el header no es válido, el rol no es poseído por el usuario, o el formato es incorrecto, lanzaría una `AuthorizationException`.

### 2. Backend: Modificación a `JWTRoleMiddleware`

-   **Propósito:** Simplificar y asegurar la autorización basada en el rol activo.
-   **Funcionamiento:** En lugar de iterar sobre *todos* los roles del usuario, este middleware leería el `ActiveRoleContext` ya validado de los atributos de la petición. Luego, simplemente compararía el `roleCode` de este contexto activo con los roles requeridos por la ruta.

### 3. Backend: Inyección de Dependencias (Service Container)

-   **Propósito:** Hacer que el `ActiveRoleContext` sea fácilmente accesible en cualquier parte del backend (controladores, servicios, etc.) sin tener que pasarlo manualmente.
-   **Funcionamiento:** Se registraría un *scoped binding* en un `ServiceProvider` (ej. `AppServiceProvider`) para el `ActiveRoleContext` DTO. Esto permitiría inyectar `ActiveRoleContext $activeRole` en constructores o métodos, y Laravel proporcionaría automáticamente el contexto validado para la petición actual.

### 4. Frontend: Integración con `AuthContext` y `Apollo Client`

-   **Propósito:** Abstraer la complejidad del envío del header para los desarrolladores de frontend.
-   **Funcionamiento:**
    1.  Cuando el usuario selecciona un rol en el selector, el `AuthContext` (a través de la `AuthMachine` y `PersistenceService`) guardaría el `roleCode` y `companyId` del rol activo.
    2.  El `authLink` de Apollo Client sería modificado para leer este rol activo del `AuthContext` y añadir automáticamente el header `X-Active-Role-Context` a **todas las peticiones salientes**.

## Decisión Actual

Debido a la complejidad y la necesidad de priorizar la refactorización del sistema de autenticación principal, la implementación de la gestión del contexto de rol activo se **pospone** para una fase futura. Se reconoce que esto introduce una brecha de seguridad temporal y una deuda técnica que deberá ser abordada.

## Impacto en Aplicaciones Móviles

Cualquier cliente (web, móvil, desktop) que interactúe con la API y necesite operar con roles específicos deberá implementar la lógica de enviar el header `X-Active-Role-Context` en cada petición. La documentación y ejemplos claros serán cruciales para facilitar el desarrollo en estas plataformas.

---

**Estado:** ⏳ Pospuesto (Deuda Técnica Crítica)
**Fecha de Documentación:** 2025-10-21
