# 🎯 Sistema Helpdesk - Tickets e Incidentes

> **Plataforma profesional de gestión de tickets y atención al cliente** - API REST + App Web + App Móvil

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![React Native](https://img.shields.io/badge/React_Native-Expo-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://reactnative.dev)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![AdminLTE](https://img.shields.io/badge/AdminLTE-v3-00A6FB?style=for-the-badge&logo=bootstrap&logoColor=white)](https://adminlte.io)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)

---

## 📋 Tabla de Contenidos

- [✨ Resumen](#-resumen)
- [🏗️ Arquitectura](#️-arquitectura)
- [📁 Estructura del Proyecto](#-estructura-del-proyecto)
- [🚀 Despliegue Rápido (Docker)](#-despliegue-rápido-docker)
- [🛠️ Despliegue Manual](#️-despliegue-manual-sin-docker)
- [📱 Configuración App Móvil](#-configuración-app-móvil)
- [📡 Endpoints API](#-endpoints-api)
- [🔐 Credenciales de Desarrollo](#-credenciales-de-desarrollo)
- [📖 Documentación de la API (Swagger)](#-documentación-de-la-api-swagger)
- [🛠️ Tech Stack](#️-tech-stack)
- [👨‍💻 Autor](#-autor)

---

## ✨ Resumen

Este repositorio contiene un **sistema completo de helpdesk** compuesto por:

| Componente | Tecnología | Ubicación |
|------------|------------|-----------|
| 🖥️ **API + Web** | Laravel 12, PostgreSQL 17, AdminLTE | `API-y-Web/` |
| 📱 **Móvil** | React Native, Expo | `movil/` |

### Características principales

- ✅ **Autenticación JWT + OAuth (Google)** - Sistema de autenticación seguro con tokens stateless
- ✅ **Sistema de tickets multi-inquilino** - Gestión completa de tickets con aislamiento por empresa
- ✅ **Gestión de empresas y usuarios** - RBAC con 4 roles (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER)
- ✅ **Sistema de anuncios** - Mantenimientos, incidentes, noticias y alertas
- ✅ **Centro de ayuda** - Artículos y categorías para autoservicio
- ✅ **Widget embebible** - Integración externa con API Keys
- ✅ **Reportes y estadísticas** - Dashboards con métricas en tiempo real

---

## 🏗️ Arquitectura

### **Arquitectura Feature-First**

El proyecto sigue una arquitectura modular donde cada feature es autocontenida:

```
app/Features/
├── Authentication/          # JWT auth, login, register, verificación
├── UserManagement/          # Usuarios, perfiles, roles (RBAC)
├── CompanyManagement/       # Empresas multi-tenant, áreas
├── ContentManagement/       # Anuncios, artículos del centro de ayuda
├── TicketManagement/        # Sistema de tickets, respuestas, adjuntos
├── Analytics/               # Dashboards y estadísticas
├── AuditLog/                # Logs de actividad del sistema
└── ExternalIntegration/     # Widget embebible, API Keys
```

Cada feature contiene:
- **Controllers** - Endpoints REST API
- **Services** - Lógica de negocio
- **Models** - Datos y relaciones
- **Policies** - Reglas de autorización
- **Resources** - Transformadores JSON
- **Requests** - Validación de formularios

### **PostgreSQL Multi-Schema**

**4 Esquemas para separación perfecta:**
- `auth` - Usuarios, roles, permisos, sesiones
- `business` - Empresas, solicitudes, industrias
- `ticketing` - Tickets, respuestas, categorías, calificaciones
- `audit` - Logs de auditoría del sistema

---

## 📁 Estructura del Proyecto

```
📦 githelpdesk/
│
├── 📂 API-y-Web/                    # Backend Laravel + Frontend Web
│   ├── 📂 app/
│   │   ├── 📂 Features/             # Arquitectura Feature-First
│   │   │   ├── Authentication/      # JWT, OAuth, sesiones
│   │   │   ├── UserManagement/      # RBAC, perfiles
│   │   │   ├── CompanyManagement/   # Multi-tenant
│   │   │   ├── ContentManagement/   # Anuncios, artículos
│   │   │   ├── TicketManagement/    # Tickets, respuestas
│   │   │   ├── Analytics/           # Dashboards
│   │   │   ├── AuditLog/            # Logs de actividad
│   │   │   └── ExternalIntegration/ # Widget, API Keys
│   │   └── 📂 Http/Middleware/      # JWT, Rate Limiting
│   │
│   ├── 📂 database/
│   │   ├── 📂 migrations/           # Esquemas PostgreSQL
│   │   └── 📂 seeders/              # Datos de prueba
│   │
│   ├── 📂 resources/
│   │   ├── 📂 views/                # Blade templates (AdminLTE)
│   │   └── 📂 js/                   # JavaScript frontend
│   │
│   ├── 📂 routes/
│   │   ├── api.php                  # 80+ endpoints REST
│   │   └── web.php                  # Rutas web
│   │
│   ├── 📂 docker/                   # Dockerfiles, nginx, php-fpm
│   ├── 📂 tests/                    # 174+ tests (Feature + Unit)
│   ├── docker-compose.yml           # Desarrollo
│   ├── docker-compose.prod.yml      # Producción
│   └── .env.example                 # Template de configuración
│
├── 📂 movil/                        # App React Native
│   ├── 📂 src/
│   │   ├── 📂 components/           # Componentes reutilizables
│   │   ├── 📂 screens/              # Pantallas de la app
│   │   ├── 📂 services/             # API calls
│   │   └── 📂 hooks/                # Custom hooks
│   │
│   ├── 📂 assets/                   # Imágenes, fuentes
│   ├── app.json                     # Configuración Expo
│   └── .env.example                 # Template de configuración
│
└── README.md                        # Este archivo
```

---

## 🚀 Despliegue Rápido (Docker)

La forma más rápida de ejecutar la API y Web. **El entrypoint de Docker automatiza** la mayoría de tareas.

```bash
# 1. Navegar al proyecto API/Web
cd API-y-Web

# 2. Copiar y configurar variables de entorno
cp .env.example .env

# 3. Editar .env con tus credenciales
nano .env   # o vim .env / notepad .env (Windows)
```

### Variables importantes a configurar en `.env`:
```env
JWT_SECRET=your_jwt_secret_here   # IMPORTANTE: Genera un secreto seguro (64 caracteres)
GOOGLE_CLIENT_ID=                 # Para login con Google (opcional)
GOOGLE_CLIENT_SECRET=             # Para login con Google (opcional)
```

> 💡 **Tip:** Para generar un JWT_SECRET seguro: `openssl rand -base64 64`

```bash
# 4. Construir y levantar contenedores
docker compose build
docker compose up -d
```

### ✅ El entrypoint automatiza:
- Esperar a que PostgreSQL esté listo
- Generar `APP_KEY` si no existe
- Ejecutar migraciones (`php artisan migrate`)
- Ejecutar seeders (`php artisan db:seed`)
- Configurar permisos de storage
- Optimizar caché de configuración y rutas
- Crear symlink de storage

### Acceder a la aplicación:
| Servicio | URL |
|----------|-----|
| 🌐 **Web** | http://localhost:8000 |
| 📧 **Mailpit (emails)** | http://localhost:8025 |
| 🗄️ **PostgreSQL** | localhost:5433 |
| 🔴 **Redis** | localhost:6379 |

---

## 🛠️ Despliegue Manual (Sin Docker)

### Requisitos previos:
- PHP 8.3+
- Composer
- PostgreSQL 17
- Redis
- Node.js 18+

### Pasos:

```bash
# 1. Navegar al proyecto
cd API-y-Web

# 2. Configurar entorno
cp .env.example .env
nano .env  # Configurar DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, JWT_SECRET

# 3. Instalar dependencias
composer install

# 4. Generar claves
php artisan key:generate
php artisan jwt:secret

# 5. Copiar JavaScript a public
cp -r resources/js public/js

# 6. Crear base de datos PostgreSQL
createdb helpdesk

# 7. Ejecutar migraciones
php artisan migrate --seed

# 8. Dar permisos
chmod -R 775 storage bootstrap/cache

# 9. Iniciar servidor
php artisan serve --port=8000
```

---

## 📱 Configuración App Móvil

```bash
# 1. Navegar al proyecto móvil
cd movil

# 2. Copiar y configurar variables de entorno
cp .env.example .env

# 3. Editar .env
nano .env
```

### Variables en `.env`:
```env
EXPO_PUBLIC_API_URL=http://localhost:8000  # URL de tu API
```

```bash
# 4. Instalar dependencias
npm install

# 5. Iniciar Expo
npx expo start
```

---

## 📡 Endpoints API

### 🔐 Autenticación

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Registrar nuevo usuario |
| POST | `/api/auth/login` | No | Login con credenciales |
| POST | `/api/auth/login/google` | No | Login con Google OAuth |
| POST | `/api/auth/refresh` | No | Refrescar access token |
| POST | `/api/auth/logout` | JWT | Cerrar sesión |
| POST | `/api/auth/password-reset` | No | Solicitar reset de contraseña |
| POST | `/api/auth/email/verify` | No | Verificar email |
| GET | `/api/auth/status` | JWT | Estado de autenticación |
| GET | `/api/auth/sessions` | JWT | Listar sesiones activas |

### 👤 Usuarios

| Método | Endpoint | Auth | Rol | Descripción |
|--------|----------|------|-----|-------------|
| GET | `/api/users/me` | JWT | Any | Usuario actual |
| PATCH | `/api/users/me/profile` | JWT | Any | Actualizar perfil |
| POST | `/api/users/me/avatar` | JWT | Any | Subir avatar |
| GET | `/api/users` | JWT | Admin | Listar usuarios |
| PUT | `/api/users/{id}/status` | JWT | Platform Admin | Cambiar estado |

### 🏢 Empresas

| Método | Endpoint | Auth | Rol | Descripción |
|--------|----------|------|-----|-------------|
| GET | `/api/companies/minimal` | No | - | Lista pública de empresas |
| GET | `/api/companies/explore` | JWT | Any | Explorar empresas |
| GET | `/api/companies/{id}` | JWT | Any | Detalles de empresa |
| POST | `/api/companies/{id}/follow` | JWT | Any | Seguir empresa |
| POST | `/api/companies` | JWT | Platform Admin | Crear empresa |
| PATCH | `/api/companies/{id}` | JWT | Owner | Actualizar empresa |

### 🎫 Tickets

| Método | Endpoint | Auth | Rol | Descripción |
|--------|----------|------|-----|-------------|
| POST | `/api/tickets` | JWT | USER | Crear ticket |
| GET | `/api/tickets` | JWT | Any | Listar tickets |
| GET | `/api/tickets/{code}` | JWT | Any | Ver ticket |
| PATCH | `/api/tickets/{code}` | JWT | Any | Actualizar ticket |
| POST | `/api/tickets/{code}/responses` | JWT | Any | Agregar respuesta |
| POST | `/api/tickets/{code}/resolve` | JWT | AGENT | Resolver ticket |
| POST | `/api/tickets/{code}/close` | JWT | Any | Cerrar ticket |
| POST | `/api/tickets/{code}/assign` | JWT | AGENT | Asignar ticket |
| POST | `/api/tickets/{code}/attachments` | JWT | Any | Subir adjunto |

### 📢 Anuncios

| Método | Endpoint | Auth | Rol | Descripción |
|--------|----------|------|-----|-------------|
| GET | `/api/announcements` | JWT | Any | Listar anuncios |
| GET | `/api/announcements/{id}` | JWT | Any | Ver anuncio |
| POST | `/api/announcements/maintenance` | JWT | Company Admin | Crear mantenimiento |
| POST | `/api/announcements/incidents` | JWT | Company Admin | Crear incidente |
| POST | `/api/announcements/news` | JWT | Company Admin | Crear noticia |
| POST | `/api/announcements/{id}/publish` | JWT | Company Admin | Publicar anuncio |

### 📊 Analytics

| Método | Endpoint | Auth | Rol | Descripción |
|--------|----------|------|-----|-------------|
| GET | `/api/analytics/company-dashboard` | JWT | Company Admin | Dashboard empresa |
| GET | `/api/analytics/agent-dashboard` | JWT | Agent | Dashboard agente |
| GET | `/api/analytics/user-dashboard` | JWT | Any | Dashboard usuario |
| GET | `/api/analytics/platform-dashboard` | JWT | Platform Admin | Dashboard plataforma |

### 🔗 Widget Externo

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/external/validate-key` | API Key | Validar API Key |
| POST | `/api/external/check-user` | API Key | Verificar si usuario existe |
| POST | `/api/external/login` | API Key | Login automático (trusted) |
| POST | `/api/external/register` | API Key | Registrar usuario externo |

> 📚 **Documentación completa:** Ver [API-y-Web/README.md](API-y-Web/README.md) para la lista completa de 80+ endpoints.

---

## 🔐 Credenciales de Desarrollo

Después de ejecutar los seeders, puedes acceder con:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Platform Admin | lukqs05@gmail.com | mklmklmkl |

---

## 📖 Documentación de la API (Swagger)

La API cuenta con documentación interactiva generada con **OpenAPI/Swagger**.

### Generar documentación:
```bash
docker compose exec app php artisan l5-swagger:generate
```

### Acceder a la documentación:
🔗 **http://localhost:8000/api/documentation**

---

## 🛠️ Tech Stack

### Backend
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **Laravel** | 12 | Framework PHP |
| **PHP** | 8.3 | Lenguaje backend |
| **PostgreSQL** | 17 | Base de datos (multi-schema) |
| **Redis** | 8 | Cache, colas, sesiones |
| **JWT** | - | Autenticación stateless |

### Frontend Web
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **AdminLTE** | 3 | Template admin |
| **Blade** | - | Motor de templates |
| **jQuery** | 3.x | Interactividad |
| **Alpine.js** | 3.15 | Componentes reactivos |

### Móvil
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **React Native** | - | Framework móvil |
| **Expo** | - | Development toolkit |

### Infraestructura
| Tecnología | Uso |
|------------|-----|
| **Docker** | Containerización |
| **Nginx** | Web server |
| **PHP-FPM** | PHP runtime |
| **Mailpit** | Testing de emails |

---

## 📚 Documentación Adicional

| Documento | Descripción |
|-----------|-------------|
| [API-y-Web/README.md](API-y-Web/README.md) | Documentación completa de la API |
| [movil/README.md](movil/README.md) | Documentación de la app móvil |
| `API-y-Web/CLAUDE.md` | Guía de desarrollo completa |

---

## 👨‍💻 Autor

**Luke De La Quintana**  
📧 lukqs05@gmail.com  
🆔 62119184

---

<div align="center">

**Sistema Helpdesk** | Proyecto Académico - Desarrollo de Software

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![React Native](https://img.shields.io/badge/React_Native-Expo-61DAFB?style=flat-square&logo=react)](https://reactnative.dev)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=flat-square&logo=postgresql)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker)](https://docker.com)

</div>
