#!/bin/bash
# =============================================================================
# CLEAN TEST DATABASES - Script de Limpieza de Bases de Datos de Test
# =============================================================================
#
# Propósito:
#   Elimina TODAS las bases de datos de test corruptas para prevenir falsos
#   negativos en tests paralelos.
#
# Cuándo usar:
#   - Antes de ejecutar tests importantes
#   - Cuando los tests paralelos muestran varianza alta (>20%)
#   - Después de cambiar RefreshDatabase traits
#   - Cuando ves errores de "relation does not exist"
#
# Uso:
#   ./scripts/clean-test-databases.sh
#
# =============================================================================

set -e

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  CLEAN TEST DATABASES - Helpdesk System                     ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Verificar que Docker Compose está corriendo
if ! docker compose ps postgres | grep -q "running"; then
    echo "❌ ERROR: PostgreSQL container is not running"
    echo "   Run: docker compose up -d postgres"
    exit 1
fi

echo "🔍 Buscando bases de datos de test..."
echo ""

# Contar bases de datos de test
DB_COUNT=$(docker compose exec -T postgres psql -U helpdesk -t -c \
    "SELECT count(*) FROM pg_database WHERE datname LIKE 'helpdesk_test%';" | tr -d ' ')

if [ "$DB_COUNT" -eq "0" ]; then
    echo "✅ No hay bases de datos de test para limpiar"
    echo ""
    exit 0
fi

echo "📊 Encontradas: $DB_COUNT bases de datos de test"
echo ""

# Mostrar lista de bases de datos
echo "📋 Lista de bases de datos que serán eliminadas:"
echo "─────────────────────────────────────────────────"
docker compose exec -T postgres psql -U helpdesk -t -c \
    "SELECT '   - ' || datname FROM pg_database WHERE datname LIKE 'helpdesk_test%' ORDER BY datname;"
echo ""

# Confirmar
read -p "¿Continuar con la eliminación? (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Operación cancelada"
    exit 0
fi

echo ""
echo "🗑️  Eliminando bases de datos de test..."
echo ""

# Eliminar todas las bases de datos de test
docker compose exec -T postgres psql -U helpdesk -c \
    "SELECT 'DROP DATABASE IF EXISTS \"' || datname || '\";'
     FROM pg_database
     WHERE datname LIKE 'helpdesk_test%';" -t | \
    docker compose exec -T postgres psql -U helpdesk

echo ""
echo "✅ Limpieza completada: $DB_COUNT bases de datos eliminadas"
echo ""

# Verificar limpieza
REMAINING=$(docker compose exec -T postgres psql -U helpdesk -t -c \
    "SELECT count(*) FROM pg_database WHERE datname LIKE 'helpdesk_test%';" | tr -d ' ')

if [ "$REMAINING" -eq "0" ]; then
    echo "✅ Verificación: Todas las bases de datos de test fueron eliminadas"
else
    echo "⚠️  Advertencia: Quedan $REMAINING bases de datos de test"
    echo "   Intenta ejecutar el script nuevamente"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  Ahora puedes ejecutar tests limpios:"
echo "  docker compose exec app php artisan test --parallel --processes=16"
echo "═══════════════════════════════════════════════════════════════"
echo ""
