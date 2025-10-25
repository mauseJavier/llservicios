# Solución del Error MercadoPago: error_unmarshal_boolean

## Problema Identificado
El error `error_unmarshal_boolean` se producía porque se estaban enviando valores `null` y campos vacíos incorrectamente formateados a la API de MercadoPago.

## Cambios Realizados

### 1. MercadoPagoApiService.php - Método createPayer()
**Antes:**
```php
return [
    'name' => $payerData['name'] ?? '',
    'surname' => $payerData['surname'] ?? '',
    'email' => $payerData['email'],
    'phone' => [
        'area_code' => $payerData['phone']['area_code'] ?? '',
        'number' => $payerData['phone']['number'] ?? ''
    ],
    'identification' => [
        'type' => $payerData['identification']['type'] ?? 'DNI',
        'number' => $payerData['identification']['number'] ?? ''
    ],
    'address' => [
        'street_name' => $payerData['address']['street_name'] ?? '',
        'street_number' => $payerData['address']['street_number'] ?? null, // ❌ PROBLEMA
        'zip_code' => $payerData['address']['zip_code'] ?? ''
    ]
];
```

**Después:**
```php
$payer = ['email' => $payerData['email']];

// Solo agregar campos que tienen valores válidos
if (!empty($payerData['name'])) {
    $payer['name'] = $payerData['name'];
}

if (!empty($payerData['surname'])) {
    $payer['surname'] = $payerData['surname'];
}

// Solo agregar teléfono si tiene número
if (!empty($payerData['phone']['number'])) {
    $payer['phone'] = ['number' => $payerData['phone']['number']];
    if (!empty($payerData['phone']['area_code'])) {
        $payer['phone']['area_code'] = $payerData['phone']['area_code'];
    }
}

// Solo agregar identificación si tiene número
if (!empty($payerData['identification']['number'])) {
    $payer['identification'] = [
        'type' => $payerData['identification']['type'] ?? 'DNI',
        'number' => (string) $payerData['identification']['number']
    ];
}

// Solo agregar dirección si tiene street_name
if (!empty($payerData['address']['street_name'])) {
    $payer['address'] = ['street_name' => $payerData['address']['street_name']];
    if (!empty($payerData['address']['street_number'])) {
        $payer['address']['street_number'] = (string) $payerData['address']['street_number'];
    }
    if (!empty($payerData['address']['zip_code'])) {
        $payer['address']['zip_code'] = $payerData['address']['zip_code'];
    }
}

return $payer;
```

### 2. MercadoPagoApiService.php - Método buildPreferenceData()
- Validación correcta del campo `auto_return`
- Solo enviar campos opcionales si tienen valores válidos
- Validación de fechas de expiración

### 3. MercadoPagoApiService.php - Método createItems()
- Eliminación de campos `null` innecesarios
- Casting correcto de tipos numéricos
- Solo enviar campos opcionales si tienen valores

### 4. PagosController.php - Generación de clienteData
**Cambio crítico:**
```php
// ANTES (problemático)
'street_number' => null,

// DESPUÉS (corregido)
'street_number' => '',
```

## Beneficios de los Cambios

1. **Elimina errores de formato**: No se envían valores `null` que MercadoPago no puede procesar
2. **API más limpia**: Solo se envían campos con valores válidos
3. **Mejor rendimiento**: Menos datos innecesarios en las peticiones
4. **Más robusto**: Manejo defensivo de datos faltantes o vacíos
5. **Cumple estándares de MercadoPago**: Formato exacto esperado por la API

## Campos que Ahora se Manejan Correctamente

- ✅ `email` - Siempre requerido
- ✅ `name` - Solo si no está vacío
- ✅ `surname` - Solo si no está vacío
- ✅ `phone` - Solo si tiene número válido
- ✅ `identification` - Solo si tiene número válido
- ✅ `address` - Solo si tiene street_name válido
- ✅ `street_number` - Solo si no está vacío (convertido a string)
- ✅ `auto_return` - Validado como "approved" o "all"

## Resultado

El error `error_unmarshal_boolean` debería estar resuelto. La API de MercadoPago ahora recibe datos en el formato correcto sin valores `null` problemáticos.