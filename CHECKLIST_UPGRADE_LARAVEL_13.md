# Checklist de seguimiento: actualización a Laravel 13

## Datos de seguimiento

| Campo | Valor |
| --- | --- |
| Rama | `feature/upgrade-laravel` |
| Base actual | Laravel 11.57.0 / PHP 8.4 |
| Objetivo | Laravel 13.x |
| Responsable | Pendiente de asignar |
| Fecha objetivo general | Pendiente de agendar |
| Última actualización | 2026-10-22 |

## Resumen de progreso

| Etapa | Estado | Próximo hito |
| --- | --- | --- |
| 1 — Laravel 10 → 11 | Código aplicado; suite automatizada en verde; smoke manual de integraciones reales pendiente | Cerrar smoke manual y commit de etapa |
| 2 — Laravel 11 → 12 | Pendiente | Revisar guía, actualizar dependencias y probar |
| 3 — Laravel 12 → 13 | Pendiente | Revisar guía oficial y actualizar dependencias/código |

**Estado de suite actual:** 146 tests, 405 aserciones, 0 errores, 0 fallos, 0 deprecaciones.
Verificado el 2026-10-22 con `scripts/test.sh`.

**Gate automatizado disponible:**

```bash
scripts/test.sh        # suite + Pint + route:list + config:cache/route:cache
scripts/smoke.sh       # smoke HTTP contra el contenedor de desarrollo
TEST_MYSQL=1 scripts/test.sh   # además migrate:fresh + rollback en MySQL
```

## Automatización incorporada

| Check del checklist | Automatización | Estado |
| --- | --- | --- |
| Suite completa y deprecaciones | `tests/` + `scripts/test.sh` | ✅ 146 tests |
| Pint | `pint --test` sobre archivos modificados | ✅ |
| Rutas y bootstrap | `php artisan route:list` (158 rutas) | ✅ |
| `config:cache` / `route:cache` | Ciclo completo en `scripts/test.sh` | ✅ |
| Migraciones MySQL `up`/`down` | `migrate:fresh` + `migrate:rollback --step=all` | ✅ |
| Smoke HTTP (`/login`, `/`, `/up`, API 401) | `SmokeTest` + `scripts/smoke.sh` | ✅ |
| Login, logout y regeneración de sesión | `Feature/AuthTest` | ✅ |
| Permisos `RolAdmin` / `RolSuper` | `Feature/AuthorizationTest` | ✅ |
| Token API válido/inválido y aislamiento por empresa | `Feature/ApiTokenTest` | ✅ |
| Webhooks excluidos de CSRF | `Feature/CsrfExclusionTest` | ✅ (config) |
| Scheduler y calendario por empresa | `Feature/SchedulerTest` | ✅ |
| Colas `notificaciones` / `alta` y worker | `Feature/QueueTest` | ✅ |
| Livewire (token de empresa) | `Feature/LivewireComponentsTest` | ✅ parcial |
| PDF y QR | `Feature/DocumentGenerationTest` | ✅ |
| Pint en CI | `.github/workflows/tests.yml` | ✅ |
| Migraciones MySQL en CI | Job `migrations` con MariaDB | ✅ |

## Etapa 1 — Laravel 10 → 11

### Migración técnica

- [x] Actualizar constraints y `composer.lock` a Laravel 11. **Evidencia:** Laravel 11.57.0 en contenedor.
- [x] Migrar el bootstrap a `bootstrap/app.php` y registrar providers en `bootstrap/providers.php`.
- [x] Migrar aliases, proxies, CSRF, redirect de usuarios y rate limiter de API.
- [x] Mover la definición del schedule a `routes/console.php`.
- [x] Retirar Kernels, `RouteServiceProvider` y middleware reemplazados por el framework.
- [x] Configurar PHPUnit con SQLite in-memory y hacer portables las aserciones de fechas.
- [x] Hacer portables las migraciones que tenían SQL específico de MySQL.
- [x] Crear la referencia del intento de MercadoPago en el insert.
- [x] Adaptar `PaymentClient::get()` al parámetro `int` requerido por el SDK actualizado.

### Contenedores y smoke test

