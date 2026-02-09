# Instrucciones de Instalación AFIP en Docker

Este documento describe cómo instalar y configurar AFIP SDK cuando el proyecto corre sobre Docker.

## 🐳 Instalación en Docker

### Opción 1: Usar el script automatizado (Recomendado)

```bash
bash install_afip.sh
```

El script detecta automáticamente si estás usando Docker y ejecuta los comandos dentro del contenedor `llservicios`.

### Opción 2: Instalación manual en Docker

```bash
# 1. Instalar el paquete dentro del contenedor
docker exec -it llservicios composer require afipsdk/afip.php

# 2. Crear estructura de directorios
docker exec -it llservicios mkdir -p storage/app/afip/empresas

# 3. Ajustar permisos
docker exec -it llservicios chmod -R 775 storage/app/afip
docker exec -it llservicios chown -R www-data:www-data storage/app/afip

# 4. Ejecutar migraciones
docker exec -it llservicios php artisan migrate --force

# 5. Reiniciar contenedor
docker restart llservicios
```

## 📝 Configuración

### 1. Agregar variables al archivo `.env`

```env
AFIP_PRODUCTION=false
AFIP_PUNTO_VENTA=1
AFIP_TIPO_COMPROBANTE=6
AFIP_IVA_DEFAULT=21
AFIP_ACCESS_TOKEN=tu_access_token
AFIP_AUTOMATION_DEV=create-cert-dev
AFIP_AUTOMATION_PROD=create-cert-prod
```

### 2. Reiniciar los servicios Docker

```bash
docker-compose restart
```

O reiniciar solo el contenedor principal:

```bash
docker restart llservicios
```

## 📁 Ubicación de archivos en Docker

Los archivos se almacenan en el volumen montado:

```
Host: ./storage/app/afip/empresas/{cuit}/
Container: /var/www/html/storage/app/afip/empresas/{cuit}/
```

Estructura de cada empresa:
```
storage/app/afip/empresas/{cuit}/
├── certificate.crt      # Certificado de AFIP
├── private.key          # Clave privada
├── ta/                  # Tickets de acceso (generados automáticamente)
└── res/                 # Respuestas de AFIP (generadas automáticamente)
```

## 🔐 Permisos importantes

Los directorios deben tener permisos de escritura para el usuario `www-data` dentro del contenedor:

```bash
# Verificar permisos actuales
docker exec llservicios ls -la storage/app/afip/

# Corregir permisos si es necesario
docker exec llservicios chmod -R 775 storage/app/afip
docker exec llservicios chown -R www-data:www-data storage/app/afip
```

## 🧪 Probar la instalación

### Desde el navegador
Accede a: `http://localllservicios/afip/test-conexion`

### Desde la línea de comandos
```bash
docker exec -it llservicios php artisan tinker

# Dentro de tinker:
$afip = new \App\Services\AfipService(1); // ID de tu empresa
$afip->obtenerUltimoComprobante(1, 6);
```

## 🔧 Comandos útiles para Docker

### Ver logs del contenedor
```bash
docker logs llservicios
docker logs -f llservicios  # Seguir logs en tiempo real
```

### Acceder al contenedor
```bash
docker exec -it llservicios bash
```

### Ejecutar comandos Artisan
```bash
docker exec -it llservicios php artisan [comando]
```

### Ver archivos en storage
```bash
docker exec llservicios ls -la storage/app/afip/empresas/
```

## 📊 Verificar instalación del paquete

```bash
# Verificar que el paquete esté instalado
docker exec llservicios composer show afipsdk/afip.php

# Ver dependencias
docker exec llservicios composer show -t | grep afip
```

## 🐛 Solución de problemas comunes

### Error: "Permission denied" al crear directorios

```bash
docker exec llservicios chmod -R 777 storage/app/afip
```

### Error: "Class 'Afip\Afip' not found"

```bash
docker exec llservicios composer dump-autoload
docker restart llservicios
```

### Error: Certificados no encontrados

Verifica que los archivos existen:
```bash
docker exec llservicios ls -la storage/app/afip/empresas/{cuit}/
```

### Las migraciones no se aplican

```bash
docker exec llservicios php artisan migrate:status
docker exec llservicios php artisan migrate --force
```

## 🔄 Actualizar el paquete AFIP

```bash
docker exec -it llservicios composer update afipsdk/afip.php
docker restart llservicios
```

## 📦 Volumes y persistencia

Los certificados y datos de AFIP persisten porque el directorio está dentro del volumen montado:

```yaml
volumes:
  - .:/var/www/html
```

Esto significa que:
- ✅ Los certificados sobreviven a reinicios de contenedores
- ✅ Los cambios en el host se reflejan en el contenedor
- ✅ No necesitas backup adicional (el backup del host incluye los certificados)

## 🚀 Despliegue en producción con Docker

1. **Cambiar a modo producción en `.env`:**
```env
AFIP_PRODUCTION=true
```

2. **Usar certificados de producción** (no los de testing)

3. **Reiniciar servicios:**
```bash
docker-compose restart
```

4. **Verificar logs:**
```bash
docker logs llservicios | grep -i afip
```

## 📚 Documentación adicional

- Documentación completa: [AFIP_INTEGRACION.md](AFIP_INTEGRACION.md)
- Docker Compose del proyecto: [docker-compose.yml](docker-compose.yml)
