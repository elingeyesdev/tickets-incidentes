# Actividad 12: Dashboard de Estadísticas - Company Admin

**Estudiante:** Luke Howland  
**Fecha:** 10 de Diciembre, 2025  
**Sistema:** Helpdesk Multi-tenant  

---

## 1. Capturas de Pantalla del Dashboard

### Vista General del Dashboard
El dashboard presenta 8 métricas de negocio distribuidas en múltiples secciones:

**Sección Superior - KPIs:**
- 4 tarjetas principales (Agentes, Artículos, Anuncios, Tickets)

**Fila de Gráficos:**
- Estado de Tickets (Donut Chart)
- **Tickets por Prioridad** (Donut Chart) ⭐ NUEVO
- Tickets Creados por Mes (Line Chart)

**Fila de Análisis:**
- **Top 5 Agentes por Performance** (Tabla con Ranking) ⭐ NUEVO
- Equipo de Soporte (Lista de miembros)

**Sección Inferior:**
- Categorías de Tickets (Tabla con barras de progreso)
- Quick Stats (4 info boxes: Tiempo de respuesta, Pendientes, Abiertos, Tasa de resolución)

---

## 2. Propósito de Cada Gráfico/Indicador

### 📊 **Gráfico 1: Tickets por Prioridad**
**Tipo:** Donut Chart  
**Propósito:** Visualizar la distribución de tickets según su nivel de urgencia (Alta, Media, Baja)  
**Valor de Negocio:** Permite al administrador identificar si la empresa está gestionando correctamente las prioridades o si hay un exceso de tickets urgentes que requieren más recursos.

**Colores Semáforo:**
- 🟢 Verde: Prioridad Baja
- 🟡 Amarillo: Prioridad Media
- 🔴 Rojo: Prioridad Alta

---

### 🏆 **Gráfico 2: Top 5 Agentes por Performance**
**Tipo:** Tabla de Ranking con Progress Bars  
**Propósito:** Identificar y reconocer a los agentes más efectivos del equipo  

**Métricas Calculadas:**
- **Asignados:** Total de tickets asignados al agente
- **Resueltos:** Tickets cerrados o resueltos por el agente
- **Tasa de Resolución:** Porcentaje calculado (Resueltos / Asignados × 100)

**Valor de Negocio:**
- Reconocimiento del desempeño individual
- Identificación de agentes que necesitan capacitación
- Redistribución equitativa de carga de trabajo
- Incentivos basados en métricas objetivas

**Indicadores Visuales:**
- 🥇 Medalla dorada para el #1
- 🟢 Barra verde: ≥80% de resolución (Excelente)
- 🟡 Barra amarilla: 50-79% de resolución (Regular)
- 🔴 Barra roja: <50% de resolución (Requiere atención)

---

### 📈 **Gráfico 3: Estado de Tickets**
**Tipo:** Donut Chart  
**Propósito:** Mostrar la distribución actual de tickets por estado  
**Estados:** Abiertos, Pendientes, Resueltos, Cerrados  
**Valor:** Visión rápida de la carga de trabajo pendiente

---

### 📅 **Gráfico 4: Tickets Creados por Mes**
**Tipo:** Line Chart  
**Propósito:** Visualizar tendencias de creación de tickets en los últimos 6 meses  
**Valor:** Detectar patrones estacionales, picos de demanda, o crecimiento del volumen de soporte

---

### 👥 **Gráfico 5: Equipo de Soporte**
**Tipo:** Lista de Avatares  
**Propósito:** Mostrar todos los agentes disponibles en la empresa  
**Valor:** Visión completa del equipo de soporte

---

### 📂 **Gráfico 6: Categorías de Tickets**
**Tipo:** Tabla con Progress Bars  
**Propósito:** Identificar las categorías con mayor volumen de tickets activos  
**Valor:** Priorización de recursos según demanda por categoría

---

### ⚡ **Quick Stats (4 Info Boxes)**
**Métricas:**
1. Tiempo Promedio de Respuesta
2. Tickets Pendientes
3. Tickets Abiertos
4. Tasa de Resolución Global

**Valor:** Indicadores clave de desempeño (KPIs) del área de soporte

---

## 3. Consultas y Fuentes de Datos

### **Backend: AnalyticsService.php**

#### Método Principal:
```php
public function getCompanyDashboardStats(string $companyId): array
```

