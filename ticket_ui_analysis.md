# Análisis y Plan de Implementación: Mejoras UI/UX Tickets

## 1. Análisis de Roles y Carpetas

### A. Rol USER: "Esperando mi respuesta"
El usuario necesita saber cuándo un agente le ha respondido y está esperando que el usuario confirme o provea más información.
*   **Nombre Sugerido:** "Pendiente de mi respuesta" (Claro y directo).
*   **Lógica:** `last_response_author_type = 'agent'` Y `status` NO es `closed` ni `resolved`.

### B. Rol AGENT: Limpieza de Listas
Actualmente, "Mis Asignados" muestra tickets cerrados, lo cual ensucia el flujo de trabajo diario.
*   **Cambio:** Filtrar para mostrar solo tickets activos (`open`, `pending`).
*   **Lógica:** `owner_agent_id = 'me'` Y `status IN ('open', 'pending')`.

### C. Rol COMPANY_ADMIN / AGENT: "Requiere Atención"
Necesario para identificar tickets críticos que se están quedando atrás.
*   **Nombre:** "Requiere Atención".
*   **Lógica:** `priority = 'high'` Y `status IN ('open', 'pending')`.

## 2. Estrategia de Filtros (Prioridad, Área, Categoría)

### ¿Dónde colocarlos?
Tu intuición de colocarlos "a la izquierda de buscar ticket" es la correcta.
*   **Sidebar (Carpetas):** Se usa para **Vistas de Flujo de Trabajo** (Bandeja de entrada, Asignados, Cerrados). Son estados macro del ticket.
*   **Barra Superior (Filtros):** Se usa para **Refinar la Búsqueda** (Atributos del ticket).

**Por qué NO poner Prioridad/Área como carpetas:**
Si tienes 3 prioridades y 5 áreas, tendrías que crear 15 carpetas combinadas o llenar el sidebar de opciones, lo que confunde al usuario. Es mejor tener una lista "Todos" y filtrar arriba por "Solo Alta Prioridad" y "Solo Área Ventas".

### Diseño Propuesto (Barra Superior)
```
[Select Categoría] [Select Área] [Select Prioridad] [   Buscar...   ]
```
Esto permite combinaciones poderosas como: *"Ver tickets de Alta Prioridad del área de Soporte"*.

## 3. Plan de Acción

1.  **Modificar Sidebar (`index.blade.php`):**
    *   Agregar carpeta "Pendiente de mi respuesta" para USER.
    *   Agregar carpeta "Requiere Atención" para ADMIN/AGENT.
    *   Ajustar lógica de contadores JS para excluir cerrados en carpetas de trabajo.

2.  **Modificar Barra Superior (`tickets-list.blade.php`):**
    *   Insertar los 3 `select` (Categoría, Área, Prioridad) antes del input de búsqueda.
    *   Conectar estos selects al evento de recarga de la lista.

3.  **Carga de Datos:**
    *   Necesitamos cargar las Categorías y Áreas disponibles en los selects. Usaremos JS para pedirlas a la API al iniciar.
