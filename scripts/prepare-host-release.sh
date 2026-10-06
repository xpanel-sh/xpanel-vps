#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == 0 ]] || { echo 'Se requiere root.' >&2; exit 1; }

base=/opt/xpanel-host
repository=https://github.com/xpanel-sh/xpanel-host.git
install -d -m 0755 "$base/releases"
exec 9>/run/lock/xpanel-host-release.lock
flock -w 1800 9 || { echo 'Hay otra preparación de Host en curso.' >&2; exit 1; }

install -d -o root -g root -m 0700 /var/log/xpanel-vps
log=/var/log/xpanel-vps/host-release-prepare.log
: > "$log"
chmod 0600 "$log"
run_step() {
  local stage="$1"
  shift
  printf '[%s] %s\n' "$(date -u +%FT%TZ)" "$stage" >> "$log"
  if ! "$@" >> "$log" 2>&1; then
    printf 'Falló la etapa: %s. Registro: %s\n' "$stage" "$log" >&2
    tail -n 35 "$log" >&2
    return 1
  fi
}

staging="$(mktemp -d "$base/releases/.prepare.XXXXXXXX")"
cleanup() {
  [[ -n "${staging:-}" && "$staging" == "$base/releases/.prepare."* ]] && rm -rf -- "$staging"
}
trap cleanup EXIT

run_step 'Descarga de XPanel Host' git clone --quiet --depth 1 --branch main "$repository" "$staging"
revision="$(git -C "$staging" rev-parse --short=12 HEAD)"
[[ "$revision" =~ ^[a-f0-9]{12}$ ]] || { echo 'Revisión de Host inválida.' >&2; exit 1; }
target="$base/releases/$revision"

if [[ ! -d "$target" ]]; then
  # Use the same PHP runtime as the initial installer. Composer's normal
  # Laravel package-discovery hook must populate the read-only release cache.
  php_version="$(sed -n 's/^XPANEL_HOST_PHP_VERSION=//p' /opt/xpanel-vps/.env | tail -n 1 | tr -d '"')"
  php_version="${php_version:-8.3}"
  [[ "$php_version" =~ ^8\.[3-9]$ ]] || { echo 'La versión PHP configurada para Host no es válida.' >&2; exit 1; }
  php_bin="/usr/bin/php$php_version"
  [[ -x "$php_bin" ]] || { echo "PHP $php_version no está disponible para preparar Host." >&2; exit 1; }
  run_step 'Dependencias PHP de Host' env COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MAX_PARALLEL_HTTP=4 "$php_bin" "$(command -v composer)" --working-dir="$staging" install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress
  run_step 'Dependencias JavaScript de Host' npm --prefix "$staging" ci --ignore-scripts --no-audit --no-fund --maxsockets=4
  run_step 'Compilación de Host' npm --prefix "$staging" run build
  chown -R root:root "$staging"
  chmod -R a+rX,go-w "$staging"
  mv -- "$staging" "$target"
  staging=''
fi

[[ -f "$target/artisan" && -f "$target/public/index.php" && -f "$target/vendor/autoload.php" && -f "$target/public/build/manifest.json" ]] || { echo 'La release preparada está incompleta.' >&2; exit 1; }
link="$base/.current.$revision"
ln -s "$target" "$link"
mv -Tf -- "$link" "$base/current"
printf '%s\n' "$revision"
