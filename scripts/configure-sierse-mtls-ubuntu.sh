#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Ejecuta este script con sudo." >&2
  exit 1
fi

VHOST="/etc/apache2/sites-available/seguridadvial-mich.com-le-ssl.conf"
TLS_DIR="/etc/ssl/sierse"
DELIVERY_DIR="/home/nasaka/sierse-delivery"
APP_DIR="/var/www/html/sistemaEstadistico"
CLIENT_NAME="galvan-api"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

if [[ ! -f "${VHOST}" ]]; then
  echo "No existe el vhost ${VHOST}." >&2
  exit 1
fi

apache2ctl configtest

install -d -m 0700 "${TLS_DIR}"
install -d -m 0700 "${DELIVERY_DIR}"
cp -a "${VHOST}" "${VHOST}.bak.${STAMP}"

if [[ ! -s "${TLS_DIR}/ca.key" || ! -s "${TLS_DIR}/ca.crt" ]]; then
  openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out "${TLS_DIR}/ca.key"
  openssl req -x509 -new -sha256 -days 3650 \
    -key "${TLS_DIR}/ca.key" \
    -out "${TLS_DIR}/ca.crt" \
    -subj "/C=MX/ST=Michoacan/O=Seguridad Vial/OU=Integraciones/CN=SIERSE Partner CA"
fi

openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out "${TLS_DIR}/${CLIENT_NAME}.key"
openssl req -new -sha256 \
  -key "${TLS_DIR}/${CLIENT_NAME}.key" \
  -out "${TLS_DIR}/${CLIENT_NAME}.csr" \
  -subj "/C=MX/ST=Michoacan/O=Gruas Galvan/OU=Integracion SIERSE/CN=galvan-api"

EXT_FILE="$(mktemp)"
trap 'rm -f "${EXT_FILE}"' EXIT
cat > "${EXT_FILE}" <<'EOF'
basicConstraints=critical,CA:FALSE
keyUsage=critical,digitalSignature,keyEncipherment
extendedKeyUsage=clientAuth
subjectAltName=DNS:galvan-api
EOF

openssl x509 -req -sha256 -days 90 \
  -in "${TLS_DIR}/${CLIENT_NAME}.csr" \
  -CA "${TLS_DIR}/ca.crt" \
  -CAkey "${TLS_DIR}/ca.key" \
  -CAcreateserial \
  -out "${TLS_DIR}/${CLIENT_NAME}.crt" \
  -extfile "${EXT_FILE}"

PFX_PASSWORD="$(openssl rand -base64 30 | tr -d '\n')"
openssl pkcs12 -export \
  -out "${DELIVERY_DIR}/${CLIENT_NAME}.pfx" \
  -inkey "${TLS_DIR}/${CLIENT_NAME}.key" \
  -in "${TLS_DIR}/${CLIENT_NAME}.crt" \
  -certfile "${TLS_DIR}/ca.crt" \
  -passout "pass:${PFX_PASSWORD}"
cp "${TLS_DIR}/ca.crt" "${DELIVERY_DIR}/sierse-ca.crt"

FINGERPRINT="$(openssl x509 -in "${TLS_DIR}/${CLIENT_NAME}.crt" -noout -fingerprint -sha256 | cut -d= -f2)"
FINGERPRINT_COMPACT="$(printf '%s' "${FINGERPRINT}" | tr -d ':')"
EXPIRES_AT="$(openssl x509 -in "${TLS_DIR}/${CLIENT_NAME}.crt" -noout -enddate | cut -d= -f2-)"
HMAC_SECRET="$(openssl rand -base64 48 | tr '+/' '-_' | tr -d '=\n')"
KEY_ID="galvan-$(date -u +%Y%m%d)-$(openssl rand -hex 4)"
SECRET_EXPIRES_AT="$(date -u -d '+90 days' +%Y-%m-%dT%H:%M:%SZ)"

python3 - "${VHOST}" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text()
begin = "# BEGIN SIERSE MTLS"
end = "# END SIERSE MTLS"
block = '''# BEGIN SIERSE MTLS
SSLCACertificateFile /etc/ssl/sierse/ca.crt
SSLVerifyClient optional
SSLVerifyDepth 2
SSLOptions +StdEnvVars +ExportCertData
<Location "/api/integraciones/sierse/">
    SSLRequireSSL
    Require expr "%{SSL_CLIENT_VERIFY} == 'SUCCESS'"
</Location>
# END SIERSE MTLS
'''

if begin in text and end in text:
    before, rest = text.split(begin, 1)
    _, after = rest.split(end, 1)
    text = before + block + after.lstrip("\r\n")
else:
    marker = "</VirtualHost>"
    if marker not in text:
        raise SystemExit("No se encontro </VirtualHost> en el vhost")
    text = text.replace(marker, block + marker, 1)

path.write_text(text)
PY

chmod 0600 "${TLS_DIR}/ca.key" "${TLS_DIR}/${CLIENT_NAME}.key"
chmod 0644 "${TLS_DIR}/ca.crt" "${TLS_DIR}/${CLIENT_NAME}.crt"

apache2ctl configtest
systemctl reload apache2

if [[ ! -f "${APP_DIR}/.env" ]]; then
  echo "No existe ${APP_DIR}/.env." >&2
  exit 1
fi

