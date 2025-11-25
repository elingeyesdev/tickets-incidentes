# PROMPT PROFESIONAL: Desarrollo de Aplicación Móvil Helpdesk - Rol USER

## 📋 CONTEXTO DEL PROYECTO

Eres un agente de desarrollo especializado en aplicaciones móviles con React Native y Expo. Tu misión es desarrollar una aplicación móvil completa, profesional y production-ready para el rol **USER** de un sistema de helpdesk empresarial multi-tenant.

### Stack Tecnológico Requerido

- **Framework**: React Native con Expo SDK ULTIMA VERSION (Expo Go compatible)
- **Lenguaje**: TypeScript estricto (`strict: true`)
- **Navegación**: Expo Router (file-based routing)
- **Estado Global**: Zustand con persistencia AsyncStorage
- **Formularios**: React Hook Form + Zod validation
- **HTTP Client**: Axios con interceptores
- **UI Components**: NativeWind (TailwindCSS) + React Native Paper
- **Notificaciones**: Expo Notifications
- **Almacenamiento Seguro**: expo-secure-store para tokens
- **Imágenes**: expo-image con cache
- **Gestos**: react-native-gesture-handler
- **Animaciones**: react-native-reanimated

### Información del Backend

- **Base URL**: Configurable via environment variable (`EXPO_PUBLIC_API_URL`)
- **Autenticación**: JWT Bearer Token (access token: 30 días, refresh en HttpOnly cookie)
- **Formato de Respuestas**: JSON con estructura `{ success: boolean, data: T, message?: string }`
- **Errores**: `{ message: string, errors?: Record<string, string[]> }`
- **IDs**: UUID v4
- **Fechas**: ISO 8601 (TIMESTAMPTZ)

---

## 🎯 ALCANCE FUNCIONAL EXCLUSIVO PARA ROL USER

El rol USER es un cliente que crea y gestiona tickets de soporte hacia empresas que sigue. NO tiene acceso a funcionalidades administrativas, de agente ni de gestión de empresas.

### Capacidades del Usuario:
1. Registrarse, autenticarse y gestionar su perfil
2. Explorar y seguir empresas disponibles en la plataforma
3. Crear tickets de soporte hacia empresas que sigue
4. Conversar con agentes a través de respuestas en tickets
5. Adjuntar archivos a tickets y respuestas
6. Calificar tickets resueltos
7. Consultar anuncios y artículos del centro de ayuda de empresas seguidas
8. Gestionar sesiones activas y preferencias

---

## 🔐 MÓDULO 1: AUTENTICACIÓN Y SEGURIDAD

### Endpoints Disponibles

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Registro de nuevo usuario |
| POST | `/api/auth/login` | No | Login con credenciales |
| POST | `/api/auth/refresh` | Header | Refrescar access token |
| POST | `/api/auth/logout` | JWT | Cerrar sesión actual o todas |
| POST | `/api/auth/password-reset` | No | Solicitar reset de contraseña |
| POST | `/api/auth/password-reset/confirm` | No | Confirmar reset con token/código |
| POST | `/api/auth/email/verify` | No | Verificar email con token |
| GET | `/api/auth/status` | JWT | Estado de autenticación |
| GET | `/api/auth/sessions` | JWT | Listar sesiones activas |
| DELETE | `/api/auth/sessions/{id}` | JWT | Revocar sesión específica |

### Pantallas Requeridas

#### 1.1 Splash Screen
- Logo animado del sistema
- Verificación automática de token almacenado
- Redirección inteligente: si hay token válido → Home, sino → Welcome

#### 1.2 Welcome Screen
- Diseño atractivo con ilustraciones
- Breve descripción del valor del sistema
- Botones: "Iniciar Sesión" y "Crear Cuenta"
- Link discreto: "Explorar sin cuenta" (solo ver empresas públicas)

#### 1.3 Login Screen
- **Campos**:
  - Email (validación RFC5322, case-insensitive)
  - Contraseña (mínimo 8 caracteres, ocultar/mostrar)
  - Checkbox "Recordar dispositivo"
- **Acciones**:
  - Botón "Iniciar Sesión" con loading state
  - Link "¿Olvidaste tu contraseña?"
  - Link "¿No tienes cuenta? Regístrate"
- **Comportamiento**:
  - Almacenar `accessToken` en expo-secure-store
  - Capturar automáticamente nombre del dispositivo
  - Manejo de errores: credenciales inválidas, cuenta suspendida, email no verificado

