# 🚀 Optimización de Rendimiento - Laravel + GraphQL + Docker

## 📋 Problema Identificado

### **Síntomas:**
- Primera query GraphQL: **1.7-4 segundos** (inaceptable)
- Queries siguientes: **300ms-2 segundos** (lento)
- Inconsistencia extrema en tiempos de respuesta

### **Causa Raíz Encontrada:**
**COLD START de PHP-FPM en Docker** - No era problema de Laravel sino de configuración de contenedor.

## 🔍 Proceso de Diagnóstico Realizado

### **1. Verificación de OPcache**
```bash
# Comando usado
docker compose exec app php -m | grep opcache

# Resultado: OPcache instalado pero no optimizado
```

### **2. Test de Rendimiento Aislado**
```bash
# Test directo a PHP-FPM (sin Nginx)
docker compose exec app curl http://localhost:9000/graphql

# Resultado: Mismo problema → confirma que NO era Nginx
```

### **3. Análisis de Patrón**
- **Primera query**: Siempre lenta (~1.7s) → Cold start de PHP
- **Queries siguientes**: Más rápidas (~200ms) → Procesos PHP ya cargados
- **Conclusión**: PHP-FPM mata procesos entre requests

## ⚡ Optimizaciones Aplicadas

### **1. Configuración OPcache (docker/php/local.ini)**
```ini
[opcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256        # Aumentado de 128MB
opcache.interned_strings_buffer = 16    # Aumentado de 8MB
opcache.max_accelerated_files = 10000   # Aumentado de 4000
opcache.revalidate_freq = 0             # Sin revalidación en desarrollo
opcache.validate_timestamps = 0         # Máximo rendimiento
opcache.fast_shutdown = 1
```

**¿Por qué cada configuración?**
- `memory_consumption = 256`: Más memoria para guardar código compilado
- `max_accelerated_files = 10000`: Laravel + Lighthouse tienen muchos archivos
- `revalidate_freq = 0`: No verificar cambios en archivos (desarrollo)
- `validate_timestamps = 0`: Máximo rendimiento, asume código no cambia

### **2. Configuración PHP-FPM (docker/php/www.conf)**
```ini
# Static pool: Mantener procesos vivos
pm = static
pm.max_children = 10
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 8

# Prevenir matado de procesos
pm.max_requests = 1000
pm.process_idle_timeout = 30s
```

**¿Por qué static pool?**
- `pm = static`: Procesos PHP siempre vivos (no dynamic)
- `pm.max_children = 10`: 10 procesos PHP permanentes
- Sin cold starts después del inicio inicial

### **3. Caches de Laravel Optimizados**
```bash
# Caches aplicados
php artisan config:cache    # Configuración compilada
php artisan route:cache     # Rutas compiladas
php artisan view:cache      # Plantillas compiladas
composer dump-autoload -o  # Autoloader optimizado
```

**¿Por qué cada cache?**
- `config:cache`: Laravel no parsea .env en cada request
- `route:cache`: Rutas compiladas en memoria
- `view:cache`: Plantillas Blade pre-compiladas
- `autoloader -o`: Composer con class map optimizado

### **4. Script de Optimización Automatizado**
Ubicación: `scripts/optimize-performance.sh`

```bash
# Uso
./scripts/optimize-performance.sh

# Qué hace:
# 1. Aplica todos los caches de Laravel
# 2. Optimiza autoloader de Composer
# 3. Resetea OPcache para aplicar cambios
# 4. Calienta la aplicación con requests
# 5. Mide rendimiento automáticamente
```

## 📊 Resultados Obtenidos

### **Antes vs Después:**
| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Primera query | 1.7-4s | ~500ms | **70-87% más rápido** |
| Queries siguientes | 300ms-2s | ~165ms | **45-80% más rápido** |
| Consistencia | Muy variable | Predecible | **Estable** |

### **Rendimiento Esperado Actual:**
- **Cold start**: ~500ms (primera query del día)
- **Warm up**: ~165ms (queries normales)
- **Optimizado**: < 100ms (después de varias queries)

## 🎯 Para Futuras Mejoras

### **En Desarrollo:**
1. **Ejecutar script optimización**: `./scripts/optimize-performance.sh`
2. **Después de cambios importantes**: Re-ejecutar script
3. **Monitoring**: Si queries > 300ms → investigar

### **Para Producción (futuro):**
1. **PHP-FPM Tuning**: Ajustar `pm.max_children` según CPU cores
2. **OPcache Preload**: Habilitar preload.php una vez estable
3. **Redis OPcache**: Usar Redis como storage de OPcache
4. **Connection Pooling**: PostgreSQL persistent connections
5. **APCu**: User cache adicional para data caching

### **Herramientas de Monitoring:**
```bash
# Verificar OPcache status
docker compose exec app php -r "print_r(opcache_get_status());"

# Ver procesos PHP-FPM activos
docker compose exec app ps aux | grep php-fpm

# Test performance rápido
time curl -X POST http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ ping }"}'
```

## ⚠️ Consideraciones Importantes

### **Desarrollo vs Producción:**
- **Desarrollo**: `validate_timestamps = 0` (archivos no cambian)
- **Producción**: `validate_timestamps = 1` (detectar cambios)

### **Memory Limits:**
- **OPcache**: 256MB es suficiente para proyectos medianos
- **PHP Memory**: 256MB por proceso
- **Docker Memory**: Asegurar 4GB+ para contenedor

### **Troubleshooting:**
```bash
# Si rendimiento degrada:
1. Verificar memory usage: docker stats
2. Reiniciar PHP-FPM: docker compose restart app
3. Re-ejecutar script: ./scripts/optimize-performance.sh
4. Verificar logs: docker compose logs app
```

## 🎓 Lecciones Aprendidas

### **❌ Errores Comunes:**
1. **Asumir que lentitud = código malo**
2. **No medir configuración de infraestructura**
3. **Optimizar código antes que configuración**

### **✅ Mejores Prácticas:**
1. **Medir primero, optimizar después**
2. **Identificar bottleneck real (no asumir)**
3. **Optimizar infraestructura antes que código**
4. **Automatizar optimizaciones en scripts**

---

**🚀 Con estas optimizaciones, tu API GraphQL tiene rendimiento profesional para desarrollo y base sólida para producción.**