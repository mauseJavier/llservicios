# Integración AFIP - Facturación Electrónica

Este documento describe la integración de AFIP (Administración Federal de Ingresos Públicos) para facturación electrónica en el sistema.

## 📋 Características

- ✅ Facturación electrónica (Facturas A, B, C)
- ✅ Notas de crédito y débito
- ✅ Recibos electrónicos
- ✅ Consulta de contribuyentes por CUIT
- ✅ Gestión de certificados por empresa
- ✅ Integración automática desde pagos
- ✅ Soporte multi-empresa

## 🚀 Instalación

### 1. Ejecutar el script de instalación

```bash
bash install_afip.sh
```

O manualmente:

```bash
composer require afipsdk/afip.php
php artisan migrate
```

### 2. Configurar variables de entorno

Agregar al archivo `.env`:

```env
AFIP_PRODUCTION=false
AFIP_PUNTO_VENTA=1
AFIP_TIPO_COMPROBANTE=6
AFIP_IVA_DEFAULT=21
AFIP_ACCESS_TOKEN=tu_access_token
AFIP_AUTOMATION_DEV=create-cert-dev
AFIP_AUTOMATION_PROD=create-cert-prod
```

### 3. Obtener certificados de AFIP

#### Entorno de Testing (Homologación)
1. Ir a: https://www.afip.gob.ar/ws/WSAA/alias.aspx
2. Generar un certificado de testing
3. Descargar el certificado (.crt) y la clave privada (.key)

#### Entorno de Producción
1. Solicitar certificado digital desde tu cuenta de AFIP
2. Seguir el proceso oficial de AFIP para certificados de producción

### 4. Subir certificados

Los certificados se suben desde el panel de administración de AFIP en la aplicación:
- Navegar a: `/afip`
- Subir `certificate.crt` y `private.key`

## 📁 Estructura de archivos

```
app/
├── Services/
│   └── AfipService.php          # Servicio principal de AFIP
├── Http/Controllers/
│   └── AfipController.php        # Controlador de AFIP
config/
└── afip.php                      # Configuración de AFIP
storage/
└── app/afip/empresas/
    └── {cuit}/
        ├── certificate.crt       # Certificado de AFIP
        ├── private.key           # Clave privada
        ├── ta/                   # Tickets de acceso
        └── res/                  # Respuestas de AFIP
```

## 🔧 Uso básico

### Crear factura desde un pago

```php
use App\Services\AfipService;

$afipService = new AfipService($empresaId);
$resultado = $afipService->crearFacturaDesdePago($pago, $puntoVenta = 1, $tipoComprobante = 6);

if ($resultado['success']) {
    echo "CAE: " . $resultado['cae'];
    echo "Número: " . $resultado['numero_comprobante'];
}
```

### Crear factura manualmente

```php
$facturaData = [
    'PtoVta' => 1,
    'CbteTipo' => 6,           // 6 = Factura B
    'Concepto' => 2,           // 2 = Servicios
    'DocTipo' => 96,           // 96 = DNI
    'DocNro' => 12345678,
    'ImpTotal' => 121.00,
    'ImpNeto' => 100.00,
    'ImpIVA' => 21.00,
    'Iva' => [
        [
            'Id' => 5,         // 5 = 21%
            'BaseImp' => 100.00,
            'Importe' => 21.00
        ]
    ]
];

$resultado = $afipService->crearFactura($facturaData);
```

### Consultar contribuyente

```php
$resultado = $afipService->consultarContribuyente('20123456789');

if ($resultado['success']) {
    $datos = $resultado['data'];
    // Acceder a información del contribuyente
}
```

## 📊 Tipos de comprobantes

| Código | Descripción |
|--------|-------------|
| 1 | Factura A |
| 6 | Factura B |
| 11 | Factura C |
| 3 | Nota de Crédito A |
| 8 | Nota de Crédito B |
| 13 | Nota de Crédito C |
| 2 | Nota de Débito A |
| 7 | Nota de Débito B |
| 12 | Nota de Débito C |

## 📄 Tipos de documento

| Código | Descripción |
|--------|-------------|
| 80 | CUIT |
| 86 | CUIL |
| 96 | DNI |
| 99 | Sin identificar |

