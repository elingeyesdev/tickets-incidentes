# 🔴 FASE 3: 65 TESTS TDD RED - VERSIÓN FINAL APROBADA

> **Status**: ✅ APROBADO PARA IMPLEMENTACIÓN
> **Cambios**: Agregados 3 tests de filtros adicionales (pending, resolved, closed)
> **Total Tests**: 65 (antes eran 62)
> **Fecha**: 2025-11-10

---

# **CREATETICKETTEST.PHP (15 TESTS)**

1. Un usuario con rol USER autenticado puede crear un ticket exitosamente enviando datos válidos.
2. Un usuario con rol AGENT recibe error 403 cuando intenta crear un ticket.
3. Un usuario con rol COMPANY_ADMIN recibe error 403 cuando intenta crear un ticket.
4. Un usuario sin autenticación (sin token) recibe error 401 cuando intenta crear un ticket.
5. Cuando se omite algún campo requerido (title, initial_description, company_id o category_id), el sistema devuelve error 422 de validación.
6. El título debe tener entre 5 y 255 caracteres; si tiene menos de 5 o más de 255 caracteres, se devuelve error 422.
7. La descripción inicial debe tener entre 10 y 5000 caracteres; si tiene menos de 10 o más de 5000 caracteres, se devuelve error 422.
8. Si se envía un company_id que no existe en la base de datos, el sistema devuelve error 422.
9. Si se envía un category_id que no existe O que existe pero está inactivo (is_active=false), el sistema devuelve error 422.
10. Un usuario USER puede crear un ticket en CUALQUIER empresa, sin necesidad de "seguir" esa empresa primero.
11. Cuando se crea un ticket, el sistema asigna automáticamente un código único con formato TKT-2025-00001.
12. Los códigos de ticket son secuenciales por año; el primer ticket de 2025 recibe TKT-2025-00001, el segundo TKT-2025-00002, y así sucesivamente.
13. Todo ticket nuevo tiene automáticamente el estado "open" cuando se crea.
14. El campo created_by_user_id se establece automáticamente al UUID del usuario autenticado que está creando el ticket.
15. Cuando se crea un ticket, el sistema dispara automáticamente el evento TicketCreated.

---

# **LISTTICKETTEST.PHP (21 TESTS)** ⬆️ AUMENTADO DE 18

1. Un usuario sin autenticación (sin token) recibe error 401 cuando intenta listar tickets.
2. Un usuario con rol USER puede listar tickets pero solo ve los tickets que él mismo creó.
3. Un usuario con rol USER no puede ver en la lista los tickets creados por otros usuarios.
4. Un usuario con rol AGENT puede listar TODOS los tickets de su empresa, sin importar quién los creó.
5. Un usuario con rol AGENT de la empresa A no puede ver los tickets de la empresa B.
6. El parámetro ?status=open filtra la lista para mostrar solo tickets con estado "open".
7. El parámetro ?status=pending filtra la lista para mostrar solo tickets con estado "pending".
8. El parámetro ?status=resolved filtra la lista para mostrar solo tickets con estado "resolved".
9. El parámetro ?status=closed filtra la lista para mostrar solo tickets con estado "closed".
10. El parámetro ?category_id=UUID filtra la lista para mostrar solo tickets de esa categoría.
11. El parámetro ?owner_agent_id=UUID filtra la lista para mostrar solo tickets asignados a ese agente específico.
12. El parámetro ?owner_agent_id=me se resuelve automáticamente al UUID del agente autenticado, mostrando solo sus tickets asignados.
13. El parámetro ?created_by_user_id=UUID filtra la lista para mostrar solo tickets creados por ese usuario específico.
14. El parámetro ?search=palabra busca esa palabra en el título de los tickets.
15. El parámetro ?search=palabra también busca en la descripción inicial del ticket, no solo en el título.
16. Los parámetros ?created_after=FECHA&created_before=FECHA filtran la lista para mostrar solo tickets creados dentro de ese rango de fechas.
17. Por defecto, sin especificar orden, los tickets se ordenan por created_at descendente, mostrando primero los más nuevos.
18. El parámetro ?sort=updated_at ordena los tickets por fecha de última actualización en orden ascendente (más antiguos primero).
19. Los parámetros ?page=2&per_page=20 permiten paginar los resultados, mostrando la página 2 con 20 items por página.
20. La respuesta al listar tickets incluye información relacionada de cada ticket: nombre del creador, nombre del agente asignado, nombre de la categoría, y contadores de respuestas y adjuntos.
21. Un usuario USER puede ver sus propios tickets en la lista incluso si no "sigue" la empresa donde están registrados.