#### 1.4 Register Screen
- **Campos obligatorios**:
  - Email (único en sistema)
  - Contraseña (mín 8 chars, letras + números + símbolos)
  - Confirmar contraseña
  - Nombre
  - Apellido
  - Checkbox "Acepto términos de servicio" (requerido)
  - Checkbox "Acepto política de privacidad" (requerido)
- **Validaciones en tiempo real** con feedback visual
- **Post-registro**: Mostrar mensaje de verificación de email pendiente

#### 1.5 Forgot Password Screen
- Input de email
- Botón "Enviar enlace de recuperación"
- Mensaje de éxito genérico (seguridad: no revelar si email existe)

#### 1.6 Reset Password Screen (deep link)
- Acceso via deep link con token o ingreso manual de código 6 dígitos
- **Campos**: Nueva contraseña, Confirmar contraseña
- Post-reset: Auto-login y redirección a Home

#### 1.7 Email Verification Screen (deep link)
- Procesamiento automático del token desde URL
- Estados: Verificando, Éxito, Error (token expirado/inválido)
- Opción de reenviar email de verificación

#### 1.8 Active Sessions Screen (desde Configuración)
- Lista de todas las sesiones activas del usuario
- Por cada sesión mostrar:
  - Nombre del dispositivo
  - IP (parcialmente oculta por privacidad)
  - Fecha de último uso
  - Badge "Sesión actual" para la sesión en uso
- Acción: Deslizar para revocar sesión (no permitir revocar la actual)
- Botón: "Cerrar todas las demás sesiones"

### Gestión de Tokens (Implementación Crítica)

```typescript
// Estructura requerida del AuthStore (Zustand)
interface AuthState {
  accessToken: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  
  // Actions
  login: (email: string, password: string) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: (everywhere?: boolean) => Promise<void>;
  refreshToken: () => Promise<boolean>;
  checkAuth: () => Promise<void>;
}
```

**Interceptor de Axios requerido**:
1. Inyectar `Authorization: Bearer {token}` en cada request autenticado
2. Interceptar respuestas 401
3. Intentar refresh automático UNA vez
4. Si refresh falla → limpiar estado y redirigir a Login
5. Reintentar request original si refresh exitoso

---

## 👤 MÓDULO 2: PERFIL DE USUARIO

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/users/me` | Información completa del usuario autenticado |
| GET | `/api/users/me/profile` | Perfil detallado (nombre, avatar, preferencias) |
| PATCH | `/api/users/me/profile` | Actualizar información personal |
| PATCH | `/api/users/me/preferences` | Actualizar preferencias (tema, idioma, notificaciones) |

### Estructura de Datos del Usuario

```typescript
interface User {
  id: string; // UUID
  userCode: string; // USR-2025-00001
  email: string;
  status: 'ACTIVE' | 'SUSPENDED' | 'DELETED';
  emailVerified: boolean;
  emailVerifiedAt: string | null;
  lastLoginAt: string | null;
  createdAt: string;
  
  profile: {
    firstName: string;
    lastName: string;
    displayName: string; // Calculado: firstName + lastName
    phoneNumber: string | null;
    avatarUrl: string | null;
    theme: 'light' | 'dark';
    language: 'es' | 'en';
    timezone: string;
    pushWebNotifications: boolean;
    notificationsTickets: boolean;
  };
  
  roleContexts: Array<{
    roleCode: 'USER';
    roleName: string;
    dashboardPath: string;
    company: null; // USER no tiene empresa asociada
  }>;
  
