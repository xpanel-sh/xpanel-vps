#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == 0 ]] || { echo 'Se requiere root.' >&2; exit 1; }

base=/opt/xpanel-host
repository=https://github.com/xpanel-sh/xpanel-host.git
install -d -m 0755 "$base/releases"
exec 9>/run/lock/xpanel-host-release.lock
flock -w 1800 9 || { echo 'Hay otra preparación de Host en curso.' >&2; exit 1; }

staging="$(mktemp -d "$base/releases/.prepare.XXXXXXXX")"
cleanup() {
  [[ -n "${staging:-}" && "$staging" == "$base/releases/.prepare."* ]] && rm -rf -- "$staging"
}
trap cleanup EXIT

git clone --quiet --depth 1 --branch main "$repository" "$staging"
revision="$(git -C "$staging" rev-parse --short=12 HEAD)"
[[ "$revision" =~ ^[a-f0-9]{12}$ ]] || { echo 'Revisión de Host inválida.' >&2; exit 1; }
target="$base/releases/$revision"

if [[ ! -d "$target" ]]; then
  COMPOSER_ALLOW_SUPERUSER=1 composer --working-dir="$staging" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
  npm --prefix "$staging" ci --ignore-scripts --no-audit --no-fund
  npm --prefix "$staging" run build
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
