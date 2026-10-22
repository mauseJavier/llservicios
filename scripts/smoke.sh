#!/usr/bin/env bash
#
# Smoke test HTTP contra el contenedor de desarrollo.
# Verifica los endpoints públicos, el healthcheck y la autenticación por token.
#
# Uso:
#   scripts/smoke.sh                       # http://localhost:1234
#   BASE_URL=http://otro-host scripts/smoke.sh

set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:1234}"
fail=0

check() {
    local path="$1"
    local expected="$2"
    local method="${3:-GET}"
    local status

    status="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 -X "$method" "${BASE_URL}${path}" || true)"

    if [[ "$status" == "$expected" ]]; then
        printf '  OK   %-6s %-32s %s\n' "$method" "$path" "$status"
    else
        printf '  FAIL %-6s %-32s esperado %s, obtenido %s\n' "$method" "$path" "$expected" "$status"
        fail=1
    fi
}

echo "==> Smoke test contra $BASE_URL"
check "/login" "200"
check "/" "200"
check "/up" "200"
check "/api/registros-cerradura" "401" "POST"

if [[ "$fail" -ne 0 ]]; then
    echo "==> Smoke test: FALLÓ"
    exit 1
fi

echo "==> Smoke test: OK"
