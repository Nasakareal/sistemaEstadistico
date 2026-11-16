#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/sistemaEstadistico"
BACKUP_LABEL="pre-sync-sierse-$(date -u +%Y%m%dT%H%M%SZ)"

SIERSE_PATHS=(
  app/Http/Controllers/Api/GruaCorralonController.php
  app/Http/Controllers/Api/SierseIntegrationController.php
  app/Http/Controllers/LiberacionCorralonController.php
  app/Models/LiberacionCorralon.php
  app/Services/SierseIntegrationEventProcessor.php
  app/Services/SierseIntegrationStatusService.php
  database/migrations/2026_11_15_040000_add_sierse_report_to_liberaciones_corralon_table.php
  docs/integraciones/ENTREGA-GALVAN.txt
  docs/integraciones/README-GALVAN.md
  docs/integraciones/sierse-openapi.yaml
  resources/views/liberaciones_corralon/show.blade.php
)

chown -R nasaka:www-data "${APP_DIR}"
chmod -R ug+rwX "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod g+s {} +

sudo -u nasaka git -C "${APP_DIR}" stash push -u -m "${BACKUP_LABEL}" -- "${SIERSE_PATHS[@]}"
sudo -u nasaka git -C "${APP_DIR}" pull --ff-only origin main
sudo -u nasaka php "${APP_DIR}/artisan" migrate --force
sudo -u nasaka php "${APP_DIR}/artisan" config:clear
sudo -u nasaka php "${APP_DIR}/artisan" config:cache
sudo -u nasaka php "${APP_DIR}/artisan" view:clear

chown -R nasaka:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R ug+rwX "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod g+s {} +

echo "Git sincronizado con origin/main. Respaldo conservado en stash: ${BACKUP_LABEL}"
sudo -u nasaka git -C "${APP_DIR}" status --short
