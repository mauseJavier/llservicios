# Plan: Integración de Spatie Laravel Permission v6

## Objetivo
Integrar la librería spatie/laravel-permission v6 en el proyecto Laravel actual, manteniendo coherencia con el esquema existente de roles y usuarios.

## Contexto actual
- Laravel 10 y PHP 8.1.
- El modelo `User` ya existe y usa `role_id`.
- Existe tabla `roles` y un modelo propio `Role`.
- No hay tabla de permisos ni modelo `Permission`.

## Estrategias posibles
1. **Migración completa**
   Reemplazar el esquema actual por las tablas de Spatie (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) y migrar los datos actuales.
2. **Compatibilidad con modelos propios**
   Extender los modelos de Spatie desde el modelo propio y mapear las tablas existentes para evitar duplicados.
3. **Coexistencia temporal**
   Mantener el `role_id` actual y añadir Spatie gradualmente, sincronizando roles y permisos en paralelo.

## Plan de trabajo (alto nivel)
1. **Auditoría inicial**
   Revisar configuración `auth`, modelo `User`, migraciones y usos actuales de roles.
2. **Decisión de estrategia**
   Elegir migración completa, compatibilidad o coexistencia.
3. **Instalación**
   Añadir dependencia y publicar configuración/migraciones de Spatie.
4. **Modelos y relaciones**
   Aplicar `HasRoles` en `User` y definir modelos `Role`/`Permission` según la estrategia elegida.
5. **Migraciones y datos**
   Crear/ajustar tablas y migrar datos actuales.
6. **Refactor de autorizaciones**
   Reemplazar verificaciones manuales por `hasRole`, `can`, `permission`.
7. **Revisión final**
   Verificar rutas protegidas, middlewares y seeds.

## Archivos clave a revisar
- Configuración de auth.
- Modelo `User`.
- Modelo `Role` propio.
- Migraciones relacionadas con usuarios y roles.
- Middlewares y controladores que verifican roles.

## Consideraciones
- Evitar colisión entre tabla `roles` existente y la de Spatie.
- Si se agrega `api` guard en el futuro, configurar `default_guard_name`.
- Asegurar compatibilidad con seeds actuales.
