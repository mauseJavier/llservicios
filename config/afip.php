<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo de Producción AFIP
    |--------------------------------------------------------------------------
    |
    | Define si la aplicación se conectará al entorno de producción de AFIP
    | o al entorno de testing. En testing no se generan comprobantes reales.
    |
    | - false: Entorno de testing (homologación)
    | - true: Entorno de producción (comprobantes reales)
    |
    */

    'production' => env('AFIP_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Access Token para automatizaciones AFIP
    |--------------------------------------------------------------------------
    |
    | Token requerido para ejecutar CreateAutomation desde el SDK.
    |
    */

    'access_token' => env('AFIP_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Automatizaciones de certificados
    |--------------------------------------------------------------------------
    |
    | Nombres de automatización para generar certificados en dev/prod.
    |
    */

    'automation_dev' => env('AFIP_AUTOMATION_DEV', 'create-cert-dev'),
    'automation_prod' => env('AFIP_AUTOMATION_PROD', 'create-cert-prod'),

    /*
    |--------------------------------------------------------------------------
    | Ruta de almacenamiento de certificados
    |--------------------------------------------------------------------------
    |
    | Directorio donde se almacenan los certificados y claves de AFIP
    | por empresa. La estructura será: storage/app/afip/empresas/{cuit}/
    |
    */

    'certificates_path' => storage_path('app/afip/empresas'),

    /*
    |--------------------------------------------------------------------------
    | Punto de venta por defecto
    |--------------------------------------------------------------------------
    |
    | Punto de venta que se utilizará por defecto si no se especifica uno.
    |
    */

    'default_punto_venta' => env('AFIP_PUNTO_VENTA', 1),

    /*
    |--------------------------------------------------------------------------
    | Tipo de comprobante por defecto
    |--------------------------------------------------------------------------
    |
    | Tipo de comprobante que se utilizará por defecto.
    | 6 = Factura B (la más común para consumidores finales)
    |
    */

    'default_tipo_comprobante' => env('AFIP_TIPO_COMPROBANTE', 6),

    /*
    |--------------------------------------------------------------------------
    | Alícuota IVA por defecto
    |--------------------------------------------------------------------------
    |
    | Alícuota de IVA que se aplicará por defecto (en porcentaje).
    | 21 = IVA 21%
    |
    */

    'default_iva' => env('AFIP_IVA_DEFAULT', 21),

];
