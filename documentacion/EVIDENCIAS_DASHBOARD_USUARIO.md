# Actividad 12: Cuadro de Estadísticas - Evidencias

## Dashboard de Usuario del Sistema Helpdesk
**Estudiante:** Lucas De La Quintana Montenegro  
**Fecha:** 10 de Diciembre de 2025  
**Sistema:** Enterprise Helpdesk System (Laravel 12 + AdminLTE v3)

---

## 1. Capturas de Pantalla del Dashboard

> **Nota:** Las capturas de pantalla deben ser insertadas aquí mostrando:
> - Vista completa del dashboard
> - Sección de KPIs
> - Gráfico de distribución por prioridad
> - Gráfico de tendencia de tickets
> - Widget de perfil de usuario

**URL del Dashboard:** `http://localhost:8000/app/user/dashboard`  
**Credenciales de prueba:** `montenegroluke999@gmail.com` / `mklmklmkl`

---

## 2. Descripción del Propósito de Cada Gráfico/Indicador

### 2.1 KPI Cards (4 indicadores)
| Indicador | Propósito |
|-----------|-----------|
| **Mis Tickets** | Muestra el total de tickets creados por el usuario. Permite una visión rápida del volumen de solicitudes. |
| **Abiertos** | Tickets que aún no han sido asignados a un agente. Indica solicitudes pendientes de atención inicial. |
| **Pendientes** | Tickets en proceso que esperan respuesta del usuario o acción adicional del agente. |
| **Resueltos/Cerrados** | Tickets completados exitosamente. Indica el historial de problemas solucionados. |

### 2.2 Widget "Mi Perfil"
Muestra información personal del usuario junto con métricas clave:
- **Avatar y nombre** del usuario
- **Miembro desde**: Fecha de registro
- **Total / Resueltos / Tasa**: Resumen estadístico de efectividad

### 2.3 Distribución por Prioridad (Progress Bars)
| Prioridad | Propósito |
|-----------|-----------|
| **Alta (Rojo)** | Tickets urgentes que requieren atención inmediata |
| **Media (Amarillo)** | Tickets con importancia moderada |
| **Baja (Verde)** | Tickets de baja urgencia |

**Valor:** Permite al usuario entender la urgencia de sus solicitudes y planificar expectativas de tiempo de respuesta.

### 2.4 Gráfico de Estado de Tickets (Doughnut Chart)
Visualización circular que muestra la proporción de tickets por estado:
- **Abiertos** (Rojo)
- **Pendientes** (Amarillo)
- **Resueltos** (Azul)
- **Cerrados** (Verde)

**Valor:** Vista rápida del estado general de todas las solicitudes del usuario.

### 2.5 Tendencia de Tickets (Line Chart - 6 meses)
Gráfico de línea que muestra la evolución temporal de tickets creados en los últimos 6 meses.

**Valor:** Permite identificar patrones de uso y picos de actividad del usuario.

### 2.6 Top 5 Empresas Más Seguidas
Lista de las empresas con más seguidores en la plataforma, mostrando:
- Logo de la empresa
- Nombre y industria
- Cantidad de seguidores

**Valor:** Descubrimiento de empresas populares para seguir y recibir sus anuncios.

### 2.7 Top 5 Artículos Más Vistos
Lista de artículos del Centro de Ayuda ordenados por número de vistas.

**Valor:** Acceso rápido a contenido de ayuda relevante y popular.

### 2.8 Tickets Recientes (Tabla)
Tabla con los últimos 5 tickets del usuario, incluyendo:
- Código del ticket
- Asunto
- Prioridad (badge de color)
- Estado
- Fecha de actualización

**Valor:** Seguimiento rápido del estado de solicitudes recientes.

---

## 3. Fuentes de Datos y Consultas Utilizadas

### 3.1 Endpoint API
```
GET /api/analytics/user-dashboard
Authorization: Bearer {JWT_TOKEN}
```

### 3.2 Consultas SQL (resumen)

| Dato | Consulta/Fuente |
|------|-----------------|
| **KPIs** | `Ticket::where('created_by_user_id', $userId)->count()` agrupado por `status` |
| **Distribución Prioridad** | `Ticket::groupBy('priority')->select('priority', DB::raw('count(*) as total'))` |
| **Tendencia 6 meses** | `Ticket::where('created_at', '>=', now()->subMonths(5))->groupBy(TO_CHAR(created_at, 'YYYY-MM'))` |
| **Perfil Usuario** | `User::with('profile')->find($userId)` |
| **Top Empresas** | `Company::active()->withCount('followers')->orderBy('followers_count', 'desc')->limit(5)` |
| **Top Artículos** | `HelpCenterArticle::published()->orderBy('views_count', 'desc')->limit(5)` |
| **Tickets Recientes** | `Ticket::where('created_by_user_id', $userId)->orderBy('updated_at', 'desc')->limit(5)` |

### 3.3 Modelos Involucrados
- `Ticket` → Tabla: `ticketing.tickets`
- `User` / `UserProfile` → Tablas: `auth.users`, `auth.user_profiles`
- `Company` → Tabla: `business.companies`
- `HelpCenterArticle` → Tabla: `business.help_center_articles`

