#!/bin/bash
set -e

echo "🚀 Starting Helpdesk container initialization..."

# --- 1. Wait for PostgreSQL to be ready ---
echo "⏳ Waiting for PostgreSQL to be ready..."
until pg_isready -h "$DB_HOST" -U "$DB_USERNAME" > /dev/null 2>&1; do
    echo "   PostgreSQL is unavailable - sleeping"
    sleep 2
done
echo "✅ PostgreSQL is ready!"

# Additional safety pause for PostgreSQL stabilization
echo "⏳ Waiting 5 seconds for PostgreSQL stability..."
sleep 5

# --- 2. Verify/Install composer dependencies (Multi-environment) ---
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    if [ "$APP_ENV" = "local" ]; then
        # DEVELOPMENT (Windows): vendor/ should be from Windows mount
        echo "❌ ERROR: Composer dependencies not found!"
        echo ""
        echo "📍 You are in DEVELOPMENT mode (Windows + Docker)"
        echo ""
        echo "⚠️  IMPORTANT: Install dependencies on Windows:"
        echo "   1. Open CMD/PowerShell on Windows"
        echo "   2. Run: composer install"
        echo "   3. Restart Docker: docker compose down && docker compose up -d"
        echo ""
        exit 1
    else
        # PRODUCTION (Linux): auto-install in container
        echo "📦 Installing Composer dependencies (Production mode)..."
        composer install \
            --prefer-dist \
            --no-dev \
            --no-interaction \
            --timeout=2400 \
            --no-suggest

        if [ ! -f "vendor/autoload.php" ]; then
            echo "❌ ERROR: Failed to install Composer dependencies!"
            exit 1
        fi
        echo "✅ Composer dependencies installed!"
    fi
else
    if [ "$APP_ENV" = "local" ]; then
        echo "✅ Composer dependencies found (using vendor/ from Windows)"
    else
        echo "✅ Composer dependencies found (Production ready)"
    fi
fi

# --- 3. Setup storage directories ---
echo "📁 Setting up storage directories..."
mkdir -p storage/logs \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/app/public \
         bootstrap/cache

# Set permissions recursively (required after restarts on Windows)
# First change ownership, then permissions
chown -R www-data:www-data storage bootstrap/cache

# Use 777 in development (Windows Docker has permission issues with mounted volumes)
# Use 775 in production for better security
if [ "$APP_ENV" = "local" ]; then
    chmod -R 777 storage bootstrap/cache
else
    chmod -R 775 storage bootstrap/cache
fi

echo "✅ Storage directories permissions fixed"

# --- 4. Generate APP_KEY if not set (run as helpdesk for correct permissions) ---
if [ ! -f .env ] || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating Laravel application key..."
    su -s /bin/bash helpdesk -c "php artisan key:generate --force"
else
    echo "✅ Application key already set"
fi

# --- 5. Run migrations (only if vendor exists) ---
# Execute as helpdesk user to ensure all generated files have correct ownership
if [ -f "vendor/autoload.php" ]; then
    echo "🗄️  Running database migrations..."
    su -s /bin/bash helpdesk -c "php artisan migrate --force"

    # --- 5.1. Seed database (roles + default user) ---
    echo "🌱 Seeding database..."
    su -s /bin/bash helpdesk -c "php artisan db:seed --class='Database\\Seeders\\DatabaseSeeder'" || true

    # --- 6. Clear and optimize cache ---
    echo "🧹 Clearing and optimizing cache..."
    su -s /bin/bash helpdesk -c "php artisan config:clear"
    su -s /bin/bash helpdesk -c "php artisan route:clear"
    su -s /bin/bash helpdesk -c "php artisan view:clear"
    su -s /bin/bash helpdesk -c "php artisan cache:clear"

    echo "⚡ Optimizing application..."
    su -s /bin/bash helpdesk -c "php artisan config:cache"
    su -s /bin/bash helpdesk -c "php artisan route:cache"
    
    # Only cache views in production (avoid permission issues in development)
    if [ "$APP_ENV" != "local" ]; then
        su -s /bin/bash helpdesk -c "php artisan view:cache"
    else
        echo "⚠️  Skipping view cache (development mode - views compile on-demand)"
    fi

    # --- 7. Create storage link ---
    if [ ! -L "public/storage" ]; then
        echo "🔗 Creating storage symlink..."
        su -s /bin/bash helpdesk -c "php artisan storage:link"
    fi
else
    echo "⚠️  Skipping migrations and cache (vendor not installed)"
fi

echo "✅ Helpdesk initialization complete!"
echo ""

# --- 8. Execute main container command ---
echo "🚀 Executing main container command: $@"
exec "$@"