  statistics: {
    totalTickets: number;
    openTickets: number;
    resolvedTickets: number;
    followedCompanies: number;
  };
}
```

### Pantallas Requeridas

#### 2.1 Profile Screen (Tab principal)
- **Header con avatar**: Foto de perfil (tap para cambiar) o iniciales si no hay
- **Información principal**:
  - Nombre completo editable inline
  - Email (solo lectura con badge de verificación)
  - Teléfono (opcional)
  - Miembro desde: fecha formateada
- **Estadísticas rápidas** (cards horizontales):
  - Total tickets creados
  - Tickets abiertos actualmente
  - Empresas que sigue
- **Secciones de navegación**:
  - "Editar Perfil" → ProfileEditScreen
  - "Preferencias" → PreferencesScreen
  - "Sesiones Activas" → SessionsScreen
  - "Cambiar Contraseña" → ChangePasswordScreen
  - "Cerrar Sesión" (con confirmación)

#### 2.2 Profile Edit Screen
- **Campos editables**:
  - Nombre (mín 2 caracteres)
  - Apellido (mín 2 caracteres)
  - Teléfono (formato internacional opcional)
  - Avatar (selección desde galería o cámara)
- **Validación en tiempo real**
- **Botón "Guardar cambios"** con loading state
- **Feedback visual**: Toast de éxito o error

#### 2.3 Preferences Screen
- **Apariencia**:
  - Toggle tema claro/oscuro (aplicación inmediata)
  - Selector de idioma (es/en)
  - Selector de zona horaria (lista desplegable con búsqueda)
- **Notificaciones**:
  - Toggle "Notificaciones push"
  - Toggle "Notificaciones de tickets"
- Cada cambio guarda automáticamente (debounce 500ms)

#### 2.4 Change Password Screen
- Campo: Contraseña actual
- Campo: Nueva contraseña (con indicador de fortaleza)
- Campo: Confirmar nueva contraseña
- Validaciones:
  - Mínimo 8 caracteres
  - Al menos una letra, número y símbolo
  - Coincidir con confirmación
- Post-éxito: Cerrar todas las demás sesiones (opcional)

---

## 🏢 MÓDULO 3: GESTIÓN DE EMPRESAS

### Endpoints Disponibles

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/companies/minimal` | No | Lista pública de empresas (nombre, logo) |
| GET | `/api/companies/explore` | JWT | Explorar empresas con filtros |
| GET | `/api/companies/{id}` | JWT | Detalle completo de empresa |
| GET | `/api/companies/{id}/is-following` | JWT | Verificar si sigue la empresa |
| POST | `/api/companies/{id}/follow` | JWT | Seguir empresa |
| DELETE | `/api/companies/{id}/unfollow` | JWT | Dejar de seguir |
| GET | `/api/company-industries` | No | Catálogo de industrias |

### Estructura de Datos

```typescript
interface Company {
  id: string;
  companyCode: string; // CMP-2025-00001
  name: string;
  legalName: string | null;
  description: string | null;
  supportEmail: string;
  phone: string | null;
  website: string | null;
  logoUrl: string | null;
  primaryColor: string; // #007bff
  industry: {
    id: string;
    name: string;
  } | null;
  businessHours: Record<string, { open: string; close: string }>;
  timezone: string;
  status: 'active' | 'suspended';
  createdAt: string;
  
  // Para usuarios autenticados
  isFollowing?: boolean;
  followedAt?: string;
  statistics?: {
    myTicketsCount: number;
    lastTicketCreatedAt: string | null;
    hasUnreadAnnouncements: boolean;
  };
}

interface CompanyExploreFilters {
  search?: string;
  industry_id?: string;
  country?: string;
  followed_by_me?: boolean;
  sort_by?: 'name' | 'followers_count' | 'created_at';
  sort_direction?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}
```

### Pantallas Requeridas

#### 3.1 Explore Companies Screen (Tab principal)
- **Barra de búsqueda** sticky con icono y placeholder "Buscar empresas..."
- **Filtros rápidos** (chips horizontales scrolleables):
  - "Todas"
  - "Siguiendo" (filter: followed_by_me=true)
  - Por industria (dropdown)
- **Lista de empresas** (FlatList optimizada):
  - Card por empresa con:
    - Logo (o placeholder con inicial)
    - Nombre
    - Industria
    - Indicador "Siguiendo" (badge verde)
    - Estadísticas: "X tickets creados" si sigue
  - Pull-to-refresh
  - Infinite scroll con loading indicator
- **Empty states**:
  - Sin resultados de búsqueda
  - Sin empresas seguidas (CTA para explorar)

#### 3.2 Company Detail Screen
- **Header hero**:
  - Logo grande centrado
  - Nombre de empresa
  - Industria como badge
  - Colores de marca aplicados al header
- **Acciones principales**:
  - Botón "Seguir" / "Siguiendo" (toggle animado)
  - Si sigue: Botón "Crear Ticket" prominente
- **Información de contacto**:
  - Email de soporte (tap para copiar)
  - Teléfono (tap para llamar)
  - Website (tap para abrir navegador)
- **Horario de atención**:
  - Lista de días con horarios
  - Indicador "Abierto ahora" / "Cerrado"
  - Zona horaria de la empresa
- **Descripción** (si existe, con expand/collapse)
- **Mis tickets en esta empresa** (si sigue):
  - Lista compacta de últimos 3 tickets
  - Link "Ver todos mis tickets"
- **Anuncios recientes** (si sigue):
  - Últimos 3 anuncios
  - Link "Ver todos los anuncios"

#### 3.3 My Followed Companies Screen
- Lista filtrada de empresas que el usuario sigue
- Quick actions por empresa:
  - "Crear ticket"
  - "Ver anuncios"
  - "Dejar de seguir" (confirmación)
- Ordenamiento: Más reciente seguida primero

---

