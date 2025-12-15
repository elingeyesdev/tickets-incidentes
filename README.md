# 🎯 Sistema Helpdesk - Tickets e Incidentes

> **Plataforma profesional de gestión de tickets y atención al cliente** - API REST + App Web + App Móvil

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![React Native](https://img.shields.io/badge/React_Native-Expo-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://reactnative.dev)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![AdminLTE](https://img.shields.io/badge/AdminLTE-v3-00A6FB?style=for-the-badge&logo=bootstrap&logoColor=white)](https://adminlte.io)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)

---

## 📋 Resumen

Este repositorio contiene un **sistema completo de helpdesk** compuesto por:

| Componente | Tecnología | Ubicación |
|------------|------------|-----------|
| 🖥️ **API + Web** | Laravel 12, PostgreSQL 17, AdminLTE | `API-y-Web/` |
| 📱 **Móvil** | React Native, Expo | `movil/` |

**Características principales:**
- ✅ Autenticación JWT + OAuth (Google)
- ✅ Sistema de tickets multi-inquilino
- ✅ Gestión de empresas y usuarios
- ✅ Notificaciones y anuncios
- ✅ Reportes y estadísticas

---

## 🚀 Despliegue Rápido (Docker)

La forma más rápida de ejecutar la API y Web. **El entrypoint de Docker automatiza** la mayoría de tareas (migraciones, seeders, permisos, optimizaciones).

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
- 🌐 **Web:** http://localhost:8000
- 📧 **Mailpit (testing emails):** http://localhost:8025
- 🗄️ **PostgreSQL:** localhost:5433

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

## 📁 Estructura del Repositorio

```
├── API-y-Web/              # Backend Laravel + Frontend Web
│   ├── app/                # Código de la aplicación
│   │   └── Features/       # Arquitectura Feature-First
│   ├── docker/             # Configuración Docker
│   ├── docker-compose.yml  # Desarrollo
│   └── .env.example        # Template de configuración
│
└── movil/                  # App React Native
    ├── src/                # Código fuente
    ├── assets/             # Recursos
    └── .env.example        # Template de configuración
```

---

## 🔐 Credenciales de Desarrollo

Después de ejecutar los seeders, puedes acceder con:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Platform Admin | admin@helpdesk.com | Password123! |

---

## 📚 Documentación Adicional

- **API Documentation:** `API-y-Web/README.md`
- **Móvil Documentation:** `movil/README.md`

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
