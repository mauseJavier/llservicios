# Checklist de seguimiento: actualización a Laravel 13

## Datos de seguimiento

| Campo | Valor |
| --- | --- |
| Rama | `feature/upgrade-laravel` |
| Base actual | Laravel 13.38.0 / PHP 8.4 |
| Objetivo | Laravel 13.x |
| Responsable | Pendiente de asignar |
| Fecha objetivo general | Pendiente de agendar |
| Última actualización | 2026-10-22 |

## Resumen de progreso

| Etapa | Estado | Próximo hito |
| --- | --- | --- |
| 1 — Laravel 10 → 11 | Cerrada; suite y smoke dev verificados | — |
| 2 — Laravel 11 → 12 | Cerrada; suite, migraciones y smoke OK | — |
| 3 — Laravel 12 → 13 | Aplicada; suite, migraciones y smoke OK | Commit de cierre y ventana de sesiones |

**Estado de suite actual:** 146 tests, 405 aserciones, 0 errores, 0 fallos, 0 deprecaciones.
Verificado el 2026-10-22 con Laravel **13.38.0**, PHPUnit 12.5.40 y `scripts/test.sh`.

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
- [ ] MercadoPago: crear preferencia, completar pago de prueba y validar retorno/webhook. **Nota:** credenciales y webhook validados; el pago real requiere confirmación porque el token actual es de producción (`APP_USR`, `sandbox=false`).
- [ ] MercadoPago QR: generar QR, recibir pago de prueba y comprobar conciliación. **Nota:** pendiente de sandbox real.
- [ ] AFIP/ARCA: validar autenticación SOAP y emisión en homologación (sin usar producción). **Evidencia:** autenticación WSAA + WSFE OK para empresa 1. La emisión de un comprobante de prueba queda pendiente.
- [x] WhatsApp: enviar notificación de prueba y comprobar manejo de errores. **Evidencia:** `WhatsAppServiceTest` (17 tests, `Http::fake()`) y conexión real `open` de la instancia `LLFACTURA`. El envío real queda pendiente de un número de destino.
- [x] PDF y QR: generar comprobante, recibo y cierre de caja; verificar descarga/impresión. **Evidencia:** `DocumentGenerationTest` genera PDF con DomPDF y PNG con Endroid. La impresión/descarga en navegador queda manual.
- [x] Comprobar migraciones `up`/`down` con MySQL de prueba, además de SQLite. **Evidencia:** `TEST_MYSQL=1 scripts/test.sh` y job de CI.

### Automatización y cierre

- [x] Ejecutar suite completa y registrar errores, fallos y deprecaciones con causa. **Evidencia:** 146 tests, 0 errores, 0 fallos, 0 deprecaciones.
- [x] Corregir o aislar las pruebas que dependen de `APP_URL`, credenciales/API de MercadoPago y tests desincronizados de WhatsApp.
- [x] Ejecutar Pint en los archivos modificados; registrar por separado los incumplimientos preexistentes del resto del repositorio. **Evidencia:** Pint pasa en los 34 archivos modificados. El repositorio completo todavía tiene deuda de formato preexistente.
- [x] Ejecutar `php artisan config:cache` y `php artisan route:cache`; validar arranque posterior del contenedor. **Evidencia:** ambos comandos pasan y `route:list` funciona con la cache activa.
- [ ] Registrar resultado final de la etapa y crear el commit de cierre.

### Resultado del smoke manual (2026-10-22)

- Contenedores: 4/4 `healthy`; Laravel 11.57.0 en PHP 8.4.27.
- `scripts/smoke.sh`: `/login`, `/`, `/up` → 200; `POST /api/registros-cerradura` sin token → 401.
- Túnel ngrok: estaba offline (`ERR_NGROK_3200`); se levantó y el dominio configurado volvió a responder 200. Las `back_urls` y el webhook de MercadoPago ya son alcanzables.
- MercadoPago: credenciales válidas (`validateCredentials()`), 16 métodos de pago obtenidos. El token es de producción (`APP_USR`, `sandbox=false`), por lo que no se generaron preferencias ni pagos reales.
- Webhook MercadoPago: `POST /mercadopago/webhook` responde 500 con pago inexistente (fuerza reintento); `POST /api/mercadopago/webhook/qr` responde 200 `ignored`.
- AFIP/ARCA homologación: empresa 1 (`20358337164`) obtiene TA de WSAA y conectividad WSFE OK. Empresa 2 (`20350796631`) falla con `Certificado no emitido por AC de confianza`.
- WhatsApp: la clave global responde 401. Con la configuración por empresa, la instancia `LLFACTURA` (empresas 1 y 2) está `open`; `ClubLaslajas` y `Carlota` están `close`; `powerconfigurar` responde 404.
- Grilla: la consulta real de `GrillaDos` contra MySQL devuelve 5 clientes y 12 períodos para la empresa 3. La validación visual en navegador queda pendiente.

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
- El túnel ngrok de desarrollo (`prepositionally-vacciniaceous-irving.ngrok-free.dev`) debe levantarse con `ngrok http 1234`; sin él, las `back_urls` y el webhook de MercadoPago no funcionan.
- La clave global de WhatsApp (`WHATSAPP_API_KEY`) responde 401; el envío funciona con la configuración por empresa.
- El certificado AFIP de la empresa 2 (`20350796631`) no es aceptado por WSAA homologación.
- El webhook principal de MercadoPago responde 500 ante un pago inexistente para forzar reintento; revisar si conviene distinguir 404/200.
- La sesión queda con `serialization=php` para no invalidar sesiones activas. Migrar a `json` requiere una ventana de mantenimiento.
- Si en el futuro se cachean objetos PHP, hay que declararlos en `cache.serializable_classes`; hoy solo se cachean escalares/arrays.
- La emisión real de un comprobante AFIP en homologación y el pago real de MercadoPago siguen pendientes de una prueba asistida con datos/credenciales de test.

