#!/bin/bash
set -e

echo "🚀 Starting Helpdesk container initialization..."

# Wait for database to be ready
echo "⏳ Waiting for PostgreSQL to be ready..."
until pg_isready -h "$DB_HOST" -U "$DB_USERNAME" > /dev/null 2>&1; do
    echo "   PostgreSQL is unavailable - sleeping"
    sleep 2
done
echo "✅ PostgreSQL is ready!"

# Install/Update composer dependencies if needed
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Composer dependencies already installed"
fi

# Create storage directories and set permissions
echo "📁 Setting up storage directories..."
mkdir -p storage/logs \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/app/public \
         bootstrap/cache

# Fix permissions (as helpdesk user, we can only set what we own)
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Generate APP_KEY if not set
if grep -q "APP_KEY=$" .env 2>/dev/null || ! grep -q "APP_KEY=" .env 2>/dev/null; then
    echo "🔑 Generating Laravel application key..."
    php artisan key:generate --force
else
    echo "✅ Application key already set"
fi

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and optimize cache
echo "🧹 Clearing and optimizing cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if it doesn't exist
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

echo "✅ Helpdesk initialization complete!"
echo ""

# Execute the main container command
exec "$@"
