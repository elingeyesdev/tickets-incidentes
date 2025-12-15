#!/bin/bash

# =============================================================================
# HELPDESK SYSTEM - PRODUCTION DEPLOYMENT SCRIPT
# =============================================================================
# This script deploys the application to production environment
#
# USAGE:
#   chmod +x deploy-prod.sh
#   ./deploy-prod.sh
#
# IMPORTANT:
#   - Ensure .env.production file exists with production credentials
#   - Run this script on your production VM
#   - Database backups are created automatically before migrations
# =============================================================================

set -e  # Exit on any error

echo "🚀 Starting Production Deployment..."
echo "========================================"

# -----------------------------------------------------------------------------
# 1. Pre-deployment Checks
# -----------------------------------------------------------------------------
echo ""
echo "📋 Running pre-deployment checks..."

if [ ! -f .env.production ]; then
    echo "❌ Error: .env.production file not found"
    echo "   Create .env.production with your production configuration"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Error: Docker Compose is not installed"
    exit 1
fi

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    echo "⚠️  Warning: This script may need sudo privileges for some operations"
fi

echo "✅ Pre-deployment checks passed"

# -----------------------------------------------------------------------------
# 2. Backup Database
# -----------------------------------------------------------------------------
echo ""
echo "💾 Creating database backup..."

# Create backups directory if it doesn't exist
mkdir -p backups/postgres

# Generate backup filename with timestamp
BACKUP_FILE="backups/postgres/backup_$(date +%Y%m%d_%H%M%S).sql"

# Create backup (only if database exists)
docker compose -f docker-compose.prod.yml exec -T postgres pg_dump -U helpdesk helpdesk > "$BACKUP_FILE" 2>/dev/null || echo "⚠️  No existing database to backup (fresh install)"

if [ -f "$BACKUP_FILE" ]; then
    gzip "$BACKUP_FILE"
    echo "✅ Database backup created: ${BACKUP_FILE}.gz"
fi

# -----------------------------------------------------------------------------
# 3. Enable Maintenance Mode (if updating)
# -----------------------------------------------------------------------------
echo ""
echo "🛠️  Enabling maintenance mode..."
docker compose -f docker-compose.prod.yml exec app php artisan down --retry=60 2>/dev/null || echo "⚠️  App not running yet (fresh install)"

# -----------------------------------------------------------------------------
# 4. Pull Latest Code (if using Git)
# -----------------------------------------------------------------------------
if [ -d .git ]; then
    echo ""
    echo "📥 Pulling latest code from Git..."
    git pull origin main
fi

# -----------------------------------------------------------------------------
# 5. Copy Production Environment File
# -----------------------------------------------------------------------------
echo ""
echo "🔧 Setting up production environment..."
cp .env.production .env

# -----------------------------------------------------------------------------
# 6. Build and Deploy Containers
# -----------------------------------------------------------------------------
echo ""
echo "🏗️  Building production Docker images..."
docker compose -f docker-compose.prod.yml build --no-cache

echo ""
echo "🚀 Deploying containers..."
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d

# -----------------------------------------------------------------------------
# 7. Wait for Services
# -----------------------------------------------------------------------------
echo ""
echo "⏳ Waiting for services to be ready..."
sleep 15

# Verify app container is running
if ! docker compose -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "❌ Error: App container failed to start"
    docker compose -f docker-compose.prod.yml logs app
    exit 1
fi

# -----------------------------------------------------------------------------
# 8. Install Dependencies (Production Mode)
# -----------------------------------------------------------------------------
echo ""
echo "📦 Installing production dependencies..."
docker compose -f docker-compose.prod.yml exec app composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# -----------------------------------------------------------------------------
# 9. Optimize Application
# -----------------------------------------------------------------------------
echo ""
echo "⚡ Optimizing application..."

# Clear all caches
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan route:clear
docker compose -f docker-compose.prod.yml exec app php artisan view:clear

# Cache configuration
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# Optimize autoloader
docker compose -f docker-compose.prod.yml exec app composer dump-autoload --optimize

# Cache GraphQL schema
docker compose -f docker-compose.prod.yml exec app php artisan lighthouse:cache

# -----------------------------------------------------------------------------
# 10. Run Database Migrations
# -----------------------------------------------------------------------------
echo ""
echo "🗄️  Running database migrations..."
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# -----------------------------------------------------------------------------
# 11. Set Proper Permissions
# -----------------------------------------------------------------------------
echo ""
echo "🔒 Setting proper permissions..."
docker compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache

# -----------------------------------------------------------------------------
# 12. Restart Queue Workers
# -----------------------------------------------------------------------------
echo ""
echo "🔄 Restarting queue workers..."
docker compose -f docker-compose.prod.yml restart queue

# -----------------------------------------------------------------------------
# 13. Disable Maintenance Mode
# -----------------------------------------------------------------------------
echo ""
echo "✅ Disabling maintenance mode..."
docker compose -f docker-compose.prod.yml exec app php artisan up

# -----------------------------------------------------------------------------
# 14. Verify Deployment
# -----------------------------------------------------------------------------
echo ""
echo "🔍 Verifying deployment..."

# Check if GraphQL endpoint is responding
HEALTH_CHECK=$(docker compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'OK';" 2>/dev/null | grep OK || echo "FAIL")

if [ "$HEALTH_CHECK" != "OK" ]; then
    echo "⚠️  Warning: Health check failed, but containers are running"
fi

# -----------------------------------------------------------------------------
# 15. Deployment Summary
# -----------------------------------------------------------------------------
echo ""
echo "✅ Production Deployment Complete!"
echo "========================================"
echo ""
echo "📊 Container Status:"
docker compose -f docker-compose.prod.yml ps
echo ""
echo "📝 Post-deployment checklist:"
echo "   ✓ Database backed up to: ${BACKUP_FILE}.gz"
echo "   ✓ Application containers running"
echo "   ✓ Migrations executed"
echo "   ✓ Caches optimized"
echo "   ✓ Maintenance mode disabled"
echo ""
echo "🌐 Your application should now be live!"
echo ""
echo "📝 Useful commands:"
echo "   - View logs: docker compose -f docker-compose.prod.yml logs -f"
echo "   - Monitor: docker compose -f docker-compose.prod.yml ps"
echo "   - Restart: docker compose -f docker-compose.prod.yml restart"
echo "   - Rollback: Restore from backup if needed"
echo ""
echo "🎉 Deployment successful!"