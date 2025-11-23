# Análisis de Arquitectura de Componentes de Tickets

Este documento detalla la estrategia para implementar la vista de detalle de tickets, maximizando la reutilización de componentes entre los roles (USER, AGENT, COMPANY_ADMIN) y definiendo claramente las responsabilidades de cada parte.

## 1. Roles y Permisos (Resumen)

Basado en `documentacion/ticketsentpoints.txt`:

*   **USER (Creador):**
    *   Ver detalles y chat.
    *   Responder (Chat).
    *   **Acciones:**
        *   Cerrar ticket (Solo si está `RESOLVED`).
        *   Reabrir ticket (Solo si está `RESOLVED` o `CLOSED` < 30 días).
    *   *No puede asignar, ni cambiar estado arbitrariamente.*

*   **AGENT:**
    *   Ver detalles y chat.
    *   Responder (Chat).
    *   **Acciones:**
        *   Resolver ticket (De `OPEN`/`PENDING` a `RESOLVED`).
        *   Cerrar ticket (Cualquier estado).
        *   Reabrir ticket (Sin límite de tiempo).
        *   Asignar ticket (A sí mismo u otros agentes de la empresa).

*   **COMPANY_ADMIN:**
    *   Hereda permisos de gestión similares a AGENT en cuanto a flujo de tickets, más gestión de categorías (no relevante para la vista de detalle, pero sí el contexto).

## 2. Desglose de Componentes

La estrategia es **"Contenedor Inteligente, Componentes Tontos"**. El contenedor principal (`ticket-detail.blade.php`) orquestará la lógica, y los componentes solo renderizarán datos.

### A. `x-ticket-header` (Reutilizable: ALTO)
Muestra el ID del ticket, título y botón de cerrar.
*   **Lógica de Rol:** Ninguna visualmente crítica.
*   **Diferencias:**
    *   El botón "Volver" es igual para todos.
    *   El "Asunto" es igual.
    *   El "De:" (Remitente) siempre es relevante. Si soy USER, veré "De: Mí" o mi nombre. Si soy AGENT, veré "De: Cliente X".
*   **Propuesta:** Un solo componente.

### B. `x-ticket-info-card` (Reutilizable: ALTO)
Columna Izquierda Superior. Muestra metadatos.
*   **Datos Comunes:** Estado, Categoría, Fecha Creación, Última Actividad.
*   **Datos Variables:**
    *   **Agente Asignado:**
        *   USER: Ve el nombre del agente (o "Sin asignar").
        *   AGENT: Ve el nombre y quizás enlace al perfil.
    *   **Empresa:**
        *   USER: No necesita ver su propia empresa (es obvio), o sí.
        *   AGENT: Crucial ver de qué empresa es el ticket.
*   **Propuesta:** Un solo componente que acepta props opcionales (ej. `show-company="true"` para agentes).

### C. `x-ticket-actions` (Reutilizable: MEDIO - Lógica Interna)
Columna Izquierda Medio. Botones de acción.
*   **Esta es la parte más divergente.**
*   **USER:**
    *   Botón "Cerrar" (Solo visible si status == resolved).
    *   Botón "Reabrir" (Solo visible si status == resolved/closed).
*   **AGENT/ADMIN:**
    *   Select/Botones para cambiar estado (Open -> Pending -> Resolved).
    *   Botón "Cerrar" (Siempre visible).
    *   Botón "Reabrir".
    *   Botón "Asignar / Reasignar".
*   **Propuesta:**
    *   Crear un componente `x-ticket-actions` que reciba el `role` y el `ticket`.
    *   Dentro del componente, usar directivas `@if` claras para separar la botonera de USER de la de AGENT.
    *   *Alternativa:* Dos componentes separados `x-ticket-actions-user` y `x-ticket-actions-agent`. **Recomendación: Un solo componente bien estructurado** para evitar duplicar estilos de contenedor.

### D. `x-ticket-attachments` (Reutilizable: ALTO)
Columna Izquierda Inferior. Lista de archivos adjuntos iniciales (del ticket, no del chat).
*   **Lógica:** Idéntica para todos.
*   **Propuesta:** Un componente `x-ticket-attachments` que reciba la lista de adjuntos.

### E. `x-ticket-chat` (Reutilizable: ALTO)
Columna Derecha. El hilo de conversación.
*   **Lógica:**
    *   Mostrar mensajes (Izquierda: Otros, Derecha: Yo).
    *   Input de texto y adjuntos.
*   **Diferencias:**
    *   **AGENT:** Podría tener opción de "Nota Interna" (futuro).
    *   **USER:** Solo respuesta pública.
*   **Propuesta:** El componente actual `ticket-chat.blade.php` es perfecto. Solo necesita lógica JS para determinar quién es "Yo" (basado en `auth()->id()`) para alinear los mensajes correctamente.

## 3. Estrategia de Implementación

1.  **Crear/Refactorizar Componentes:**
    *   Mover el HTML del mock a archivos Blade en `resources/views/components/tickets/`.
    *   `header.blade.php`
    *   `info-card.blade.php`
    *   `actions.blade.php`
    *   `attachments.blade.php`
    *   (El chat ya existe, solo ajustarlo).

2.  **Contenedor Principal (`ticket-detail.blade.php`):**
    *   Este archivo será cargado por AJAX en el `index.blade.php` (dentro de `#view-ticket-details`).
    *   Recibirá el objeto `ticket` completo.
    *   Pasará los datos a los componentes.

3.  **Manejo de Datos (JS):**
    *   Al hacer clic en un ticket, `index.blade.php` llamará a la API `/api/tickets/{id}`.
    *   Con la respuesta JSON, renderizaremos la vista.
    *   *Reto:* Blade es servidor, JS es cliente.
    *   **Solución (AdminLTE Standard - jQuery):**
        *   Usaremos **jQuery** para manipular el DOM, manteniendo la consistencia con el resto del template.
        *   Los componentes Blade tendrán `id`s específicos (ej. `#ticket-detail-subject`, `#ticket-detail-status`).
        *   Una función `loadTicketDetails(ticket)` se encargará de rellenar estos campos y mostrar/ocultar botones según el estado y rol.

## 4. Plan de Acción Inmediato

1.  Crear la estructura de carpetas `resources/views/app/shared/tickets/components/`.
2.  Desglosar `ticket-detail-mock.blade.php` en estos componentes.
3.  Implementar la lógica de carga de datos en `index.blade.php` para inyectar los datos en estos componentes.

---
*Generado por Antigravity*
