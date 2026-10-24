<?php

/*
|--------------------------------------------------------------------------
| Provincias y ciudades aceptadas por MercadoPago (Sucursales QR)
|--------------------------------------------------------------------------
|
| La API de sucursales de MercadoPago valida "location.state_name" y
| "location.city_name" contra su propio padrón. Si el valor no coincide,
| responde 400 "validation_error" indicando la lista de valores válidos.
|
| Este catálogo alimenta el selector de provincia y el datalist de ciudades
| del componente MercadoPagoQrManager. No es obligatorio que esté completo:
| cuando MercadoPago rechaza una ciudad, la lista de valores válidos que
| devuelve se guarda en caché (clave "mercadopago.store.cities.{provincia}")
| y se agrega automáticamente al datalist.
|
| Para ampliar el catálogo manualmente, agregar la provincia como clave del
| arreglo "cities" con la lista exacta de valores aceptados.
|
*/

return [

    // Provincias / jurisdicciones en el formato que espera MercadoPago.
    'states' => [
        'Buenos Aires',
        'Ciudad Autónoma de Buenos Aires',
        'Catamarca',
        'Chaco',
        'Chubut',
        'Córdoba',
        'Corrientes',
        'Entre Ríos',
        'Formosa',
        'Jujuy',
        'La Pampa',
        'La Rioja',
        'Mendoza',
        'Misiones',
        'Neuquén',
        'Río Negro',
        'Salta',
        'San Juan',
        'San Luis',
        'Santa Cruz',
        'Santa Fe',
        'Santiago del Estero',
        'Tierra del Fuego',
        'Tucumán',
    ],

    // Ciudades / departamentos aceptados, agrupados por provincia.
    // Neuquén: lista devuelta por MercadoPago en la validación de la API.
    'cities' => [
        'Neuquén' => [
            'Alianza',
            'Aluminé',
            'Arroyito',
            'Añelo',
            'Buena Esperanza',
            'Catán Lil',
            'Centenario',
            'Challaco',
            'Chos Malal',
            'Collón Curá',
            'Confluencia',
            'Cullin Manzano',
            'Cutral Có',
            'Gente Grande',
            'Huiliches',
            'Isla Victoria',
            'Loncopué',
            'Los Lagos',
            'Lácar',
            'Minas',
            'Neuquén',
            'Pehuenches',
            'Picunches',
            'Picún Leufú',
            'Planicie Banderita',
            'Plottier',
            'Quila Quina',
            'San Martin de los Andes',
            'Senillosa',
            'Villa El Chocon',
            'Villa La Angostura',
            'Zapala',
            'Ñorquín',
        ],
    ],

];
