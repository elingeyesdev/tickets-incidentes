# Recomendación: Manejo de Tickets con SLA Vencido (24h sin respuesta)

## Contexto
Actualmente, el sistema eleva automáticamente la prioridad a **ALTA** cuando un ticket pasa 24 horas sin respuesta. Sin embargo, "Prioridad Alta" puede ser ambiguo (un ticket puede ser de prioridad alta desde su creación).

Para diferenciar claramente los tickets que requieren atención urgente por **retraso** (SLA vencido), se recomienda la siguiente estrategia:

## 1. Backend: Nuevo Indicador `is_overdue`

En lugar de confiar solo en la prioridad, agrega un indicador explícito.

### Opción A: Atributo Virtual (Computed)
Si el cálculo es ligero, puedes hacerlo en tiempo de ejecución (Appends).
*   **Ventaja:** Fácil de implementar.
*   **Desventaja:** No se puede indexar/filtrar eficientemente en base de datos si son muchos registros.

### Opción B: Columna en Base de Datos (Recomendada)
Agrega una columna `is_overdue` (boolean) o `sla_breached_at` (timestamp) en la tabla `tickets`.
*   **Trigger/Job:** El mismo proceso que actualiza la prioridad a "Alta" debe marcar `is_overdue = true`.
*   **Ventaja:** Permite filtrado ultra-rápido en SQL.

## 2. API: Nuevo Filtro

Actualiza el endpoint `GET /api/tickets` para aceptar un nuevo parámetro:

*   **Parámetro:** `?overdue=true`
*   **Lógica:**
    ```php
    if ($filters['overdue']) {
        $query->where('is_overdue', true);
        // O si usas lógica dinámica:
        // $query->where('updated_at', '<=', now()->subHours(24))
        //       ->where('status', '!=', 'closed');
    }
    ```

## 3. Frontend: Indicadores Visuales

Para que los agentes identifiquen estos tickets rápidamente:

1.  **Icono de Alerta:** Muestra un icono de "fuego" o "reloj rojo" junto al título del ticket en la lista.
2.  **Filtro Rápido:** Agrega un botón/tab en la parte superior de la lista: "🚨 Retrasados".
3.  **Ordenamiento:** Sugerir un ordenamiento por defecto donde los `overdue` aparezcan primero.

## 4. Ejemplo de Implementación (Resumen)

**Modelo Ticket:**
```php
// Accessor para uso rápido en frontend si no creas columna
public function getIsOverdueAttribute()
{
    return $this->status !== 'closed' 
        && $this->status !== 'resolved'
        && $this->updated_at < now()->subHours(24);
}
```

**Respuesta API (TicketResource):**
```php
'is_overdue' => $this->is_overdue, // true/false
'sla_status' => $this->is_overdue ? 'breached' : 'ok',
```

**Beneficio:**
Esto permite a los agentes distinguir entre "Un problema importante" (Prioridad Alta) y "Un problema que hemos ignorado demasiado tiempo" (Overdue), permitiendo una gestión más eficiente del backlog.
