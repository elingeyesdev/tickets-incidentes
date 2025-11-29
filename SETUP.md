# 🚀 Setup Helpdesk (Windows + Docker)

## 📋 Requisitos Previos

- **Windows 11/10** con WSL2
- **Docker Desktop** instalado
- **Composer** instalado en Windows
- **PHP 8.2+** en Windows (para composer)
- **Git**

---

## ⚡ Quick Start (Windows)

### 1️⃣ Instalar Dependencias Composer

**IMPORTANTE:** Instala las dependencias EN WINDOWS, no en Docker.

```bash
# En tu terminal Windows (CMD, PowerShell, o Git Bash)
cd C:\Users\tu-usuario\Projects\Helpdesk
composer install
```

**¿Por qué en Windows?**
- Docker I/O en Windows es 20-30x más lento
- Composer instalará los ~130 paquetes en 2-3 minutos en Windows
- Docker simplemente reutilizará la carpeta `vendor/` vía volume mount

### 2️⃣ Iniciar Docker

```bash
docker compose up -d
```

**¿Qué pasa?**
1. Docker inicia PostgreSQL, Redis, Nginx, PHP-FPM
2. El entrypoint verifica que `vendor/` existe (desde Windows)
3. Ejecuta migraciones
4. Siembra datos
5. Optimiza caches

### 3️⃣ Acceder a la Aplicación

```
http://localhost:8000
```

**Credenciales por defecto:**
- Email: `lukqs05@gmail.com`
- Password: `123456` (debes cambiarla en desarrollo)

---

## 🌍 Configurar GeoIP (Ubicación de Usuarios)

La aplicación captura la **ubicación geográfica** de los usuarios cuando hacen login usando MaxMind GeoLite2.

### Setup GeoIP (Una sola vez)

#### 1️⃣ Registrarse en MaxMind

```
1. Ir a: https://www.maxmind.com/en/geolite2/signup
2. Crear cuenta gratis
3. Confirmar email
4. Ir a: https://www.maxmind.com/en/account/login
```

#### 2️⃣ Descargar GeoLite2-City

```
1. En tu dashboard, click en "Download files"
2. Buscar "GeoLite City" → GeoIP2 Binary (.mmdb)
3. Click en "Download GZIP"
4. Descargar el archivo
```

#### 3️⃣ Descomprimir y Copiar

```bash
# El archivo descargado es GeoLite2-City.mmdb.gz
# Descomprímelo (Windows lo hace automático con WinRAR, 7-Zip, etc.)

# Copia el archivo a:
storage/geoip/GeoLite2-City.mmdb
```

**Ejemplo de ruta completa:**
```
C:\Users\tu-usuario\Projects\Helpdesk\storage\geoip\GeoLite2-City.mmdb
```

#### 4️⃣ Verificar

```bash
docker compose exec app php artisan tinker --execute="echo app(\App\Features\Authentication\Services\GeoIPService::class)->getLocationFromIp('8.8.8.8') ? 'GeoIP Working!' : 'Error';"
```

Si ves `GeoIP Working!`, está todo bien ✅

### ¿Qué Captura GeoIP?

Cuando un usuario hace login, se captura:
```json
{
  "city": "Buenos Aires",
  "country": "Argentina",
  "country_code": "AR",
  "latitude": -34.6037,
  "longitude": -58.3816,
  "timezone": "America/Argentina/Buenos_Aires"
}
```

### Ver Datos de Sesiones con Ubicación

```bash
# Test endpoint de sesiones:
curl -H "Authorization: Bearer TU_TOKEN" \
  http://localhost:8000/api/auth/sessions
```

**Respuesta incluye:**
```json
{
  "sessions": [
    {
      "id": "xxx",
      "device_name": "Chrome",
      "location": {
        "city": "Buenos Aires",
        "country": "Argentina",
        ...
      }
    }
  ]
}
```

---

## 🛠️ Desarrollo

### Agregar un Paquete Nuevo

```bash
# 1. En Windows, ejecuta:
composer require vendor/package-name

# 2. Reinicia Docker para que reconozca la nueva dependencia:
docker compose down && docker compose up -d
```

### Cambiar Código

El código en `./app` está montado en Docker en tiempo real:
- Cambias un archivo en Windows
- Docker lo ve inmediatamente
- Refresh en el navegador

### Ejecutar Comandos Artisan

```bash
# Dentro de Docker:
docker compose exec app php artisan [comando]

# Ejemplos:
docker compose exec app php artisan tinker          # REPL
docker compose exec app php artisan route:list      # Ver rutas
docker compose exec app php artisan make:model Foo  # Crear modelo
```

### Ejecutar Tests

```bash
# Todos los tests:
docker compose exec app php artisan test

# Test específico:
docker compose exec app php artisan test --filter=AuthenticationTest

# Con paralelismo (más rápido):
docker compose exec app php artisan test --parallel
```