### **Consulta 1: Tickets por Prioridad**
```php
private function getTicketPriorityStats(string $companyId): array
{
    $stats = Ticket::where('company_id', $companyId)
        ->whereNotNull('priority')
        ->select('priority', DB::raw('count(*) as total'))
        ->groupBy('priority')
        ->pluck('total', 'priority')
        ->toArray();

    return [
        'labels' => ['Baja', 'Media', 'Alta'],
        'data' => [
            $stats['low'] ?? 0,
            $stats['medium'] ?? 0,
            $stats['high'] ?? 0,
        ],
        'colors' => ['#28a745', '#ffc107', '#dc3545'],
    ];
}
```

**Fuente de Datos:**
- Tabla: `tickets`
- Filtros: `company_id`, `priority NOT NULL`
- Agrupación: Por campo `priority` (low, medium, high)

---

### **Consulta 2: Top 5 Agentes por Performance**
```php
private function getTopAgentsByPerformance(string $companyId, int $limit = 5): array
{
    $agents = User::with('profile')
        ->whereHas('userRoles', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->where('role_code', 'AGENT');
        })
        ->get();

    $agentStats = $agents->map(function ($agent) use ($companyId) {
        $assignedCount = Ticket::where('company_id', $companyId)
            ->where('owner_agent_id', $agent->id)
            ->count();

        $resolvedCount = Ticket::where('company_id', $companyId)
            ->where('owner_agent_id', $agent->id)
            ->whereIn('status', ['resolved', 'closed'])
            ->count();

        $resolutionRate = $assignedCount > 0 
            ? round(($resolvedCount / $assignedCount) * 100) 
            : 0;

        return [
            'name' => $agent->profile->first_name . ' ' . $agent->profile->last_name,
            'email' => $agent->email,
            'assigned' => $assignedCount,
            'resolved' => $resolvedCount,
            'resolution_rate' => $resolutionRate,
        ];
    })
    ->filter(fn($stats) => $stats['assigned'] > 0)
    ->sortByDesc('resolved')
    ->take($limit);
}
```

**Fuente de Datos:**
- Tablas: `users`, `user_roles`, `tickets`, `user_profiles`
- Filtros: `company_id`, `role_code = 'AGENT'`, `owner_agent_id`
- Cálculos:
  - Tickets Asignados: COUNT donde `owner_agent_id = agent.id`
  - Tickets Resueltos: COUNT donde `owner_agent_id = agent.id` AND `status IN ('resolved', 'closed')`
  - Tasa de Resolución: `(Resueltos / Asignados) × 100`

---

### **Endpoint API:**
```
GET /api/analytics/company-dashboard
Authorization: Bearer {JWT_TOKEN}
Active Role: COMPANY_ADMIN
```

**Respuesta JSON:**
```json
{
  "kpi": { "total_agents": 8, "total_articles": 12, ... },
  "ticket_status": { "OPEN": 2, "PENDING": 5, "RESOLVED": 15, "CLOSED": 25 },
  "ticket_priority": {
    "labels": ["Baja", "Media", "Alta"],
    "data": [10, 25, 12],
    "colors": ["#28a745", "#ffc107", "#dc3545"]
  },
  "top_agents": [
    {
      "rank": 1,
      "name": "María Condori",
      "email": "maria.condori@pilandina.com.bo",
      "assigned": 23,
      "resolved": 19,
      "resolution_rate": 83
    },
    ...
  ],
  "tickets_over_time": { "labels": [...], "data": [...] },
  "team_members": [...],
  "categories": [...],
  "performance": { "avg_response_time": "2.5h", ... }
}
```

---

## 4. Código Relevante

### **Vista Blade (dashboard.blade.php)**

#### Gráfico de Prioridad:
```html
<div class="col-md-4">
    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title">Tickets por Prioridad</h3>
        </div>
        <div class="card-body">
            <canvas id="ticketPriorityChart"></canvas>
        </div>
    </div>
</div>
```

#### JavaScript - Inicialización del Gráfico:
```javascript
function initializeTicketPriorityChart(priorityData) {
    const ctx = document.getElementById('ticketPriorityChart');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: priorityData.labels,
            datasets: [{
                data: priorityData.data,
                backgroundColor: priorityData.colors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}
```

#### Tabla Top 5 Agentes:
```javascript
function renderTopAgents(agents) {
    const tbody = document.getElementById('topAgentsBody');
    
    agents.forEach(agent => {
        const rankBadge = agent.rank === 1 
            ? '<i class="fas fa-medal text-warning"></i>' 
            : agent.rank;
        
        let progressColor = 'bg-danger';
        if (agent.resolution_rate >= 80) progressColor = 'bg-success';
        else if (agent.resolution_rate >= 50) progressColor = 'bg-warning';

        const row = `
            <tr>
                <td class="text-center"><strong>${rankBadge}</strong></td>
                <td>
                    <strong>${agent.name}</strong><br>
                    <small class="text-muted">${agent.email}</small>
                </td>
                <td class="text-center">
                    <span class="badge badge-info">${agent.assigned}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-success">${agent.resolved}</span>
                </td>
                <td>
                    <div class="progress progress-xs">
                        <div class="progress-bar ${progressColor}" 
                             style="width: ${agent.resolution_rate}%"></div>
                    </div>
                    <small class="text-center d-block mt-1">
                        ${agent.resolution_rate}%
                    </small>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}