## 🎫 MÓDULO 4: GESTIÓN DE TICKETS (CORE)

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tickets` | Listar tickets del usuario (paginado) |
| POST | `/api/tickets` | Crear nuevo ticket |
| GET | `/api/tickets/{ticket_code}` | Detalle de ticket |
| POST | `/api/tickets/{ticket_code}/reopen` | Reabrir ticket cerrado |
| GET | `/api/tickets/categories?company_id={uuid}` | Categorías por empresa |
| POST | `/api/tickets/{ticket_code}/rate` | Calificar ticket resuelto |

### Estructura de Datos

```typescript
interface Ticket {
  id: string;
  ticketCode: string; // TKT-2025-00001
  title: string;
  description: string; // initial_description
  status: 'open' | 'pending' | 'resolved' | 'closed';
  lastResponseAuthorType: 'none' | 'user' | 'agent';
  
  company: {
    id: string;
    name: string;
    logoUrl: string | null;
  };
  
  category: {
    id: string;
    name: string;
  } | null;
  
  createdBy: {
    id: string;
    displayName: string;
  };
  
  ownerAgent: {
    id: string;
    displayName: string;
    avatarUrl: string | null;
  } | null;
  
  rating: {
    rating: number; // 1-5
    comment: string | null;
    createdAt: string;
  } | null;
  
  attachmentsCount: number;
  responsesCount: number;
  
  createdAt: string;
  updatedAt: string;
  firstResponseAt: string | null;
  resolvedAt: string | null;
  closedAt: string | null;
}

interface TicketFilters {
  status?: 'open' | 'pending' | 'resolved' | 'closed';
  company_id?: string;
  search?: string;
  sort_by?: 'created_at' | 'updated_at' | 'status';
  sort_direction?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}

interface CreateTicketData {
  company_id: string;
  category_id?: string;
  title: string; // min 5, max 255 chars
  description: string; // required
}

