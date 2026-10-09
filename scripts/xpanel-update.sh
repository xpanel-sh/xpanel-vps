#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[[ "$(id -u)" == "0" ]] || { echo "Ejecuta la actualización con sudo." >&2; exit 1; }
[[ -f "$ROOT/.env" ]] || { echo "Falta $ROOT/.env" >&2; exit 1; }

ensure_quota_tools() {
  local tool
  for tool in setquota quotaon tune2fs chattr lsattr; do
    if ! command -v "$tool" >/dev/null 2>&1; then
      printf 'Instalando herramientas de cuotas necesarias para las instancias Host...\n'
      apt-get update -y
      DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends quota e2fsprogs
      break
    fi
  done
  for tool in setquota quotaon tune2fs chattr lsattr; do
    command -v "$tool" >/dev/null 2>&1 || { echo "Falta $tool después de instalar quota y e2fsprogs." >&2; return 1; }
  done
}

# Existing VPS installations may predate project quotas. Repair these small
# system dependencies before putting the panel into maintenance mode.
ensure_quota_tools

backup_root="$ROOT/storage/app/backups/updates/$(date -u +%Y%m%dT%H%M%SZ)"
install -d -o www-data -g www-data -m 0700 "$backup_root"
install -o www-data -g www-data -m 0600 "$ROOT/.env" "$backup_root/.env"

sudo -u www-data php "$ROOT/artisan" down --retry=30 || true
restore_panel() { sudo -u www-data php "$ROOT/artisan" up >/dev/null 2>&1 || true; }
trap restore_panel EXIT

composer --working-dir="$ROOT" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm --prefix "$ROOT" install --ignore-scripts --no-audit --no-fund
npm --prefix "$ROOT" run build
chown -R www-data:www-data "$ROOT/storage" "$ROOT/bootstrap/cache"
sudo -u www-data php "$ROOT/artisan" migrate --force
sudo -u www-data php "$ROOT/artisan" db:seed --class=DefaultDataSeeder --force
sudo -u www-data php "$ROOT/artisan" optimize

XPANEL_SKIP_PACKAGES=true XPANEL_INSTALL_CLI=no XPANEL_PRESERVE_HOST_RELEASE=true bash "$ROOT/install.sh"
printf 'Verificando y reconciliando instancias Host existentes...\n'
sudo -u www-data php "$ROOT/artisan" xpanel:system-reconcile --repair --no-interaction
sudo -u www-data php "$ROOT/artisan" up
trap - EXIT

echo "XPanel VPS actualizado. Respaldo previo: $backup_root"
