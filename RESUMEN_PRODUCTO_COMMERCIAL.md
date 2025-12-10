# ENTERPRISE HELPDESK SYSTEM - RESUMEN COMERCIAL DEL PRODUCTO

## 1. ¿QUÉ ES EL PRODUCTO?

**Enterprise Helpdesk System** es una plataforma SaaS multi-tenant profesional de gestión de tickets de soporte diseñada para empresas que necesitan brindar servicio técnico o atención al cliente de manera estructurada y eficiente.

### Concepto Multi-Tenant
Múltiples empresas comparten la misma plataforma tecnológica, pero cada una tiene su espacio completamente aislado y privado. Es como un edificio de oficinas donde cada empresa tiene su piso exclusivo - comparten el edificio (infraestructura) pero sus datos y operaciones están completamente separados.

### Tipo de Producto
Sistema B2B (Business to Business) donde:
- **Clientes:** Empresas de cualquier industria que necesitan gestionar solicitudes de soporte
- **Usuarios finales:** Empleados, clientes o usuarios de esas empresas que crean tickets
- **Modelo:** SaaS (Software as a Service) - las empresas se registran y usan el sistema sin instalación

---

## 2. ¿PARA QUIÉN ESTÁ DISEÑADO?

### Empresas Objetivo

**Cualquier organización que reciba solicitudes de soporte, reclamos o problemas**, incluyendo:

- **Empresas de Tecnología:** Soporte técnico para usuarios de software/hardware
- **Bancos y Financieras:** Atención de reclamos, problemas de tarjetas, banca online
- **Telecomunicaciones:** Reportes de fallas de servicio, cobertura, facturación
- **Manufactura:** Problemas de producción, calidad, mantenimiento de equipos
- **Alimentos y Bebidas:** Control de calidad, reclamos de producto, distribución
- **Energía (petróleo, gas, electricidad):** Fallas en infraestructura, seguridad industrial
- **Farmacéuticas:** Control de calidad, cadena de frío, distribución
- **Retail/Supermercados:** Reclamos de clientes, problemas de inventario
- **Salud (hospitales, clínicas):** Tickets internos de equipamiento médico, sistemas
- **Educación:** Soporte técnico, problemas administrativos
- **Gobierno y ONGs:** Atención ciudadana, gestión de solicitudes

### Tamaños de Empresa
- **Pequeñas:** 1-3 agentes, 50-200 tickets/mes
- **Medianas:** 5-15 agentes, 500-2000 tickets/mes
- **Grandes:** 20+ agentes, múltiples departamentos, 5000+ tickets/mes

---

## 3. ¿QUÉ PROBLEMA RESUELVE?

### Problemas Actuales Sin el Sistema

1. **Desorganización Total**
   - Tickets llegan por email, WhatsApp, llamadas, redes sociales
   - Se pierden solicitudes
   - No hay registro centralizado
   - Imposible hacer seguimiento

2. **Falta de Trazabilidad**
   - "¿Quién está atendiendo este caso?"
   - "¿Cuándo reportó este problema el cliente?"
   - "¿Qué respuestas se dieron?"
   - Sin historial = cliente repite el problema cada vez

3. **Múltiples Empresas = Múltiples Instalaciones**
   - Cada empresa necesita su propio servidor
   - Costos altos de infraestructura
   - Mantenimiento individual
   - Actualizaciones complejas

4. **Roles Confusos**
   - No hay separación entre usuarios finales y agentes de soporte
   - Cualquiera puede ver/editar cualquier cosa
   - Sin permisos granulares

5. **Sin Métricas ni Reportes**
   - "¿Cuánto tardamos en responder?"
   - "¿Cuántos tickets resueltos este mes?"
   - "¿Cuál es la satisfacción del cliente?"
   - Sin datos = sin mejora continua

6. **Comunicación Fragmentada**
   - Cliente escribe por email → agente responde por teléfono → cliente vuelve a escribir
   - Sin hilo único de conversación
   - Información se pierde

### Soluciones que Ofrece el Sistema

✅ **Centralización Total:** Todos los tickets en un solo lugar
✅ **Trazabilidad Completa:** Historial de cada solicitud desde creación hasta cierre
✅ **Multi-Tenancy:** Múltiples empresas en una sola plataforma, datos separados
✅ **Roles Claros:** Platform Admin → Company Admin → Agents → Users
✅ **Métricas Automáticas:** Tiempos de respuesta, resolución, satisfacción (ratings)
✅ **Conversación Unificada:** Todo el diálogo ticket-agente en un hilo
✅ **Auto-Servicio:** Centro de ayuda reduce tickets repetitivos
✅ **Comunicación Proactiva:** Anuncios de mantenimientos, incidentes, noticias

---

## 4. ROLES Y RESPONSABILIDADES

### 4.1 PLATFORM_ADMIN (Administrador de Plataforma)

**Quién es:** Dueño o administrador del sistema completo (no pertenece a ninguna empresa)