interface TicketCategory {
  id: string;
  name: string;
  description: string | null;
  isActive: boolean;
  ticketsCount: number; // tickets activos en esta categoría
}
```

### Pantallas Requeridas

#### 4.1 My Tickets Screen (Tab principal)
- **Header con estadísticas**:
  - Tarjetas: Total, Abiertos, Pendientes, Resueltos
- **Filtros** (segmented control):
  - Todos | Abiertos | Pendientes | Resueltos | Cerrados
- **Barra de búsqueda**: Buscar por código o título
- **Lista de tickets** (FlatList optimizada):
  - **Card de ticket**:
    - Código prominente (TKT-2025-00001)
    - Título (2 líneas máximo, ellipsis)
    - Badge de estado con color:
      - 🟢 ABIERTO (verde)
      - 🟡 PENDIENTE (amarillo)
      - 🔵 RESUELTO (azul)
      - ⚫ CERRADO (gris)
    - Logo de empresa + nombre
    - Tiempo transcurrido ("hace 2 horas")
    - Indicador de última respuesta (icono usuario/agente)
    - Badge si hay respuesta de agente sin leer
  - Pull-to-refresh
  - Infinite scroll
- **FAB "Crear Ticket"** (floating action button)
- **Empty states**:
  - Sin tickets: Ilustración + CTA "Crear tu primer ticket"
  - Sin resultados de filtro

#### 4.2 Create Ticket Screen (Flow de 3 pasos)

**Paso 1: Seleccionar Empresa**
- Lista de empresas que el usuario sigue
- Cada empresa muestra: logo, nombre, último ticket creado
- Barra de búsqueda si tiene muchas empresas
- **Si no sigue ninguna empresa**: Mensaje con CTA a Explorar

**Paso 2: Consultar Centro de Ayuda** (Opcional pero recomendado)
- Mensaje: "Antes de crear un ticket, revisa si ya existe una solución"
- Buscador de artículos de la empresa seleccionada
- Lista de artículos sugeridos/populares
- Botón "No encontré solución, continuar"
- Botón "Encontré solución, cancelar ticket"

**Paso 3: Formulario de Ticket**
- **Campos**:
  - Empresa seleccionada (solo lectura, con opción de cambiar)
  - Categoría (dropdown con categorías activas de la empresa)
  - Título (min 5, max 255 caracteres, contador visible)
  - Descripción (textarea, sin límite, con toolbar de formato básico)
  - Archivos adjuntos (máx 5 archivos, 10MB c/u)
    - Preview de archivos seleccionados
    - Botón para eliminar cada archivo
- **Preview en tiempo real** (panel colapsable)
- **Validación completa antes de enviar**
- **Botón "Crear Ticket"** con loading state
- **Post-creación**: 
  - Animación de éxito
  - Mostrar código de ticket generado
  - Opciones: "Ver ticket" o "Crear otro"

#### 4.3 Ticket Detail Screen
- **Header**:
  - Código de ticket prominente
  - Badge de estado grande
  - Botón compartir (copiar link/código)
- **Timeline visual** del ciclo de vida:
  - Creado → Primera respuesta → Resuelto → Cerrado
  - Con fechas en cada punto alcanzado
- **Card de información**:
  - Título completo
  - Empresa (tap para ir a detalle)
  - Categoría
  - Fecha de creación (formato completo)
  - Última actualización
- **Agente asignado** (si existe):
  - Avatar, nombre
  - Mensaje contextual: "está atendiendo tu ticket"
- **Acciones según estado**:
  - Si `resolved` y sin rating: Modal de calificación
  - Si `closed` y < 30 días: Botón "Reabrir ticket"
- **Tabs de contenido**:
  - **Conversación**: Lista de respuestas (ver 4.4)
  - **Adjuntos**: Galería de archivos (ver 4.6)
  - **Información**: Datos técnicos del ticket

#### 4.4 Ticket Conversation (Tab dentro de Ticket Detail)
- **Descripción inicial** como primer mensaje (estilo burbuja diferenciada)
- **Lista de respuestas** ordenadas cronológicamente:
  - **Burbuja de mensaje**:
    - Avatar del autor
    - Nombre y tipo (Usuario/Agente badge)
    - Contenido del mensaje
    - Fecha y hora
    - Adjuntos inline (previews)
  - Diferenciación visual: mensajes propios a la derecha, agente a la izquierda
- **Input de respuesta** (sticky bottom):
  - Textarea autoexpandible
  - Botón adjuntar archivo
  - Botón enviar
  - Disabled si ticket está `closed`
  - Mensaje informativo si ticket `resolved`: "Responder reabrirá el ticket"

#### 4.5 Rate Ticket Modal
- Aparece automáticamente al abrir ticket `resolved` sin calificación
- **Componentes**:
  - 5 estrellas interactivas (1-5)
  - Textarea para comentario opcional (max 500 chars)
  - Botón "Enviar calificación"
  - Link "Omitir por ahora"
- **Post-rating**: Agradecimiento con animación

#### 4.6 Ticket Attachments Tab/Screen
- **Galería de adjuntos** (grid 2 columnas):
  - Thumbnail para imágenes
  - Icono + nombre para documentos
  - Tamaño del archivo
  - Quien lo subió y cuándo
- **Acciones por archivo**:
  - Tap: Vista previa (imágenes) o descarga (documentos)
  - Long press: Menú con "Descargar" / "Eliminar" (solo propios)
- **Tipos permitidos**: jpg, jpeg, png, gif, bmp, webp, svg, pdf, txt, log, doc, docx, xls, xlsx, csv, mp4
- **Restricciones**: Max 10MB por archivo, max 5 adjuntos por ticket

---

## 💬 MÓDULO 5: RESPUESTAS DE TICKETS

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tickets/{ticket}/responses` | Listar respuestas |
| POST | `/api/tickets/{ticket}/responses` | Crear respuesta |
| GET | `/api/tickets/{ticket}/responses/{id}` | Detalle de respuesta |

### Estructura de Datos

```typescript
interface TicketResponse {
  id: string;
  ticketId: string;
  authorId: string;
  content: string;
  authorType: 'user' | 'agent';
  createdAt: string;
  
  author: {
    id: string;
    displayName: string;
    avatarUrl: string | null;
  };
  
  attachments: Attachment[];
}

interface CreateResponseData {
  content: string; // max 5000 chars
}
```

### Comportamiento Importante

- **Usuario crea respuesta**:
  - `author_type` se asigna automáticamente como `'user'`
  - Si ticket estaba `pending`, vuelve a `open` (trigger automático del backend)
  - No se puede responder a tickets `closed`

---

