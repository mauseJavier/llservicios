<?php

/**
 * Script de prueba para DniHelper
 * Ejecutar: php test_dni_helper.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Helpers\DniHelper;

echo "=== PRUEBAS DniHelper ===\n\n";

// Test 1: Extraer DNI desde CUIT
echo "Test 1: Extraer DNI desde CUIT (11 dígitos)\n";
$cuit = '20358337164';
$dniExtraido = DniHelper::extractDni($cuit);
echo "CUIT: $cuit -> DNI: $dniExtraido\n";
echo "Esperado: 35833716 | Resultado: " . ($dniExtraido === '35833716' ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 2: DNI de 8 dígitos
echo "Test 2: DNI de 8 dígitos\n";
$dni = '35833716';
$dniExtraido = DniHelper::extractDni($dni);
echo "DNI: $dni -> DNI: $dniExtraido\n";
echo "Esperado: 35833716 | Resultado: " . ($dniExtraido === '35833716' ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 3: DNI de 7 dígitos
echo "Test 3: DNI de 7 dígitos\n";
$dni = '5833716';
$dniExtraido = DniHelper::extractDni($dni);
echo "DNI: $dni -> DNI: $dniExtraido\n";
echo "Esperado: 5833716 | Resultado: " . ($dniExtraido === '5833716' ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 4: CUIT con guiones
echo "Test 4: CUIT con guiones (normalizar)\n";
$cuit = '20-35833716-4';
$dniExtraido = DniHelper::extractDni($cuit);
echo "CUIT: $cuit -> DNI: $dniExtraido\n";
echo "Esperado: 35833716 | Resultado: " . ($dniExtraido === '35833716' ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 5: Valor null
echo "Test 5: Valor null\n";
$dniExtraido = DniHelper::extractDni(null);
echo "NULL -> DNI: " . ($dniExtraido === null ? 'null' : $dniExtraido) . "\n";
echo "Esperado: null | Resultado: " . ($dniExtraido === null ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 6: Valor vacío
echo "Test 6: Valor vacío\n";
$dniExtraido = DniHelper::extractDni('');
echo "'' -> DNI: " . ($dniExtraido === null ? 'null' : $dniExtraido) . "\n";
echo "Esperado: null | Resultado: " . ($dniExtraido === null ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 7: Comparar DNI con CUIT
echo "Test 7: Comparar DNI con CUIT\n";
$resultado = DniHelper::compararDni('35833716', '20358337164');
echo "compararDni('35833716', '20358337164')\n";
echo "Esperado: true | Resultado: " . ($resultado ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 8: Comparar DNI con DNI
echo "Test 8: Comparar DNI con DNI\n";
$resultado = DniHelper::compararDni('35833716', '35833716');
echo "compararDni('35833716', '35833716')\n";
echo "Esperado: true | Resultado: " . ($resultado ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 9: Comparar DNI diferente
echo "Test 9: Comparar DNI diferente\n";
$resultado = DniHelper::compararDni('35833716', '12345678');
echo "compararDni('35833716', '12345678')\n";
echo "Esperado: false | Resultado: " . (!$resultado ? '✓ PASS' : '✗ FAIL') . "\n\n";

// Test 10: CUIT tipo 27 (femenino)
echo "Test 10: CUIT tipo 27 (femenino)\n";
$cuit = '27358337165';
$dniExtraido = DniHelper::extractDni($cuit);
echo "CUIT: $cuit -> DNI: $dniExtraido\n";
echo "Esperado: 35833716 | Resultado: " . ($dniExtraido === '35833716' ? '✓ PASS' : '✗ FAIL') . "\n\n";

echo "=== FIN DE PRUEBAS ===\n";
