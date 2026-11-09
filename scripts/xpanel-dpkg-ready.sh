#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == 0 ]] || { echo "xpanel-dpkg-ready: se requiere root." >&2; exit 1; }
command -v dpkg >/dev/null 2>&1 || { echo "xpanel-dpkg-ready: falta dpkg." >&2; exit 1; }

# A previous package installation may have been interrupted by a lost SSH
# session or a service restart. Finish its pending configuration before any
# apt operation. dpkg itself enforces its lock if another operation is active.
if ! DEBIAN_FRONTEND=noninteractive dpkg --configure -a; then
  echo "xpanel-dpkg-ready: dpkg no pudo terminar los paquetes pendientes. Revisa el error anterior; no se inició la actualización del panel." >&2
  exit 1
fi

if audit="$(dpkg --audit)" && [[ -z "$audit" ]]; then
  exit 0
fi

printf 'xpanel-dpkg-ready: quedan paquetes incompletos:\n%s\n' "$audit" >&2
exit 1
