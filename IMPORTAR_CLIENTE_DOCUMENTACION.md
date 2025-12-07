# Componente ImportarCliente - Documentación

## Descripción General

El componente `ImportarCliente` es un componente Livewire que permite importar múltiples clientes desde un archivo CSV de manera masiva, facilitando la carga inicial de datos o la actualización de información de clientes existentes.

## Ubicación de Archivos

- **Componente PHP**: `/app/Livewire/ImportarCliente.php`
- **Vista Blade**: `/resources/views/livewire/importar-cliente.blade.php`
- **Ruta**: `/ImportarClientesCSV` (nombre: `ImportarClientesCSV`)

## Características Principales

### 1. Importación Masiva
- Importa múltiples clientes desde un archivo CSV
- Validación de datos en tiempo real
- Procesamiento por lotes

### 2. Prevención de Duplicados
- Detecta clientes existentes por **DNI** o **nombre**
- Si el cliente existe, actualiza sus datos en lugar de crear uno nuevo
- Vinculación automática a la empresa del usuario autenticado

### 3. Validaciones
- **Archivo**: Formato CSV, tamaño máximo 2MB
- **Campos obligatorios**: nombre, correo
- **Campos opcionales**: teléfono, DNI, domicilio
- **Validación de email**: Formato válido
- **Validación de DNI**: 7-8 dígitos numéricos

### 4. Resumen Detallado
Al finalizar la importación, se muestra:
- Total de filas procesadas
- Clientes creados
- Clientes actualizados
- Errores encontrados (con detalle)
- Filas omitidas (vacías)

## Formato del Archivo CSV

### Estructura Requerida

El archivo CSV debe tener exactamente 5 columnas en este orden:

```csv
nombre,correo,telefono,dni,domicilio
```

### Ejemplo de Archivo CSV

```csv
nombre,correo,telefono,dni,domicilio
Juan Pérez,juan.perez@email.com,3516123456,12345678,Av. Siempre Viva 123
María González,maria.gonzalez@email.com,3517654321,87654321,Calle Falsa 456
Pedro Martínez,pedro.martinez@email.com,3518765432,11223344,Barrio Los Pinos 789
```

### Descripción de Campos

| Campo | Requerido | Tipo | Descripción | Ejemplo |
|-------|-----------|------|-------------|---------|
| `nombre` | **Sí** | String (máx. 255) | Nombre completo del cliente | Juan Pérez |
| `correo` | **Sí** | Email válido | Correo electrónico | juan.perez@email.com |
| `telefono` | No | String (máx. 255) | Número sin código de país | 3516123456 |
| `dni` | No | Numérico (7-8 dígitos) | DNI del cliente | 12345678 |
| `domicilio` | No | String (máx. 255) | Dirección completa | Av. Siempre Viva 123 |

## Comportamiento de la Importación

### Detección de Duplicados

El sistema verifica duplicados en este orden:

1. **Por DNI**: Si el DNI existe en la base de datos, actualiza ese cliente
2. **Por Nombre**: Si no hay DNI pero el nombre coincide, actualiza ese cliente
3. **Cliente Nuevo**: Si no encuentra coincidencias, crea un nuevo cliente

### Vinculación a Empresa

- Todos los clientes se vinculan automáticamente a la empresa del usuario autenticado
- Si un cliente existente no está vinculado a la empresa, se crea la vinculación
- Si ya está vinculado, no se duplica la vinculación

### Actualización de Datos

Cuando se detecta un cliente duplicado:
- Se actualizan **todos** los campos con los valores del CSV
- Se mantiene la vinculación con la empresa
- Se preservan las relaciones existentes (servicios vinculados, pagos, etc.)

## Uso del Componente

### 1. Acceso

```
http://tu-dominio.com/ImportarClientesCSV
```

### 2. Flujo de Trabajo

1. **Descargar plantilla** (opcional)
   - Click en "Descargar Plantilla CSV"
   - Se descarga un archivo de ejemplo con el formato correcto

2. **Preparar archivo CSV**
   - Crear o editar archivo CSV con los datos de clientes
   - Verificar que los encabezados sean exactos: `nombre,correo,telefono,dni,domicilio`
   - Guardar como `.csv` con codificación UTF-8

3. **Subir archivo**
   - Click en "Seleccionar archivo"
   - Elegir el archivo CSV
   - El sistema muestra el nombre y tamaño del archivo

4. **Importar**
   - Click en "Importar Clientes"
   - El botón muestra "Procesando..." mientras se ejecuta
   - Esperar a que termine el proceso

5. **Revisar resumen**
   - Se muestra un resumen con estadísticas
   - Si hay errores, se pueden ver en la sección desplegable
   - Cada error indica la línea y el motivo del problema

## Modelos Eloquent Utilizados

El componente utiliza **únicamente modelos Eloquent** para todas las operaciones:

### Cliente Model

```php
// Crear nuevo cliente
Cliente::create($datosCliente);

// Actualizar cliente existente
$cliente->update($datosCliente);

// Buscar por DNI
Cliente::where('dni', $dni)->first();

// Buscar por nombre
Cliente::where('nombre', $nombre)->first();

// Vincular a empresa
$cliente->empresas()->attach($empresaId);
```

### Ventajas del Uso de Eloquent

- ✅ Código más limpio y mantenible
- ✅ Validaciones automáticas
- ✅ Timestamps automáticos (created_at, updated_at)
- ✅ Protección contra asignación masiva
- ✅ Eventos y observadores disponibles
- ✅ Relaciones fáciles de manejar

## Manejo de Errores

### Errores de Validación

Cada línea del CSV se valida individualmente. Los errores comunes incluyen:

- **Email inválido**: "El campo correo debe ser una dirección de correo electrónica válida"
- **Nombre vacío**: "El campo nombre es obligatorio"
- **DNI inválido**: "El campo dni debe tener entre 7 y 8 dígitos"
- **Correo vacío**: "El campo correo es obligatorio"

### Errores de Procesamiento

Si ocurre un error durante la creación/actualización:
- Se captura la excepción
- Se registra en el listado de errores
- Se continúa con la siguiente línea
- El proceso no se interrumpe

### Visualización de Errores

Los errores se muestran en un panel desplegable que incluye:
- Número de línea donde ocurrió el error
- Datos que se intentaron importar
- Lista de mensajes de error específicos

## Seguridad

### Autenticación y Autorización
- Requiere usuario autenticado (middleware `auth`)
- Solo usuarios con rol Admin pueden acceder (middleware `RolAdmin`)
- Los clientes se vinculan únicamente a la empresa del usuario

### Validación de Archivos
- Tamaño máximo: 2MB
- Tipos permitidos: `.csv`, `.txt`
- Validación de encabezados obligatoria

### Protección de Datos
- Usa `$guarded = []` en el modelo Cliente (mass assignment protection)
- Validación estricta de tipos de datos
- Sanitización de entrada con `trim()`

## Ejemplo de Uso Completo

### 1. Preparar Archivo CSV

Crear un archivo `clientes.csv`:

```csv
nombre,correo,telefono,dni,domicilio
Ana López,ana.lopez@gmail.com,3519876543,98765432,Calle Luna 456
Carlos Ruiz,carlos.ruiz@hotmail.com,3512345678,23456789,Av. Sol 789
Laura Fernández,laura.fernandez@yahoo.com,3517894561,34567890,Barrio Norte 321
```

### 2. Importar

- Ir a `/ImportarClientesCSV`
- Seleccionar el archivo `clientes.csv`
- Click en "Importar Clientes"

### 3. Resultado Esperado

```
Resumen de Importación:
- Total filas procesadas: 3
- Clientes creados: 3
- Clientes actualizados: 0
- Errores: 0
- Filas omitidas: 0
```

## Troubleshooting

### Problema: "Los encabezados del CSV no son correctos"

**Solución**: Verificar que la primera línea del CSV sea exactamente:
```
nombre,correo,telefono,dni,domicilio
```
Sin espacios adicionales, en minúsculas, y en ese orden.

### Problema: "El campo correo debe ser una dirección válida"

**Solución**: Revisar que todos los correos tengan formato válido (contienen `@` y dominio).

### Problema: "El archivo debe ser de tipo CSV"

**Solución**: Guardar el archivo con extensión `.csv` o `.txt`, no `.xlsx` u otros formatos.

### Problema: Clientes no se importan

**Solución**: 
1. Verificar que el usuario esté autenticado
2. Verificar que el usuario tenga rol Admin
3. Revisar los errores en el resumen
4. Verificar que el archivo no esté vacío

## Mejoras Futuras Sugeridas

1. **Importación asíncrona**: Para archivos grandes, usar Jobs y colas
2. **Progreso en tiempo real**: Mostrar barra de progreso con Livewire
3. **Validación previa**: Vista previa de datos antes de importar
4. **Múltiples formatos**: Soporte para Excel (.xlsx)
5. **Mapeo de columnas**: Permitir al usuario mapear columnas personalizadas
6. **Historial de importaciones**: Registro de todas las importaciones realizadas
7. **Rollback**: Opción para deshacer una importación
8. **Notificaciones**: Email al finalizar importaciones grandes

## Soporte

Para problemas o consultas sobre el componente:
- Revisar los logs de Laravel: `storage/logs/laravel.log`
- Verificar errores en el navegador (consola F12)
- Consultar esta documentación

## Versión

- **Componente**: ImportarCliente v1.0
- **Laravel**: 10.x
- **Livewire**: 3.x
- **Fecha**: Noviembre 2025