## 🔐 Campos agregados a la tabla `pagos`

```php
- afip_cae                    // CAE de AFIP
- afip_cae_vencimiento        // Fecha de vencimiento del CAE
- afip_numero_comprobante     // Número de comprobante
- afip_tipo_comprobante       // Tipo (1, 6, 11, etc)
- afip_punto_venta            // Punto de venta usado
```

## 🛣️ Rutas disponibles

```php
GET  /afip                              // Panel de configuración
POST /afip/subir-certificados          // Subir certificados
POST /afip/generar-certificados        // Generar certificados (dev/prod)
POST /afip/generar-factura/{pago}      // Generar factura para un pago
POST /afip/consultar-contribuyente     // Consultar CUIT
GET  /afip/ultimo-comprobante          // Obtener último número
GET  /afip/puntos-venta                // Obtener puntos de venta
GET  /afip/test-conexion               // Probar conexión
```

## ⚙️ Métodos del servicio AfipService

### `__construct($empresaId)`
Inicializa el servicio con la empresa especificada.

### `crearFactura(array $data)`
Crea una factura electrónica en AFIP.

### `crearFacturaDesdePago($pago, $puntoVenta, $tipoComprobante)`
Crea una factura desde un registro de pago del sistema.

### `obtenerUltimoComprobante($puntoVenta, $tipoComprobante)`
Obtiene el último número de comprobante emitido.

### `consultarContribuyente($cuit)`
Consulta información de un contribuyente por CUIT.

### `verificarCertificados()`
Verifica si los certificados de la empresa son válidos.

## 🔍 Ejemplo completo: Flujo de facturación

```php
// 1. Usuario realiza un pago
$pago = Pagos::create([
    'id_servicio_pagar' => $servicioPagar->id,
    'id_usuario' => auth()->id(),
    'forma_pago' => 1,
    'importe' => 1210.00,
]);

// 2. Generar factura en AFIP
$afipService = new AfipService(auth()->user()->empresa_id);
$resultado = $afipService->crearFacturaDesdePago($pago, 1, 6);

// 3. Actualizar pago con datos de AFIP
if ($resultado['success']) {
    $pago->update([
        'afip_cae' => $resultado['cae'],
        'afip_cae_vencimiento' => $resultado['cae_vencimiento'],
        'afip_numero_comprobante' => $resultado['numero_comprobante'],
        'afip_tipo_comprobante' => 6,
        'afip_punto_venta' => 1,
    ]);
}
```

## 🐛 Debugging

Los logs de AFIP se guardan en `storage/logs/laravel.log`:

```php
// Ejemplo de log exitoso
[2026-02-05 10:30:00] local.INFO: Factura AFIP creada exitosamente {"empresa_id":1,"comprobante":123,"cae":"12345678901234"}

// Ejemplo de log de error
[2026-02-05 10:30:00] local.ERROR: Error al crear factura AFIP {"empresa_id":1,"error":"..."}
```

## 📚 Documentación oficial

- AFIP SDK PHP: https://docs.afipsdk.com/integracion/php
- Web Services AFIP: https://www.afip.gob.ar/ws/
- Testing AFIP: https://www.afip.gob.ar/ws/WSAA/

## ⚠️ Consideraciones importantes

1. **Certificados**: Cada empresa debe tener sus propios certificados
2. **Testing vs Producción**: Cambiar `AFIP_PRODUCTION` en `.env`
3. **Punto de venta**: Debe estar habilitado en AFIP
4. **IVA**: El sistema calcula IVA 21% por defecto
5. **Normalización DNI**: Usa `DniHelper` para manejar DNI/CUIT

## 🔒 Seguridad

- Los certificados se almacenan en `storage/app/afip/empresas/{cuit}/`
- Este directorio debe estar protegido (no accesible públicamente)
- Permisos recomendados: `755` para directorios, `644` para archivos

## 🆘 Soporte

Para problemas con AFIP:
1. Verificar logs en `storage/logs/`
2. Probar conexión con `/afip/test-conexion`
3. Verificar que los certificados sean válidos
4. Consultar documentación oficial de AFIP
