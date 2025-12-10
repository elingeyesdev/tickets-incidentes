# ENTERPRISE HELPDESK SYSTEM - RESUMEN DEL NEGOCIO

## ¿QUÉ ES?

Plataforma SaaS multi-tenant donde **múltiples empresas** dan soporte a sus **usuarios/clientes** mediante un **sistema de tickets**. Cada empresa opera de forma independiente en su propio espacio aislado.

---

## ACTORES PRINCIPALES

### 1. PLATFORM_ADMIN
- Dueño de la plataforma
- Aprueba nuevas empresas que quieren usar el sistema
- Gestiona todas las empresas

### 2. COMPANY (Empresa)
- Organización que se registra en la plataforma
- Tiene su propio espacio aislado (multi-tenant)
- Cada empresa pertenece a una **industria**: banca, manufactura, telecomunicaciones, alimentos, energía, retail, salud, etc. (23 industrias disponibles)
- Configura: logo, colores, horarios de atención, categorías personalizadas

**Ejemplos:**
- Banco Fassil (industria: banking)
- PIL Andina (industria: food_and_beverage)
- Tigo Bolivia (industria: telecommunications)
- YPFB (industria: energy)

### 3. COMPANY_ADMIN
- Administrador de UNA empresa específica
- Configura la empresa
- Contrata agentes (empleados que atienden tickets)
- Crea categorías de tickets personalizadas según su negocio
- Publica anuncios y artículos de ayuda

### 4. AGENT (Agente)
- Empleado de la empresa
- Responde y resuelve tickets de usuarios
- Puede trabajar para UNA empresa

### 5. USER (Usuario Final/Cliente)
- Cliente que necesita soporte
- Crea tickets a empresas específicas
- Sigue empresas para recibir actualizaciones
- Califica el servicio recibido

---

## FLUJO DE REGISTRO DE EMPRESA

```
1. Interesado completa solicitud (CompanyRequest)
   ↓
2. PLATFORM_ADMIN revisa y aprueba/rechaza
   ↓
3. Si aprueba → Sistema crea Company + crea User admin + envía credenciales
   ↓
4. COMPANY_ADMIN hace login y configura empresa
```

---

## CATEGORÍAS DE TICKETS (Personalizadas por Empresa)

Cada empresa define sus propias categorías según su industria:

**Banco Fassil (banking):**
- Tarjetas de Crédito
- Créditos Hipotecarios
- Banca Online
- Reclamos
- Consultas Generales

**PIL Andina (food_and_beverage):**
- Problema de Producción
- Control de Calidad
- Mantenimiento de Equipos
- Logística y Distribución
- Seguridad Alimentaria

**Tigo Bolivia (telecommunications):**
- Falla de Servicio
- Cobertura de Red
- Facturación
- Soporte Técnico
- Atención al Cliente

**YPFB (energy):**
- Operaciones de Campo
- Mantenimiento de Infraestructura
- Seguridad Industrial
- Gestión Ambiental
- Logística de Distribución

---

## ÁREAS/DEPARTAMENTOS (Opcional)

Las empresas grandes pueden habilitar **áreas** para organizar mejor sus tickets por departamento:

**PIL Andina (manufactura láctea):**
- Producción Láctea
- Líneas de Empaque
- Control de Calidad y Laboratorio
- Logística y Almacenes
- Mantenimiento y Seguridad

**YPFB (energía):**
- Exploración
- Producción
- Refinación
- Transporte y Ductos
- QHSE (Calidad, Salud, Seguridad, Ambiente)

**Banco Fassil:**
- Atención al Cliente
- Mesa de Dinero
- IT y Sistemas
- Fraudes y Seguridad

---

## TICKETS (Sistema de Soporte)

