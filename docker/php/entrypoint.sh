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

# --- 2. Install/Update composer dependencies (as root to avoid permission issues) ---
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing Composer dependencies..."
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Composer dependencies already installed"
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

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# --- 4. Generate APP_KEY if not set ---
if [ ! -f .env ] || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating Laravel application key..."
    php artisan key:generate --force
else
    echo "✅ Application key already set"
fi

# --- 5. Run migrations ---
echo "🗄️  Running database migrations..."
php artisan migrate --force

# --- 5.1. Seed default user ---
echo "👤 Seeding default user..."
php artisan db:seed --class="Database\\Seeders\\DatabaseSeeder" || true
php artisan db:seed --class="App\\Features\\UserManagement\\Database\\Seeders\\DefaultUserSeeder" || true

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

echo "✅ Helpdesk initialization complete!"
echo ""

# --- 8. Execute main container command ---
echo "🚀 Executing main container command: $@"
exec "$@"