**Qué puede hacer:**
- Revisar solicitudes de nuevas empresas que quieren usar la plataforma
- Aprobar o rechazar solicitudes (cuando aprueba, se crea la empresa automáticamente)
- Crear empresas directamente (bypass del proceso de solicitud)
- Suspender empresas que violen políticas
- Ver métricas globales (total empresas, tickets, usuarios)
- Gestionar industrias del sistema

**Dashboard:** `/admin/dashboard`

**Escenario Real:**
```
Juan es el PLATFORM_ADMIN de "Enterprise Helpdesk System"
- Recibe solicitud de "Banco Fassil" para usar la plataforma
- Revisa información: nombre legal, tax ID, industria (banking)
- Aprueba la solicitud
- Sistema crea empresa "Banco Fassil" y envía email al admin con credenciales
```

---

### 4.2 COMPANY_ADMIN (Administrador de Empresa)

**Quién es:** Administrador de UNA empresa específica dentro de la plataforma

**Qué puede hacer:**

**Configuración de Empresa:**
- Subir logo, colores corporativos
- Configurar datos de contacto (email soporte, teléfono, dirección)
- Establecer horarios de atención
- Configurar zona horaria

**Gestión de Equipo:**
- Contratar agentes (enviar invitaciones con credenciales temporales)
- Desactivar agentes
- Ver rendimiento de agentes (tickets resueltos, calificaciones)

**Catálogos Personalizados:**
- Crear categorías de tickets según el negocio
  Ejemplo Banco: "Tarjetas", "Créditos", "Banca Online", "Reclamos"
  Ejemplo Manufactura: "Producción", "Calidad", "Mantenimiento"
- Crear áreas/departamentos (opcional): "IT", "Finanzas", "RRHH"

**Gestión de Contenido:**
- Crear anuncios (mantenimientos, incidentes, noticias, alertas)
- Publicar artículos en centro de ayuda (auto-servicio para usuarios)

**Gestión de Tickets:**
- Ver todos los tickets de la empresa
- Responder como agente
- Asignar tickets a agentes específicos
- Cerrar tickets
- Eliminar tickets cerrados (limpieza)

**Dashboard:** `/company/dashboard`

**Escenario Real:**
```
María es COMPANY_ADMIN de "Banco Fassil"

Día 1 - Configuración Inicial:
- Sube logo del banco, colores azul/rojo
- Configura email soporte: soporte@bancofassil.com
- Establece horarios: Lun-Vie 8:00-20:00, Sáb 8:00-14:00
- Crea categorías: "Tarjetas", "Créditos", "Banca Online", "Reclamos", "Consultas"

Día 2 - Contratar Equipo:
- Invita 5 agentes (emails de empleados)
- Sistema envía emails con credenciales temporales
- Agentes hacen login y cambian contraseña

Semana 1 - Gestión Operativa:
- Ve dashboard: 142 tickets este mes, 87% resueltos, calificación 4.6/5
- Nota que agente Pedro tiene baja calificación (3.2/5)
- Revisa tickets de Pedro para coaching

Día 15 - Comunicación:
- Crea anuncio: "Mantenimiento banca online sábado 18/12 de 2am a 6am"
- Tipo: MAINTENANCE, urgencia: MEDIUM
- Publica artículo: "Cómo recuperar contraseña de banca online" (reduce tickets)
```

---

### 4.3 AGENT (Agente de Soporte)

**Quién es:** Empleado de la empresa encargado de responder y resolver tickets

**Qué puede hacer:**

**Atención de Tickets:**
- Ver todos los tickets de la empresa
- Abrir ticket y ver historial completo de conversación
- Responder tickets (al dar primera respuesta, se auto-asigna como responsable)
- Adjuntar archivos en respuestas (capturas, manuales, documentos)
- Agregar notas internas (solo visibles para agentes/admins, no usuario)

**Acciones sobre Tickets:**
- Cambiar prioridad (LOW, MEDIUM, HIGH)
- Marcar como resuelto (usuario puede calificar o cerrar)
- Cerrar definitivamente
- Reabrir ticket si vuelve a haber problema
- Asignar a otro agente

**Dashboard:** `/agent/dashboard`

**Escenario Real:**
```
Pedro es AGENT de "Banco Fassil"

Lunes 8:00 AM - Inicio de turno:
- Ve dashboard: 12 tickets nuevos (OPEN), 8 tickets pendientes míos
- Filtra por prioridad: 2 HIGH, 7 MEDIUM, 3 LOW
- Abre ticket HIGH: "No puedo hacer transferencias - Error 502"

Atención del Ticket:
- Lee descripción del usuario
- Ve captura de pantalla adjunta
- Identifica problema (incidente conocido del servidor backend)
- Responde: "Hola, identificamos el problema. Equipo técnico trabajando. Estimamos 2 horas."
- Al enviar primera respuesta → ticket pasa de OPEN a PENDING
- Pedro se auto-asigna como owner del ticket

2 horas después:
- Equipo IT resuelve problema
- Pedro prueba el sistema → funciona
- Responde: "Problema solucionado. Ya puedes hacer transferencias normalmente."
- Marca ticket como RESOLVED
- Usuario recibe notificación: "Tu ticket fue resuelto"

Usuario califica:
- 5 estrellas: "Excelente atención, problema resuelto rápido"
- Rating de Pedro sube a 4.5/5
```