### Creación
Un **USER** crea ticket dirigido a una **empresa** específica:
- Selecciona empresa
- Elige categoría (personalizada por empresa)
- Elige área/departamento (si empresa tiene habilitado)
- Escribe título y descripción del problema
- Adjunta archivos (capturas, documentos)
- Selecciona prioridad: LOW, MEDIUM, HIGH

### Ciclo de Vida

```
1. OPEN (Abierto)
   - Usuario crea ticket
   - Ningún agente ha respondido
   - Usuario puede editar

   ↓ AGENTE DA PRIMERA RESPUESTA

2. PENDING (En Atención)
   - Agente respondió → se AUTO-ASIGNA como responsable
   - Conversación activa (múltiples respuestas usuario-agente)
   - Usuario NO puede editar (solo responder)

   ↓ AGENTE MARCA COMO RESUELTO

3. RESOLVED (Resuelto)
   - Agente considera que solucionó el problema
   - Usuario puede:
     * Calificar servicio (1-5 estrellas + comentario)
     * Cerrar ticket
     * Reabrir si no está conforme

   ↓ USUARIO O AGENTE CIERRA

4. CLOSED (Cerrado)
   - Ticket finalizado
   - Usuario puede calificar si no lo hizo
   - Puede reabrirse (usuario: 30 días, agente: sin límite)
```

### Prioridades
- **LOW:** No urgente, puede esperar
- **MEDIUM:** Prioridad normal (default)
- **HIGH:** Requiere atención pronta

### Ejemplo Real

```
Usuario Ana (cliente Banco Fassil):
"No puedo hacer transferencias - Error 502"

↓ Crea ticket TKT-2025-00142
  Categoría: Banca Online
  Prioridad: MEDIUM
  Estado: OPEN

↓ Agente Pedro responde (45 min después)
  "Identificamos el problema. Equipo técnico trabajando. 2 horas estimado."
  Estado: OPEN → PENDING
  Pedro se auto-asigna

↓ Conversación (2-3 respuestas más)

↓ Pedro resuelve problema (4 horas después)
  "Problema solucionado. Ya puedes hacer transferencias."
  Estado: PENDING → RESOLVED

↓ Ana califica 5⭐
  "Excelente atención. Problema resuelto rápido."
  Estado: RESOLVED → CLOSED

Métricas:
- Tiempo primera respuesta: 45 min ✓
- Tiempo resolución: 4 horas ✓
- Calificación: 5/5 ✓
```

### Respuestas y Conversación
- Cada ticket tiene un **hilo de conversación**
- Usuario y agente intercambian **respuestas**
- Agentes pueden agregar **notas internas** (solo visibles para agentes/admins)
- Se pueden adjuntar **archivos** en respuestas

### Calificación (Rating)
- Usuario califica ticket cuando está RESOLVED o CLOSED
- **1-5 estrellas** + **comentario opcional**
- Solo puede calificar **una vez** (permanente)
- Mide satisfacción y rendimiento de agentes

---

## SEGUIR EMPRESAS (Followers)

Los **usuarios** pueden **seguir empresas** que les interesen:

**¿Para qué?**
- Recibir **anuncios** de esas empresas
- Ver **artículos** del centro de ayuda
- Estar informado de **mantenimientos** e **incidentes**
- Facilita crear tickets a empresas que siguen

**Ejemplo:**
- Ana sigue a: Banco Fassil, Tigo, ENTEL
- Recibe anuncios de esas 3 empresas
- No recibe anuncios de PIL, YPFB, etc.

---

## ANUNCIOS (Comunicaciones de Empresa a Usuarios)

Las empresas publican **anuncios** para informar a sus usuarios/clientes:

### Tipos de Anuncios

**1. MAINTENANCE (Mantenimiento)**
- Mantenimientos programados del sistema
- Requiere: fechas inicio/fin, urgencia
- **Ejemplo:** "Mantenimiento banca online sábado 15/12 de 2am a 6am - Servicios no disponibles"

