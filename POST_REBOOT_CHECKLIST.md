# ✅ POST-REBOOT CHECKLIST - Helpdesk System

**Cuándo usar este checklist:** Después de reiniciar tu laptop/computadora

---

## 🔍 1. VERIFICAR SERVICIOS DE DOCKER

### Verificar que Docker Desktop esté corriendo

```bash
# Windows: Verificar que Docker Desktop esté abierto
# Buscar el ícono de Docker en la bandeja del sistema (system tray)
```

### Verificar estado de contenedores

```bash
docker compose ps
```

**Salida esperada:** Todos los servicios deben mostrar `Up` y `(healthy)`:
```
NAME                 STATUS
helpdesk_app         Up (healthy)
helpdesk_nginx       Up (healthy)
helpdesk_postgres    Up (healthy)
helpdesk_redis       Up (healthy)
helpdesk_queue       Up
helpdesk_scheduler   Up
helpdesk_mailpit     Up (healthy)
```

### Si algún servicio no está corriendo:

```bash
# Iniciar todos los servicios
docker compose up -d

# Esperar a que estén healthy (30-60 segundos)
docker compose ps

# Ver logs si hay problemas
docker compose logs [nombre_servicio] --tail=50
```

---

## 🗄️ 2. VERIFICAR BASE DE DATOS

### Verificar conexión a PostgreSQL

```bash
docker compose exec postgres psql -U helpdesk -c "SELECT 1;"
```

**Salida esperada:**
```
 ?column?
----------
        1
(1 row)
```

### Verificar configuración de PostgreSQL

```bash
docker compose exec postgres psql -U helpdesk -c "SHOW max_connections; SHOW shared_buffers;"
```

**Salida esperada:**
```
 max_connections
-----------------
 200
(1 row)

 shared_buffers
----------------
 256MB
(1 row)
```

### Si PostgreSQL tiene problemas:

```bash
# Reiniciar solo PostgreSQL
docker compose restart postgres

# Ver logs
docker compose logs postgres --tail=100
```

---

## 🧹 3. LIMPIAR BASES DE DATOS DE TEST (RECOMENDADO)

**¿Por qué?** Después de un reinicio abrupto, las bases de datos de test pueden quedar corruptas.

```bash
# Windows PowerShell
.\scripts\clean-test-databases.ps1

# Git Bash / WSL
./scripts/clean-test-databases.sh

# O manualmente:
docker compose exec postgres psql -U helpdesk -c "SELECT 'DROP DATABASE IF EXISTS \"' || datname || '\";' FROM pg_database WHERE datname LIKE 'helpdesk_test%';" -t | docker compose exec -T postgres psql -U helpdesk
```

---

## 🔴 4. LIMPIAR REDIS (OPCIONAL PERO RECOMENDADO)

```bash
docker compose exec redis redis-cli FLUSHALL
```

---

## ✅ 5. VERIFICAR QUE TESTS FUNCIONEN

### Test simple (secuencial)

```bash
docker compose exec app php artisan test --filter=AuthStatusTest
```

**Salida esperada:** Debe pasar todos los tests sin errores fatales.

### Tests paralelos (si el simple pasó)

```bash
docker compose exec app php artisan test --parallel --processes=8
```

**Notas:**
- Empezar con 8 procesos (más estable)
- Si funciona bien, intentar con 16 procesos
- Si falla con "too many clients", reiniciar PostgreSQL

---

## 🚨 TROUBLESHOOTING COMÚN

### Error: "Trait not found"

**Causa:** Los archivos de código tienen un error en los `use` statements

**Solución:**
```bash
# Verificar que los archivos de Authentication tengan el trait correcto
grep -r "use RefreshDatabase;" tests/Feature/Authentication/

# Si aparecen resultados, significa que hay archivos incorrectos
# Contactar al equipo o revisar el commit más reciente
```

### Error: "too many clients already"

**Causa:** PostgreSQL no tiene suficientes conexiones disponibles

**Solución:**
```bash
# 1. Verificar max_connections
docker compose exec postgres psql -U helpdesk -c "SHOW max_connections;"

# Si no muestra 200, reiniciar:
docker compose down postgres
docker compose up -d postgres

# 2. Si persiste, reducir procesos paralelos:
docker compose exec app php artisan test --parallel --processes=8
```

### Error: "SQLSTATE[08006] connection refused"

**Causa:** PostgreSQL no está corriendo o no está listo

**Solución:**
```bash
# 1. Verificar que esté corriendo
docker compose ps postgres

# 2. Reiniciar
docker compose restart postgres

# 3. Esperar 30 segundos y verificar health
docker compose ps postgres
```

### Error: "Redis connection refused"

**Causa:** Redis no está corriendo

**Solución:**
```bash
# Reiniciar Redis
docker compose restart redis

# Verificar
docker compose exec redis redis-cli PING
# Debe responder: PONG
```

### Tests extremadamente lentos (>5 minutos)

**Causa:** Bases de datos corruptas o cache sin limpiar

**Solución:**
```bash
# Limpiar TODO
./scripts/clean-test-databases.sh
docker compose exec redis redis-cli FLUSHALL
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Ejecutar tests de nuevo
docker compose exec app php artisan test --parallel --processes=8
```

---

## 📋 CHECKLIST RÁPIDO (COPIA Y PEGA)

```bash
# 1. Verificar servicios
docker compose ps

# 2. Iniciar si no están corriendo
docker compose up -d

# 3. Limpiar bases de datos de test
.\scripts\clean-test-databases.ps1  # Windows
# O: ./scripts/clean-test-databases.sh  # Linux/Mac

# 4. Limpiar Redis
docker compose exec redis redis-cli FLUSHALL

# 5. Verificar configuración de PostgreSQL
docker compose exec postgres psql -U helpdesk -c "SHOW max_connections;"

# 6. Test simple
docker compose exec app php artisan test --filter=AuthStatusTest

# 7. Tests paralelos
docker compose exec app php artisan test --parallel --processes=8
```

---

## 📞 SOPORTE

Si después de seguir este checklist los tests siguen fallando:

1. **Capturar información de diagnóstico:**
   ```bash
   docker compose ps > diagnostico.txt
   docker compose logs app --tail=100 >> diagnostico.txt
   docker compose logs postgres --tail=100 >> diagnostico.txt
   docker compose exec postgres psql -U helpdesk -c "SELECT count(*) FROM pg_stat_activity;" >> diagnostico.txt
   ```

2. **Ejecutar test con output completo:**
   ```bash
   docker compose exec app php artisan test --filter=AuthStatusTest > test-output.txt 2>&1
   ```

3. **Compartir archivos:**
   - `diagnostico.txt`
   - `test-output.txt`
   - Descripción de qué estabas haciendo antes de reiniciar

---

## ✅ VERIFICACIÓN FINAL

Después de completar el checklist, deberías ver:

```bash
docker compose exec app php artisan test --parallel --processes=16
```

**Resultados esperados:**
- **Errors:** 0-3 (muy bajo)
- **Failures:** ~35-40 (tests reales pendientes, OK)
- **Tests:** 1,313
- **Tiempo:** ~2-3 minutos

Si ves estos números, ¡todo está funcionando correctamente! 🎉

---

**Última actualización:** 2025-11-27
**Versión:** 1.0
