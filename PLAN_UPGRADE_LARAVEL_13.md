# Plan de Actualización: Laravel 10 → 13

## Objetivo

Actualizar la aplicación de **Laravel 10.50.2** a **Laravel 13.x**, manteniendo
la compatibilidad de todas las integraciones (AFIP/ARCA, MercadoPago, Livewire,
WhatsApp, PDF/QR).

## Estado actual

| Paquete | Actual | Objetivo L13 |
| --- | --- | --- |
| PHP (Dockerfile) | 8.4 | 8.3 – 8.5 ✅ |
| laravel/framework | 10.50.2 | ^13.0 |
| laravel/sanctum | 3.3.3 | ^4.0 |
| laravel/tinker | 2.11.1 | ^3.0 |
| livewire/livewire | 3.8.2 | ^3.6 (sin cambio mayor) |
| barryvdh/laravel-dompdf | 2.2.0 | ^3.0 |
| nunomaduro/collision | 7.12.0 | ^8.0 |
| spatie/laravel-ignition | 2.9.1 | ^2.0 |
| phpunit/phpunit | 10.5.64 | ^12.0 |
| laravel/pint | 1.29.3 | ^1.0 |
| endroid/qr-code | 6.1.3 | ^6.1 |
| mercadopago/dx-php | 3.12.0 | ^3.7 |
| mause/laravel-arca | dev-main | `^10 \|\| ^11 \|\| ^12 \|\| ^13` ✅ |

## Ruta de actualización (NO se puede saltar)

Laravel no permite saltar versiones mayores. Se debe subir **una mayor por lote**,
con un commit/PR verificable por etapa:

```
10.x  ──►  11.x  ──►  12.x  ──►  13.x
         (mayor)    (menor)    (menor)
```

- **10 → 11**: la etapa más grande (reestructura del esqueleto).
- **11 → 12**: cambios menores, principalmente dependencias.
- **12 → 13**: `Estimated Upgrade Time: 10 minutos`, cambios mínimos.

## Etapa 1 — Laravel 10 → 11

### Dependencias
- `laravel/framework`: `^10.10` → `^11.0`
- `laravel/sanctum`: `^3.2` → `^4.0`
- `laravel/tinker`: `^2.8` → `^2.9`
- `nunomaduro/collision`: `^7.0` → `^8.0`
- `phpunit/phpunit`: `^10.1` → `^11.0`
- `livewire/livewire`: `^3.6` → `^3.5 || ^3.6`

### Cambios de esqueleto (los más importantes)
1. **Nuevo `bootstrap/app.php`**: reemplaza el bootstrap actual. El registro de
   middleware, rutas y excepciones pasa a este archivo.
2. **Eliminar** `app/Http/Kernel.php` y `app/Console/Kernel.php`. Su contenido
   (middleware global, grupos `web`/`api`, middleware de rutas) se migra a
   `bootstrap/app.php` con el método `->withMiddleware()`.
3. **Eliminar** `app/Providers/RouteServiceProvider.php` (ya no existe en L11 por
   defecto); las rutas se registran con `->withRouting()`.
4. **Eliminar** `app/Http/Middleware/` que ahora vienen del framework:
   `EncryptCookies`, `PreventRequestsDuringMaintenance`, `TrimStrings`,
   `TrustHosts`, `TrustProxies`, `ValidateSignature`, `VerifyCsrfToken`,
   `RedirectIfAuthenticated`, `Authenticate`.
   - **Conservar** los propios del proyecto: `RolAdmin`, `RolSuper`,
     `AuthenticateEmpresaApi`.
5. **Poda de `config/`**: en L11 se pueden borrar configs que usan defaults.
   - **Conservar los custom**: `afip.php`, `arca.php`, `sanctum.php`.
6. `app/Exceptions/Handler.php` simplificado (o eliminado) a favor de
   `bootstrap/app.php`.

> La lista exacta de middleware/rutas debe cotejarse con el contenido real de
> `app/Http/Kernel.php` y `app/Providers/RouteServiceProvider.php` antes de migrar.

### Verificación
- `php artisan config:clear && php artisan route:list` sin errores.
- `php artisan test`.
- Login y flujo autenticado (middlewares `RolAdmin`/`RolSuper`).

