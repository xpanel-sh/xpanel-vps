#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ACTION="${1:-}"

fail() { echo "$1" >&2; exit 1; }
valid_domain() { [[ "$1" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ && "$1" == *.* && "$1" != *..* ]]; }
valid_document_root() { [[ "$1" =~ ^/(var|srv)/www/[A-Za-z0-9._/-]+$ && "$1" != *".."* ]]; }
valid_site_user() { [[ "$1" =~ ^xps[a-z0-9]{9,29}$ ]]; }
valid_identifier() { [[ "$1" =~ ^[a-z0-9_]{3,32}$ ]]; }

database_action() {
  local database="${2:-}" username="${3:-}" password="" privileges="${4:-}"
  valid_identifier "$database" || fail "Nombre de base de datos invalido."
  valid_identifier "$username" || fail "Usuario de base de datos invalido."

  case "$ACTION" in
    database-remove)
      mariadb --protocol=socket -e "DROP DATABASE IF EXISTS \`$database\`; DROP USER IF EXISTS '$username'@'localhost';"
      ;;
    database-user-remove)
      mariadb --protocol=socket -e "DROP USER IF EXISTS '$username'@'localhost';"
      ;;
    database-create|database-user-add|database-user-password)
      IFS= read -r password || true
      [[ "$password" =~ ^[A-Za-z0-9!@#%\^*_=+.,:-]{8,128}$ ]] || fail "La contraseña contiene caracteres no admitidos."
      if [[ "$ACTION" == "database-create" ]]; then
        [[ "$(mariadb --protocol=socket --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$database'")" == "0" ]] || fail "La base de datos ya existe."
        [[ "$(mariadb --protocol=socket --batch --skip-column-names -e "SELECT COUNT(*) FROM mysql.user WHERE User='$username' AND Host='localhost'")" == "0" ]] || fail "El usuario de base de datos ya existe."
        cleanup_failed_database() {
          trap - ERR
          mariadb --protocol=socket -e "DROP DATABASE IF EXISTS \`$database\`; DROP USER IF EXISTS '$username'@'localhost';"
        }
        trap cleanup_failed_database ERR
        mariadb --protocol=socket <<SQL
CREATE DATABASE \`$database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$username'@'localhost' IDENTIFIED BY '$password';
GRANT ALL PRIVILEGES ON \`$database\`.* TO '$username'@'localhost';
SQL
        trap - ERR
      elif [[ "$ACTION" == "database-user-add" ]]; then
        mariadb --protocol=socket <<SQL
CREATE USER '$username'@'localhost' IDENTIFIED BY '$password';
GRANT ALL PRIVILEGES ON \`$database\`.* TO '$username'@'localhost';
SQL
      else
        mariadb --protocol=socket -e "ALTER USER '$username'@'localhost' IDENTIFIED BY '$password';"
      fi
      ;;
    database-user-permissions)
      local grant="" privilege=""
      IFS=',' read -ra requested <<< "$privileges"
      for privilege in "${requested[@]}"; do
        case "$privilege" in
          SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|INDEX|ALTER|REFERENCES|"ALL PRIVILEGES") ;;
          *) fail "Privilegio de base de datos invalido." ;;
        esac
      done
      mariadb --protocol=socket -e "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$username'@'localhost';"
      if (( ${#requested[@]} > 0 )); then
        grant="$(IFS=','; echo "${requested[*]}")"
        mariadb --protocol=socket -e "GRANT $grant ON \`$database\`.* TO '$username'@'localhost';"
      fi
      ;;
  esac
}

[[ "$(id -u)" == "0" ]] || fail "xpanel-site-helper debe ejecutarse como root."
case "$ACTION" in
  database-create|database-remove|database-user-add|database-user-remove|database-user-password|database-user-permissions)
    database_action "$@"
    exit 0
    ;;
  apply|remove|restart|ssl-issue|ssl-delete) ;;
  *) fail "Acción inválida." ;;
esac

DOMAIN="${2:-}"
ENGINE="${3:-}"
TYPE="${4:-}"
PHP_VERSION="${5:-}"
DOCUMENT_ROOT="${6:-}"
SITE_USER="${7:-}"

valid_domain "$DOMAIN" || fail "Dominio inválido."
[[ "$ENGINE" == "nginx" || "$ENGINE" == "apache" ]] || fail "Motor web inválido."
[[ "$TYPE" == "php" || "$TYPE" == "static" ]] || fail "Tipo de sitio inválido."
[[ "$PHP_VERSION" =~ ^8\.[1-4]$ ]] || fail "Versión PHP inválida."
valid_document_root "$DOCUMENT_ROOT" || fail "El document root debe estar bajo /var/www o /srv/www."
valid_site_user "$SITE_USER" || fail "Identidad Unix inválida."

if [[ "$ACTION" == "ssl-issue" ]]; then
  EMAIL="${8:-}"
  [[ "$EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "Correo ACME invalido."
  install -d -o "$SITE_USER" -g "$SITE_USER" -m 0770 "$DOCUMENT_ROOT/.well-known/acme-challenge"
  certbot certonly --non-interactive --agree-tos --no-eff-email --expand \
    --webroot -w "$DOCUMENT_ROOT" --cert-name "$DOMAIN" -d "$DOMAIN" -d "www.$DOMAIN" -m "$EMAIL"
  CERTIFICATE="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"
  [[ -f "$CERTIFICATE" ]] || fail "Certbot no creo el certificado esperado."
  printf 'not_after=%s\n' "$(date -u -d "$(openssl x509 -enddate -noout -in "$CERTIFICATE" | cut -d= -f2-)" +%Y-%m-%dT%H:%M:%SZ)"
  printf 'issuer=%s\n' "$(openssl x509 -issuer -noout -in "$CERTIFICATE" | sed 's/^issuer=//')"
  exit 0
fi

if [[ "$ACTION" == "ssl-delete" ]]; then
  certbot delete --non-interactive --cert-name "$DOMAIN" >/dev/null 2>&1 || true
  exit 0
fi

NGINX_SOURCE="$ROOT/storage/app/native/nginx/$DOMAIN.conf"
APACHE_SOURCE="$ROOT/storage/app/native/apache/$DOMAIN.conf"
POOL_SOURCE="$ROOT/storage/app/native/php-fpm/$DOMAIN.conf"
NGINX_TARGET="/etc/nginx/sites-available/xpanel-$DOMAIN.conf"
APACHE_TARGET="/etc/apache2/sites-available/xpanel-$DOMAIN.conf"

reload_services() {
  nginx -t
  systemctl reload nginx

  if [[ "$ENGINE" == "apache" ]]; then
    apache2ctl configtest
    systemctl reload apache2
  fi

  if [[ "$TYPE" == "php" ]]; then
    "php-fpm$PHP_VERSION" -t
    if command -v systemd-run >/dev/null 2>&1; then
      systemd-run --quiet --collect --on-active=2s systemctl reload "php$PHP_VERSION-fpm"
    else
      systemctl reload "php$PHP_VERSION-fpm"
    fi
  fi
}

remove_runtime_configs() {
  rm -f "/etc/nginx/sites-enabled/xpanel-$DOMAIN.conf" "$NGINX_TARGET"
  a2dissite "xpanel-$DOMAIN.conf" >/dev/null 2>&1 || true
  rm -f "$APACHE_TARGET"

  local version
  for version in 8.1 8.2 8.3 8.4; do
    rm -f "/etc/php/$version/fpm/pool.d/xpanel-$DOMAIN.conf"
  done
}

ensure_site_identity() {
  getent group "$SITE_USER" >/dev/null || groupadd --system "$SITE_USER"

  if ! id "$SITE_USER" >/dev/null 2>&1; then
    useradd --system --gid "$SITE_USER" --home-dir "$DOCUMENT_ROOT" --shell /usr/sbin/nologin "$SITE_USER"
  fi

  [[ "$(id -gn "$SITE_USER")" == "$SITE_USER" ]] || fail "El usuario del sitio tiene un grupo primario inesperado."
  usermod -a -G "$SITE_USER" www-data

  install -d -o root -g root -m 0755 "$(dirname "$(dirname "$DOCUMENT_ROOT")")"
  install -d -o root -g root -m 0751 "$(dirname "$DOCUMENT_ROOT")"
  install -d -o "$SITE_USER" -g "$SITE_USER" -m 0770 "$DOCUMENT_ROOT"
  chown -R --no-dereference "$SITE_USER:$SITE_USER" "$DOCUMENT_ROOT"
  chmod -R g+rwX,o-rwx "$DOCUMENT_ROOT"
}

if [[ "$ACTION" == "remove" ]]; then
  remove_runtime_configs
  nginx -t
  systemctl reload nginx
  if command -v apache2ctl >/dev/null 2>&1; then
    apache2ctl configtest
    systemctl reload apache2
  fi
  exit 0
fi

if [[ "$ACTION" == "restart" ]]; then
  reload_services
  exit 0
fi

[[ -f "$NGINX_SOURCE" && ! -L "$NGINX_SOURCE" ]] || fail "No existe la configuración Nginx preparada."
if [[ "$ENGINE" == "apache" ]]; then
  [[ -f "$APACHE_SOURCE" && ! -L "$APACHE_SOURCE" ]] || fail "No existe la configuración Apache preparada."
fi
if [[ "$TYPE" == "php" ]]; then
  [[ -f "$POOL_SOURCE" && ! -L "$POOL_SOURCE" ]] || fail "No existe el pool PHP-FPM preparado."
fi

ensure_site_identity
remove_runtime_configs

if [[ "$TYPE" == "php" ]]; then
  install -o root -g root -m 0644 "$POOL_SOURCE" "/etc/php/$PHP_VERSION/fpm/pool.d/xpanel-$DOMAIN.conf"
  if [[ ! -e "$DOCUMENT_ROOT/index.php" && ! -e "$DOCUMENT_ROOT/index.html" ]]; then
    printf '%s\n' '<?php echo "XPanel VPS: sitio listo"; ?>' > "$DOCUMENT_ROOT/index.php"
    chown "$SITE_USER:$SITE_USER" "$DOCUMENT_ROOT/index.php"
  fi
elif [[ ! -e "$DOCUMENT_ROOT/index.html" ]]; then
  printf '%s\n' '<!doctype html><html lang="es"><meta charset="utf-8"><title>Sitio listo</title><h1>XPanel VPS: sitio listo</h1></html>' > "$DOCUMENT_ROOT/index.html"
  chown "$SITE_USER:$SITE_USER" "$DOCUMENT_ROOT/index.html"
fi

install -o root -g root -m 0644 "$NGINX_SOURCE" "$NGINX_TARGET"
ln -sfn "$NGINX_TARGET" "/etc/nginx/sites-enabled/xpanel-$DOMAIN.conf"

if [[ "$ENGINE" == "apache" ]]; then
  install -o root -g root -m 0644 "$APACHE_SOURCE" "$APACHE_TARGET"
  a2ensite "xpanel-$DOMAIN.conf" >/dev/null
fi

reload_services