## 📎 MÓDULO 6: ARCHIVOS ADJUNTOS

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/tickets/{ticket}/attachments` | Listar adjuntos |
| POST | `/api/tickets/{ticket}/attachments` | Subir adjunto |
| GET | `/api/tickets/{ticket}/attachments/{id}/download` | Descargar |
| DELETE | `/api/tickets/{ticket}/attachments/{id}` | Eliminar (solo propios) |
| POST | `/api/tickets/{ticket}/responses/{id}/attachments` | Adjuntar a respuesta |

### Estructura de Datos

```typescript
interface Attachment {
  id: string;
  ticketId: string;
  responseId: string | null;
  fileName: string;
  fileUrl: string;
  fileType: string; // MIME type
  fileSizeBytes: number;
  uploadedBy: {
    id: string;
    displayName: string;
  };
  createdAt: string;
}
```

### Restricciones del Sistema

- **Tamaño máximo por archivo**: 10MB
- **Máximo adjuntos por ticket**: 5
- **Tipos permitidos**:
  - Documentos: pdf, txt, log, doc, docx, xls, xlsx, csv
  - Imágenes: jpg, jpeg, png, gif, bmp, webp, svg
  - Video: mp4
- **Adjuntos a respuestas**: Solo dentro de 30 minutos de creada la respuesta

---

## 📢 MÓDULO 7: CONTENIDO (ANUNCIOS Y ARTÍCULOS)

### Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/announcements` | Listar anuncios de empresas seguidas |
| GET | `/api/announcements/{id}` | Detalle de anuncio |
| GET | `/api/help-center/articles` | Artículos de ayuda |
| GET | `/api/help-center/articles/{id}` | Detalle de artículo |
| GET | `/api/help-center/categories` | Categorías de artículos |

### Estructura de Datos

```typescript
interface Announcement {
  id: string;
  companyId: string;
  title: string;
  content: string;
  type: 'MAINTENANCE' | 'INCIDENT' | 'NEWS' | 'ALERT';
  status: 'PUBLISHED';
  publishedAt: string;
  metadata: {
    // Varía según tipo
    urgency?: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
    scheduled_start?: string;
    scheduled_end?: string;
    affected_services?: string[];
    resolution_summary?: string;
  };
  company: {
    id: string;
    name: string;
    logoUrl: string | null;
  };
}

interface HelpArticle {
  id: string;
  companyId: string;
  title: string;
  excerpt: string; // max 500 chars
  content: string;
  status: 'PUBLISHED';
  viewsCount: number;
  publishedAt: string;
  category: {
    id: string;
    code: 'ACCOUNT_PROFILE' | 'SECURITY_PRIVACY' | 'BILLING_PAYMENTS' | 'TECHNICAL_SUPPORT';
    name: string;
  };
  company: {
    id: string;
    name: string;
  };
}
```

### Pantallas Requeridas

#### 7.1 Announcements Screen
- **Filtros por tipo** (chips):
  - Todos | Mantenimiento | Incidentes | Noticias | Alertas
- **Filtro por empresa** (dropdown con empresas seguidas)
- **Lista de anuncios**:
  - Card con:
    - Tipo (icono + color distintivo):
      - 🔧 Mantenimiento (naranja)
      - ⚠️ Incidente (rojo)
      - 📰 Noticias (azul)
      - 🚨 Alerta (púrpura)
    - Título
    - Empresa
    - Fecha de publicación
    - Badge de urgencia si aplica
- **Empty state**: "No hay anuncios de las empresas que sigues"

#### 7.2 Announcement Detail Screen
- **Header con tipo** (color de fondo según tipo)
- **Información**:
  - Título
  - Empresa (con logo)
  - Fecha de publicación
  - Urgencia (si aplica)
- **Contenido** (renderizado markdown/HTML)
- **Metadata específica por tipo**:
  - Mantenimiento: Fechas programadas, servicios afectados
  - Incidente: Estado, timeline de actualizaciones
  - Alerta: Acciones requeridas, servicios afectados

#### 7.3 Help Center Screen
- **Barra de búsqueda** prominente
- **Selector de empresa** (si sigue más de una)
- **Categorías** (4 fijas):
  - 👤 Cuenta y Perfil
  - 🔒 Seguridad y Privacidad
  - 💳 Facturación y Pagos
  - 🔧 Soporte Técnico
- **Artículos por categoría** (collapsable sections)
- **Artículos populares** (ordenados por viewsCount)

#### 7.4 Article Detail Screen
- **Breadcrumb**: Help Center > Categoría > Artículo
- **Título**
- **Metadata**: Categoría, vistas, última actualización
- **Contenido** (renderizado markdown/HTML con estilos)
- **Acciones**:
  - "¿Te fue útil?" (like/dislike - si existe endpoint)
  - "Crear ticket sobre esto" (pre-llenar contexto)
- **Artículos relacionados** (misma categoría)

---

## 🧭 ARQUITECTURA DE NAVEGACIÓN

### Estructura de Tabs (Bottom Navigation)