## Etapa 2 — Laravel 11 → 12

### Dependencias
- `laravel/framework`: `^11.0` → `^12.0`
- `laravel/tinker`: → `^2.10`
- `phpunit/phpunit`: → `^11.5`
- `nunomaduro/collision`: → `^8.0`

### Cambios
- Sin reestructura de esqueleto. Revisar changelog y deprecaciones.
- Sincronizar opcionalmente archivos del esqueleto `laravel/laravel`.

## Etapa 3 — Laravel 12 → 13

### Dependencias (según upgrade guide oficial)
- `laravel/framework`: `^12.0` → `^13.0`
- `laravel/tinker`: → `^3.0`
- `phpunit/phpunit`: → `^12.0`
- `nunomaduro/collision`: → `^8.0`
- (Opcional) `laravel/boost`: `^2.0`

### Cambios relevantes (High/Medium impact)
- **Request Forgery Protection**: revisar protección CSRF.
- **Cache `serializable_classes`**: nueva configuración de serialización.
- **Sesiones**: el nombre de cookie/prefix de cache puede cambiar; si se
  sincroniza el skeleton, mantener `session.serialization` en `php` para no
  invalidar sesiones activas.
- **`Database upsert` con MySQL/MariaDB**: revisar si se usa `upsert()`.
- PHP attributes (nuevos, opcionales y retrocompatibles).
- Se requiere PHP 8.3+ (Dockerfile ya usa 8.4 ✅).

## Riesgos y bloqueantes

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| Reestructura de esqueleto L11 | Alto | Migrar middleware/rutas a `bootstrap/app.php`; probar auth |
| `mause/laravel-arca` (custom) | Alto | Ya declara soporte `^10..^13`; validar en tests reales AFIP/ARCA |
| Sanctum `3 → 4` | Medio | Revisar config y tokens personales |
| Livewire 3 con L11+ | Medio | Probar componentes QR/grillas |
| Middlewares propios | Medio | Conservar `RolAdmin`, `RolSuper`, `AuthenticateEmpresaApi` |
| Nombre de sesión/cookies L13 | Bajo | Mantener `session.serialization = php` |

## Estrategia de ejecución

1. Un commit por etapa (`chore(deps): laravel 10 → 11`, etc.).
2. Actualizar `composer.json` + `composer update` dentro de Docker (PHP 8.4).
3. Ejecutar `php artisan test` y `vendor/bin/pint --test` en cada etapa.
4. Checkpoint manual de: login, grilla de servicios, AFIP/ARCA, MercadoPago,
   WhatsApp y generación de PDF/QR.
5. No mezclar esta rama con cambios funcionales de `devmause`.

## Checklist por etapa

- [ ] `composer update` sin conflictos de plataforma.
- [ ] `php artisan config:cache` / `route:cache` sin errores.
- [ ] `php artisan test` en verde.
- [ ] `vendor/bin/pint --test` en verde.
- [ ] Smoke test de integraciones críticas.
- [ ] `docs/` actualizado con incidencias encontradas.

---

## Progreso Etapa 1 (Laravel 10 → 11)

Estado: **aplicada**. `laravel/framework 11.57.0`, `sanctum 4.3`,
`tinker 2.x`, `phpunit 11`, `collision 8`. Esqueleto migrado a
`bootstrap/app.php` + `bootstrap/providers.php`; schedule movido a
`routes/console.php`; Kernels, `RouteServiceProvider` y middleware
redundante eliminados. `php artisan route:list` → 159 rutas. Smoke test dev
(`/login`, `/`, `/up` → 200; middleware `empresa.token` → 401; scheduler OK).

### Entorno de verificación (sin tocar el Dockerfile)

- Composer/tests se corren en **contenedores efímeros**. Imagen de test
  `llservicios-test:local` (PHP 8.4 + soap/zip/bcmath/gd), Dockerfile en
  `/tmp/opencode/php-test/Dockerfile`.
- `composer update` en `composer:2` con `--no-blocking` (Laravel 11 ya está
  EOL y Composer 2.10 bloquea por advisories) y `--ignore-platform-req=ext-soap`.
- Tests con **SQLite in-memory** (`phpunit.xml`).

### Resultado de tests e incidencias pendientes