---

## 4. Código Relevante

### 4.1 Backend: AnalyticsService.php (Método principal)
```php
public function getUserDashboardStats(string $userId): array
{
    return [
        'kpi' => $this->getUserKpiStats($userId),
        'ticket_status' => $this->getUserTicketStatusStats($userId),
        'recent_tickets' => $this->getUserRecentTickets($userId),
        'recent_articles' => $this->getRecentArticles(),
        // Datos adicionales para dashboard estadístico
        'profile' => $this->getUserProfile($userId),
        'priority_distribution' => $this->getUserPriorityDistribution($userId),
        'tickets_trend' => $this->getUserTicketsTrend($userId),
        'top_companies' => $this->getTopFollowedCompanies(),
        'top_articles' => $this->getTopViewedArticles(),
    ];
}
```

### 4.2 Consulta de Distribución por Prioridad
```php
private function getUserPriorityDistribution(string $userId): array
{
    $stats = Ticket::where('created_by_user_id', $userId)
        ->select('priority', DB::raw('count(*) as total'))
        ->groupBy('priority')
        ->pluck('total', 'priority')
        ->toArray();

    $total = array_sum($stats);

    return [
        'high' => [
            'count' => $stats[TicketPriority::HIGH->value] ?? 0,
            'percentage' => $total > 0 ? round((($stats[TicketPriority::HIGH->value] ?? 0) / $total) * 100) : 0,
        ],
        // ... medium, low
    ];
}
```

### 4.3 Consulta de Tendencia de Tickets
```php
private function getUserTicketsTrend(string $userId): array
{
    $data = Ticket::where('created_by_user_id', $userId)
        ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
        ->select(
            DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
            DB::raw('count(*) as total')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month')
        ->toArray();

    // Llenar meses faltantes con 0
    // Retornar labels y data para Chart.js
}
```

### 4.4 Frontend: Renderizado con Chart.js
```javascript
// Gráfico de Tendencia (Line Chart)
ticketsTrendChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
        labels: data.labels,  // ['Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
        datasets: [{
            label: 'Tickets Creados',
            data: data.data,  // [2, 5, 3, 1, 4, 2]
            backgroundColor: 'rgba(23, 162, 184, 0.3)',
            borderColor: '#17a2b8',
            fill: true,
            tension: 0.4
        }]
    }
});
```

---

## 5. Justificación del Valor para el Cliente

### ¿Cómo aporta valor este dashboard al usuario final?

| Aspecto | Beneficio |
|---------|-----------|
| **Visión General Rápida** | Los KPIs permiten al usuario ver el estado de todas sus solicitudes en segundos, sin necesidad de navegar por múltiples páginas. |
| **Toma de Decisiones** | La distribución por prioridad ayuda a entender qué tickets necesitan seguimiento urgente. |
| **Seguimiento Temporal** | El gráfico de tendencia permite identificar si el usuario está teniendo más problemas que lo usual, lo que podría indicar un patrón (ej: problemas con un producto específico). |
| **Autoservicio** | Los artículos más vistos proveen acceso rápido a soluciones comunes, reduciendo la necesidad de crear tickets. |
| **Transparencia** | El widget de perfil con tasa de resolución muestra al usuario que sus problemas están siendo atendidos efectivamente. |
| **Descubrimiento** | Las empresas más seguidas permiten encontrar nuevos servicios de soporte relevantes. |

### Criterios de Diseño Aplicados

1. **Claridad**: Uso de iconos, colores y badges para comunicar información instantáneamente
2. **Consistencia**: Diseño alineado con AdminLTE v3 y el resto del sistema
3. **Responsividad**: Layout adaptable a diferentes tamaños de pantalla
4. **Carga Asíncrona**: Loading overlays para feedback visual durante la carga
5. **Accesibilidad**: Etiquetas claras y contraste de colores adecuado

---

## 6. Tecnologías Utilizadas

| Tecnología | Uso |
|------------|-----|
| **Laravel 12** | Backend, API REST, Eloquent ORM |
| **PostgreSQL 17** | Base de datos con esquemas multi-tenant |
| **Chart.js 3.9** | Gráficos interactivos (Doughnut, Line) |
| **AdminLTE v3** | Framework CSS, componentes UI |
| **jQuery** | Manipulación DOM, llamadas AJAX |
| **JWT** | Autenticación de API |

---

## 7. Archivos del Proyecto Relacionados

```
├── app/Features/Analytics/
│   ├── Http/Controllers/AnalyticsController.php
│   └── Services/AnalyticsService.php          ← Lógica de consultas
│
├── resources/views/app/user/
│   └── dashboard.blade.php                     ← Vista del dashboard
│
├── routes/
│   └── api.php                                 ← Endpoint /api/analytics/user-dashboard
```

---

**Fecha de entrega:** 10 de Diciembre de 2025  
**Proyecto:** Enterprise Helpdesk System - Universidad Privada de Santa Cruz de la Sierra (UPSA)
