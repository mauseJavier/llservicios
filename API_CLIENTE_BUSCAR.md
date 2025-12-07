# API - Buscar Cliente

## Endpoint
```
GET /api/cliente/buscar
```

Este endpoint permite buscar un cliente por DNI, correo o nombre y devuelve sus servicios pagos e impagos.

## Características
- ✅ Sin middleware de autenticación (acceso público)
- ✅ Búsqueda por DNI, correo o nombre
- ✅ Filtrado opcional por empresa (ID o nombre)
- ✅ Devuelve servicios pagos e impagos
- ✅ Estado del cliente basado en servicios impagos

## Parámetros de búsqueda

### Parámetros del cliente (al menos uno requerido)
| Parámetro | Tipo   | Requerido | Descripción |
|-----------|--------|-----------|-------------|
| `dni`     | string | No        | DNI del cliente (búsqueda exacta) |
| `correo`  | email  | No        | Correo del cliente (búsqueda parcial) |
| `nombre`  | string | No        | Nombre del cliente (búsqueda parcial) |

### Parámetros de filtro de empresa (opcionales)
| Parámetro        | Tipo    | Requerido | Descripción |
|------------------|---------|-----------|-------------|
| `empresa_id`     | integer | No        | ID de la empresa (filtro exacto) |
| `nombre_empresa` | string  | No        | Nombre de la empresa (búsqueda parcial) |

## Ejemplos de uso

### 1. Buscar por DNI
```bash
GET /api/cliente/buscar?dni=12345678
```

### 2. Buscar por correo
```bash
GET /api/cliente/buscar?correo=cliente@example.com
```

### 3. Buscar por nombre
```bash
GET /api/cliente/buscar?nombre=Juan
```

### 4. Buscar por DNI y filtrar por empresa
```bash
GET /api/cliente/buscar?dni=12345678&empresa_id=1
```

### 5. Buscar por correo y filtrar por nombre de empresa
```bash
GET /api/cliente/buscar?correo=cliente@example.com&nombre_empresa=Edenor
```

## Respuesta exitosa

### HTTP Status: 200
```json
{
  "success": true,
  "data": {
    "cliente": {
      "id": 1,
      "nombre": "Juan Pérez",
      "dni": "12345678",
      "correo": "juan@example.com",
      "telefono": "1234567890",
      "direccion": "Calle Falsa 123"
    },
    "estado_cliente": false,
    "servicios_pagos": [
      {
        "id": 1,
        "servicio_id": 1,
        "servicio_nombre": "Electricidad",
        "empresa_id": 1,
        "empresa_nombre": "Edenor",
        "cantidad": 1,
        "precio": "1500.00",
        "total": 1500.00,
        "estado": "pago",
        "fecha_vencimiento": "2025-09-15",
        "periodo_servicio": "Septiembre 2025",
        "mp_payment_id": "123456789",
        "comentario": null,
        "created_at": "2025-09-01T10:00:00.000000Z",
        "updated_at": "2025-09-10T14:30:00.000000Z"
      }
    ],
    "servicios_impagos": [
      {
        "id": 2,
        "servicio_id": 1,
        "servicio_nombre": "Electricidad",
        "empresa_id": 1,
        "empresa_nombre": "Edenor",
        "cantidad": 1,
        "precio": "1600.00",
        "total": 1600.00,
        "estado": "impago",
        "fecha_vencimiento": "2025-10-15",
        "periodo_servicio": "Octubre 2025",
        "mp_preference_id": "987654321",
        "comentario": null,
        "created_at": "2025-10-01T10:00:00.000000Z",
        "updated_at": "2025-10-01T10:00:00.000000Z"
      }
    ],
    "resumen": {
      "total_pagos": 1,
      "total_impagos": 1,
      "monto_total_impagos": 1600.00
    }
  }
}
```

## Campo `estado_cliente`
- **`true`**: El cliente NO tiene servicios impagos (servicios_impagos = 0)
- **`false`**: El cliente tiene al menos un servicio impago (servicios_impagos > 0)

## Respuestas de error

### Cliente no encontrado - HTTP Status: 404
```json
{
  "success": false,
  "message": "Cliente no encontrado"
}
```

### Parámetros faltantes - HTTP Status: 400
```json
{
  "success": false,
  "message": "Debe proporcionar al menos uno de los siguientes parámetros: dni, correo o nombre"
}
```

### Error de validación - HTTP Status: 422
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "correo": [
      "El campo correo debe ser una dirección de correo válida."
    ]
  }
}
```

### Error del servidor - HTTP Status: 500
```json
{
  "success": false,
  "message": "Error al buscar el cliente",
  "error": "Mensaje de error detallado"
}
```

## Notas importantes

1. **Sin autenticación**: Este endpoint no requiere autenticación ni token de acceso.

2. **Búsqueda del cliente**: Se requiere al menos uno de los parámetros: `dni`, `correo` o `nombre`.

3. **Búsqueda múltiple**: Si se proporcionan varios parámetros (dni, correo, nombre), se utilizan con operador OR.

4. **Filtro de empresa**: Los parámetros `empresa_id` y `nombre_empresa` son opcionales y filtran los servicios devueltos.

5. **Búsqueda parcial**: Los campos `correo`, `nombre` y `nombre_empresa` utilizan búsqueda parcial (LIKE).

6. **Búsqueda exacta**: El campo `dni` y `empresa_id` utilizan búsqueda exacta.

7. **Información de empresa**: Cada servicio incluye el ID y nombre de la empresa asociada.

8. **Resumen**: La respuesta incluye un resumen con totales de servicios pagos e impagos, y el monto total de servicios impagos.
