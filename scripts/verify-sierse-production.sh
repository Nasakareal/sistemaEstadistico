#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/sistemaEstadistico"
DELIVERY_DIR="/home/nasaka/sierse-delivery"
ENV_FILE="${APP_DIR}/.env"
PFX_FILE="${DELIVERY_DIR}/galvan-api.pfx"
CERT_INFO="${DELIVERY_DIR}/certificado-galvan.txt"
BASE_URL="https://seguridadvial-mich.com"

env_value() {
  local key="$1"
  local value
  value="$(grep -m1 "^${key}=" "${ENV_FILE}" | cut -d= -f2-)"
  value="${value%\"}"
  value="${value#\"}"
  printf '%s' "${value}"
}

CLIENT_ID="$(env_value SIERSE_API_CLIENT_ID)"
KEY_ID="$(env_value SIERSE_API_KEY_ID)"
HMAC_SECRET="$(env_value SIERSE_API_CLIENT_SECRET)"
PFX_PASSWORD="$(grep -m1 '^Contrasena PFX: ' "${CERT_INFO}" | sed 's/^Contrasena PFX: //')"

if [[ -z "${CLIENT_ID}" || -z "${KEY_ID}" || -z "${HMAC_SECRET}" || -z "${PFX_PASSWORD}" ]]; then
  echo "Faltan credenciales de prueba." >&2
  exit 1
fi

signed_get() {
  local path_query="$1"
  local output_file="$2"
  local timestamp nonce empty_hash canonical signature
  timestamp="$(date -u +%s)"
  nonce="$(openssl rand -hex 24)"
  empty_hash="e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
  canonical="$(printf 'GET\n%s\n%s\n%s\n%s\n%s\n%s' "${path_query}" "${CLIENT_ID}" "${KEY_ID}" "${timestamp}" "${nonce}" "${empty_hash}")"
  signature="$(printf '%s' "${canonical}" | openssl dgst -sha256 -hmac "${HMAC_SECRET}" -binary | xxd -p -c 256)"

  rm -f "${output_file}"
  local http_code
  http_code="$(curl --silent --show-error \
    --cert-type P12 \
    --cert "${PFX_FILE}:${PFX_PASSWORD}" \
    -H "Accept: application/json" \
    -H "X-SIERSE-Client: ${CLIENT_ID}" \
    -H "X-SIERSE-Key-Id: ${KEY_ID}" \
    -H "X-SIERSE-Timestamp: ${timestamp}" \
    -H "X-SIERSE-Nonce: ${nonce}" \
    -H "X-SIERSE-Signature: ${signature}" \
    --output "${output_file}" \
    --write-out '%{http_code}' \
    "${BASE_URL}${path_query}")"

  echo "${path_query} -> HTTP ${http_code}"
  if [[ "${http_code}" -lt 200 || "${http_code}" -ge 300 ]]; then
    cat "${output_file}"
    echo
    return 1
  fi
}

signed_post() {
  local path_query="$1"
  local body="$2"
  local output_file="$3"
  local timestamp nonce body_hash canonical signature http_code
  timestamp="$(date -u +%s)"
  nonce="$(openssl rand -hex 24)"
  body_hash="$(printf '%s' "${body}" | openssl dgst -sha256 -binary | xxd -p -c 256)"
  canonical="$(printf 'POST\n%s\n%s\n%s\n%s\n%s\n%s' "${path_query}" "${CLIENT_ID}" "${KEY_ID}" "${timestamp}" "${nonce}" "${body_hash}")"
  signature="$(printf '%s' "${canonical}" | openssl dgst -sha256 -hmac "${HMAC_SECRET}" -binary | xxd -p -c 256)"

  rm -f "${output_file}"
  http_code="$(curl --silent --show-error \
    --cert-type P12 \
    --cert "${PFX_FILE}:${PFX_PASSWORD}" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "X-SIERSE-Client: ${CLIENT_ID}" \
    -H "X-SIERSE-Key-Id: ${KEY_ID}" \
    -H "X-SIERSE-Timestamp: ${timestamp}" \
    -H "X-SIERSE-Nonce: ${nonce}" \
    -H "X-SIERSE-Signature: ${signature}" \
    --data-binary "${body}" \
    --output "${output_file}" \
    --write-out '%{http_code}' \
    "${BASE_URL}${path_query}")"

  echo "${path_query} -> HTTP ${http_code}"
  if [[ "${http_code}" -lt 200 || "${http_code}" -ge 300 ]]; then
    cat "${output_file}"
    echo
    return 1
  fi
}

