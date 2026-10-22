#!/usr/bin/env bash
#
# Gate de tests reproducible para la actualización de Laravel.
#
# Corre la suite completa, Pint sobre los archivos modificados y el ciclo de
# cache en una copia temporal del repositorio, dentro de un contenedor PHP 8.4.
# No deja archivos generados en el árbol de trabajo.
#
# Uso:
#   scripts/test.sh                 # suite + Pint + caches
#   SKIP_BUILD=1 scripts/test.sh    # reutiliza la imagen local
#   PINT_ALL=1 scripts/test.sh      # Pint sobre todo el código
#   TEST_MYSQL=1 scripts/test.sh    # además prueba migraciones up/down en MySQL
#
# Variables para MySQL (si TEST_MYSQL=1):
#   TEST_DB_HOST (mariadb), TEST_DB_PORT (3306), TEST_DB_DATABASE (llservicios_test),
#   TEST_DB_USERNAME, TEST_DB_PASSWORD.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IMAGE="${TEST_IMAGE:-llservicios-test:local}"
WORK="$(mktemp -d "${TMPDIR:-/tmp}/llservicios-test.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT

if [[ "${SKIP_BUILD:-0}" != "1" ]]; then
    echo "==> Construyendo imagen de test ($IMAGE)"
    docker build -q -t "$IMAGE" -f "$ROOT/docker/test/Dockerfile" "$ROOT" >/dev/null
fi

echo "==> Preparando copia temporal de trabajo"
rsync -a \
    --exclude .git \
    --exclude storage/logs \
    --exclude storage/framework \
    --exclude bootstrap/cache \
    --exclude .phpunit.result.cache \
    "$ROOT/" "$WORK/"

mkdir -p \
    "$WORK/storage/logs" \
    "$WORK/storage/framework/cache" \
    "$WORK/storage/framework/sessions" \
    "$WORK/storage/framework/views" \
    "$WORK/bootstrap/cache"
chmod -R 777 "$WORK/storage" "$WORK/bootstrap/cache"

if [[ ! -f "$WORK/vendor/autoload.php" ]]; then
    echo "==> Instalando dependencias de Composer"
    docker run --rm -v "$WORK":/app -w /app "$IMAGE" \
        composer install --no-interaction --prefer-dist --no-progress
fi

run() {
    docker run --rm \
        -v "$WORK":/app \
        -w /app \
        -e APP_ENV=testing \
        "${NETWORK_ARGS[@]}" \
        "${DOCKER_ENV_ARGS[@]}" \
        "$IMAGE" "$@"
}

# Permite conectar el contenedor de test a la red de los servicios (MariaDB).
NETWORK_ARGS=()
if [[ -n "${TEST_DOCKER_NETWORK:-}" ]]; then
    NETWORK_ARGS+=(--network "$TEST_DOCKER_NETWORK")
fi

# Permite pasar variables de entorno extra al contenedor (por ejemplo MySQL).
DOCKER_ENV_ARGS=()
if [[ "${TEST_MYSQL:-0}" == "1" ]]; then
    DOCKER_ENV_ARGS+=(
        -e DB_CONNECTION=mysql
        -e "DB_HOST=${TEST_DB_HOST:-mariadb}"
        -e "DB_PORT=${TEST_DB_PORT:-3306}"
        -e "DB_DATABASE=${TEST_DB_DATABASE:-llservicios_test}"
        -e "DB_USERNAME=${TEST_DB_USERNAME:-root}"
        -e "DB_PASSWORD=${TEST_DB_PASSWORD:-}"
    )
fi

echo "==> Limpiando configuración"
run php artisan config:clear >/dev/null
run php artisan view:clear >/dev/null

echo "==> Suite de tests"
run php vendor/bin/phpunit --do-not-cache-result --colors=never

if [[ "${PINT_ALL:-0}" == "1" ]]; then
    echo "==> Pint (todo el repositorio)"
    run php vendor/bin/pint --test
else
    echo "==> Pint (archivos modificados en el working tree)"
    mapfile -t PINT_FILES < <(
        { git -C "$ROOT" diff --name-only --diff-filter=ACM HEAD -- '*.php';
          git -C "$ROOT" ls-files --others --exclude-standard -- '*.php'; } | sort -u
    )
    if [[ ${#PINT_FILES[@]} -gt 0 ]]; then
        run php vendor/bin/pint --test "${PINT_FILES[@]}"
    else
        echo "    (sin archivos PHP modificados)"
    fi
fi

echo "==> Rutas"
run php artisan route:list >/dev/null
echo "    $(run php artisan route:list --json | grep -o '"uri"' | wc -l) rutas registradas"

echo "==> Ciclo de cache (config + routes)"
run php artisan config:cache >/dev/null
run php artisan route:cache >/dev/null
run php artisan route:list >/dev/null
run php artisan optimize:clear >/dev/null
echo "    OK"

if [[ "${TEST_MYSQL:-0}" == "1" ]]; then
    echo "==> Migraciones MySQL (up/down)"
    run php artisan migrate:fresh --force
    run php artisan migrate:rollback --step=all --force
    echo "    OK"
fi

echo "==> Gate completo: OK"
