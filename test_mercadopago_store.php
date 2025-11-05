#!/usr/bin/env php
<?php

/**
 * Script de prueba para crear una sucursal en MercadoPago
 * Uso: php test_mercadopago_store.php
 */

// Configura tus credenciales aquí
$accessToken = 'APP_USR-6598778765213486-062114-8e3eecbc8aedfbc47ea79811539567fc-105046639';
$userId = '105046639';

// Datos de la sucursal
$data = [
    'name' => 'Sucursal Test ' . date('Y-m-d H:i:s'),
    'external_id' => 'TEST_' . time(),
    'location' => [
        'street_number' => '123',
        'street_name' => 'Calle Ejemplo',
        'city_name' => 'Neuquén',
        'state_name' => 'Neuquén',
        'latitude' => -34.603722,
        'longitude' => -58.381592,
        'reference' => 'Cerca del centro'
    ]
];

$url = "https://api.mercadopago.com/users/{$userId}/stores";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

echo "Enviando solicitud a: {$url}\n";
echo "Datos: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Código de respuesta HTTP: {$httpCode}\n";
echo "Respuesta: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "\n✅ ¡Sucursal creada exitosamente!\n";
} else {
    echo "\n❌ Error al crear la sucursal\n";
}

curl_close($ch);