signed_get "/api/integraciones/sierse/v1/estado" "${DELIVERY_DIR}/prueba-estado.json"
signed_get "/api/integraciones/sierse/v1/vehiculos?source=vehicle_registry&page=1&per_page=1" "${DELIVERY_DIR}/prueba-vehiculo.json"
signed_get "/api/integraciones/sierse/v1/vehiculos?source=conduce_legalidad&page=1&per_page=1" "${DELIVERY_DIR}/prueba-conduce.json"

SAMPLE_ID="$(php -r '$d=json_decode(file_get_contents($argv[1]),true); echo $d["data"][0]["id"] ?? "";' "${DELIVERY_DIR}/prueba-vehiculo.json")"
SAMPLE_PLATE="$(php -r '$d=json_decode(file_get_contents($argv[1]),true); echo rawurlencode($d["data"][0]["plate"] ?? "");' "${DELIVERY_DIR}/prueba-vehiculo.json")"
if [[ -z "${SAMPLE_ID}" || -z "${SAMPLE_PLATE}" ]]; then
  echo "La muestra no contiene id o placas." >&2
  exit 1
fi

signed_get "/api/integraciones/sierse/v1/buscar?type=plate&value=${SAMPLE_PLATE}" "${DELIVERY_DIR}/prueba-busqueda.json"

EVENT_ID="TEST-$(date -u +%Y%m%dT%H%M%SZ)-$(openssl rand -hex 3)"
EVENT_BODY="{\"external_event_id\":\"${EVENT_ID}\",\"event_type\":\"other\",\"schema_version\":\"1.0\",\"data\":{\"test\":true,\"note\":\"Prueba automatizada; no aplicar al expediente oficial\"}}"
signed_post "/api/integraciones/sierse/v1/vehiculos/vehicle_registry/${SAMPLE_ID}/eventos" "${EVENT_BODY}" "${DELIVERY_DIR}/prueba-evento.json"
signed_get "/api/integraciones/sierse/v1/eventos/${EVENT_ID}" "${DELIVERY_DIR}/prueba-evento-consulta.json"

php -r '
$status = json_decode(file_get_contents($argv[1]), true);
$vehicles = json_decode(file_get_contents($argv[2]), true);
$conduce = json_decode(file_get_contents($argv[3]), true);
$search = json_decode(file_get_contents($argv[4]), true);
$event = json_decode(file_get_contents($argv[5]), true);
$eventQuery = json_decode(file_get_contents($argv[6]), true);
$item = $vehicles["data"][0] ?? [];
echo json_encode([
    "status_ok" => $status["ok"] ?? false,
    "integration" => $status["integration"] ?? null,
    "data_exchange_enabled" => $status["data_exchange_enabled"] ?? null,
    "vehicle_response_ok" => $vehicles["ok"] ?? false,
    "vehicle_total" => $vehicles["meta"]["total"] ?? null,
    "conduce_response_ok" => $conduce["ok"] ?? false,
    "conduce_total" => $conduce["meta"]["total"] ?? null,
    "plate_search_results" => count($search["data"] ?? []),
    "inbound_event_status" => $event["event"]["status"] ?? null,
    "event_query_status" => $eventQuery["event"]["status"] ?? null,
    "sample_fields" => array_keys($item),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
' "${DELIVERY_DIR}/prueba-estado.json" "${DELIVERY_DIR}/prueba-vehiculo.json" "${DELIVERY_DIR}/prueba-conduce.json" "${DELIVERY_DIR}/prueba-busqueda.json" "${DELIVERY_DIR}/prueba-evento.json" "${DELIVERY_DIR}/prueba-evento-consulta.json"

chmod 0600 "${DELIVERY_DIR}"/prueba-*.json