## Etapa 2 — Laravel 11 → 12

- [x] Leer upgrade guide 12.x y verificar requisitos de PHP/dependencias. **Evidencia:** impacto alto solo en dependencias; Carbon 3 obligatorio.
- [x] Revisar soporte de dependencias directas y actualizar `composer.json` / `composer.lock`. **Evidencia:** framework 12.70.1, Tinker 2.11.1, PHPUnit 11.5.57, DomPDF 3.1.2, Livewire 3.8.11, Carbon 3.15.
- [x] Actualizar `barryvdh/laravel-dompdf` a `^3.0` (v2 solo soporta hasta Laravel 11). **Evidencia:** DomPDF 3.1.6 + laravel-dompdf 3.1.2; `DocumentGenerationTest` pasa.
- [x] Revisar `spatie/laravel-ignition` y `laravel/sail` (el skeleton 12 no incluye ignition). **Decisión:** se quitaron ambos por no usarse con el Docker propio.
- [x] Revisar deprecaciones y cambios de framework aplicables a la aplicación. **Evidencia:** sin `diffIn*`, UUID, validación `image`, `mergeIfMissing`; disco `local` definido explícitamente. La precedencia de rutas cambiada por L12 ya estaba corregida con los nombres duplicados.
- [x] Ejecutar composer install/update, Artisan, Pint y suite de tests. **Evidencia:** 146 tests verdes, Pint OK, `route:list` con 158 rutas.
- [x] Reconstruir contenedores y repetir pruebas funcionales e integraciones críticas. **Evidencia:** 4 contenedores healthy con Laravel 12.70.1; smoke 200/401 OK; grilla MySQL, MercadoPago, WhatsApp y AFIP homologación OK.
- [ ] Documentar resultado y crear commit de cierre de etapa.

### Resultado de la Etapa 2 (2026-10-22)

- `composer update --with-all-dependencies --no-blocking` sin conflictos.
- `laravel/framework` 11.57.0 → **12.70.1**; Carbon 3.15; DomPDF 3.1.6/3.1.2; Livewire 3.8.11.
- `spatie/laravel-ignition` y `laravel/sail` eliminados por no usarse.
- Suite completa: 146 tests, 405 aserciones, 0 fallos, 0 deprecaciones.
- Migraciones MySQL `up`/`down`: OK.
- Contenedores dev reconstruidos: 4/4 healthy con Laravel 12.70.1.
- Smoke HTTP: `/login`, `/`, `/up` → 200; API sin token → 401.
- Integraciones: grilla MySQL (5 clientes, 12 períodos), MercadoPago válido, WhatsApp `open` y AFIP homologación OK.

## Etapa 3 — Laravel 12 → 13

