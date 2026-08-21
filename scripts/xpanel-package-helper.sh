#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

ACTION="${1:-}"
SLUG="${2:-}"

fail() { echo "$1" >&2; exit 1; }
[[ "$(id -u)" == "0" ]] || fail "xpanel-package-helper debe ejecutarse como root."
[[ "$ACTION" == "install" ]] || fail "Acción inválida."

case "$SLUG" in
  nginx)
    packages=(nginx)
    service=nginx
    ;;
  apache)
    packages=(apache2 libapache2-mod-fcgid)
    service=apache2
    ;;
  php81|php82|php83|php84)
    digits="${SLUG#php}"
    version="${digits:0:1}.${digits:1:1}"
    packages=("php$version-fpm" "php$version-cli" "php$version-mysql" "php$version-mbstring" "php$version-xml" "php$version-curl" "php$version-zip" "php$version-intl" "php$version-gd")
    service="php$version-fpm"
    ;;
  *) fail "Paquete no permitido." ;;
esac

apt-cache show "${packages[0]}" >/dev/null 2>&1 || fail "El paquete no existe en los repositorios APT configurados."
DEBIAN_FRONTEND=noninteractive apt-get install -y "${packages[@]}"

if [[ "$SLUG" == "apache" ]]; then
  a2enmod proxy proxy_fcgi setenvif rewrite >/dev/null
  a2dissite 000-default >/dev/null 2>&1 || true
  if grep -Eq '^[[:space:]]*Listen[[:space:]]+80[[:space:]]*$' /etc/apache2/ports.conf; then
    cp -n /etc/apache2/ports.conf /etc/apache2/ports.conf.xpanel-backup
    sed -i -E 's/^[[:space:]]*Listen[[:space:]]+80[[:space:]]*$/Listen 127.0.0.1:8082/' /etc/apache2/ports.conf
  elif ! grep -Eq '^[[:space:]]*Listen[[:space:]]+127\.0\.0\.1:8082[[:space:]]*$' /etc/apache2/ports.conf; then
    printf '\nListen 127.0.0.1:8082\n' >> /etc/apache2/ports.conf
  fi
  apache2ctl configtest
fi

systemctl enable --now "$service"
printf 'installed=%s\n' "$SLUG"
