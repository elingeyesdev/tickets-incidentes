#!/bin/bash
set -e

echo "🎨 Starting Vite container initialization..."

# Install/Update npm dependencies if needed
# Usamos el lock file para una comprobación más robusta
if [ ! -d "node_modules" ] || [ ! -f "package-lock.json" ]; then
    echo "📦 Installing npm dependencies..."
    npm install
else
    echo "✅ npm dependencies already installed"
fi

echo "✅ Vite initialization complete!"
echo "🚀 Starting Vite development server..."
echo ""

# Execute the main container command
# Esto ejecutará CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
exec "$@"