```
Tab 1: 🏠 Inicio (Home)
  └── HomeScreen (resumen general)
  
Tab 2: 🎫 Tickets
  └── MyTicketsScreen
      └── CreateTicketFlow (modal/stack)
      └── TicketDetailScreen
          └── ConversationTab
          └── AttachmentsTab
          └── InfoTab
          
Tab 3: 🏢 Empresas
  └── ExploreCompaniesScreen
      └── CompanyDetailScreen
      └── MyFollowedCompaniesScreen
      
Tab 4: 📢 Contenido
  └── AnnouncementsScreen
      └── AnnouncementDetailScreen
  └── HelpCenterScreen
      └── ArticleDetailScreen
      
Tab 5: 👤 Perfil
  └── ProfileScreen
      └── ProfileEditScreen
      └── PreferencesScreen
      └── SessionsScreen
      └── ChangePasswordScreen
```

### Flujos de Autenticación (Stack separado)

```
AuthStack:
  ├── WelcomeScreen
  ├── LoginScreen
  ├── RegisterScreen
  ├── ForgotPasswordScreen
  ├── ResetPasswordScreen (deep link)
  └── EmailVerificationScreen (deep link)
```

### Deep Links Requeridos

- `helpdesk://verify-email?token={token}` → EmailVerificationScreen
- `helpdesk://reset-password?token={token}` → ResetPasswordScreen
- `helpdesk://ticket/{ticketCode}` → TicketDetailScreen
- `helpdesk://company/{companyId}` → CompanyDetailScreen
- `helpdesk://announcement/{id}` → AnnouncementDetailScreen

---

## 🎨 ESPECIFICACIONES DE UX/UI

### Principios de Diseño

1. **Mobile-First**: Diseñado exclusivamente para interacción táctil
2. **Accesibilidad**: Contraste adecuado, áreas de toque mínimo 44x44px
3. **Feedback inmediato**: Loading states, animaciones de transición
4. **Offline-First**: Indicador de conexión, caché de datos críticos
5. **Consistencia**: Patrones repetidos en toda la app

### Sistema de Colores

```typescript
const colors = {
  primary: '#007bff',    // Acciones principales
  secondary: '#6c757d',  // Acciones secundarias
  success: '#28a745',    // Estados exitosos, ticket resuelto
  warning: '#ffc107',    // Atención, ticket pendiente
  danger: '#dc3545',     // Errores, alertas críticas
  info: '#17a2b8',       // Información
  
  // Estados de tickets
  ticketOpen: '#28a745',
  ticketPending: '#ffc107',
  ticketResolved: '#17a2b8',
  ticketClosed: '#6c757d',
  
  // Tipos de anuncios
  maintenance: '#fd7e14',
  incident: '#dc3545',
  news: '#007bff',
  alert: '#6f42c1',
  
  // Backgrounds
  background: '#f8f9fa',
  surface: '#ffffff',
  
  // Text
  textPrimary: '#212529',
  textSecondary: '#6c757d',
  textDisabled: '#adb5bd',
};
```

### Tipografía

- **Títulos principales**: 24px, Bold
- **Títulos secundarios**: 18px, SemiBold
- **Cuerpo de texto**: 16px, Regular
- **Subtexto/Captions**: 14px, Regular
- **Labels pequeños**: 12px, Medium

### Componentes Reutilizables Requeridos

1. **Button**: Primary, Secondary, Outline, Ghost, Danger variants
2. **Input**: Text, Password, Email, Phone, Textarea
3. **Select/Dropdown**: Simple y con búsqueda
4. **Card**: Elevation, border variants
5. **Badge**: Colores por estado
6. **Avatar**: Con imagen, con iniciales, con placeholder
7. **TicketCard**: Componente específico para listar tickets
8. **CompanyCard**: Componente para listar empresas
9. **MessageBubble**: Para conversación de tickets
10. **EmptyState**: Ilustración + mensaje + CTA
11. **LoadingState**: Skeletons y spinners
12. **ErrorState**: Con botón de reintentar
13. **Toast/Snackbar**: Feedback de acciones
14. **Modal/BottomSheet**: Para formularios y confirmaciones
15. **StarRating**: Input de 1-5 estrellas

### Estados de Pantalla Obligatorios

Cada pantalla con carga de datos debe manejar:
1. **Loading**: Skeleton loaders o spinner centrado
2. **Error**: Mensaje + botón reintentar
3. **Empty**: Ilustración + mensaje + CTA
4. **Success**: Contenido normal
5. **Offline**: Indicador + datos cacheados si disponibles

### Animaciones Requeridas

- Transiciones entre pantallas (slide, fade)
- Botones: feedback táctil (scale down)
- Listas: animación de entrada escalonada
- Pull-to-refresh: animación de recarga
- Toasts: slide in/out
- Modales: fade + scale

---

## 📱 CONSIDERACIONES TÉCNICAS

### Gestión de Estado (Zustand)

