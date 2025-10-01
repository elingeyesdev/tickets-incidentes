# Helpdesk System

[![Laravel 12](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)
[![React 18](https://img.shields.io/badge/React-18-blue)](https://reactjs.org)
[![PostgreSQL 17](https://img.shields.io/badge/PostgreSQL-17-blue)](https://www.postgresql.org)
[![GraphQL](https://img.shields.io/badge/GraphQL-Lighthouse-E10098)](https://lighthouse-php.com)

Sistema de helpdesk empresarial construido con Laravel 12, React 18 (Inertia.js), PostgreSQL 17 y GraphQL API.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#️-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
  - [Desarrollo](#desarrollo)
  - [Producción](#producción)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Comandos Útiles](#-comandos-útiles)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Troubleshooting](#-troubleshooting)

## ✨ Características

- **Dual Frontend**: Web (Inertia.js) + Mobile API (GraphQL)
- **Multi-tenant**: Soporte para múltiples empresas
- **Sistema de Tickets**: Gestión completa de tickets de soporte
- **Autenticación JWT**: Con refresh tokens
- **Base de datos multi-schema**: PostgreSQL con 4 schemas (auth, business, ticketing, audit)
- **Queue System**: Procesamiento asíncrono de tareas
- **Scheduler**: Tareas programadas con cron
- **Email Testing**: Mailpit para desarrollo

## 🛠️ Tecnologías

### Backend
- **Laravel 12**: Framework PHP
- **Lighthouse GraphQL 6**: API GraphQL
- **PostgreSQL 17**: Base de datos relacional
- **Redis 8**: Cache y sesiones
- **Inertia.js**: SSR con React

### Frontend
- **React 18**: Librería UI
- **TypeScript**: Tipado estático
- **Vite**: Build tool
- **TailwindCSS**: Framework CSS (opcional)

### DevOps
- **Docker & Docker Compose**: Containerización
- **Nginx**: Servidor web
- **Mailpit**: Email testing

## 📦 Requisitos

- **Docker** >= 20.10
- **Docker Compose** >= 2.0
- **Git**
- (Opcional) **Node.js** >= 20 para desarrollo local sin Docker

## 🚀 Instalación

### Desarrollo

#### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/helpdesk.git
cd helpdesk
```

#### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita `.env` si necesitas cambiar alguna configuración. Los valores por defecto funcionan para desarrollo.

#### 3. Ejecutar script de deployment

```bash
chmod +x deploy-dev.sh
./deploy-dev.sh
```

El script automáticamente:
- ✅ Construye las imágenes Docker
- ✅ Instala dependencias (Composer + NPM)
- ✅ Genera APP_KEY
- ✅ Ejecuta migraciones
- ✅ Compila assets frontend
- ✅ Configura permisos

#### 4. Acceder a la aplicación

- **Aplicación Web**: http://localhost:8000
- **GraphQL API**: http://localhost:8000/graphql
- **GraphiQL IDE**: http://localhost:8000/graphiql
- **Mailpit UI**: http://localhost:8025

#### 5. Desarrollo con Hot Reload

```bash
# Terminal 1: Mantener docker compose corriendo
docker compose up

# Terminal 2: Vite dev server (HMR)
docker compose exec app npm run dev
```

Ahora puedes editar archivos en `resources/js/` y verás los cambios en tiempo real.

---

### Producción

#### 1. En tu servidor/VM, clonar el repositorio

```bash
git clone https://github.com/tu-usuario/helpdesk.git
cd helpdesk
```

#### 2. Crear archivo de configuración de producción

```bash
cp .env.example .env.production
```

Edita `.env.production` con tus credenciales de producción:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Cambia estas credenciales
DB_PASSWORD=tu_password_seguro_aqui
REDIS_PASSWORD=tu_redis_password_aqui

# Configura tu SMTP real (no Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuproveedor.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@dominio.com
MAIL_PASSWORD=tu_password_smtp
MAIL_ENCRYPTION=tls
```

#### 3. Ejecutar script de deployment de producción

```bash
chmod +x deploy-prod.sh
./deploy-prod.sh
```

El script automáticamente:
- ✅ Hace backup de la base de datos
- ✅ Activa modo mantenimiento
- ✅ Pull del código más reciente (si usa Git)
- ✅ Construye imágenes optimizadas
- ✅ Instala dependencias de producción (sin dev)
- ✅ Ejecuta migraciones
- ✅ Optimiza caches (config, routes, views)
- ✅ Reinicia queue workers
- ✅ Desactiva modo mantenimiento

#### 4. Configurar dominio y SSL

Para producción, actualiza `docker/nginx/default.prod.conf` con tu dominio y configura SSL (Let's Encrypt recomendado).

## 📁 Estructura del Proyecto

```
helpdesk/
├── app/
│   ├── Core/              # Código compartido entre features
│   │   ├── Services/
│   │   └── GraphQL/
│   ├── Features/          # Organización feature-first
│   │   ├── Authentication/
│   │   ├── UserManagement/
│   │   └── CompanyManagement/
│   └── Shared/            # GraphQL Scalars, Directives, Queries
│
├── resources/
│   └── js/
│       ├── Pages/         # Páginas Inertia.js
│       ├── Features/      # Lógica frontend por feature
│       └── Shared/        # Componentes compartidos
│
├── docker/
│   ├── php/
│   │   ├── Dockerfile     # Multi-stage (dev + prod)
│   │   └── local.ini
│   ├── nginx/
│   │   ├── default.conf   # Config desarrollo
│   │   └── default.prod.conf
│   └── postgres/
│       ├── init.sql
│       └── create-multiple-databases.sh
│
├── graphql/
│   ├── schema.graphql     # Schema principal
│   └── shared/            # Types, scalars, directives compartidos
│
├── docker-compose.yml     # Desarrollo
├── docker-compose.prod.yml # Producción
├── deploy-dev.sh          # Script deployment dev
├── deploy-prod.sh         # Script deployment prod
└── .env.example           # Template de configuración
```

## 🔧 Comandos Útiles

### Docker

```bash
# Ver logs
docker compose logs -f [servicio]

# Acceder al contenedor
docker compose exec app bash

# Reiniciar servicios
docker compose restart [servicio]

# Detener todo
docker compose down

# Detener y eliminar volúmenes (⚠️ borra datos)
docker compose down -v
```

### Laravel (dentro del contenedor)

```bash
# Acceder al contenedor
docker compose exec app bash

# Artisan commands
php artisan migrate
php artisan db:seed
php artisan tinker
php artisan queue:work
php artisan schedule:run

# Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan lighthouse:cache
```

### Composer & NPM

```bash
# Composer
docker compose exec app composer install
docker compose exec app composer update
docker compose exec app composer require paquete

# NPM
docker compose exec app npm install
docker compose exec app npm run dev    # Desarrollo (HMR)
docker compose exec app npm run build  # Producción
```

### Base de Datos

```bash
# Conectarse a PostgreSQL
docker compose exec postgres psql -U helpdesk -d helpdesk

# Backup
docker compose exec postgres pg_dump -U helpdesk helpdesk > backup.sql

# Restaurar
cat backup.sql | docker compose exec -T postgres psql -U helpdesk -d helpdesk
```

### Redis

```bash
# Conectarse a Redis CLI
docker compose exec redis redis-cli

# Ver todas las keys
KEYS *

# Limpiar cache
FLUSHALL
```

## 🧪 Testing

```bash
# Ejecutar todos los tests
docker compose exec app php artisan test

# Tests específicos
docker compose exec app php artisan test --filter=UserTest

# Con coverage
docker compose exec app php artisan test --coverage
```

## 🚢 Deployment

### Desarrollo

```bash
./deploy-dev.sh
```

### Producción

```bash
./deploy-prod.sh
```

### Workflow con Git

#### Primera vez en nuevo entorno

```bash
git clone https://github.com/tu-usuario/helpdesk.git
cd helpdesk

# Desarrollo
./deploy-dev.sh

# Producción
cp .env.example .env.production
# Editar .env.production con credenciales reales
./deploy-prod.sh
```

#### Actualizaciones

```bash
# En tu máquina local
git pull origin main
git add .
git commit -m "Descripción de cambios"
git push origin main

# En producción (VM)
cd helpdesk
./deploy-prod.sh  # Automáticamente hace git pull
```

## ❗ Troubleshooting

### Problema: Permisos en storage/

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Problema: Puerto 8000 ya en uso

Cambia el puerto en `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8080:80"  # Cambia 8000 por 8080
```

### Problema: Base de datos no conecta

```bash
# Verificar que postgres esté corriendo
docker compose ps postgres

# Ver logs
docker compose logs postgres

# Recrear contenedor
docker compose down
docker compose up -d postgres
```

### Problema: Vite HMR no funciona

Asegúrate de que el puerto 5173 esté expuesto en `docker-compose.yml` y que ejecutas:

```bash
docker compose exec app npm run dev
```

### Problema: GraphQL schema errors

```bash
# Validar schema
docker compose exec app php artisan lighthouse:validate-schema

# Limpiar cache de Lighthouse
docker compose exec app php artisan lighthouse:clear-cache
```

## 📝 Licencia

[MIT License](LICENSE)

## 👥 Contribución

1. Fork el proyecto
2. Crea una rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📧 Contacto

Tu Nombre - [@tuusuario](https://twitter.com/tuusuario) - email@ejemplo.com

Project Link: [https://github.com/tu-usuario/helpdesk](https://github.com/tu-usuario/helpdesk)

---

**Hecho con ❤️ usando Laravel, React y GraphQL**