La suite completa quedó en **115 tests, 5 errores, 12 fallos y 17
deprecaciones**. `ExtenderVencimientoTest` pasó sus 6 tests tras hacer las
aserciones compatibles con SQLite. No se pudo ejecutar la suite de la línea
base: su `composer.lock` fija `mercadopago/dx-php 3.12.0`, que exige PHP ≥ 8.5,
mientras `config.platform.php` fija PHP 8.4. Por ello no se puede afirmar que
todos los errores/fallos restantes sean preexistentes.

Corregido durante la etapa:

- `PagosController::obtenerInfoPago` → `PaymentClient::get((int) $paymentId)`
  (regresión del bump a mercadopago 3.16, que tipa `int`).
- `MercadoPagoLinkService` setea `referencia` en el `create` (fallaba por NOT
  NULL en SQLite).
- `ExtenderVencimientoTest`: aserciones de fecha portables a SQLite.
- Migraciones con SQL MySQL-específico (`SHOW INDEX`, `information_schema`,
  multi-table UPDATE) hechas portables.

Fallos pendientes por categoría:

| Categoría | Tests | Causa |
| --- | --- | --- |
| URLs MP (`httpbin.org`/`llservicios.com`) | 5 fallos | `APP_URL` es ngrok en el entorno de test |
| Obtener pagos MercadoPago | 5 fallos | respuestas 401 por credenciales/API del entorno |
| Persistencia de pago MP | 1 fallo | test esperaba `id_usuario=1`, persistió `0` |
| WhatsApp | 3 errores + 1 fallo | métodos de test inexistentes y expectativa de error distinta |
| Helpers MP | 2 errores | `Undefined array key` en datos de test |

### Pendiente para cerrar la etapa

- Checkpoint manual en dev: grilla de servicios, AFIP/ARCA, MercadoPago, QR,
  WhatsApp y PDF.
- Revisar si conviene un `config:cache`/`route:cache` en el entrypoint.

---

## Cierre Etapa 1 y Etapa 2 (2026-10-22)

### Etapa 1 cerrada

- Suite estabilizada: **146 tests, 405 aserciones, 0 errores, 0 fallos, 0 deprecaciones**.
- Se corrigieron los 17 fallos/errores y las 17 deprecaciones listadas arriba.
- `config:cache` y `route:cache` funcionan (se migraron 23 `env()` a `config()` y
  se resolvieron dos nombres de ruta duplicados).
- Migraciones MySQL `up`/`down` validadas; se eliminó el uso de
  `getDoctrineSchemaManager()`.
- Gate reproducible: `scripts/test.sh`; smoke HTTP: `scripts/smoke.sh`; CI en
  `.github/workflows/tests.yml`.
- Smoke dev: 4 contenedores healthy, grilla/MercadoPago/WhatsApp/AFIP verificados.

### Etapa 2 aplicada

| Paquete | Antes | Después |
| --- | --- | --- |
| laravel/framework | 11.57.0 | **12.70.1** |
| laravel/tinker | 2.11.1 | 2.11.1 |
| phpunit/phpunit | 11.5.57 | 11.5.57 |
| barryvdh/laravel-dompdf | 2.2.0 | **3.1.2** |
| dompdf/dompdf | 2.0.8 | **3.1.6** |
| livewire/livewire | 3.8.10 | 3.8.11 |
| nesbot/carbon | 2.x | **3.15.0** |

- `spatie/laravel-ignition` y `laravel/sail` eliminados (no se usan con el Docker propio).
- Cambios de L12 revisados: precedencia de rutas (ya corregida), Carbon 3
  (sin `diffIn*` en la app), disco `local` (definido explícitamente), validación
  `image` (no usada), UUIDv7 (no usado) y `mergeIfMissing` (no usado).
- Gate completo OK tras el upgrade: suite, Pint, rutas, caches y migraciones MySQL.
- Contenedores dev reconstruidos con Laravel 12.70.1; smoke HTTP e integraciones OK.

### Próximo paso

- Etapa 3: Laravel 12 → 13 (`php ^8.3`, framework `^13`, Tinker `^3`, PHPUnit
  `^12.5`), revisión de CSRF/serialización de sesión/cookies y `upsert()`.