---

### 4.4 USER (Usuario Final / Cliente)

**Quién es:** Cliente o usuario final que necesita soporte de una empresa

**Qué puede hacer:**

**Exploración de Empresas:**
- Ver lista de empresas activas en la plataforma
- Filtrar por industria
- Seguir empresas para recibir actualizaciones

**Creación de Tickets:**
- Seleccionar empresa a la que dirigir el ticket
- Elegir categoría (personalizada por empresa)
- Elegir área/departamento (si empresa tiene habilitado)
- Escribir título y descripción detallada
- Adjuntar archivos (capturas, documentos)
- Seleccionar prioridad

**Gestión de Tickets Propios:**
- Ver solo sus propios tickets
- Editar ticket (solo si está OPEN, antes de que agente responda)
- Agregar respuestas (aportar más información)
- Adjuntar archivos adicionales
- Cerrar ticket si está RESOLVED
- Reabrir ticket (solo dentro de 30 días)
- Calificar servicio (1-5 estrellas + comentario)

**Centro de Ayuda:**
- Buscar artículos para resolver dudas sin crear ticket
- Ver artículos por categoría
- Ver artículos más populares

**Anuncios:**
- Ver anuncios de empresas que sigue
- Filtrar por tipo (mantenimiento, incidente, noticia, alerta)

**Dashboard:** `/tickets`

**Escenario Real:**
```
Ana es USER (cliente de Banco Fassil)

Lunes 8:30 AM - Problema:
- No puede hacer transferencia en banca online
- Recibe error 502 Bad Gateway

Crea Ticket:
1. Abre navegador → helpdesk.com → login
2. Selecciona "Banco Fassil"
3. Clic "Crear Ticket"
4. Llena formulario:
   - Categoría: "Banca Online"
   - Prioridad: MEDIUM
   - Título: "No puedo transferir dinero - Error 502"
   - Descripción: "Desde esta mañana no puedo hacer transferencias. Al confirmar operación sale error 502"
   - Adjunta: captura.png
5. Envía ticket → Código generado: TKT-2025-00142

Sigue Conversación:
- 9:15 AM: Recibe email "Tu ticket ha sido respondido"
- Abre ticket → lee respuesta de agente Pedro
- 10:00 AM: Agrega respuesta: "También intenté desde app móvil, mismo error. ¿Hay alternativa temporal?"
- 11:45 AM: Recibe respuesta de Pedro con alternativa (ventanilla/cajero)

Resolución:
- 12:30 PM: Recibe email "Tu ticket ha sido resuelto"
- Prueba hacer transferencia → funciona ✓
- Califica servicio: 5⭐ "Excelente atención, problema resuelto rápido"
- Cierra ticket

Resultado:
- Tiempo total: 4 horas
- Satisfacción: 5/5
- Problema resuelto
```

---

## 5. ENTIDADES DEL NEGOCIO

### 5.1 Company (Empresa)

**Qué es:** Organización que usa la plataforma para dar soporte a sus usuarios.

**Información Principal:**
- Código único: CMP-2025-00001
- Nombre comercial y legal
- Industria (23 opciones: tecnología, banca, manufactura, telecomunicaciones, etc.)
- Datos de contacto (email soporte, teléfono, dirección)
- Branding (logo, favicon, colores corporativos)
- Horarios de atención
- Zona horaria
- Estado (activa/suspendida)

**Relaciones:**
- Tiene agentes (AGENT roles)
- Tiene administrador (COMPANY_ADMIN role)
- Recibe tickets de usuarios
- Publica anuncios
- Publica artículos de ayuda
- Define categorías personalizadas
- Puede tener áreas/departamentos

**Ejemplos:**
- Banco Fassil (industria: banking)
- PIL Andina (industria: food_and_beverage)
- Tigo Bolivia (industria: telecommunications)
- YPFB (industria: energy)

---

### 5.2 CompanyRequest (Solicitud de Empresa)

**Qué es:** Solicitud formal para que una nueva empresa se una a la plataforma.

**Flujo:**
1. Interesado completa formulario de solicitud
2. Sistema crea solicitud con estado PENDING
3. PLATFORM_ADMIN revisa solicitud
4. Si aprueba:
   - Sistema crea empresa
   - Crea usuario admin con password temporal
   - Envía email con credenciales
   - Marca solicitud como APPROVED
5. Si rechaza:
   - Marca solicitud como REJECTED
   - Guarda motivo del rechazo
   - Envía email al solicitante

