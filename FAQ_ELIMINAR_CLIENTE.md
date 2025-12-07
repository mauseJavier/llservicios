# ❓ FAQ - Eliminar Cliente

## Preguntas Frecuentes sobre la Funcionalidad de Eliminar Cliente

---

### 📌 Preguntas Generales

#### ❓ ¿Qué se elimina exactamente cuando elimino un cliente?

Al eliminar un cliente se eliminan **todos** los registros asociados:

- ✅ El registro del cliente
- ✅ Todos los servicios impagos
- ✅ Todos los servicios pagos
- ✅ Todos los pagos registrados
- ✅ Todas las vinculaciones con servicios
- ✅ Todas las vinculaciones con empresas

**Es una eliminación completa y permanente.**

---

#### ❓ ¿Puedo recuperar un cliente eliminado?

**NO.** La eliminación es permanente y no se puede deshacer. Por eso:

- Siempre se muestra un modal de confirmación
- Se listan claramente todos los datos que se eliminarán
- Se requiere confirmación explícita del usuario

**Recomendación:** Antes de eliminar, considera exportar o respaldar los datos del cliente.

---

#### ❓ ¿Qué pasa si elimino un cliente por error?

Si eliminaste un cliente por error:

1. **NO hay forma automática de recuperarlo**
2. Deberás volver a crear el cliente manualmente
3. Tendrás que volver a vincular todos los servicios
4. Los datos históricos se perderán permanentemente

**Por eso es crucial verificar dos veces antes de confirmar la eliminación.**

---

### 🔐 Seguridad y Permisos

#### ❓ ¿Quién puede eliminar clientes?

Actualmente, **cualquier usuario autenticado** con acceso al módulo puede eliminar clientes de su empresa.

**Nota para administradores:** Se recomienda implementar control de permisos para que solo administradores puedan eliminar clientes. Ver archivo `EJEMPLOS_CODIGO_ELIMINAR_CLIENTE.php` para implementación.

---

#### ❓ ¿Se registra quién eliminó un cliente?

En la versión actual, **NO** se registra automáticamente.

**Mejora recomendada:** Implementar tabla de auditoría para registrar:
- Quién eliminó el cliente
- Cuándo se eliminó
- Qué datos tenía el cliente

Ver `EJEMPLOS_CODIGO_ELIMINAR_CLIENTE.php` - Ejemplo 3 para implementación.

---

### 💰 Servicios y Pagos

#### ❓ ¿Qué pasa con los servicios impagos del cliente?

**Se eliminan todos** los servicios impagos. Esto significa:

- ❌ No se podrá cobrar esos servicios
- ❌ No aparecerán en reportes de deudas
- ❌ No se enviarán notificaciones de pago

**Si el cliente debe dinero y planeas cobrarlo, NO lo elimines.**

---

#### ❓ ¿Puedo eliminar solo los servicios sin eliminar el cliente?

**No con esta funcionalidad.** Esta función elimina el cliente Y todos sus servicios.

Si solo quieres eliminar servicios específicos:
- Ve al módulo de "Servicios"
- Busca los servicios del cliente
- Elimínalos individualmente (si esa función existe)

O contacta al administrador del sistema.

---

#### ❓ ¿Los pagos registrados también se eliminan?

**Sí.** Todos los pagos asociados a los servicios del cliente se eliminan.

Esto puede afectar:
- ❌ Reportes de caja
- ❌ Balances contables
- ❌ Estadísticas de cobros
- ❌ Historial de transacciones

**Recomendación:** Exporta reportes antes de eliminar clientes con pagos históricos importantes.

---

### 📊 Reportes e Historial

#### ❓ ¿Afecta los reportes existentes?

**Sí, definitivamente.** Al eliminar un cliente:

- Los reportes históricos perderán esos datos
- Las estadísticas cambiarán
- Los totales de facturación se verán afectados
- Los gráficos no mostrarán esa información

**No es recomendable eliminar clientes con mucha actividad histórica.**

---

#### ❓ ¿Puedo ver qué clientes fueron eliminados?

En la versión actual, **NO**. Una vez eliminado, no hay registro.

**Mejora sugerida:** Implementar:
- Tabla de auditoría de eliminaciones
- Soft deletes (papelera de reciclaje)
- Logs de sistema

---

### 🔧 Técnico / Errores

#### ❓ ¿Qué hago si aparece un error al eliminar?

Si aparece un error:

1. **Lee el mensaje de error** - Proporciona información sobre qué falló
2. **Verifica tu conexión** - Asegúrate de tener conexión estable
3. **Intenta nuevamente** - El error pudo ser temporal
4. **Contacta al administrador** - Si el error persiste

La funcionalidad usa **transacciones**, así que si falla:
- ✅ No se elimina nada parcialmente
- ✅ Los datos quedan intactos
- ✅ Es seguro intentar de nuevo

---

#### ❓ ¿Por qué el botón "Eliminar" no aparece?

Posibles razones:

1. **No tienes permisos** - Contacta al administrador
2. **Error de carga** - Recarga la página
3. **JavaScript deshabilitado** - Habilita JavaScript en tu navegador
4. **Versión antigua** - Limpia caché del navegador

---

#### ❓ ¿El sistema se pone lento al eliminar?