**2. INCIDENT (Incidente)**
- Problemas en curso que afectan el servicio
- Requiere: urgencia, estado de resolución
- **Ejemplo:** "Falla en red zona sur La Paz - Técnicos trabajando en solución - 2h estimado"

**3. NEWS (Noticias)**
- Lanzamientos, actualizaciones, novedades
- **Ejemplo:** "Nueva línea de productos lácteos PIL - Yogurt sin lactosa disponible"

**4. ALERT (Alerta)**
- Avisos urgentes que requieren acción del usuario
- **Ejemplo:** "URGENTE: Actualiza app móvil antes del 20/12 para seguir operando"

### Estados de Anuncios
- **DRAFT:** Borrador (en edición)
- **SCHEDULED:** Programado para publicarse en fecha futura
- **PUBLISHED:** Publicado, visible para usuarios
- **ARCHIVED:** Archivado, ya no visible

### Niveles de Urgencia
- **LOW:** Información general
- **MEDIUM:** Importante
- **HIGH:** Requiere atención
- **CRITICAL:** Urgente, acción inmediata

### Ejemplos por Industria

**Banco Fassil:**
- MAINTENANCE: "Mantenimiento sistema bancario 15/12 de 2am a 6am"
- INCIDENT: "Problemas cajeros zona norte - En resolución"
- NEWS: "Nuevo programa puntos Visa - 2x puntos hasta fin de mes"
- ALERT: "Actualiza token digital antes del 20/12"

**Tigo Bolivia:**
- MAINTENANCE: "Mejora antena Villa Fátima 18/12 10pm-2am"
- INCIDENT: "Caída red zona sur - Técnicos trabajando"
- NEWS: "Nuevo plan 5G Ilimitado disponible - 50% descuento"
- ALERT: "Bloqueo chip sin registro - Regulariza antes del 25/12"

**PIL Andina:**
- MAINTENANCE: "Mantenimiento planta producción 20/12 - Sin despachos ese día"
- INCIDENT: "Retraso distribución zona Cochabamba por bloqueos - Normalización en 48h"
- NEWS: "Nuevo yogurt sin lactosa PIL - En puntos de venta desde hoy"
- ALERT: "Retiro voluntario lote 20251205-L por control de calidad"

---

## CENTRO DE AYUDA (Artículos de Auto-Servicio)

Las empresas publican **artículos de conocimiento** para que usuarios resuelvan dudas **sin crear ticket**:

### Objetivo
✅ Reducir volumen de tickets
✅ Documentar soluciones comunes
✅ Educar usuarios sobre productos/servicios
✅ Disponible 24/7

### Estructura
- Cada artículo pertenece a una **categoría**
- Tiene **título**, **resumen** y **contenido** (HTML)
- Estados: DRAFT, PUBLISHED, ARCHIVED
- Contador de **vistas** (popularidad)

### Ejemplos de Artículos

**Banco Fassil:**
- "Cómo recuperar contraseña de banca online"
- "Pasos para activar tarjeta de crédito nueva"
- "Cómo aumentar límite de tarjeta"
- "Configurar notificaciones de transacciones en app móvil"

**Tigo Bolivia:**
- "Configurar APN para datos móviles 4G"
- "Cómo consultar saldo y paquetes activos"
- "Activar roaming internacional"
- "Solucionar problemas de señal en zonas urbanas"

**PIL Andina:**
- "Política de devoluciones de productos lácteos"
- "Cómo identificar fecha de vencimiento en empaques"
- "Tabla nutricional productos PIL"
- "Puntos de venta autorizados PIL"

### Categorías de Artículos (Ejemplos)
- Primeros Pasos
- Configuración de Cuenta
- Solución de Problemas (Troubleshooting)
- Preguntas Frecuentes (FAQ)
- Políticas y Términos
- Guías de Usuario

---

## DATOS MULTI-TENANT (Aislamiento)

### ¿Qué significa Multi-Tenant?

