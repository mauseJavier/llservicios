# Ejemplos Prácticos - ImportarCliente

## Ejemplo 1: Importación Básica (Clientes Nuevos)

### Archivo CSV: `clientes_nuevos.csv`
```csv
nombre,correo,telefono,dni,domicilio
Roberto Sánchez,roberto.sanchez@gmail.com,3516789012,45678901,Barrio Alberdi 234
Lucía Ramírez,lucia.ramirez@hotmail.com,3517890123,56789012,Villa Carlos Paz 567
Diego Torres,diego.torres@yahoo.com,3518901234,67890123,Nueva Córdoba 890
```

### Resultado Esperado:
```
✅ Total filas procesadas: 3
✅ Clientes creados: 3
✅ Clientes actualizados: 0
❌ Errores: 0
⚠️ Filas omitidas: 0
```

---

## Ejemplo 2: Actualización de Clientes Existentes

### Escenario:
Ya existe un cliente "Juan Pérez" con DNI 12345678 en la base de datos.

### Archivo CSV: `actualizar_juan.csv`
```csv
nombre,correo,telefono,dni,domicilio
Juan Pérez,juan.nuevo@email.com,3519999999,12345678,Nueva Dirección 999
```

### Resultado Esperado:
```
✅ Total filas procesadas: 1
✅ Clientes creados: 0
✅ Clientes actualizados: 1
❌ Errores: 0
⚠️ Filas omitidas: 0
```

### Lo que sucede:
- El sistema detecta que DNI 12345678 ya existe
- Actualiza el correo de `juan.perez@email.com` a `juan.nuevo@email.com`
- Actualiza el teléfono de `3516123456` a `3519999999`
- Actualiza el domicilio a "Nueva Dirección 999"
- **NO crea un nuevo cliente**

---

## Ejemplo 3: Mix de Creación y Actualización

### Archivo CSV: `mix_clientes.csv`
```csv
nombre,correo,telefono,dni,domicilio
María González,maria.actualizada@email.com,3510000000,87654321,Domicilio Actualizado 100
Nuevo Cliente,nuevo.cliente@email.com,3511111111,99999999,Barrio Nuevo 200
Ana López,ana.lopez@gmail.com,3519876543,98765432,Calle Luna 456
```

### Resultado Esperado:
```
✅ Total filas procesadas: 3
✅ Clientes creados: 1  (Nuevo Cliente)
✅ Clientes actualizados: 2  (María González, Ana López)
❌ Errores: 0
⚠️ Filas omitidas: 0
```

---

## Ejemplo 4: Archivo con Errores de Validación

### Archivo CSV: `clientes_con_errores.csv`
```csv
nombre,correo,telefono,dni,domicilio
,contacto@email.com,3516123456,12345678,Calle Test 123
Pedro García,email-sin-arroba,3517654321,87654321,Av. Test 456
Laura Martínez,laura@test.com,3518765432,123,Barrio Test 789
Carlos Díaz,carlos@test.com,3519876543,123456789,Calle Test 321
```

### Resultado Esperado:
```
✅ Total filas procesadas: 4
✅ Clientes creados: 0
✅ Clientes actualizados: 0
❌ Errores: 4
⚠️ Filas omitidas: 0
```

### Detalle de Errores:

**Línea 2:** (nombre vacío)
- Datos: , contacto@email.com, 3516123456, 12345678, Calle Test 123
- Error: El campo nombre es obligatorio

**Línea 3:** (email inválido)
- Datos: Pedro García, email-sin-arroba, 3517654321, 87654321, Av. Test 456
- Error: El campo correo debe ser una dirección de correo electrónica válida

**Línea 4:** (DNI muy corto)
- Datos: Laura Martínez, laura@test.com, 3518765432, 123, Barrio Test 789
- Error: El campo dni debe tener entre 7 y 8 dígitos

