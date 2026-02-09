#!/bin/bash

# Script para probar la funcionalidad de recuperación de contraseña

echo "======================================"
echo "PRUEBA: Recuperación de Contraseña"
echo "======================================"
echo ""

# Verificar que las rutas estén registradas
echo "1. Verificando rutas de password reset..."
php artisan route:list --name=password
echo ""

# Limpiar tokens antiguos (opcional)
echo "2. Limpiando tokens de reset antiguos..."
php artisan db:table password_reset_tokens --truncate 2>/dev/null || echo "Tabla limpia"
echo ""

# Verificar configuración de email
echo "3. Configuración de correo actual:"
echo "   MAIL_MAILER: $(grep MAIL_MAILER .env | cut -d '=' -f2)"
echo "   MAIL_HOST: $(grep MAIL_HOST .env | cut -d '=' -f2)"
echo "   MAIL_FROM_ADDRESS: $(grep MAIL_FROM_ADDRESS .env | cut -d '=' -f2)"
echo ""

echo "======================================"
echo "INSTRUCCIONES DE PRUEBA:"
echo "======================================"
echo ""
echo "Para probar la recuperación de contraseña:"
echo ""
echo "1. Asegúrate de tener un usuario registrado"
echo "2. Visita: http://localhost/forgot-password"
echo "3. Ingresa el correo del usuario"
echo "4. Revisa el correo en Mailpit: http://localhost:8025"
echo "5. Haz clic en el enlace del correo"
echo "6. Ingresa la nueva contraseña"
echo ""
echo "Mailpit (desarrollo):"
echo "   URL: http://localhost:8025"
echo "   Todos los correos enviados aparecerán aquí"
echo ""
echo "======================================"
echo "PRUEBA MANUAL CON CURL:"
echo "======================================"
echo ""
echo "# Solicitar enlace de recuperación:"
echo 'curl -X POST http://localhost/forgot-password \'
echo '  -H "Content-Type: application/x-www-form-urlencoded" \'
echo '  -d "email=tu-email@ejemplo.com&_token=$(obtener token CSRF)"'
echo ""
echo "# Ver tokens generados:"
echo "docker exec localllservicios php artisan tinker --execute=\"DB::table('password_reset_tokens')->get()\""
echo ""