**Información:**
- Código único: REQ-2025-00001
- Nombre de empresa solicitada
- Email del futuro admin
- Industria
- Descripción del negocio
- Estimación de usuarios
- Estado (PENDING/APPROVED/REJECTED)
- Motivo de rechazo (si aplica)

---

### 5.3 Ticket (Solicitud de Soporte)

**Qué es:** Reporte de problema o solicitud de ayuda creado por un usuario dirigido a una empresa.

**Información Principal:**
- Código único: TKT-2025-00001
- Creado por (USER)
- Dirigido a (COMPANY)
- Categoría (personalizada por empresa)
- Área/Departamento (opcional)
- Título y descripción
- Prioridad (LOW/MEDIUM/HIGH)
- Estado actual
- Agente asignado (owner)
- Archivos adjuntos

**Ciclo de Vida (Estados):**

```
1. OPEN (Abierto, Nuevo)
   - Usuario acaba de crear el ticket
   - Ningún agente ha respondido
   - Usuario puede editar título/descripción
   - Esperando primera respuesta

   ↓ Agente da primera respuesta

2. PENDING (Pendiente, En Atención)
   - Agente respondió (se auto-asigna)
   - Conversación activa
   - Usuario NO puede editar (solo responder)
   - Puede tener múltiples respuestas usuario-agente

   ↓ Agente marca como resuelto

3. RESOLVED (Resuelto)
   - Agente considera que solucionó el problema
   - Usuario puede:
     * Calificar servicio
     * Cerrar ticket
     * Reabrir si no está conforme

   ↓ Usuario o agente cierra

4. CLOSED (Cerrado, Finalizado)
   - Ticket cerrado definitivamente
   - Usuario puede calificar si no lo hizo
   - Puede reabrirse dentro de 30 días (usuario) o sin límite (agente)
```

**Métricas Importantes:**
- `created_at`: Cuándo se creó
- `first_response_at`: Cuándo agente respondió por primera vez (SLA)
- `resolved_at`: Cuándo se marcó como resuelto
- `closed_at`: Cuándo se cerró definitivamente

**Prioridades:**
- LOW: No urgente, puede esperar
- MEDIUM: Prioridad normal (default)
- HIGH: Requiere atención pronta

---

### 5.4 TicketResponse (Respuesta de Ticket)

**Qué es:** Mensaje en la conversación del ticket (puede ser del usuario o del agente).

**Información:**
- A qué ticket pertenece
- Quién escribió (USER o AGENT)
- Contenido del mensaje
- Si es nota interna (solo para agentes/admins)
- Fecha y hora
- Archivos adjuntos

**Comportamiento Especial:**
- Primera respuesta de AGENT → ticket pasa de OPEN a PENDING
- Cada respuesta actualiza "última respuesta de" (USER o AGENT)

---

### 5.5 TicketRating (Calificación)

**Qué es:** Evaluación del servicio recibido por parte del usuario.

**Información:**
- Calificación (1-5 estrellas)
- Comentario (opcional)
- Fecha de calificación

**Reglas:**
- Solo puede calificar el creador del ticket
- Solo si ticket está RESOLVED o CLOSED
- Solo puede calificar una vez (permanente)

**Uso:**
- Medir satisfacción del cliente
- Evaluar rendimiento de agentes
- Identificar áreas de mejora

---

### 5.6 Category (Categoría de Ticket)

**Qué es:** Clasificación personalizada de tickets por empresa.

**Ejemplos por Industria:**

**Banking:**
- Tarjetas de Crédito
- Créditos Hipotecarios
- Banca Online
- Reclamos
- Consultas Generales

**Manufacturing:**
- Problema de Producción
- Control de Calidad
- Mantenimiento de Equipos
- Logística y Distribución

**Telecommunications:**
- Falla de Servicio
- Cobertura de Red
- Facturación
- Soporte Técnico
- Atención al Cliente

**Food & Beverage:**
- Calidad de Producto
- Distribución
- Reclamos de Cliente
- Consultas de Recetas

---

### 5.7 Announcement (Anuncio)

**Qué es:** Comunicación de la empresa hacia usuarios (mantenimientos, incidentes, noticias).

**Tipos:**

**MAINTENANCE (Mantenimiento):**
- Mantenimientos programados del sistema
- Requiere: fechas de inicio y fin, urgencia
- Ejemplo: "Mantenimiento del sistema bancario sábado 15/12 de 2am a 6am"

**INCIDENT (Incidente):**
- Problemas en curso que afectan el servicio
- Requiere: urgencia, estado de resolución
- Ejemplo: "Falla en red zona sur - Técnicos trabajando en solución"

**NEWS (Noticias):**
- Lanzamientos, actualizaciones, mejoras
- Ejemplo: "Nueva línea de productos lácteos PIL disponible"