- [x] Ejecutar `docker compose -f docker-compose.dev.yml up -d --build`.
- [x] Confirmar app, scheduler y workers en estado `healthy`.
- [x] Confirmar versión Laravel con `php artisan --version`.
- [x] Verificar `/login`, `/` y `/up` (HTTP 200). **Evidencia:** `SmokeTest` + `scripts/smoke.sh`.
- [x] Verificar endpoint `/api/registros-cerradura` sin token (HTTP 401 esperado). **Evidencia:** `SmokeTest` + `ApiTokenTest`.
- [x] Probar login real, logout y expiración/renovación de sesión. **Evidencia:** `AuthTest` cubre login, logout y regeneración. Queda pendiente la expiración real en navegador.
- [x] Probar perfiles y permisos `RolAdmin` / `RolSuper`, incluyendo redirecciones. **Evidencia:** `AuthorizationTest`.
- [x] Probar rutas API con token válido e inválido de empresa. **Evidencia:** `ApiTokenTest`.
- [x] Probar webhooks excluidos de CSRF y confirmar que las demás rutas web mantienen CSRF. **Evidencia:** `CsrfExclusionTest`. La verificación HTTP real queda en `scripts/smoke.sh`.
- [x] Confirmar funcionamiento de colas `default/notificaciones` y `alta`; revisar logs de workers. **Evidencia:** `QueueTest` procesa un job con `queue:work --once` en `notificaciones` y valida el encolado en `alta`. Los logs de los workers reales se revisan en el smoke manual.
- [x] Confirmar ejecución del scheduler para cada ambiente y calendario por empresa. **Evidencia:** `SchedulerTest` valida comandos y `monthlyOn` por empresa. La ejecución real se valida en el smoke manual.

### Pruebas funcionales de integraciones

- [ ] Grilla de servicios: listar, filtrar y actualizar vencimientos. **Nota:** `GrillaDos` usa `DATE_FORMAT` (MySQL) y no es portable a SQLite; queda como prueba manual/MySQL.
- [x] Livewire: navegar grillas/componentes y comprobar validación/errores. **Evidencia:** `LivewireComponentsTest` cubre `EmpresaToken` y permisos. Resto de componentes: smoke manual.
- [ ] MercadoPago: crear preferencia, completar pago de prueba y validar retorno/webhook. **Nota:** preferencia/webhook están cubiertos con `Http::fake()`; el pago real en sandbox sigue siendo manual.
- [ ] MercadoPago QR: generar QR, recibir pago de prueba y comprobar conciliación. **Nota:** pendiente de sandbox real.
- [ ] AFIP/ARCA: validar autenticación SOAP y emisión en homologación (sin usar producción). **Nota:** pendiente con certificado real.
- [x] WhatsApp: enviar notificación de prueba y comprobar manejo de errores. **Evidencia:** `WhatsAppServiceTest` (17 tests, `Http::fake()`). El envío real queda como smoke manual.
- [x] PDF y QR: generar comprobante, recibo y cierre de caja; verificar descarga/impresión. **Evidencia:** `DocumentGenerationTest` genera PDF con DomPDF y PNG con Endroid. La impresión/descarga en navegador queda manual.
- [x] Comprobar migraciones `up`/`down` con MySQL de prueba, además de SQLite. **Evidencia:** `TEST_MYSQL=1 scripts/test.sh` y job de CI.

### Automatización y cierre

- [x] Ejecutar suite completa y registrar errores, fallos y deprecaciones con causa. **Evidencia:** 146 tests, 0 errores, 0 fallos, 0 deprecaciones.
- [x] Corregir o aislar las pruebas que dependen de `APP_URL`, credenciales/API de MercadoPago y tests desincronizados de WhatsApp.
- [x] Ejecutar Pint en los archivos modificados; registrar por separado los incumplimientos preexistentes del resto del repositorio. **Evidencia:** Pint pasa en los 34 archivos modificados. El repositorio completo todavía tiene deuda de formato preexistente.
- [x] Ejecutar `php artisan config:cache` y `php artisan route:cache`; validar arranque posterior del contenedor. **Evidencia:** ambos comandos pasan y `route:list` funciona con la cache activa.
- [ ] Registrar resultado final de la etapa y crear el commit de cierre.

### Incidencias corregidas durante la estabilización

