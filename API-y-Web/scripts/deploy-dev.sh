#!/bin/bash

# =============================================================================
# HELPDESK SYSTEM - DEVELOPMENT DEPLOYMENT SCRIPT
# =============================================================================
# This script sets up and deploys the development environment
#
# USAGE:
#   chmod +x deploy-dev.sh
#   ./deploy-dev.sh
# =============================================================================

set -e  # Exit on any error

echo "🚀 Starting Development Deployment..."
echo "========================================"

# -----------------------------------------------------------------------------
# 1. Check Requirements
# -----------------------------------------------------------------------------
echo ""
echo "📋 Checking requirements..."

if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Error: Docker Compose is not installed"
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"

# -----------------------------------------------------------------------------
# 2. Environment Configuration
# -----------------------------------------------------------------------------
echo ""
echo "🔧 Checking environment configuration..."

if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Copying from .env.example..."
    cp .env.example .env
    echo "⚠️  IMPORTANT: Edit .env and set APP_KEY before continuing"
    echo "⚠️  Run: docker compose exec app php artisan key:generate"
    echo ""
fi

# -----------------------------------------------------------------------------
# 3. Stop and Remove Existing Containers
# -----------------------------------------------------------------------------
echo ""
echo "🛑 Stopping existing containers..."
docker compose down

# -----------------------------------------------------------------------------
# 4. Build and Start Containers
# -----------------------------------------------------------------------------
echo ""
echo "🏗️  Building Docker images..."
docker compose build --no-cache

echo ""
echo "🚀 Starting containers..."
docker compose up -d

# -----------------------------------------------------------------------------
# 5. Wait for Services to be Ready
# -----------------------------------------------------------------------------
echo ""
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check if app container is running
if ! docker compose ps app | grep -q "Up"; then
    echo "❌ Error: App container failed to start"
    docker compose logs app
    exit 1
fi

# -----------------------------------------------------------------------------
# 6. Install Dependencies
# -----------------------------------------------------------------------------
echo ""
echo "📦 Installing Composer dependencies..."
docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

echo ""
echo "📦 Installing NPM dependencies..."
docker compose exec app npm install

# -----------------------------------------------------------------------------
# 7. Generate Application Key (if needed)
# -----------------------------------------------------------------------------
echo ""
echo "🔑 Checking application key..."
if ! docker compose exec app grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    docker compose exec app php artisan key:generate
fi

# -----------------------------------------------------------------------------
# 8. Run Database Migrations
# -----------------------------------------------------------------------------
echo ""
echo "🗄️  Running database migrations..."
docker compose exec app php artisan migrate --force

# -----------------------------------------------------------------------------
# 9. Clear Caches
# -----------------------------------------------------------------------------
echo ""
echo "🧹 Clearing caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# -----------------------------------------------------------------------------
# 10. Set Permissions
# -----------------------------------------------------------------------------
echo ""
echo "🔒 Setting storage permissions..."
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache

# -----------------------------------------------------------------------------
# 11. Build Frontend Assets
# -----------------------------------------------------------------------------
echo ""
echo "🎨 Building frontend assets..."
docker compose exec app npm run build

# -----------------------------------------------------------------------------
# 12. Verify Deployment
# -----------------------------------------------------------------------------
echo ""
echo "✅ Deployment Complete!"
echo "========================================"
echo ""
echo "📊 Container Status:"
docker compose ps
echo ""
echo "🌐 Application URLs:"
echo "   - Application: http://localhost:8000"
echo "   - GraphQL API: http://localhost:8000/graphql"
echo "   - GraphiQL IDE: http://localhost:8000/graphiql"
echo "   - Mailpit UI: http://localhost:8025"
echo "   - Database: localhost:5432 (user: helpdesk, pass: helpdesk_password)"
echo "   - Redis: localhost:6379"
echo ""
echo "📝 Useful commands:"
echo "   - View logs: docker compose logs -f"
echo "   - Stop: docker compose down"
echo "   - Restart: docker compose restart"
echo "   - Enter container: docker compose exec app bash"
echo ""
echo "🎉 Happy coding!"