Múltiples empresas comparten la **misma plataforma**, pero cada una tiene **datos completamente separados**:

**Empresa A (Banco Fassil):**
- 15 agentes
- 5 categorías: "Tarjetas", "Créditos", "Banca Online", "Reclamos", "Consultas"
- 245 tickets
- 8 anuncios
- 23 artículos

**Empresa B (PIL Andina):**
- 8 agentes
- 5 categorías: "Producción", "Calidad", "Mantenimiento", "Logística", "RRHH"
- 156 tickets
- 5 anuncios
- 15 artículos

**Aislamiento Total:**
- Agente de Banco Fassil **NUNCA** ve tickets de PIL
- Categorías son **completamente diferentes**
- Anuncios son **independientes**
- Métricas son **separadas**

---

## INDUSTRIAS DISPONIBLES (23)

```
technology          - Empresas de tecnología/SaaS/IT
healthcare          - Hospitales, clínicas
education           - Instituciones educativas
finance             - Empresas financieras
banking             - Bancos
retail              - Comercio retail
supermarket         - Supermercados
manufacturing       - Manufactura general
food_and_beverage   - Alimentos y bebidas
beverage            - Bebidas (cervecerías)
telecommunications  - Telecomunicaciones
energy              - Energía (petróleo, gas, electricidad)
pharmacy            - Farmacéuticas
electronics         - Electrónica
veterinary          - Veterinarias
real_estate         - Bienes raíces
hospitality         - Hoteles, turismo
transportation      - Transporte
professional_services - Servicios profesionales
media               - Medios, publicidad
agriculture         - Agricultura
government          - Gobierno
non_profit          - ONGs
other               - Otros
```

---

## MÉTRICAS IMPORTANTES

### Por Empresa (Company Admin Dashboard)
- Total tickets creados
- Tickets abiertos (OPEN + PENDING)
- Tickets resueltos este mes
- **Tiempo promedio primera respuesta** (SLA)
- **Tiempo promedio de resolución**
- **Rating promedio** (satisfacción del cliente)
- Tickets por agente
- Rating por agente

### Por Agente (Agent Dashboard)
- Mis tickets asignados
- Mis tickets resueltos este mes
- Mi tiempo promedio de respuesta
- Mi rating promedio
- Mis tickets reabiertos (indica calidad)

### Globales (Platform Admin)
- Total empresas activas
- Total usuarios en plataforma
- Total tickets en plataforma
- Tickets creados este mes (tendencia)

---

## RESUMEN PARA SEEDERS

### Estructura de Datos Recomendada

**1. Platform Admin:**
- 1 usuario: admin@helpdesk.com

**2. Empresas (5-8 empresas):**
- Banco Fassil (banking) - 15 agentes
- PIL Andina (food_and_beverage) - 8 agentes, áreas habilitadas
- Tigo Bolivia (telecommunications) - 20 agentes
- YPFB (energy) - 12 agentes, áreas habilitadas
- Ketal (supermarket) - 10 agentes
- Hospital Arco Iris (healthcare) - 8 agentes
- CBN Cervecería (beverage) - 10 agentes

**3. Usuarios (50-100 usuarios finales):**
- Nombres bolivianos comunes
- Algunos siguen empresas (followers)
- Algunos son también AGENT en empresas

**4. Tickets (150-300 tickets):**
- Distribuidos en empresas
- **10% OPEN** (nuevos, sin respuesta)
- **30% PENDING** (en atención, 2-5 respuestas)
- **40% RESOLVED** (resueltos, 3-8 respuestas)
- **20% CLOSED** (cerrados, calificados)

**5. Prioridades:**
- 60% MEDIUM
- 25% LOW
- 15% HIGH

**6. Calificaciones:**
- 70% rating 4-5 (bueno/excelente)
- 20% rating 3 (regular)
- 10% rating 1-2 (malo)

