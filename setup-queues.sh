#!/bin/bash

# Script de configuración rápida para el sistema de colas
# Autor: Sistema de Validación de Clientes
# Fecha: 2024

echo "=========================================="
echo "  Configuración de Sistema de Colas"
echo "=========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Preguntar al usuario qué configuración quiere
echo "Selecciona el entorno:"
echo "1) Desarrollo (sync - sin worker)"
echo "2) Producción (database - con worker)"
echo ""
read -p "Opción (1 o 2): " option

case $option in
    1)
        echo ""
        echo "Configurando para DESARROLLO..."
        echo ""
        
        # Configurar sync
        if grep -q "QUEUE_CONNECTION=" .env; then
            sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
            print_success "QUEUE_CONNECTION actualizado a 'sync'"
        else
            echo "QUEUE_CONNECTION=sync" >> .env
            print_success "QUEUE_CONNECTION agregado como 'sync'"
        fi
        
        # Limpiar caché
        php artisan config:clear > /dev/null 2>&1
        print_success "Caché de configuración limpiado"
        
        echo ""
        print_success "Configuración completada!"
        echo ""
        echo "Con 'sync', los jobs se ejecutan inmediatamente sin necesidad de worker."
        echo "Esto es ideal para desarrollo y testing."
        echo ""
        ;;
        
    2)
        echo ""
        echo "Configurando para PRODUCCIÓN..."
        echo ""
        
        # Configurar database
        if grep -q "QUEUE_CONNECTION=" .env; then
            sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env
            print_success "QUEUE_CONNECTION actualizado a 'database'"
        else
            echo "QUEUE_CONNECTION=database" >> .env
            print_success "QUEUE_CONNECTION agregado como 'database'"
        fi
        
        # Crear tabla de jobs
        echo ""
        echo "Creando tabla de jobs..."
        php artisan queue:table > /dev/null 2>&1
        print_success "Migración de tabla de jobs creada"
        
        # Ejecutar migraciones
        echo ""
        echo "Ejecutando migraciones..."
        php artisan migrate --force
        print_success "Migraciones ejecutadas"
        
        # Limpiar caché
        php artisan config:clear > /dev/null 2>&1
        print_success "Caché de configuración limpiado"
        
        echo ""
        print_success "Configuración completada!"
        echo ""
        print_warning "IMPORTANTE: Debes iniciar el queue worker:"
        echo ""
        echo "  php artisan queue:work --tries=3 --timeout=300"
        echo ""
        echo "O configurar Supervisor para mantenerlo corriendo en producción."
        echo "Ver SOLUCION_TIMEOUT_USUARIOS.md para más detalles."
        echo ""
        ;;
        
    *)
        print_error "Opción inválida"
        exit 1
        ;;
esac

# Verificar configuración
echo "=========================================="
echo "  Verificación de Configuración"
echo "=========================================="
echo ""

QUEUE_CONN=$(grep "QUEUE_CONNECTION=" .env | cut -d '=' -f2)
echo "Driver de colas: $QUEUE_CONN"

if [ "$QUEUE_CONN" = "database" ]; then
    echo ""
    print_warning "Recuerda iniciar el worker:"
    echo "  php artisan queue:work"
fi

echo ""
echo "=========================================="
echo "  ¡Listo para usar!"
echo "=========================================="
echo ""
echo "Ahora puedes crear usuarios sin problemas de timeout."
echo "Los productos se copiarán en segundo plano."
echo ""