**Línea 5:** (DNI muy largo)
- Datos: Carlos Díaz, carlos@test.com, 3519876543, 123456789, Calle Test 321
- Error: El campo dni debe tener entre 7 y 8 dígitos

---

## Ejemplo 5: Clientes sin Campos Opcionales

### Archivo CSV: `clientes_minimos.csv`
```csv
nombre,correo,telefono,dni,domicilio
Sofía Hernández,sofia.hernandez@email.com,,,
Martín Gómez,martin.gomez@email.com,3516123456,,
Patricia Silva,patricia.silva@email.com,,45678901,
```

### Resultado Esperado:
```
✅ Total filas procesadas: 3
✅ Clientes creados: 3
✅ Clientes actualizados: 0
❌ Errores: 0
⚠️ Filas omitidas: 0
```

### Observaciones:
- Los campos vacíos se almacenan como `NULL` en la base de datos
- Solo `nombre` y `correo` son obligatorios
- El sistema acepta clientes sin teléfono, DNI o domicilio

---

## Ejemplo 6: Archivo con Líneas Vacías

### Archivo CSV: `clientes_con_vacias.csv`
```csv
nombre,correo,telefono,dni,domicilio
Cliente 1,cliente1@email.com,3516111111,11111111,Calle 1

Cliente 2,cliente2@email.com,3516222222,22222222,Calle 2
,,,,

Cliente 3,cliente3@email.com,3516333333,33333333,Calle 3
```

### Resultado Esperado:
```
✅ Total filas procesadas: 6
✅ Clientes creados: 3
✅ Clientes actualizados: 0
❌ Errores: 0
⚠️ Filas omitidas: 3  (líneas vacías)
```

---

## Ejemplo 7: Importación Masiva (50+ clientes)

### Generación Automática con Script

```bash
#!/bin/bash
# Generar archivo con 50 clientes de prueba

echo "nombre,correo,telefono,dni,domicilio" > clientes_masivo.csv

for i in {1..50}; do
    nombre="Cliente Test $i"
    correo="cliente$i@test.com"
    telefono="351600$i"
    dni="1000000$i"
    domicilio="Calle Test $i"
    echo "$nombre,$correo,$telefono,$dni,$domicilio" >> clientes_masivo.csv
done

echo "Archivo generado: clientes_masivo.csv"
```

### Resultado Esperado:
```
✅ Total filas procesadas: 50
✅ Clientes creados: 50
✅ Clientes actualizados: 0
❌ Errores: 0
⚠️ Filas omitidas: 0
```

---

## Ejemplo 8: Detección de Duplicados por Nombre (sin DNI)

### Escenario:
Existe un cliente "María González" sin DNI registrado.

### Archivo CSV: `actualizar_sin_dni.csv`
```csv
nombre,correo,telefono,dni,domicilio
María González,maria.nuevo@email.com,3517777777,,Nuevo Domicilio 777
```

### Resultado Esperado:
```
✅ Total filas procesadas: 1
✅ Clientes creados: 0
✅ Clientes actualizados: 1
❌ Errores: 0
⚠️ Filas omitidas: 0
```

### Lo que sucede:
- No hay DNI en el CSV para comparar
- El sistema busca por nombre exacto "María González"
- Encuentra coincidencia y actualiza los datos
- **NO crea un nuevo cliente**

---

## Ejemplo 9: Caracteres Especiales y Acentos

### Archivo CSV: `clientes_especiales.csv`
```csv
nombre,correo,telefono,dni,domicilio
José María Ñúñez,jose.nunez@email.com,3516123456,12345678,Av. Libertador 123
María José O'Connor,maria.oconnor@email.com,3517654321,23456789,B° San Martín 456
François Dúpont,francois.dupont@email.com,3518765432,34567890,Calle José Hernández 789
```

### Resultado Esperado:
```
✅ Total filas procesadas: 3
✅ Clientes creados: 3
✅ Clientes actualizados: 0
❌ Errores: 0
⚠️ Filas omitidas: 0
```

