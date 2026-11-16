#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Ejecuta este script con sudo." >&2
  exit 1
fi

VHOST="/etc/apache2/sites-available/seguridadvial-mich.com-le-ssl.conf"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

cp -a "${VHOST}" "${VHOST}.bak.sierse-env.${STAMP}"

python3 - "${VHOST}" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text()
old = "SSLOptions +ExportCertData"
new = "SSLOptions +StdEnvVars +ExportCertData"

if new not in text:
    if old not in text:
        raise SystemExit("No se encontro la directiva SSLOptions de SIERSE")
    text = text.replace(old, new, 1)
    path.write_text(text)
PY

apache2ctl configtest
systemctl reload apache2
echo "Variables TLS exportadas a PHP correctamente."