```typescript
// Stores requeridos
stores/
  ├── authStore.ts       // Autenticación y tokens
  ├── userStore.ts       // Datos del usuario
  ├── ticketStore.ts     // Tickets y caché
  ├── companyStore.ts    // Empresas y follows
  ├── contentStore.ts    // Anuncios y artículos
  └── uiStore.ts         // Estados UI (loading global, modals)
```

### Estructura de Carpetas Recomendada

```
src/
├── app/                    # Expo Router screens
│   ├── (auth)/            # Auth stack
│   ├── (tabs)/            # Main tab navigation
│   └── _layout.tsx        # Root layout
├── components/
│   ├── ui/                # Componentes base
│   ├── tickets/           # Componentes de tickets
│   ├── companies/         # Componentes de empresas
│   └── common/            # Componentes compartidos
├── hooks/                  # Custom hooks
├── services/
│   ├── api/               # Cliente Axios + endpoints
│   └── storage/           # AsyncStorage + SecureStore
├── stores/                # Zustand stores
├── types/                 # TypeScript interfaces
├── utils/                 # Helpers y utilidades
├── constants/             # Colores, config, etc.
└── assets/                # Imágenes, fonts
```

### Manejo de Errores

```typescript
// Estructura estándar de errores de API
interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  statusCode: number;
}

// Interceptor debe:
// 1. Parsear errores de validación (422)
// 2. Manejar 401 (refresh o logout)
// 3. Manejar 403 (forbidden)
// 4. Manejar 404 (not found)
// 5. Manejar 429 (rate limit)
// 6. Manejar 500 (server error)
```

### Optimizaciones Requeridas

1. **Memoización**: useMemo y useCallback donde corresponda
2. **Lista virtualizadas**: FlashList o FlatList optimizada
3. **Imágenes**: expo-image con caching
4. **Debounce**: En búsquedas y autosave
5. **Caché**: SWR o React Query para datos de API
6. **Lazy loading**: Código splitting por pantallas

### Notificaciones Push (Expo Notifications)

- Solicitar permisos en onboarding
- Registrar token con backend (endpoint a implementar)
- Manejar notificaciones recibidas:
  - Nueva respuesta en ticket
  - Ticket resuelto
  - Nuevo anuncio de empresa seguida
- Deep links desde notificaciones

---

## ✅ CHECKLIST DE ENTREGABLES

### Obligatorios

- [ ] Configuración completa de Expo con TypeScript
- [ ] Sistema de navegación con Expo Router
- [ ] Flujo completo de autenticación
- [ ] CRUD completo de tickets
- [ ] Conversación en tiempo real (polling/refetch)
- [ ] Gestión de empresas (follow/unfollow)
- [ ] Visualización de anuncios y artículos
- [ ] Perfil de usuario editable
- [ ] Tema claro/oscuro
- [ ] Manejo robusto de errores
- [ ] Estados de carga y vacíos
- [ ] Pull-to-refresh en listas
- [ ] Infinite scroll en listas
- [ ] Almacenamiento seguro de tokens
- [ ] Interceptor de Axios con refresh automático

### Deseables

- [ ] Notificaciones push
- [ ] Caché offline básico
- [ ] Animaciones pulidas
- [ ] Biometría para login
- [ ] Búsqueda con debounce
- [ ] Skeleton loaders

---

## ⚠️ RESTRICCIONES Y REGLAS

1. **NO implementar** funcionalidades de otros roles (AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
2. **NO hardcodear** URLs, tokens ni configuraciones
3. **SIEMPRE** usar TypeScript estricto
4. **SIEMPRE** validar inputs antes de enviar a API
5. **SIEMPRE** manejar estados de error
6. **NUNCA** almacenar tokens en AsyncStorage plano (usar SecureStore)
7. **RESPETAR** las restricciones del backend (tamaños, formatos, límites)

---

## 📚 RECURSOS DE REFERENCIA

- Base URL API: Variable de entorno `EXPO_PUBLIC_API_URL`
- Documentación OpenAPI: `/api-docs.json` del backend
- Códigos de ticket: Formato `TKT-YYYY-NNNNN`
- Códigos de usuario: Formato `USR-YYYY-NNNNN`
- Códigos de empresa: Formato `CMP-YYYY-NNNNN`
- Zona horaria por defecto: America/La_Paz
- Idiomas soportados: es (español), en (inglés)

---

**FIN DEL PROMPT - Versión 1.0**

Este documento contiene todas las especificaciones necesarias para desarrollar una aplicación móvil profesional y completa para el rol USER del sistema Helpdesk. El agente de código debe seguir estas especificaciones al pie de la letra, consultando la documentación de la API (`api-docs.json`) para detalles específicos de payloads y respuestas.