### Observaciones:
- El sistema maneja correctamente acentos (á, é, í, ó, ú, ñ)
- Caracteres especiales como apóstrofes (') funcionan correctamente
- Asegurarse de que el archivo CSV esté en codificación **UTF-8**

---

## Ejemplo 10: Recuperación de Error Parcial

### Archivo CSV: `mix_validos_errores.csv`
```csv
nombre,correo,telefono,dni,domicilio
Cliente Válido 1,valido1@email.com,3516111111,11111111,Calle Válida 1
,invalido@email.com,3516222222,22222222,Calle Error 2
Cliente Válido 2,valido2@email.com,3516333333,33333333,Calle Válida 3
Cliente Error,email-sin-arroba,3516444444,44444444,Calle Error 4
Cliente Válido 3,valido3@email.com,3516555555,55555555,Calle Válida 5
```

### Resultado Esperado:
```
✅ Total filas procesadas: 5
✅ Clientes creados: 3  (Cliente Válido 1, 2, 3)
✅ Clientes actualizados: 0
❌ Errores: 2  (Línea 2: nombre vacío, Línea 4: email inválido)
⚠️ Filas omitidas: 0
```

### Observaciones:
- El proceso continúa aunque haya errores
- Los clientes válidos se importan correctamente
- Los errores se reportan en el resumen detallado
- **No se hace rollback**: los clientes válidos permanecen en la BD

---

## Tips y Mejores Prácticas

### 1. Preparación del Archivo
- Usar Excel o Google Sheets y guardar como CSV UTF-8
- Verificar que no haya espacios extra en los encabezados
- Revisar que los emails sean válidos antes de importar

### 2. Validación Previa
- Probar con un archivo pequeño (5-10 clientes) primero
- Revisar el resumen de errores
- Corregir el archivo y volver a importar

### 3. Backup
- Hacer backup de la base de datos antes de importaciones grandes
- Usar el entorno de desarrollo/testing primero

### 4. Rendimiento
- Para archivos muy grandes (1000+ clientes), considerar dividir en lotes
- Monitorear el uso de memoria del servidor

### 5. Resolución de Errores Comunes
```
Error: "El campo correo debe ser una dirección válida"
Solución: Verificar que todos los emails tengan @ y dominio

Error: "El campo dni debe tener entre 7 y 8 dígitos"
Solución: DNI debe ser numérico, sin puntos ni guiones

Error: "Los encabezados del CSV no son correctos"
Solución: Verificar que sea: nombre,correo,telefono,dni,domicilio
```

---

## Testing Recomendado

### Secuencia de Pruebas:

1. **Importar archivo válido** (Ejemplo 1)
2. **Actualizar cliente existente** (Ejemplo 2)
3. **Mix de creación y actualización** (Ejemplo 3)
4. **Archivo con errores** (Ejemplo 4)
5. **Campos opcionales vacíos** (Ejemplo 5)
6. **Líneas vacías** (Ejemplo 6)
7. **Caracteres especiales** (Ejemplo 9)
8. **Error parcial** (Ejemplo 10)

### Archivos de Prueba Incluidos:

Ya están creados en `storage/app/test/`:
- `clientes_validos.csv` - 3 clientes válidos
- `clientes_con_errores.csv` - 3 clientes con errores de validación

---

## Comandos Útiles

### Ver clientes importados:
```bash
php artisan tinker
>>> App\Models\Cliente::latest()->take(10)->get(['id', 'nombre', 'correo']);
```

### Limpiar clientes de prueba:
```bash
php artisan tinker
>>> App\Models\Cliente::where('correo', 'like', '%@test.com')->delete();
```

### Ver vinculaciones con empresa:
```bash
php artisan tinker
>>> DB::table('cliente_empresa')->latest()->take(10)->get();
```

---

**¡Componente listo para usar! 🎉**