**ALERT (Alerta):**
- Avisos urgentes que requieren acción del usuario
- Ejemplo: "URGENTE: Actualiza app móvil antes del 20/12"

**Estados:**
- DRAFT: Borrador
- SCHEDULED: Programado para fecha futura
- PUBLISHED: Publicado, visible
- ARCHIVED: Archivado

**Niveles de Urgencia:**
- LOW: Información general
- MEDIUM: Importante
- HIGH: Requiere atención
- CRITICAL: Urgente, acción inmediata

---

### 5.8 HelpCenterArticle (Artículo de Ayuda)

**Qué es:** Artículos de conocimiento para auto-servicio de usuarios.

**Objetivo:**
- Reducir volumen de tickets
- Documentar soluciones comunes
- Educar usuarios sobre productos/servicios

**Información:**
- Título y resumen
- Contenido (HTML)
- Categoría de artículo
- Estado (DRAFT/PUBLISHED/ARCHIVED)
- Contador de vistas

**Ejemplos:**
- "Cómo recuperar contraseña de banca online"
- "Pasos para activar tarjeta de crédito"
- "Configurar APN para datos móviles"
- "Política de devoluciones de productos lácteos"

---

## 6. FLUJO DE TRABAJO COMPLETO

### Ejemplo Real: Usuario con Problema en Banca Online

```
═══════════════════════════════════════════════════════════
ESCENARIO: No puedo hacer transferencias - Error 502
═══════════════════════════════════════════════════════════

LUNES 8:30 AM - USUARIO CREA TICKET (Estado: OPEN)
───────────────────────────────────────────────────────────
Ana (cliente de Banco Fassil):
- Login → Selecciona "Banco Fassil"
- Crea ticket:
  * Categoría: "Banca Online"
  * Prioridad: MEDIUM
  * Título: "No puedo transferir dinero - Error 502"
  * Descripción: "Desde esta mañana no puedo hacer transferencias..."
  * Adjunta: captura.png
- Sistema genera: TKT-2025-00142
- Estado: OPEN ← ticket nuevo, esperando agente

═══════════════════════════════════════════════════════════

LUNES 9:15 AM - AGENTE RESPONDE (OPEN → PENDING)
───────────────────────────────────────────────────────────
Pedro (agente de Banco Fassil):
- Ve notificación: "1 ticket nuevo"
- Abre TKT-2025-00142 → lee problema → ve captura
- Identifica: problema conocido del servidor
- Responde: "Hola Ana, identificamos el problema. Equipo técnico
  trabajando. Estimamos 2 horas de resolución."

Sistema (automático):
- Al enviar primera respuesta de AGENTE:
  ✓ Estado cambia: OPEN → PENDING
  ✓ Pedro se auto-asigna como owner del ticket
  ✓ Se registra first_response_at (métrica SLA)
- Notifica a Ana: "Tu ticket ha sido respondido"

═══════════════════════════════════════════════════════════

LUNES 10:00 AM - USUARIO AGREGA MÁS INFO
───────────────────────────────────────────────────────────
Ana:
- Recibe email → abre ticket
- Lee respuesta de Pedro
- Agrega respuesta: "También intenté desde app móvil, mismo error.
  ¿Hay alternativa temporal?"

Sistema:
- Crea nueva respuesta (autor: USER)
- Notifica a Pedro: "Ana respondió en TKT-2025-00142"

═══════════════════════════════════════════════════════════

LUNES 11:45 AM - AGENTE DA ALTERNATIVA
───────────────────────────────────────────────────────────
Pedro:
- Lee pregunta de Ana
- Agrega NOTA INTERNA (solo agentes ven):
  "Usuario reporta problema también en app. Backend confirmado.
   Coordinar con IT."
- Responde a Ana (público):
  "El problema afecta todos los canales digitales. Como alternativa
   temporal puedes hacer transferencias en ventanilla o cajero.
   Servicio estará restablecido al mediodía."

═══════════════════════════════════════════════════════════

LUNES 12:30 PM - PROBLEMA RESUELTO (PENDING → RESOLVED)
───────────────────────────────────────────────────────────
Pedro:
- IT reporta: servidor arreglado
- Prueba banca online → funciona ✓
- Responde: "Ana, problema solucionado. Ya puedes hacer transferencias
  normalmente. Disculpa las molestias."
- Marca: "Resolver ticket" ✓

Sistema:
- Estado cambia: PENDING → RESOLVED
- Se registra resolved_at (métrica)
- Notifica a Ana: "Tu ticket ha sido resuelto"
- Email a Ana: "¿Quedaste satisfecha? Califica el servicio"

═══════════════════════════════════════════════════════════

LUNES 1:00 PM - CALIFICACIÓN Y CIERRE (RESOLVED → CLOSED)
───────────────────────────────────────────────────────────
Ana:
- Prueba transferencia → funciona ✓
- Abre ticket → ve botón "Calificar"
- Califica:
  * Estrellas: ⭐⭐⭐⭐⭐ (5/5)
  * Comentario: "Excelente atención. Pedro muy amable y me mantuvo
    informada. Problema resuelto rápido."
- Cierra ticket ✓

Sistema:
- Guarda calificación (permanente)
- Estado cambia: RESOLVED → CLOSED
- Se registra closed_at
- Notifica a Pedro: "Ana calificó TKT-2025-00142: 5⭐"
- Dashboard de Pedro: rating promedio sube 4.4 → 4.5

═══════════════════════════════════════════════════════════

RESULTADO FINAL
───────────────────────────────────────────────────────────
Ticket: TKT-2025-00142 - CLOSED ✓

Métricas:
✓ Tiempo total: 4h 30m
✓ Tiempo primera respuesta: 45 minutos (SLA: ✓)
✓ Tiempo resolución: 4 horas
✓ Calificación: 5/5 estrellas
✓ Total respuestas: 4 (2 agente, 2 usuario)
✓ Notas internas: 1

Dashboard Company Admin:
✓ Tickets cerrados hoy: +1
✓ Rating promedio Pedro: 4.5/5 ↑
✓ SLA primera respuesta: 98%
✓ Satisfacción clientes: 4.7/5
```

