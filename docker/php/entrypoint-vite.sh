#!/bin/bash
set -e

echo "🎨 Starting Vite container initialization..."

# Install/Update npm dependencies if needed
if [ ! -d "node_modules" ] || [ ! -f "node_modules/.package-lock.json" ]; then
    echo "📦 Installing npm dependencies..."
    npm install
else
    echo "✅ npm dependencies already installed"
fi

echo "✅ Vite initialization complete!"
echo "🚀 Starting Vite development server..."
echo ""

# Execute the main container command
exec "$@"