Es normal que tome unos segundos si el cliente tiene:
- Muchos servicios (cientos o miles)
- Muchos pagos históricos
- Muchas vinculaciones

El sistema está eliminando **todos** esos registros de forma segura.

**Sé paciente y NO recargues la página durante el proceso.**

---

### 🎯 Casos de Uso

#### ❓ ¿Cuándo DEBERÍA eliminar un cliente?

✅ **Casos apropiados:**

- Cliente duplicado por error
- Cliente de prueba que ya no necesitas
- Cliente que pidió ser eliminado del sistema (RGPD/GDPR)
- Cliente que nunca tuvo actividad real

---

#### ❓ ¿Cuándo NO debería eliminar un cliente?

❌ **NO eliminar si:**

- El cliente tiene deuda pendiente
- Necesitas los datos para reportes
- El cliente puede volver en el futuro
- Tiene mucho historial de pagos
- Solo quieres "pausar" su actividad

**Alternativas:**
- Desactivar el cliente (si existe esa función)
- Marcar como "inactivo"
- Desvincular servicios específicos
- Contactar al administrador para otras opciones

---

### 💡 Mejores Prácticas

#### ❓ ¿Cómo puedo eliminar clientes de forma segura?

**Checklist de seguridad:**

1. ✅ **Verifica dos veces** que es el cliente correcto
2. ✅ **Exporta los datos** si pueden ser importantes
3. ✅ **Revisa la deuda** - Asegúrate de no perder dinero
4. ✅ **Consulta reportes** - Verifica si afectará estadísticas
5. ✅ **Lee todas las advertencias** del modal
6. ✅ **Haz backup** de la base de datos (admin)

---

#### ❓ ¿Qué hago si tengo muchos clientes inactivos?

**NO elimines masivamente.** En su lugar:

1. Evalúa por qué están inactivos
2. Considera implementar "estado inactivo"
3. Archiva en lugar de eliminar
4. Consulta con el equipo de administración

**Eliminar en masa puede:**
- ❌ Corromper datos
- ❌ Afectar reportes severamente
- ❌ Causar problemas de integridad

---

### 🆘 Emergencias

#### ❓ ¡Eliminé el cliente equivocado! ¿Qué hago?

**Pasos inmediatos:**

1. **NO entres en pánico**
2. **DETÉN cualquier otra acción**
3. **Contacta INMEDIATAMENTE al administrador del sistema**
4. **Proporciona:**
   - Nombre del cliente eliminado
   - DNI del cliente
   - Hora aproximada de eliminación
   - Qué servicios tenía

**El administrador puede:**
- Intentar recuperar de backups automáticos
- Restaurar desde copias de seguridad
- Verificar logs del sistema

**¡El tiempo es crítico! Actúa rápido.**

---

#### ❓ ¿Hay backup automático?

Depende de la configuración del servidor. **Pregunta al administrador:**

- ¿Hay backups diarios?
- ¿Cuánto tardan en restaurar?
- ¿Qué información se puede recuperar?

---

### 🔮 Mejoras Futuras

#### ❓ ¿Se agregarán nuevas características?

**Mejoras planeadas/sugeridas:**

1. **Soft Deletes** - Papelera de reciclaje con recuperación
2. **Control de permisos** - Solo admin puede eliminar
3. **Auditoría completa** - Registro de todas las eliminaciones
4. **Exportación previa** - Descargar datos antes de eliminar
5. **Confirmación doble** - Escribir nombre del cliente
6. **Recuperación temporal** - Restaurar en 30 días

**Contacta al equipo de desarrollo para priorizar estas mejoras.**

---

### 📞 Soporte

#### ❓ ¿Dónde puedo obtener más ayuda?

**Recursos disponibles:**

📄 **Documentación completa:**
- `FUNCIONALIDAD_ELIMINAR_CLIENTE.md`
- `RESUMEN_ELIMINAR_CLIENTE.md`

💻 **Ejemplos de código:**
- `EJEMPLOS_CODIGO_ELIMINAR_CLIENTE.php`

🧪 **Script de prueba:**
- `test_eliminar_cliente.sh`

📧 **Contacto:**
- Email: soporte@empresa.com
- Ticket de soporte en el sistema
- Administrador del sistema

---

## 🎓 Términos Clave

- **Eliminación permanente**: No se puede deshacer
- **Transacción**: Operación que se ejecuta completa o no se ejecuta
- **Rollback**: Revertir cambios si hay error
- **Soft Delete**: Marcar como eliminado sin borrar realmente
- **Hard Delete**: Eliminar permanentemente de la base de datos
- **Integridad referencial**: Mantener consistencia en los datos

---

## ✅ Resumen Rápido

| Pregunta | Respuesta |
|----------|-----------|
| ¿Se puede deshacer? | ❌ NO |
| ¿Afecta reportes? | ✅ SÍ |
| ¿Elimina servicios? | ✅ SÍ - Todos |
| ¿Elimina pagos? | ✅ SÍ - Todos |
| ¿Es seguro? | ✅ Usa transacciones |
| ¿Hay confirmación? | ✅ Sí - Modal de advertencia |
| ¿Necesito permisos especiales? | ⚠️ Depende de configuración |
| ¿Hay backup automático? | ⚠️ Consultar con admin |

---

**¿No encontraste tu respuesta? Contacta al equipo de soporte.**

*Última actualización: Noviembre 2025*
