#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/sistemaEstadistico"
STAGE_DIR="/home/nasaka/sierse-deploy-20261115/stage"

install -o root -g root -m 0644 \
  "${STAGE_DIR}/show.blade.php" \
  "${APP_DIR}/resources/views/liberaciones_corralon/show.blade.php"

install -o root -g root -m 0644 \
  "${STAGE_DIR}/sierse-openapi.yaml" \
  "${APP_DIR}/docs/integraciones/sierse-openapi.yaml"

install -o root -g root -m 0644 \
  "${STAGE_DIR}/README-GALVAN.md" \
  "${APP_DIR}/docs/integraciones/README-GALVAN.md"

install -o root -g root -m 0644 \
  "${STAGE_DIR}/ENTREGA-GALVAN.txt" \
  "${APP_DIR}/docs/integraciones/ENTREGA-GALVAN.txt"

cd "${APP_DIR}"
sudo -u nasaka php artisan view:clear

echo "Vista web y documentacion SIERSE instaladas correctamente."