---

## 7. CASOS DE USO POR INDUSTRIA

### 7.1 Banking (Banco Mercantil Santa Cruz)

**Categorías Típicas:**
- Tarjetas de Crédito/Débito
- Créditos Hipotecarios
- Banca Online y App Móvil
- Reclamos y Fraudes
- Consultas Generales

**Tickets Comunes:**
- "No puedo acceder a mi cuenta online" (Banca Online, MEDIUM)
- "Cobro indebido en mi tarjeta" (Reclamos, HIGH)
- "Quiero aumentar límite de mi tarjeta" (Tarjetas, LOW)
- "Mi app móvil no sincroniza" (Banca Online, MEDIUM)
- "No reconozco transacción de Bs 500" (Fraudes, HIGH)

**Anuncios:**
- MAINTENANCE: "Mantenimiento sistema bancario 15/12 de 2am a 6am"
- INCIDENT: "Problemas en cajeros zona norte - En resolución"
- NEWS: "Nuevo programa de puntos tarjetas Visa disponible"
- ALERT: "Actualiza token digital antes del 20/12"

**Agentes:**
- 15 agentes en "Atención al Cliente"
- 5 agentes en "Fraudes y Seguridad"
- 3 agentes en "Soporte Técnico IT"

---

### 7.2 Manufacturing (PIL Andina - Lácteos)

**Categorías Típicas:**
- Problema de Producción
- Control de Calidad
- Mantenimiento de Equipos
- Logística y Distribución
- Seguridad Alimentaria

**Áreas (Departamentos):**
- Producción Láctea
- Líneas de Empaque
- Control de Calidad y Laboratorio
- Logística y Almacenes
- Ventas y Distribución
- Mantenimiento y Seguridad
- Recursos Humanos

**Tickets Comunes:**
- "Máquina pasteurizadora 3 no arranca" (Mantenimiento, HIGH)
- "Lote 20251208-A con pH fuera de rango" (Calidad, HIGH)
- "Retraso en llegada de leche cruda zona Cochabamba" (Logística, MEDIUM)
- "Temperatura cámara fría B fuera de rango" (Producción, HIGH)
- "Falta de insumos para empaque de yogurt" (Producción, MEDIUM)

**Usuarios:**
- Supervisores de turno (crean tickets de problemas)
- Operadores de línea (reportan fallas de equipos)
- Coordinadores de calidad (tickets de control)

**Agentes:**
- Coordinadores técnicos
- Jefes de turno
- Especialistas de mantenimiento

---

### 7.3 Telecommunications (Tigo Bolivia)

**Categorías Típicas:**
- Falla de Servicio (Sin Señal)
- Cobertura de Red
- Facturación y Pagos
- Soporte Técnico Móvil
- Atención al Cliente

**Tickets Comunes:**
- "No tengo señal en mi zona desde ayer" (Falla, HIGH)
- "Mi factura tiene cargos que no reconozco" (Facturación, MEDIUM)
- "No puedo activar datos móviles 4G" (Soporte Técnico, MEDIUM)
- "Solicitud de portabilidad numérica" (Atención Cliente, LOW)
- "Internet muy lento en zona sur La Paz" (Cobertura, MEDIUM)

**Anuncios:**
- INCIDENT: "Caída red zona sur La Paz - Técnicos trabajando (2h estimado)"
- MAINTENANCE: "Mejora antena Villa Fátima 18/12 10pm-2am - Sin servicio temporal"
- NEWS: "Nuevo plan Tigo Ilimitado 5G disponible - 50% descuento primer mes"
- ALERT: "Bloqueo chip sin registro - Regulariza antes del 25/12 o perderás línea"

