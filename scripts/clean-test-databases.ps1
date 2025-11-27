# =============================================================================
# CLEAN TEST DATABASES - Script de Limpieza de Bases de Datos de Test (PowerShell)
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
#   .\scripts\clean-test-databases.ps1
#   O desde Git Bash: pwsh scripts/clean-test-databases.ps1
#
# =============================================================================

Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  CLEAN TEST DATABASES - Helpdesk System                     ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Verificar que Docker Compose está corriendo
$postgresStatus = docker compose ps postgres | Select-String "running"
if (-not $postgresStatus) {
    Write-Host "❌ ERROR: PostgreSQL container is not running" -ForegroundColor Red
    Write-Host "   Run: docker compose up -d postgres" -ForegroundColor Yellow
    exit 1
}

Write-Host "🔍 Buscando bases de datos de test..." -ForegroundColor Yellow
Write-Host ""

# Contar bases de datos de test
$dbCountOutput = docker compose exec -T postgres psql -U helpdesk -t -c "SELECT count(*) FROM pg_database WHERE datname LIKE 'helpdesk_test%';"
$dbCount = [int]($dbCountOutput.Trim())

if ($dbCount -eq 0) {
    Write-Host "✅ No hay bases de datos de test para limpiar" -ForegroundColor Green
    Write-Host ""
    exit 0
}

Write-Host "📊 Encontradas: $dbCount bases de datos de test" -ForegroundColor Yellow
Write-Host ""

# Mostrar lista de bases de datos
Write-Host "📋 Lista de bases de datos que serán eliminadas:" -ForegroundColor Cyan
Write-Host "─────────────────────────────────────────────────" -ForegroundColor Gray
docker compose exec -T postgres psql -U helpdesk -t -c "SELECT '   - ' || datname FROM pg_database WHERE datname LIKE 'helpdesk_test%' ORDER BY datname;"
Write-Host ""

# Confirmar
$confirmation = Read-Host "¿Continuar con la eliminación? (y/N)"
if ($confirmation -ne 'y' -and $confirmation -ne 'Y') {
    Write-Host "❌ Operación cancelada" -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "🗑️  Eliminando bases de datos de test..." -ForegroundColor Yellow
Write-Host ""

# Eliminar todas las bases de datos de test
$dropCommands = docker compose exec -T postgres psql -U helpdesk -c "SELECT 'DROP DATABASE IF EXISTS \"' || datname || '\";' FROM pg_database WHERE datname LIKE 'helpdesk_test%';" -t
$dropCommands | docker compose exec -T postgres psql -U helpdesk

Write-Host ""
Write-Host "✅ Limpieza completada: $dbCount bases de datos eliminadas" -ForegroundColor Green
Write-Host ""

# Verificar limpieza
$remainingOutput = docker compose exec -T postgres psql -U helpdesk -t -c "SELECT count(*) FROM pg_database WHERE datname LIKE 'helpdesk_test%';"
$remaining = [int]($remainingOutput.Trim())

if ($remaining -eq 0) {
    Write-Host "✅ Verificación: Todas las bases de datos de test fueron eliminadas" -ForegroundColor Green
} else {
    Write-Host "⚠️  Advertencia: Quedan $remaining bases de datos de test" -ForegroundColor Yellow
    Write-Host "   Intenta ejecutar el script nuevamente"
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  Ahora puedes ejecutar tests limpios:" -ForegroundColor White
Write-Host "  docker compose exec app php artisan test --parallel --processes=16" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