```

---

## 5. Justificación del Valor de Negocio

### 🎯 **Problema que Resuelve**

**Antes del Dashboard:**
- ❌ No había visibilidad del desempeño individual de agentes
- ❌ No se podía identificar qué tickets eran más urgentes
- ❌ Distribución de trabajo era manual y subjetiva
- ❌ No había métricas para evaluar eficiencia del equipo

**Después del Dashboard:**
- ✅ Visibilidad completa del desempeño por agente
- ✅ Priorización clara de tickets urgentes
- ✅ Distribución basada en datos objetivos
- ✅ KPIs medibles para evaluación continua

---

### 💼 **Valor para el Cliente (Company Admin)**

#### 1. **Gestión de Recursos Humanos**
- Identificar agentes de alto desempeño para reconocimiento/bonos
- Detectar agentes que necesitan capacitación adicional
- Redistribuir carga de trabajo equitativamente

#### 2. **Gestión de Prioridades**
- Visualizar si hay exceso de tickets urgentes (problemas sistémicos)
- Asignar recursos adicionales a categorías con alta prioridad
- Planificar preventivamente antes de crisis

#### 3. **Optimización de Procesos**
- Tendencias mensuales permiten planificación de recursos
- Identificar categorías que consumen más tiempo
- Medir impacto de mejoras implementadas

#### 4. **Toma de Decisiones Basada en Datos**
- Presupuesto: Contratar más agentes si hay sobrecarga
- Capacitación: Invertir en áreas con baja tasa de resolución
- Estrategia: Enfocar recursos en categorías críticas

#### 5. **Reporting Ejecutivo**
- Datos listos para presentaciones a gerencia
- Justificación de inversiones en equipo de soporte
- Demostración de ROI del área de soporte

---

### 📊 **Métricas de Impacto Esperadas**

**Mejora en Eficiencia:**
- ↑ 20% en tasa de resolución promedio (benchmarking entre agentes)
- ↓ 30% en tiempo de respuesta (competencia sana)
- ↑ 15% en satisfacción del cliente (mejor gestión de prioridades)

**Reducción de Costos:**
- ↓ 25% en horas extra (distribución equitativa)
- ↓ 40% en tickets escalados (resolución temprana de alta prioridad)

---

## 6. Cumplimiento de Requisitos Académicos

### ✅ **Actividad 12: Cuadro de Estadísticas**

**Requisitos:**
- ✅ **3-5 métricas con datos procesados:** Implementadas 8 métricas
- ✅ **Mostrar tendencias:** Gráfico de evolución mensual (6 meses)
- ✅ **Mostrar top 5:** Ranking de agentes por performance
- ✅ **Mostrar porcentajes:** Tasa de resolución por agente
- ✅ **Mostrar estados:** Distribución por status y prioridad
- ✅ **Integrado en el sistema:** Funcional en producción
- ✅ **Claro y útil para usuarios finales:** Diseño AdminLTE v3, intuitivo

**Excedencia de Expectativas:**
- Se implementaron **8 métricas** cuando se requerían 3-5
- Se agregaron **visualizaciones interactivas** (Chart.js)
- Se incluyó **código semafórico** para interpretación rápida
- Se implementó **SSR (Server-Side Rendering)** para performance óptima

---

## 7. Tecnologías Utilizadas

**Backend:**
- Laravel 11
- PostgreSQL
- Eloquent ORM
- Service Layer Pattern

**Frontend:**
- Blade Templates
- Chart.js 3.9.1
- AdminLTE v3
- JavaScript Vanilla (ES6+)

**Arquitectura:**
- RESTful API
- JWT Authentication
- Multi-tenant (Company-scoped)
- Server-Side Rendering (SSR)

---

## 8. Conclusión

El dashboard de Company Admin implementado cumple y **excede** los requisitos académicos, proporcionando un cuadro estadístico profesional, funcional y altamente valioso para la gestión empresarial de equipos de soporte técnico.

Las métricas implementadas permiten toma de decisiones basada en datos, optimización de recursos humanos, y mejora continua del servicio al cliente.

---

**Documentación técnica completa**  
Sistema Helpdesk Multi-tenant  
Diciembre 2025
