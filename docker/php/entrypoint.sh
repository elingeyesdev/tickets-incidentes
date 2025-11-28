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
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Storage directories permissions fixed"

# --- 4. Generate APP_KEY if not set ---
if [ ! -f .env ] || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating Laravel application key..."
    php artisan key:generate --force
else
    echo "✅ Application key already set"
fi

# --- 5. Run migrations (only if vendor exists) ---
if [ -f "vendor/autoload.php" ]; then
    echo "🗄️  Running database migrations..."
    php artisan migrate --force

    # --- 5.1. Seed database (roles + default user) ---
    echo "🌱 Seeding database..."
    php artisan db:seed --class="Database\\Seeders\\DatabaseSeeder" || true

    # --- 6. Clear and optimize cache ---
    echo "🧹 Clearing and optimizing cache..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear

    echo "⚡ Optimizing application..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # --- 7. Create storage link ---
    if [ ! -L "public/storage" ]; then
        echo "🔗 Creating storage symlink..."
        php artisan storage:link
    fi
else
    echo "⚠️  Skipping migrations and cache (vendor not installed)"
fi

echo "✅ Helpdesk initialization complete!"
echo ""

# --- 8. Execute main container command ---
echo "🚀 Executing main container command: $@"
exec "$@"