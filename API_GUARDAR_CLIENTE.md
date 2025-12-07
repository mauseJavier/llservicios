# API - Guardar Cliente

## Descripción
Este endpoint permite guardar un nuevo cliente en la base de datos y vincularlo automáticamente a una empresa mediante la tabla `cliente_empresa`.

**Comportamiento especial:** Si ya existe un cliente con el mismo nombre, el endpoint NO creará un cliente duplicado. En su lugar, devolverá los datos del cliente existente y lo vinculará a la empresa solicitada si aún no lo está.

**Importante:** Este endpoint NO requiere autenticación.

## Endpoint

```
POST /api/cliente/guardar
```

## Headers Requeridos

```
Content-Type: application/json
Accept: application/json
```

## Parámetros del Body (JSON)

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `nombre` | string | **Sí** | Nombre completo del cliente (máx. 255 caracteres) |
| `empresa_id` | integer | **Sí** | ID de la empresa a la que se vinculará el cliente |
| `correo` | string | No | Correo electrónico del cliente (debe ser válido si se proporciona) |
| `telefono` | string | No | Teléfono del cliente (máx. 255 caracteres) |
| `dni` | integer | No | DNI del cliente (debe ser único si se crea un nuevo cliente) |
| `domicilio` | string | No | Dirección/domicilio del cliente (máx. 255 caracteres) |

## Validaciones

- **nombre**: Obligatorio, texto, máximo 255 caracteres. **Si ya existe un cliente con este nombre, se devolverá el cliente existente**
- **empresa_id**: Obligatorio, debe existir en la tabla `empresas`
- **correo**: Opcional, debe ser un email válido
- **telefono**: Opcional, texto, máximo 255 caracteres
- **dni**: Opcional, número entero, debe ser único en la base de datos (solo valida si se crea un nuevo cliente)
- **domicilio**: Opcional, texto, máximo 255 caracteres

## Respuestas

### Éxito - Cliente Nuevo Creado (201 Created)

```json
{
  "success": true,
  "message": "Cliente creado y vinculado exitosamente",
  "cliente_existente": false,
  "data": {
    "cliente": {
      "id": 1,
      "nombre": "Juan Pérez",
      "correo": "juan@example.com",
      "telefono": "123456789",
      "dni": 12345678,
      "domicilio": "Calle Falsa 123",
      "created_at": "2025-11-02T12:00:00.000000Z",
      "updated_at": "2025-11-02T12:00:00.000000Z"
    },
    "empresas": [
      {
        "id": 1,
        "nombre": "Mi Empresa"
      }
    ]
  }
}
```

### Éxito - Cliente Existente (200 OK)

Cuando ya existe un cliente con el mismo nombre, se devuelve el cliente existente:

```json
{
  "success": true,
  "message": "Cliente ya existente. Se vinculó a la empresa solicitada.",
  "cliente_existente": true,
  "data": {
    "cliente": {
      "id": 1,
      "nombre": "Juan Pérez",
      "correo": "juan@example.com",
      "telefono": "123456789",
      "dni": 12345678,
      "domicilio": "Calle Falsa 123",
      "created_at": "2025-11-01T10:00:00.000000Z",
      "updated_at": "2025-11-01T10:00:00.000000Z"
    },
    "empresas": [
      {
        "id": 1,
        "nombre": "Mi Empresa"
      },
      {
        "id": 2,
        "nombre": "Otra Empresa"
      }
    ]
  }
}
```

### Error de Validación (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "nombre": [
      "El campo nombre es obligatorio."
    ],
    "empresa_id": [
      "El campo empresa id es obligatorio."
    ]
  }
}
```

### Error de DNI Duplicado (422)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "dni": [
      "El dni ya ha sido registrado."
    ]
  }
}
```

### Error de Empresa No Encontrada (422)

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "empresa_id": [
      "El empresa id seleccionado no es válido."
    ]
  }
}
```

### Error del Servidor (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Error al guardar el cliente",
  "error": "Mensaje de error detallado"
}
```

## Ejemplos de Uso

### Ejemplo 1: Cliente con todos los campos

```bash
curl -X POST "http://localhost:8000/api/cliente/guardar" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "correo": "juan@example.com",
    "telefono": "123456789",
    "dni": 12345678,
    "domicilio": "Calle Falsa 123",
    "empresa_id": 1
  }'
```

### Ejemplo 2: Cliente solo con campos requeridos

```bash
curl -X POST "http://localhost:8000/api/cliente/guardar" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nombre": "María López",
    "empresa_id": 1
  }'
```

### Ejemplo 3: Con JavaScript/Fetch

```javascript
const guardarCliente = async () => {
  const response = await fetch('http://localhost:8000/api/cliente/guardar', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      nombre: 'Juan Pérez',
      correo: 'juan@example.com',
      telefono: '123456789',
      dni: 12345678,
      domicilio: 'Calle Falsa 123',
      empresa_id: 1
    })
  });

  const data = await response.json();
  console.log(data);
};
```

### Ejemplo 4: Con PHP

```php
<?php

$data = [
    'nombre' => 'Juan Pérez',
    'correo' => 'juan@example.com',
    'telefono' => '123456789',
    'dni' => 12345678,
    'domicilio' => 'Calle Falsa 123',
    'empresa_id' => 1
];

$ch = curl_init('http://localhost:8000/api/cliente/guardar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
```

## Flujo de Funcionamiento

1. El endpoint recibe los datos del cliente en formato JSON
2. Valida que los campos requeridos estén presentes y sean válidos
3. **Verifica si ya existe un cliente con el mismo nombre:**
   - **Si existe:** Devuelve el cliente existente y lo vincula a la empresa solicitada (si no estaba vinculado)
   - **Si no existe:** Crea el nuevo registro del cliente en la tabla `clientes`
4. Crea la relación en la tabla `cliente_empresa` vinculando al cliente con la empresa especificada
5. Retorna el cliente (nuevo o existente) junto con la información de todas las empresas vinculadas

## Notas Importantes

- **Sin Autenticación**: Este endpoint es público y no requiere token de autenticación
- **Cliente Existente**: Si ya existe un cliente con el mismo nombre, NO se crea uno nuevo. Se devuelve el existente
- **DNI Único**: Si se proporciona un DNI, debe ser único en toda la base de datos (solo valida al crear nuevo cliente)
- **Empresa Existente**: El `empresa_id` debe corresponder a una empresa existente en la base de datos
- **Correo Opcional**: El correo es opcional, pero si se proporciona debe ser válido
- **Vinculación Automática**: La vinculación con la empresa se realiza automáticamente, incluso para clientes existentes
- **Múltiples Empresas**: Un cliente puede estar vinculado a múltiples empresas simultáneamente
- **Identificador de Respuesta**: El campo `cliente_existente` en la respuesta indica si se encontró un cliente existente (true) o se creó uno nuevo (false)

## Códigos de Estado HTTP

- `201 Created`: Cliente nuevo creado exitosamente
- `200 OK`: Cliente existente encontrado y vinculado a la empresa
- `422 Unprocessable Entity`: Error de validación en los datos proporcionados
- `500 Internal Server Error`: Error en el servidor al procesar la solicitud

## Testing

Para probar este endpoint, puedes usar el script de prueba incluido:

```bash
chmod +x test_guardar_cliente.sh
./test_guardar_cliente.sh
```

Este script ejecuta varios casos de prueba incluyendo:
- Cliente con todos los campos
- Cliente solo con campos requeridos
- Validación de errores (campos faltantes, empresa inexistente, correo inválido, etc.)