**7. Categorías:**
- 5 categorías por empresa, coherentes con industria

**8. Áreas (solo empresas grandes):**
- PIL: 8 áreas (producción, empaque, calidad, logística, etc.)
- YPFB: 8 áreas (exploración, producción, refinación, etc.)
- Banco Fassil: 4 áreas (atención cliente, fraudes, IT, mesa dinero)

**9. Anuncios (por empresa):**
- 2 MAINTENANCE (1 futuro, 1 pasado)
- 2 INCIDENT (1 resuelto, 1 en curso)
- 3 NEWS (noticias recientes)
- 1 ALERT (alerta urgente)

**10. Artículos Centro de Ayuda (por empresa):**
- 10-15 artículos
- 3-4 categorías de artículos
- Títulos coherentes con industria

**11. Tickets Coherentes por Industria:**

**Banco:**
- "No puedo acceder a banca online"
- "Cobro indebido en tarjeta"
- "App móvil no sincroniza"

**Manufactura (PIL):**
- "Máquina pasteurizadora no arranca"
- "Lote con pH fuera de rango"
- "Temperatura cámara fría fuera de rango"

**Telecoms:**
- "Sin señal en mi zona"
- "Factura con cargos no reconocidos"
- "No puedo activar datos 4G"

**Energía (YPFB):**
- "Fuga menor en válvula pozo"
- "Sensor presión fuera de calibración"
- "Retraso entrega combustible"

---

## FLUJO TÍPICO COMPLETO

```
1. PLATFORM_ADMIN aprueba solicitud de "Banco Fassil"
   ↓
2. Sistema crea empresa + COMPANY_ADMIN (admin@bancofassil.com)
   ↓
3. COMPANY_ADMIN configura:
   - Logo, colores
   - Categorías: Tarjetas, Créditos, Banca Online, Reclamos, Consultas
   - Contrata 15 agentes
   ↓
4. COMPANY_ADMIN publica:
   - 8 anuncios (mantenimientos, incidentes, noticias)
   - 23 artículos centro de ayuda
   ↓
5. USERS siguen a Banco Fassil (200 followers)
   ↓
6. USERS crean tickets (245 tickets total):
   - 25 OPEN (nuevos)
   - 74 PENDING (en atención)
   - 98 RESOLVED (resueltos)
   - 48 CLOSED (cerrados con rating)
   ↓
7. AGENTS responden tickets:
   - Auto-asignación con primera respuesta
   - Conversación hasta resolver
   - Marcan como RESOLVED
   ↓
8. USERS califican servicio:
   - 172 tickets con rating
   - Rating promedio: 4.6/5 estrellas
   ↓
9. Dashboard muestra métricas:
   - Tiempo primera respuesta: 38 min promedio
   - Tiempo resolución: 4.2 horas promedio
   - Satisfacción: 4.6/5
```

---

## RESUMEN EJECUTIVO

**Enterprise Helpdesk System** es una plataforma donde:

1. **Múltiples empresas** (banking, manufactura, telecoms, energía, retail, salud, etc.) dan soporte a sus usuarios mediante **tickets**

2. Cada empresa tiene **espacio aislado** (multi-tenant) con:
   - Agentes propios
   - Categorías personalizadas
   - Áreas/departamentos opcionales

3. **Usuarios** crean **tickets** a empresas específicas:
   - Estados: OPEN → PENDING → RESOLVED → CLOSED
   - Conversación completa usuario-agente
   - Calificaciones 1-5 estrellas

4. Empresas publican **anuncios** (mantenimientos, incidentes, noticias, alertas)

5. Empresas publican **artículos** de auto-servicio (centro de ayuda)

6. Usuarios **siguen empresas** para recibir actualizaciones

7. **Métricas** completas: tiempos de respuesta/resolución, ratings, volumen

**Objetivo:** Centralizar, organizar y medir el soporte al cliente con datos completamente aislados por empresa.