cp -a "${APP_DIR}/.env" "${APP_DIR}/.env.bak.sierse.${STAMP}"
export SIERSE_ENV_FILE="${APP_DIR}/.env"
export SIERSE_HMAC_SECRET="${HMAC_SECRET}"
export SIERSE_KEY_ID_VALUE="${KEY_ID}"
export SIERSE_SECRET_EXPIRY="${SECRET_EXPIRES_AT}"
export SIERSE_CERT_FINGERPRINT="${FINGERPRINT_COMPACT}"
python3 <<'PY'
from pathlib import Path
import os

path = Path(os.environ['SIERSE_ENV_FILE'])
values = {
    'SIERSE_API_ENABLED': 'true',
    'SIERSE_API_DATA_EXCHANGE_ENABLED': 'true',
    'SIERSE_API_REQUIRE_MTLS': 'true',
    'SIERSE_API_CLIENT_ID': 'galvan',
    'SIERSE_API_CLIENT_NAME': '"Gruas Galvan"',
    'SIERSE_API_GRUA_NAME': 'GALVAN',
    'SIERSE_API_KEY_ID': os.environ['SIERSE_KEY_ID_VALUE'],
    'SIERSE_API_CLIENT_SECRET': os.environ['SIERSE_HMAC_SECRET'],
    'SIERSE_API_SECRET_EXPIRES_AT': os.environ['SIERSE_SECRET_EXPIRY'],
    # mTLS + HMAC siguen siendo obligatorios. Sustituir por IP fija cuando Galvan la informe.
    'SIERSE_API_ALLOWED_IPS': '0.0.0.0/0,::/0',
    'SIERSE_API_CERTIFICATE_FINGERPRINTS': os.environ['SIERSE_CERT_FINGERPRINT'],
    'SIERSE_API_CLOCK_SKEW_SECONDS': '300',
    'SIERSE_API_NONCE_TTL_SECONDS': '600',
}

lines = path.read_text().splitlines()
found = set()
output = []
for line in lines:
    key = line.split('=', 1)[0].strip() if '=' in line and not line.lstrip().startswith('#') else None
    if key in values:
        output.append(f"{key}={values[key]}")
        found.add(key)
    else:
        output.append(line)

if found != set(values):
    output.extend(['', '# Integracion privada SIERSE / Gruas Galvan'])
    for key, value in values.items():
        if key not in found:
            output.append(f"{key}={value}")

path.write_text('\n'.join(output) + '\n')
PY

chown nasaka:nasaka "${APP_DIR}/.env"
runuser -u nasaka -- bash -lc "cd '${APP_DIR}' && php artisan config:clear && php artisan config:cache"

cat > "${DELIVERY_DIR}/certificado-galvan.txt" <<EOF
Archivo PFX: ${CLIENT_NAME}.pfx
Contrasena PFX: ${PFX_PASSWORD}
Huella SHA-256: ${FINGERPRINT}
Vencimiento: ${EXPIRES_AT}
EOF

cat > "${DELIVERY_DIR}/api-galvan.txt" <<EOF
URL base: https://seguridadvial-mich.com/api/integraciones/sierse/v1
Client ID: galvan
Key ID: ${KEY_ID}
Secreto HMAC: ${HMAC_SECRET}
Vencimiento del secreto: ${SECRET_EXPIRES_AT}
Autenticacion: certificado mTLS + firma HMAC-SHA256
EOF

chown -R nasaka:nasaka "${DELIVERY_DIR}"
chmod 0600 "${DELIVERY_DIR}/${CLIENT_NAME}.pfx" "${DELIVERY_DIR}/certificado-galvan.txt" "${DELIVERY_DIR}/api-galvan.txt"
chmod 0644 "${DELIVERY_DIR}/sierse-ca.crt"

TIMESTAMP="$(date -u +%s)"
NONCE="$(openssl rand -hex 24)"
EMPTY_HASH="e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
CANONICAL="$(printf 'GET\n/api/integraciones/sierse/v1/estado\ngalvan\n%s\n%s\n%s\n%s' "${KEY_ID}" "${TIMESTAMP}" "${NONCE}" "${EMPTY_HASH}")"
SIGNATURE="$(printf '%s' "${CANONICAL}" | openssl dgst -sha256 -hmac "${HMAC_SECRET}" -binary | xxd -p -c 256)"

curl --silent --show-error --fail \
  --cert-type P12 \
  --cert "${DELIVERY_DIR}/${CLIENT_NAME}.pfx:${PFX_PASSWORD}" \
  -H "Accept: application/json" \
  -H "X-SIERSE-Client: galvan" \
  -H "X-SIERSE-Key-Id: ${KEY_ID}" \
  -H "X-SIERSE-Timestamp: ${TIMESTAMP}" \
  -H "X-SIERSE-Nonce: ${NONCE}" \
  -H "X-SIERSE-Signature: ${SIGNATURE}" \
  "https://seguridadvial-mich.com/api/integraciones/sierse/v1/estado" \
  > "${DELIVERY_DIR}/prueba-estado.json"

chown nasaka:nasaka "${DELIVERY_DIR}/prueba-estado.json"
chmod 0600 "${DELIVERY_DIR}/prueba-estado.json"

echo
echo "mTLS SIERSE instalado correctamente."
echo "Entrega preparada en ${DELIVERY_DIR}."
echo "Huella SHA-256: ${FINGERPRINT}"
echo "Prueba /estado: $(cat "${DELIVERY_DIR}/prueba-estado.json")"
