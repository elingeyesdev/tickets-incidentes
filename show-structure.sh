#!/bin/bash
# Script para mostrar la estructura del proyecto de forma eficiente

DEPTH=${1:-3}
IGNORE_DIRS="node_modules|vendor|.git|storage|bootstrap|.idea|__pycache__|tests"

echo "📁 Estructura del Proyecto Helpdesk (Profundidad: $DEPTH)"
echo "=============================================="
tree -L "$DEPTH" -I "$IGNORE_DIRS" /home/luke/Projects/Helpdesk/

echo ""
echo "💡 Uso: ./show-structure.sh [profundidad]"
echo "   Ejemplo: ./show-structure.sh 4"