- [x] Leer upgrade guide 13.x y anotar cambios de alto/medio impacto aplicables. **Evidencia:** CSRF renombrado, cache `serializable_classes`, upsert, cookies/sesión.
- [x] Confirmar PHP 8.3+ en imagen, CI y entornos de ejecución. **Evidencia:** `composer.json` con `php ^8.3`; Docker y CI en PHP 8.4.
- [x] Revisar soporte de `laravel/framework` (^13), Tinker (^3), PHPUnit (^12.5) y Collision (^8). **Evidencia:** framework 13.38.0, Tinker 3.0.2, PHPUnit 12.5.40, Collision 8.9.5.
- [x] Confirmar Livewire 3.8.11+ (soporta Laravel 13) o evaluar Livewire 4. **Decisión:** se mantiene Livewire 3.8.11.
- [x] Actualizar dependencias y resolver incompatibilidades o deprecaciones. **Evidencia:** Sanctum 4.3.3, DomPDF 3.1.2, `mause/laravel-arca` dev-main; Symfony 8.1.9 por CVE-2026-108082.
- [x] Revisar CSRF, serialización de cache/sesión, nombres de cookies y uso de `upsert()`. **Evidencia:** CSRF migrado a `PreventRequestForgery`; sesión con `serialization=php` explícito; prefijo/cookie ya definidos en config; sin `upsert()`.
- [x] Ejecutar suite, Pint, Artisan cache commands y reconstruir contenedores. **Evidencia:** 146 tests, Pint OK, caches OK, 4 contenedores healthy con Laravel 13.38.0.
- [x] Repetir pruebas funcionales de autenticación, pagos, ARCA, WhatsApp, PDF y QR. **Evidencia:** smoke 200/401, CSRF 419, webhooks excluidos, grilla MySQL, MP válido, WhatsApp `open`, AFIP homologación OK, PDF/QR tests.
- [ ] Registrar resultado final y crear commit de cierre de etapa.

### Resultado de la Etapa 3 (2026-10-22)

- `laravel/framework` 12.70.1 → **13.38.0**; Tinker 3.0.2; PHPUnit 12.5.40.
- CSRF: `validateCsrfTokens()` → `preventRequestForgery()`; test actualizado a `PreventRequestForgery`.
- Sesión: `serialization=php` explícito para no invalidar sesiones activas.
- Cache: `serializable_classes` queda en el default seguro (`false`); la app solo cachea escalares/arrays.
- Sin usos de `upsert()`, UUIDv7, `array_first()`, paginación Bootstrap ni eventos de cola afectados.
- Suite: 146 tests, 405 aserciones, 0 fallos, 0 deprecaciones con PHPUnit 12.
- Migraciones MySQL `up`/`down`: OK.
- Contenedores dev: 4/4 healthy con Laravel 13.38.0.
- Smoke: endpoints 200/401, CSRF activo (419), webhooks excluidos, grilla/MP/WhatsApp/AFIP OK.
- `composer audit`: sin vulnerabilidades conocidas.

## Registro de cambios / decisiones

| Fecha | Etapa | Decisión o resultado | Responsable |
| --- | --- | --- | --- |
| 2026-10-12 | 1 | Stack dev reconstruido; 4 contenedores healthy; endpoints básicos responden. | Pendiente |
| 2026-10-22 | 1 | Baseline reproducible en contenedor: 115 tests, 5 errores, 12 fallos, 17 deprecaciones. | OpenCode |
| 2026-10-22 | 1 | Suite estabilizada: 146 tests, 0 errores, 0 fallos, 0 deprecaciones. | OpenCode |
| 2026-10-22 | 1 | `route:cache` y `config:cache` habilitados; 23 `env()` migrados a `config()`. | OpenCode |
| 2026-10-22 | 1 | Migraciones MySQL `up`/`down` validadas; corregida dependencia de Doctrine eliminada en L11. | OpenCode |
| 2026-10-22 | 1 | Harness `scripts/test.sh`, `scripts/smoke.sh`, imagen de test y CI agregados. | OpenCode |
| 2026-10-22 | 1 | Smoke en dev: 4 contenedores healthy; endpoints 200/401 OK; grilla MySQL OK. | OpenCode |
| 2026-10-22 | 1 | MercadoPago: credenciales y webhook alcanzables; token de producción, no se generaron pagos. | OpenCode |
| 2026-10-22 | 1 | AFIP homologación OK para empresa 1; empresa 2 con certificado no confiable. | OpenCode |
| 2026-10-22 | 1 | WhatsApp: instancia `LLFACTURA` open por empresa; clave global con 401. | OpenCode |
| 2026-10-22 | 2 | Dependencias actualizadas a Laravel 12.70.1 / DomPDF 3 / Carbon 3. | OpenCode |
| 2026-10-22 | 2 | Suite y migraciones MySQL OK; contenedores reconstruidos y smoke dev OK. | OpenCode |
| 2026-10-22 | 2 | Integraciones post-upgrade: grilla, MercadoPago, WhatsApp y AFIP homologación OK. | OpenCode |
| 2026-10-22 | 3 | Dependencias a Laravel 13.38.0, Tinker 3, PHPUnit 12.5 y Symfony 8.1.9. | OpenCode |
| 2026-10-22 | 3 | CSRF migrado a `PreventRequestForgery`; sesión con `serialization=php`. | OpenCode |
| 2026-10-22 | 3 | Suite, migraciones MySQL y smoke dev OK; 4 contenedores healthy. | OpenCode |
