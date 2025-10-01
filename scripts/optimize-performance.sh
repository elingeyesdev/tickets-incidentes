
#!/bin/bash
# Performance optimization script for Laravel Docker

echo "🚀 Optimizing Laravel Performance..."

# 1. Cache Laravel configurations
echo "📦 Caching Laravel configurations..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# 2. Optimize Composer autoloader
echo "🎵 Optimizing Composer autoloader..."
docker compose exec app composer dump-autoload -o

# 3. Clear and warm up OPcache
echo "⚡ Resetting OPcache..."
docker compose exec app php artisan optimize:clear
docker compose exec app php -r "if(function_exists('opcache_reset')) opcache_reset();"

# 4. Warm up application
echo "🔥 Warming up application..."
for i in {1..3}; do
    docker compose exec app curl -s http://localhost:9000/health > /dev/null 2>&1 || true
done

# 5. Test GraphQL performance
echo "🧪 Testing GraphQL performance..."
for i in {1..3}; do
    echo "Test $i:"
    time curl -s -X POST http://localhost:8000/graphql \
        -H "Content-Type: application/json" \
        -d '{"query": "{ ping }"}' > /dev/null
done

echo "✅ Performance optimization complete!"
echo ""
echo "Expected performance:"
echo "- First request: ~200-500ms (cold start)"
echo "- Subsequent requests: <100ms"