- `route:cache` fallaba por nombres de ruta duplicados (`reciboSueldo` y `loginUsuario`). Se unificó `reciboSueldo` con `Route::match(['get', 'post'])` y se renombró el GET legado de `loginUsuario`.
- `config:cache` dejaba `env('APP_URL')` en `NULL`. Se migraron 23 usos de `env()` a `config()` y se agregó `services.mercadopago.test_mode` / `public_url`.
- La migración `2025_10_18_122000_add_usuario_fields_to_expenses_table` usaba `getDoctrineSchemaManager()`, eliminado en Laravel 11. Se reemplazó por `dropForeign` con manejo de errores.
- `WhatsAppService::sendImage()` llamaba a `formatPhoneNumber()` inexistente; `sendCustomMessage()` estaba comentada pero se usaba desde `EnviarWhatsAppJob`. Ambos quedaron implementados.
- `PagosController::obtenerInfoPago()` y los callbacks usaban el SDK que no permite `Http::fake()`. Se migraron a `MercadoPagoApiService`.
- Los 17 `/** @test */` se convirtieron a `#[Test]` (PHPUnit 12 los elimina).
- `QueryTest`/suite: se fijó `APP_URL`, `MERCADOPAGO_TEST` y credenciales dummy en `phpunit.xml`; `Http::preventStrayRequests()` evita llamadas reales.

### Deuda conocida

- `GrillaDos::getClientes()` usa `DATE_FORMAT` de MySQL; no funciona en SQLite.
- Quedan usos de SQL específico de MySQL en consultas de reportes/grilla que conviene revisar antes de la Etapa 2.
- El repositorio completo no pasa `pint --test` por deuda preexistente; el gate solo exige los archivos modificados.
- La suite corre con SQLite; el job de CI con MariaDB cubre migraciones, no toda la suite funcional.

## Etapa 2 — Laravel 11 → 12

- [ ] Leer upgrade guide 12.x y verificar requisitos de PHP/dependencias.
- [ ] Revisar soporte de dependencias directas y actualizar `composer.json` / `composer.lock`.
- [ ] Actualizar `barryvdh/laravel-dompdf` a `^3.0` (v2 solo soporta hasta Laravel 11).
- [ ] Revisar `spatie/laravel-ignition` y `laravel/sail` (el skeleton 12 ya no los incluye).
- [ ] Revisar deprecaciones y cambios de framework aplicables a la aplicación.
- [ ] Ejecutar composer install/update, Artisan, Pint y suite de tests.
- [ ] Reconstruir contenedores y repetir pruebas funcionales e integraciones críticas.
- [ ] Documentar resultado y crear commit de cierre de etapa.

## Etapa 3 — Laravel 12 → 13

- [ ] Leer upgrade guide 13.x y anotar cambios de alto/medio impacto aplicables.
- [ ] Confirmar PHP 8.3+ en imagen, CI y entornos de ejecución. **Nota:** Laravel 13 requiere `php ^8.3`; actualizar `composer.json` (hoy `^8.2`).
- [ ] Revisar soporte de `laravel/framework` (^13), Tinker (^3), PHPUnit (^12.5) y Collision (^8).
- [ ] Confirmar Livewire 3.8.11+ (soporta Laravel 13) o evaluar Livewire 4.
- [ ] Actualizar dependencias y resolver incompatibilidades o deprecaciones.
- [ ] Revisar CSRF, serialización de cache/sesión, nombres de cookies y uso de `upsert()`.
- [ ] Ejecutar suite, Pint, Artisan cache commands y reconstruir contenedores.
- [ ] Repetir pruebas funcionales de autenticación, pagos, ARCA, WhatsApp, PDF y QR.
- [ ] Registrar resultado final y crear commit de cierre de etapa.

## Registro de cambios / decisiones

| Fecha | Etapa | Decisión o resultado | Responsable |
| --- | --- | --- | --- |
| 2026-10-12 | 1 | Stack dev reconstruido; 4 contenedores healthy; endpoints básicos responden. | Pendiente |
| 2026-10-22 | 1 | Baseline reproducible en contenedor: 115 tests, 5 errores, 12 fallos, 17 deprecaciones. | OpenCode |
| 2026-10-22 | 1 | Suite estabilizada: 146 tests, 0 errores, 0 fallos, 0 deprecaciones. | OpenCode |
| 2026-10-22 | 1 | `route:cache` y `config:cache` habilitados; 23 `env()` migrados a `config()`. | OpenCode |
| 2026-10-22 | 1 | Migraciones MySQL `up`/`down` validadas; corregida dependencia de Doctrine eliminada en L11. | OpenCode |
| 2026-10-22 | 1 | Harness `scripts/test.sh`, `scripts/smoke.sh`, imagen de test y CI agregados. | OpenCode |