**Agentes:**
- 20 agentes call center (primer nivel)
- 8 agentes técnicos (segundo nivel)
- 3 agentes redes (fallas de infraestructura)

---

### 7.4 Energy (YPFB - Petróleo y Gas)

**Categorías Típicas:**
- Operaciones de Campo
- Mantenimiento de Infraestructura
- Seguridad Industrial
- Gestión Ambiental
- Logística de Distribución

**Áreas:**
- Exploración
- Producción
- Refinación
- Transporte y Ductos
- Comercialización
- QHSE (Calidad, Salud, Seguridad, Ambiente)
- Finanzas
- Legal y Regulatorio

**Tickets Comunes:**
- "Fuga menor en válvula pozo YPF-342" (Operaciones, HIGH)
- "Sensor presión ducto norte fuera de calibración" (Mantenimiento, MEDIUM)
- "Incidente menor en planta Río Grande - Derrame controlado" (Seguridad, HIGH)
- "Retraso entrega combustible estación Santa Cruz" (Logística, MEDIUM)
- "Reporte ambiental emisiones planta Cochabamba vencido" (Ambiental, MEDIUM)

**Prioridades (más estrictas):**
- LOW: Consultas administrativas
- MEDIUM: Mantenimiento preventivo
- HIGH: Fallas operativas, seguridad
- (Industria de alto riesgo = menos tickets LOW)

---

### 7.5 Food & Beverage (Cervecería Boliviana Nacional - CBN)

**Categorías Típicas:**
- Calidad de Producto
- Distribución y Logística
- Producción y Embotellado
- Reclamos de Cliente
- Mantenimiento de Equipos

**Tickets Comunes:**
- "Lote cerveza Paceña con sabor extraño - Cliente reporta" (Calidad, HIGH)
- "Camión refrigerado C-45 con falla en sistema de frío" (Distribución, HIGH)
- "Línea embotellado 2 atascada - Producción detenida" (Producción, HIGH)
- "Falta stock cerveza Huari en distribuidora Santa Cruz" (Logística, MEDIUM)
- "Etiquetas lote 20251208 con impresión borrosa" (Calidad, MEDIUM)

**Usuarios Típicos:**
- Supervisores de planta
- Coordinadores de logística
- Distribuidores (clientes B2B)
- Control de calidad

---

### 7.6 Retail/Supermarkets (Ketal)

**Categorías Típicas:**
- Reclamos de Cliente
- Problemas de Inventario
- Sistemas POS (Cajas)
- Logística Interna
- Recursos Humanos

**Tickets Comunes:**
- "Cliente reclama producto vencido comprado ayer" (Reclamos, HIGH)
- "Caja 5 no acepta pagos con tarjeta" (Sistemas POS, HIGH)
- "Falta reposición lácteos en refrigerador principal" (Inventario, MEDIUM)
- "Sistema de inventario muestra stock negativo sku 12345" (Sistemas, MEDIUM)

---

### 7.7 Healthcare (Hospital Arco Iris)

**Categorías Típicas:**
- Equipamiento Médico
- Sistemas IT (HIS, PACS)
- Farmacia y Medicamentos
- Infraestructura (Planta física)
- Recursos Humanos

**Tickets Comunes:**
- "Tomógrafo sala 3 no enciende" (Equipamiento, HIGH)
- "Sistema HIS no permite registrar pacientes nuevos" (Sistemas IT, HIGH)
- "Stock bajo de insulina en farmacia" (Farmacia, MEDIUM)
- "Aire acondicionado quirófano 2 fallando" (Infraestructura, HIGH)

**Usuarios:**
- Personal médico (doctores, enfermeras)
- Personal técnico (radiólogos, laboratoristas)
- Personal administrativo

---

## 8. DATOS PARA SEEDERS REALISTAS

### 8.1 Industrias (23 disponibles)

```
technology, healthcare, education, finance, banking, retail,
supermarket, manufacturing, food_and_beverage, beverage,
telecommunications, energy, pharmacy, electronics, veterinary,
real_estate, hospitality, transportation, professional_services,
media, agriculture, government, non_profit, other
```

### 8.2 Estructura de Datos Recomendada para Seeders

**Platform Admin:**
- 1 usuario: admin@helpdesk.com

**Empresas (5-10 empresas ejemplo):**
1. Banco Fassil (banking)
   - 15 agentes
   - 5 categorías
   - 200+ tickets

2. PIL Andina (food_and_beverage)
   - 8 agentes
   - 5 categorías, 8 áreas
   - 150+ tickets

3. Tigo Bolivia (telecommunications)
   - 20 agentes
   - 5 categorías
   - 300+ tickets

4. YPFB (energy)
   - 12 agentes
   - 5 categorías, 8 áreas
   - 100+ tickets

5. Cervecería CBN (beverage)
   - 10 agentes
   - 5 categorías
   - 120+ tickets

**Usuarios (50-100 usuarios finales):**
- Nombres bolivianos comunes
- Emails realistas
- Algunos con múltiples roles (AGENT en empresa A + USER)

