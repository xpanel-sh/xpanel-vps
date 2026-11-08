#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Ignore a Host instance runtime accidentally sourced by an administrator.
# Otherwise VPS Artisan can inherit the tenant SQLite and cache locations.
unset APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL
unset APP_CONFIG_CACHE APP_EVENTS_CACHE APP_PACKAGES_CACHE APP_ROUTES_CACHE APP_SERVICES_CACHE
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
unset SESSION_DRIVER CACHE_STORE QUEUE_CONNECTION
unset XPANEL_MANAGEMENT_MODE XPANEL_INSTANCE_ID XPANEL_INSTANCE_ROOT XPANEL_PROJECT_ID
unset XPANEL_BROKER_URL XPANEL_BROKER_SECRET XPANEL_BROKER_CA XPANEL_BROKER_CA_HOST
unset XPANEL_CONTROL_PLANE_URL XPANEL_PANEL_DOMAIN XPANEL_PANEL_ACCESS_MODE XPANEL_PANEL_PORT
[[ "$(id -u)" == "0" ]] || { echo "Ejecuta la actualización con sudo." >&2; exit 1; }
[[ -f "$ROOT/.env" ]] || { echo "Falta $ROOT/.env" >&2; exit 1; }

# A checkout may restore Git's portable 0644 mode before install.sh runs.
# These helpers are invoked directly through sudo, so repair ownership and
# execution permissions at the very beginning of every update.
for helper in xpanel-site-helper.sh xpanel-instance-helper.sh xpanel-host-broker-helper.sh xpanel-control-plane-helper.sh; do
  [[ -f "$ROOT/scripts/$helper" && ! -L "$ROOT/scripts/$helper" ]] || { echo "Falta el helper $helper." >&2; exit 1; }
  chown root:www-data "$ROOT/scripts/$helper"
  chmod 0750 "$ROOT/scripts/$helper"
done

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
bash "$ROOT/scripts/configure-managed-mail.sh" --packages-only
if ! bash "$ROOT/scripts/xpanel-project-quota.sh" status >/dev/null 2>&1; then
  if ! bash "$ROOT/scripts/xpanel-quota-setup.sh" prepare; then
    echo "AVISO: no se pudieron activar las cuotas. La actualización del panel continuará, pero el aprovisionamiento Host quedará bloqueado." >&2
  fi
fi

backup_root="$ROOT/storage/app/backups/updates/$(date -u +%Y%m%dT%H%M%SZ)"
install -d -o www-data -g www-data -m 0700 "$backup_root"
install -o www-data -g www-data -m 0600 "$ROOT/.env" "$backup_root/.env"

sudo -u www-data php "$ROOT/artisan" down --retry=30 || true
restore_panel() { sudo -u www-data php "$ROOT/artisan" up >/dev/null 2>&1 || true; }
trap restore_panel EXIT

composer --working-dir="$ROOT" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm --prefix "$ROOT" ci --ignore-scripts --no-audit --no-fund
npm --prefix "$ROOT" run build
chown -R www-data:www-data "$ROOT/storage" "$ROOT/bootstrap/cache"
sudo -u www-data php "$ROOT/artisan" migrate --force
sudo -u www-data php "$ROOT/artisan" db:seed --class=DefaultDataSeeder --force
sudo -u www-data php "$ROOT/artisan" optimize

XPANEL_SKIP_PACKAGES=true XPANEL_INSTALL_CLI=no XPANEL_PRESERVE_HOST_RELEASE=true bash "$ROOT/install.sh"
if bash "$ROOT/scripts/xpanel-project-quota.sh" status >/dev/null 2>&1; then
  printf 'Verificando y reconciliando instancias Host existentes...\n'
  sudo -u www-data php "$ROOT/artisan" xpanel:system-reconcile --repair --no-interaction
else
  echo "AVISO: cuotas inactivas. Se omitió la reconciliación de Host; no se crearán ni modificarán cuentas hasta verificar las cuotas." >&2
fi
sudo -u www-data php "$ROOT/artisan" up
trap - EXIT

echo "XPanel VPS actualizado. Respaldo previo: $backup_root"