### Formatear Código

```bash
# Formatear TODO el código:
docker compose exec app ./vendor/bin/pint

# Formatear archivo específico:
docker compose exec app ./vendor/bin/pint app/Features/UserManagement/Http/Controllers/AuthController.php

# Revisar sin cambiar (dry-run):
docker compose exec app ./vendor/bin/pint --test
```

---

## 📊 Servicios y Puertos

| Servicio | URL/Puerto | Descripción |
|----------|-----------|------------|
| **Aplicación** | http://localhost:8000 | Laravel Helpdesk |
| **Mailpit** | http://localhost:8025 | Email testing (SMTP en 1025) |
| **PostgreSQL** | localhost:5432 | Base de datos |
| **Redis** | localhost:6379 | Cache/Session store |

---

## 🗄️ Base de Datos

### Ver Base de Datos

```bash
# Acceder a PostgreSQL CLI:
docker compose exec postgres psql -U helpdesk -d helpdesk

# Comandos útiles:
\dt                    # Ver todas las tablas
\d table_name          # Ver estructura de tabla
SELECT * FROM users;   # Query
\q                     # Salir
```

### Migraciones

```bash
# Ejecutar migraciones:
docker compose exec app php artisan migrate

# Rollback última migración:
docker compose exec app php artisan migrate:rollback

# Rollback y redo:
docker compose exec app php artisan migrate:refresh

# Rollback y seed:
docker compose exec app php artisan migrate:fresh --seed
```

### Seeders

```bash
# Ejecutar seeder:
docker compose exec app php artisan db:seed

# Seeder específico:
docker compose exec app php artisan db:seed --class=RolesSeeder
```

---

## 🐛 Troubleshooting

### Container no inicia
```bash
# Ver logs:
docker compose logs app

# Si ves "vendor not found" en DEVELOPMENT:
# → Instala en Windows: composer install
# → Reinicia: docker compose down && docker compose up -d
```

### Composer timeout (debería no pasar)
```bash
# Si ocurre, instala en Windows:
composer install

# Si estás en Linux/Producción:
# → El entrypoint lo instala automáticamente con timeout extendido
```

### Permisos quebrados en storage/
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

### Cache viejo
```bash
docker compose exec app php artisan optimize:clear
```

### Tests fallan
```bash
# Limpiar config:
docker compose exec app php artisan config:clear --env=testing

# Ejecutar con verbosity:
docker compose exec app php artisan test --verbose
```

---

## 🔄 Workflow Típico

```bash
# 1. Iniciar día:
docker compose up -d

# 2. Instalar dependencia nueva:
composer require monolog/monolog
docker compose down && docker compose up -d

# 3. Crear migración:
docker compose exec app php artisan make:migration create_tickets_table

# 4. Ejecutar migraciones:
docker compose exec app php artisan migrate

# 5. Formatear código:
docker compose exec app ./vendor/bin/pint

# 6. Ejecutar tests:
docker compose exec app php artisan test

# 7. Commit:
git add .
git commit -m "feat: add ticket system"

# 8. Finalizar:
docker compose down
```

---

## ⚠️ Notas Importantes

### ❌ NUNCA hagas esto

```bash
# ❌ NO instales dependencias en Docker (será lento):
docker compose exec app composer install

# ❌ NO uses --prefer-source (descarga git repos, lentísimo):
composer install --prefer-source

# ❌ NO cambies composer.json en Docker y lo sincronices a Windows
```

### ✅ SIEMPRE haz esto

```bash
# ✅ Instala en Windows:
composer install

# ✅ Reinicia Docker después de cambiar dependencias:
docker compose down && docker compose up -d

# ✅ Formatea código antes de commit:
docker compose exec app ./vendor/bin/pint
```

---

## 🚀 Deploy a Producción (Linux)

En servidores Linux (AWS, Digital Ocean, etc.):

```bash
git clone tu-repo
cd tu-repo

# Usar docker-compose.prod.yml (si existe):
docker compose -f docker-compose.prod.yml up -d

# En producción, el entrypoint:
# 1. Detecta APP_ENV != "local"
# 2. Instala composer automáticamente
# 3. Ejecuta migraciones
# 4. ¡Listo!
```

---

## 📚 Documentación Adicional

- **CLAUDE.md** - Guía de arquitectura y patrones
- **documentacion/ESTADO_COMPLETO_PROYECTO.md** - Estado del proyecto
- **documentacion/GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md** - Estructura de carpetas

---

## 💬 Preguntas?

Si algo no funciona:

1. Revisa los logs: `docker compose logs -f [servicio]`
2. Busca en esta guía
3. Revisa CLAUDE.md para patrones

---

**Last updated:** 28 Nov 2025