**Tickets por Estado:**
- 10% OPEN (nuevos, sin respuesta)
- 30% PENDING (en atención)
- 40% RESOLVED (resueltos, esperando cierre)
- 20% CLOSED (cerrados con calificación)

**Tickets por Prioridad:**
- 60% MEDIUM (normal)
- 25% LOW (no urgente)
- 15% HIGH (urgente)

**Calificaciones:**
- 70% con rating 4-5 (servicio bueno/excelente)
- 20% con rating 3 (servicio regular)
- 10% con rating 1-2 (servicio malo)

**Respuestas por Ticket:**
- OPEN: 0 respuestas (nuevo)
- PENDING: 2-5 respuestas (conversación activa)
- RESOLVED: 3-8 respuestas (conversación completa)
- CLOSED: 3-10 respuestas + calificación

**Anuncios por Empresa:**
- 2 MAINTENANCE (programados, futuros)
- 2 INCIDENT (1 resuelto, 1 en curso)
- 3 NEWS (noticias recientes)
- 1 ALERT (alerta urgente)

**Artículos Centro de Ayuda:**
- 10-15 artículos por empresa
- Distribuidos en 3-4 categorías
- Títulos coherentes con industria

---

## 9. MÉTRICAS CLAVE DEL PRODUCTO

### Métricas de Empresa (Company Admin Dashboard)

**Volumen:**
- Total tickets creados
- Tickets abiertos (OPEN + PENDING)
- Tickets resueltos este mes
- Tickets cerrados este mes

**Performance:**
- Tiempo promedio primera respuesta (SLA)
- Tiempo promedio de resolución
- % tickets resueltos en <24h
- % tickets resueltos en <48h

**Satisfacción:**
- Rating promedio (1-5 estrellas)
- % tickets con calificación 4-5
- Total calificaciones recibidas

**Agentes:**
- Tickets por agente
- Rating promedio por agente
- Agente más eficiente

### Métricas de Agente (Agent Dashboard)

**Mis Tickets:**
- Tickets asignados a mí (OPEN + PENDING)
- Tickets resueltos este mes
- Tickets cerrados este mes

**Mi Performance:**
- Mi tiempo promedio de respuesta
- Mi tiempo promedio de resolución
- Mi rating promedio
- Mis tickets reabiertos (indica calidad de resolución)

### Métricas de Platform Admin

**Global:**
- Total empresas activas
- Total usuarios registrados
- Total tickets en plataforma
- Tickets creados este mes (tendencia)

**Por Industria:**
- Empresas por industria
- Tickets por industria
- Satisfacción por industria

---

## 10. VALOR DEL PRODUCTO

### Para Empresas (Clientes del Sistema)

✅ **Centralización:** Todos los tickets en un solo lugar
✅ **Organización:** Categorías personalizadas según su negocio
✅ **Eficiencia:** Asignación automática, historial completo
✅ **Métricas:** Datos para mejorar continuamente
✅ **Satisfacción:** Calificaciones miden calidad del servicio
✅ **Reducción de Costos:** Menos tickets mediante centro de ayuda
✅ **Comunicación Proactiva:** Anuncios evitan tickets de consulta

### Para Usuarios Finales

✅ **Facilidad:** Crear ticket en minutos
✅ **Seguimiento:** Ver estado y conversación completa
✅ **Transparencia:** Saber quién atiende, cuándo responden
✅ **Auto-Servicio:** Centro de ayuda para resolver dudas comunes
✅ **Información:** Anuncios de mantenimientos, incidentes

### Para Agentes

✅ **Claridad:** Ver todos los tickets en un dashboard
✅ **Priorización:** Filtrar por urgencia
✅ **Contexto:** Historial completo de conversación
✅ **Colaboración:** Notas internas, asignación a colegas
✅ **Reconocimiento:** Ratings miden su desempeño

---

## CONCLUSIÓN

**Enterprise Helpdesk System** es una plataforma completa, profesional y escalable para gestión de tickets de soporte multi-tenant que resuelve los problemas de:

1. **Desorganización** → Centralización total
2. **Falta de trazabilidad** → Historial completo con métricas
3. **Costos de infraestructura** → Multi-tenancy eficiente
4. **Roles confusos** → RBAC granular
5. **Sin datos** → Dashboards con métricas clave
6. **Comunicación fragmentada** → Conversación unificada

Diseñado para **23 industrias diferentes**, desde banca hasta manufactura, energía, telecomunicaciones, retail y más.

Con roles claros (Platform Admin → Company Admin → Agent → User), flujos bien definidos (OPEN → PENDING → RESOLVED → CLOSED), y características profesionales (auto-asignación, SLA tracking, ratings, centro de ayuda, anuncios).

**Ideal para generar seeders realistas** con datos coherentes por industria, tickets típicos del sector, conversaciones completas, y métricas representativas.