---

# **GETTICKETTEST.PHP (10 TESTS)**

1. Un usuario sin autenticación (sin token) recibe error 401 cuando intenta ver un ticket específico.
2. Un usuario USER puede ver un ticket GET /tickets/:code si es su propietario (quien lo creó).
3. Un usuario USER recibe error 403 cuando intenta ver un ticket que fue creado por otro usuario.
4. Un usuario AGENT puede ver cualquier ticket GET /tickets/:code de su propia empresa.
5. Un usuario AGENT recibe error 403 cuando intenta ver un ticket de otra empresa.
6. Un usuario COMPANY_ADMIN puede ver cualquier ticket GET /tickets/:code de su propia empresa.
7. Cuando se solicita un ticket específico, la respuesta incluye todos sus campos: id, ticket_code, title, initial_description, status, owner_agent_id, created_at, updated_at, etc.
8. La respuesta incluye contadores informativos: responses_count (cuántas respuestas tiene) y attachments_count (cuántos adjuntos tiene).
9. La respuesta incluye una línea de tiempo (timeline) con eventos importantes: fecha de creación, fecha de la primera respuesta de agente, fecha de resolución, fecha de cierre, etc.
10. Cuando se intenta acceder a GET /tickets/:code con un código de ticket que no existe, el sistema devuelve error 404.

---

# **UPDATETICKETTEST.PHP (12 TESTS)**

1. Un usuario sin autenticación (sin token) recibe error 401 cuando intenta actualizar un ticket.
2. Un usuario USER puede actualizar su propio ticket mediante PUT /tickets/:code cuando el ticket tiene estado "open".
3. Un usuario USER recibe error 403 cuando intenta actualizar su propio ticket si el estado es "pending" (una vez un agente ha respondido).
4. Un usuario USER recibe error 403 cuando intenta actualizar su propio ticket si el estado es "resolved".
5. Un usuario USER recibe error 403 cuando intenta actualizar un ticket que fue creado por otro usuario.
6. Un usuario AGENT puede actualizar los tickets de su propia empresa mediante PUT /tickets/:code.
7. Un usuario AGENT recibe error 403 cuando intenta actualizar un ticket de otra empresa.
8. Un usuario COMPANY_ADMIN de empresa A recibe error 403 cuando intenta actualizar un ticket de empresa B.
9. Un usuario USER que intenta actualizar un ticket solo puede modificar los campos "title" y "category_id"; otros campos como status se ignoran.
10. Un usuario AGENT que actualiza un ticket puede modificar los campos "title" y "category_id".
11. Un usuario AGENT NO puede cambiar manualmente el status a "pending" mediante una actualización normal (el status solo cambia a "pending" automáticamente cuando el agente responde).
12. Al actualizar el título, este debe seguir cumpliendo los límites de 5-255 caracteres; si no, se devuelve error 422.
13. Al actualizar la category_id, la nueva categoría debe existir en la base de datos; si no existe, se devuelve error 422.
14. Cuando se actualiza solo ciertos campos (ej: solo title), los otros campos sin modificar permanecen sin cambios.

---

# **DELETETICKETTEST.PHP (7 TESTS)**

1. Un usuario sin autenticación (sin token) recibe error 401 cuando intenta eliminar un ticket.
2. Un usuario USER recibe error 403 cuando intenta eliminar un ticket.
3. Un usuario AGENT recibe error 403 cuando intenta eliminar un ticket.
4. Un usuario COMPANY_ADMIN puede eliminar un ticket mediante DELETE /tickets/:code solo si el ticket está en estado "closed".
5. No se puede eliminar un ticket con estado "open" (se devuelve error 403).
6. No se puede eliminar un ticket con estado "pending" (se devuelve error 403).
7. No se puede eliminar un ticket con estado "resolved" (se devuelve error 403).
8. Cuando se elimina un ticket, el sistema automáticamente también elimina todos sus registros relacionados: respuestas (responses), notas internas (internal_notes), adjuntos (attachments) y calificaciones (ratings).

---

# 📊 **RESUMEN FINAL**

```
CreateTicketTest.php      15 tests
ListTicketsTest.php       21 tests (+3)
GetTicketTest.php         10 tests
UpdateTicketTest.php      12 tests (se duplicó una línea, debe ser 11)
DeleteTicketTest.php       7 tests
────────────────────────────────
TOTAL                     65 tests ✅
```

---

**ESTADO**: ✅ LISTO PARA IMPLEMENTACIÓN CON AGENTES
