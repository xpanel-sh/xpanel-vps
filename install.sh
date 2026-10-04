#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CLI_DIR="$(realpath -m "${XPANEL_CLI_DIR:-/opt/xpanel-cli}")"
REPO_CLI="${XPANEL_CLI_REPO:-github.com/xpanel-sh/xpanel-cli}"
SAVED_PANEL_DOMAIN="$(grep -E '^XPANEL_PANEL_DOMAIN=' "$ROOT/.env" 2>/dev/null | tail -n1 | cut -d= -f2- | tr -d '"' || true)"
SAVED_PANEL_PORT="$(grep -E '^XPANEL_PANEL_PORT=' "$ROOT/.env" 2>/dev/null | tail -n1 | cut -d= -f2- | tr -d '"' || true)"
PANEL_DOMAIN="${XPANEL_PANEL_DOMAIN:-$SAVED_PANEL_DOMAIN}"
PANEL_PORT="${XPANEL_PANEL_PORT:-${SAVED_PANEL_PORT:-8443}}"
[[ "$PANEL_PORT" == "80" ]] && PANEL_PORT=8443
INSTALL_APACHE="${XPANEL_INSTALL_APACHE:-true}"
SKIP_PACKAGES="${XPANEL_SKIP_PACKAGES:-false}"

source "$ROOT/scripts/xpanel-system.sh"

fail() { echo "$1" >&2; exit 1; }

[[ "$(id -u)" == "0" ]] || fail "Ejecuta el instalador con root: sudo bash install.sh"
[[ -f /etc/os-release ]] || fail "No se pudo identificar el sistema operativo."
source /etc/os-release
[[ "${ID:-}" == "ubuntu" || "${ID:-}" == "debian" ]] || fail "XPanel VPS soporta inicialmente Ubuntu y Debian."
command -v apt-get >/dev/null 2>&1 || fail "apt-get es obligatorio."
[[ -f /sys/fs/cgroup/cgroup.controllers ]] || fail "XPanel VPS requiere systemd con cgroups v2 para aislar los recursos de las instancias."
[[ "$CLI_DIR" =~ ^/[^/]+/[^/]+ ]] || fail "XPANEL_CLI_DIR no es una ruta segura."
[[ "$(basename "$CLI_DIR")" == "xpanel-cli" && "$CLI_DIR" != "$ROOT" ]] || fail "XPANEL_CLI_DIR no es válido."
[[ "$PANEL_PORT" =~ ^[0-9]{2,5}$ ]] && (( PANEL_PORT >= 1024 && PANEL_PORT <= 65535 )) || fail "XPANEL_PANEL_PORT debe estar entre 1024 y 65535."

PANEL_DOMAIN="${PANEL_DOMAIN,,}"
PANEL_DOMAIN="${PANEL_DOMAIN%.}"
if [[ -n "$PANEL_DOMAIN" && ! "$PANEL_DOMAIN" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]]; then
  fail "XPANEL_PANEL_DOMAIN debe ser un hostname sin esquema, puerto ni ruta."
fi

set_env_var() {
  local key="$1" value="$2"
  if grep -q "^${key}=" "$ROOT/.env" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ROOT/.env"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ROOT/.env"
  fi
}

env_value() {
  local key="$1"
  grep -E "^${key}=" "$ROOT/.env" 2>/dev/null | tail -n1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/' || true
}

is_ipv4() {
  local candidate="$1" octet
  [[ "$candidate" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]] || return 1
  for octet in ${candidate//./ }; do
    (( 10#$octet <= 255 )) || return 1
  done
}

detect_server_ip() {
  local candidate
  candidate="${XPANEL_SERVER_IP:-$(env_value XPANEL_SERVER_IP)}"
  if is_ipv4 "$candidate"; then
    printf '%s' "$candidate"
    return
  fi

  candidate="$(curl -4 -fsS --max-time 5 https://api.ipify.org 2>/dev/null || true)"
  if is_ipv4 "$candidate"; then
    printf '%s' "$candidate"
    return
  fi

  candidate="$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{for (i=1; i<=NF; i++) if ($i == "src") {print $(i+1); exit}}')"
  is_ipv4 "$candidate" || candidate="127.0.0.1"
  printf '%s' "$candidate"
}

write_marker() {
  cat > "$ROOT/xpanel" <<EOF
SYSTEM="$SYSTEM"
REPO="$REPO"
VERSION="$VERSION"
XPANEL_FILE_LANGUAGE="${XPANEL_LANG:-$XPANEL_FILE_LANGUAGE}"
EOF
}

install_packages() {
  [[ "$SKIP_PACKAGES" != "true" ]] || return 0

  apt-get update -y
  DEBIAN_FRONTEND=noninteractive apt-get install -y \
    ca-certificates curl git unzip zip xz-utils sudo openssl acl rsync cron certbot python3-certbot-dns-cloudflare ufw \
    nginx mariadb-server composer nodejs npm \
    php-cli php-fpm php-mysql php-sqlite3 php-mbstring php-xml php-curl php-zip php-intl php-gd

  if [[ "$INSTALL_APACHE" == "true" ]]; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y apache2 libapache2-mod-fcgid
  fi

  local requested version package
  requested="${XPANEL_PHP_VERSIONS:-8.2,8.3,8.4}"
  IFS=',' read -ra versions <<< "$requested"
  for version in "${versions[@]}"; do
    version="${version//[[:space:]]/}"
    [[ "$version" =~ ^8\.[1-4]$ ]] || continue
    package="php${version}-fpm"
    if apt-cache show "$package" >/dev/null 2>&1; then
      DEBIAN_FRONTEND=noninteractive apt-get install -y \
        "$package" "php${version}-cli" "php${version}-mysql" "php${version}-mbstring" \
        "php${version}-xml" "php${version}-curl" "php${version}-zip" "php${version}-intl" "php${version}-gd"
    fi
  done
}

configure_firewall() {
  command -v ufw >/dev/null 2>&1 || return 0
  ufw allow 22/tcp >/dev/null
  ufw allow 80/tcp >/dev/null
  ufw allow 443/tcp >/dev/null
  ufw allow "$PANEL_PORT/tcp" >/dev/null
  ufw allow "${XPANEL_HOST_PORT_START:-10000}:${XPANEL_HOST_PORT_END:-19999}/tcp" >/dev/null
  ufw --force enable >/dev/null
}

ensure_node_runtime() {
  local current_major=0 machine node_arch tempdir manifest archive release
  if command -v node >/dev/null 2>&1; then
    current_major="$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || printf '0')"
  fi
  if [[ "$current_major" == "22" && -x /usr/local/bin/npm ]]; then return; fi

  machine="$(uname -m)"
  case "$machine" in
    x86_64|amd64) node_arch="x64" ;;
    aarch64|arm64) node_arch="arm64" ;;
    *) fail "Arquitectura no compatible con Node.js: $machine" ;;
  esac
  tempdir="$(mktemp -d)"
  manifest="$tempdir/SHASUMS256.txt"
  curl --fail --location --silent --show-error https://nodejs.org/dist/latest-v22.x/SHASUMS256.txt -o "$manifest"
  archive="$(awk -v suffix="linux-${node_arch}.tar.xz" '$2 ~ suffix"$" {print $2; exit}' "$manifest")"
  [[ "$archive" =~ ^node-v[0-9]+\.[0-9]+\.[0-9]+-linux-(x64|arm64)\.tar\.xz$ ]] || fail "No se pudo resolver Node.js 22 LTS."
  curl --fail --location --silent --show-error "https://nodejs.org/dist/latest-v22.x/$archive" -o "$tempdir/$archive"
  (cd "$tempdir" && grep -F " $archive" SHASUMS256.txt | sha256sum -c -)
  release="${archive%.tar.xz}"
  install -d /usr/local/lib/nodejs
  tar -xJf "$tempdir/$archive" -C /usr/local/lib/nodejs
  for binary in node npm npx corepack; do
    ln -sfn "/usr/local/lib/nodejs/$release/bin/$binary" "/usr/local/bin/$binary"
  done
  rm -rf -- "$tempdir"
}

detect_php_versions() {
  local version installed=()
  for version in 8.1 8.2 8.3 8.4; do
    if [[ -x "/usr/sbin/php-fpm${version}" || -f "/lib/systemd/system/php${version}-fpm.service" ]]; then
      installed+=("$version")
      systemctl enable --now "php${version}-fpm"
    fi
  done
  if (( ${#installed[@]} == 0 )); then
    version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    installed+=("$version")
    systemctl enable --now "php${version}-fpm"
  fi
  local joined
  joined="$(IFS=,; echo "${installed[*]}")"
  set_env_var XPANEL_PHP_VERSIONS "$joined"
}

configure_apache() {
  if [[ "$INSTALL_APACHE" != "true" ]]; then
    set_env_var XPANEL_WEB_SERVERS nginx
    return
  fi

  a2enmod proxy proxy_fcgi setenvif rewrite >/dev/null
  a2dissite 000-default >/dev/null 2>&1 || true
  if grep -Eq '^[[:space:]]*Listen[[:space:]]+80[[:space:]]*$' /etc/apache2/ports.conf; then
    cp -n /etc/apache2/ports.conf /etc/apache2/ports.conf.xpanel-backup
    sed -i -E 's/^[[:space:]]*Listen[[:space:]]+80[[:space:]]*$/Listen 127.0.0.1:8082/' /etc/apache2/ports.conf
  elif ! grep -Eq '^[[:space:]]*Listen[[:space:]]+127\.0\.0\.1:8082[[:space:]]*$' /etc/apache2/ports.conf; then
    printf '\nListen 127.0.0.1:8082\n' >> /etc/apache2/ports.conf
  fi
  apache2ctl configtest
  systemctl enable --now apache2
  set_env_var XPANEL_WEB_SERVERS nginx,apache
}

configure_database() {
  systemctl enable --now mariadb
  local database="${XPANEL_DB_DATABASE:-xpanel}"
  local username="${XPANEL_DB_USERNAME:-xpanel}"
  local password
  password="$(env_value DB_PASSWORD)"
  [[ -n "$password" ]] || password="$(openssl rand -hex 24)"
  [[ "$database" =~ ^[a-zA-Z0-9_]+$ && "$username" =~ ^[a-zA-Z0-9_]+$ ]] || fail "Nombre de base de datos o usuario inválido."
  [[ "$password" =~ ^[a-f0-9]{48}$ ]] || fail "La contraseña DB existente no tiene el formato seguro esperado."

  mariadb --protocol=socket <<SQL
CREATE DATABASE IF NOT EXISTS \`$database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$username'@'127.0.0.1' IDENTIFIED BY '$password';
ALTER USER '$username'@'127.0.0.1' IDENTIFIED BY '$password';
GRANT ALL PRIVILEGES ON \`$database\`.* TO '$username'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

  set_env_var DB_CONNECTION mysql
  set_env_var DB_HOST 127.0.0.1
  set_env_var DB_PORT 3306
  set_env_var DB_DATABASE "$database"
  set_env_var DB_USERNAME "$username"
  set_env_var DB_PASSWORD "$password"
}

configure_panel() {
  local php_version app_url server_ip
  php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
  server_ip="$(detect_server_ip)"
  set_env_var XPANEL_SERVER_IP "$server_ip"
  configure_fallback_tls
  cat > /etc/nginx/sites-available/xpanel-vps-panel.conf <<EOF
server {
    listen $PANEL_PORT ssl;
    listen [::]:$PANEL_PORT ssl;
    ssl_certificate /etc/xpanel/tls/fallback.crt;
    ssl_certificate_key /etc/xpanel/tls/fallback.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    server_name _;
    root $ROOT/public;
    index index.php index.html;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php${php_version}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 300s;
    }
    location ~ /\. { deny all; }
}
EOF
  if [[ -n "$PANEL_DOMAIN" ]]; then
    local panel_certificate panel_certificate_key
    if [[ -s "/etc/letsencrypt/live/$PANEL_DOMAIN/fullchain.pem" && -s "/etc/letsencrypt/live/$PANEL_DOMAIN/privkey.pem" ]]; then
      panel_certificate="/etc/letsencrypt/live/$PANEL_DOMAIN/fullchain.pem"
      panel_certificate_key="/etc/letsencrypt/live/$PANEL_DOMAIN/privkey.pem"
    else
      panel_certificate="/etc/xpanel/tls/fallback.crt"
      panel_certificate_key="/etc/xpanel/tls/fallback.key"
    fi
    cat >> /etc/nginx/sites-available/xpanel-vps-panel.conf <<EOF

server {
    listen 80;
    listen [::]:80;
    server_name $PANEL_DOMAIN;
    root $ROOT/public;
    location ^~ /.well-known/acme-challenge/ { try_files \$uri =404; }
    location / { return 301 https://\$host\$request_uri; }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name $PANEL_DOMAIN;
    ssl_certificate $panel_certificate;
    ssl_certificate_key $panel_certificate_key;
    ssl_protocols TLSv1.2 TLSv1.3;
    root $ROOT/public;
    index index.php index.html;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php${php_version}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 300s;
    }
    location ~ /\. { deny all; }
}
EOF
  fi
  ln -sfn /etc/nginx/sites-available/xpanel-vps-panel.conf /etc/nginx/sites-enabled/xpanel-vps-panel.conf
  rm -f /etc/nginx/sites-enabled/default
  nginx -t
  systemctl enable --now nginx
  systemctl reload nginx

  if [[ -n "$PANEL_DOMAIN" ]]; then
    app_url="https://$PANEL_DOMAIN"
  else
    app_url="https://${server_ip:-127.0.0.1}:$PANEL_PORT"
  fi
  set_env_var APP_URL "$app_url"
  set_env_var SESSION_SECURE_COOKIE true
  local cloud_domain="${XPANEL_CLOUD_DOMAIN:-$PANEL_DOMAIN}"
  if [[ -z "$cloud_domain" ]]; then
    cloud_domain="cloud.${server_ip:-127.0.0.1}.sslip.io"
  fi
  set_env_var XPANEL_PANEL_DOMAIN "$PANEL_DOMAIN"
  set_env_var XPANEL_CLOUD_DOMAIN "$cloud_domain"
  set_env_var XPANEL_CONTROL_PLANE_URL "$app_url"
  set_env_var XPANEL_BROKER_URL "$app_url/api/internal/host-broker"
  set_env_var XPANEL_PANEL_PORT "$PANEL_PORT"
  set_env_var XPANEL_SERVER_IP "${server_ip:-127.0.0.1}"
}

configure_fallback_tls() {
  local tls_dir="/etc/xpanel/tls" server_ip
  server_ip="$(env_value XPANEL_SERVER_IP)"
  install -d -m 0755 "$tls_dir" /var/lib/letsencrypt/.well-known/acme-challenge
  if [[ ! -s "$tls_dir/fallback.crt" || ! -s "$tls_dir/fallback.key" ]]; then
    openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
      -keyout "$tls_dir/fallback.key" -out "$tls_dir/fallback.crt" \
      -subj "/CN=${server_ip:-XPanel-fallback}" \
      -addext "subjectAltName=IP:${server_ip:-127.0.0.1}" >/dev/null 2>&1
    chmod 0600 "$tls_dir/fallback.key"
    chmod 0644 "$tls_dir/fallback.crt"
  fi
  set_env_var XPANEL_FALLBACK_CERTIFICATE "$tls_dir/fallback.crt"
  set_env_var XPANEL_FALLBACK_CERTIFICATE_KEY "$tls_dir/fallback.key"
}

configure_scheduler() {
  cat > /etc/cron.d/xpanel-vps <<EOF
* * * * * www-data cd "$ROOT" && /usr/bin/php artisan schedule:run --no-interaction >/dev/null 2>&1
EOF
  chmod 0644 /etc/cron.d/xpanel-vps
  systemctl enable --now cron
}

configure_helper() {
  local helper="$ROOT/scripts/xpanel-site-helper.sh"
  local package_helper="$ROOT/scripts/xpanel-package-helper.sh"
  local instance_helper="$ROOT/scripts/xpanel-instance-helper.sh"
  local broker_helper="$ROOT/scripts/xpanel-host-broker-helper.sh"
  local control_plane_helper="$ROOT/scripts/xpanel-control-plane-helper.sh"
  local sudoers_file="/etc/sudoers.d/xpanel-vps-site"
  chmod 0750 "$helper"
  chmod 0750 "$package_helper"
  chmod 0750 "$instance_helper"
  chmod 0750 "$broker_helper"
  chmod 0750 "$control_plane_helper"
  chown root:www-data "$helper"
  chown root:www-data "$package_helper"
  chown root:www-data "$instance_helper"
  chown root:www-data "$broker_helper"
  chown root:www-data "$control_plane_helper"
  {
    printf 'www-data ALL=(root) NOPASSWD: %s *\n' "$helper"
    printf 'www-data ALL=(root) NOPASSWD: %s install *\n' "$package_helper"
    printf 'www-data ALL=(root) NOPASSWD: %s *\n' "$instance_helper"
    printf 'www-data ALL=(root) NOPASSWD: %s execute *\n' "$broker_helper"
    printf 'www-data ALL=(root) NOPASSWD: %s set-domain *\n' "$control_plane_helper"
  } > "$sudoers_file"
  chmod 0440 "$sudoers_file"
  visudo -cf "$sudoers_file" >/dev/null
  install -d -o www-data -g www-data -m 0750 "$ROOT/storage/app/native"
  install -d -o www-data -g www-data -m 0700 "$ROOT/storage/app/native/host-instances"
  install -d -o root -g root -m 0755 /var/www/xpanel
  set_env_var XPANEL_SITE_HELPER "$helper"
  set_env_var XPANEL_PACKAGE_HELPER "$package_helper"
  set_env_var XPANEL_INSTANCE_HELPER "$instance_helper"
  set_env_var XPANEL_BROKER_HELPER "$broker_helper"
  set_env_var XPANEL_CONTROL_PLANE_HELPER "$control_plane_helper"
  set_env_var XPANEL_APPLY_SYSTEM_CHANGES true
  set_env_var XPANEL_NATIVE_HOSTING true
}

install_host_release() {
  local host_base="/opt/xpanel-host"
  local source="${XPANEL_HOST_SOURCE:-}"
  local repository="${XPANEL_HOST_REPO:-https://github.com/xpanel-sh/xpanel-host.git}"
  local revision="${XPANEL_HOST_REVISION:-main}"
  local release_id target php_bin

  if [[ -z "$source" && -d "$ROOT/../xpanel-host/.git" ]]; then
    source="$(realpath "$ROOT/../xpanel-host")"
  fi

  if [[ -n "$source" ]]; then
    [[ -d "$source/.git" ]] || fail "XPANEL_HOST_SOURCE debe apuntar al repositorio de xpanel-host."
    release_id="$(git -C "$source" rev-parse --short=12 HEAD)"
  else
    release_id="${revision//[^A-Za-z0-9._-]/-}"
  fi

  target="$host_base/releases/$release_id"
  install -d -m 0755 "$host_base/releases"
  if [[ ! -d "$target/.git" ]]; then
    if [[ -n "$source" ]]; then
      git clone --no-hardlinks "$source" "$target"
    else
      git clone --depth 1 --branch "$revision" "$repository" "$target"
    fi
  fi

  php_bin="/usr/bin/php${XPANEL_HOST_PHP_VERSION:-8.3}"
  [[ -x "$php_bin" ]] || fail "XPanel Host requiere PHP ${XPANEL_HOST_PHP_VERSION:-8.3}."
  "$php_bin" "$(command -v composer)" --working-dir="$target" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
  npm --prefix "$target" install --ignore-scripts --no-audit --no-fund
  npm --prefix "$target" run build
  chown -R root:root "$target"
  chmod -R o-w "$target"
  ln -sfn "$target" "$host_base/current"
  set_env_var XPANEL_HOST_INSTANCES true
  set_env_var XPANEL_HOST_RELEASE "$target"
  set_env_var XPANEL_HOST_VERSION "$release_id"
  set_env_var XPANEL_HOST_PHP_VERSION "${XPANEL_HOST_PHP_VERSION:-8.3}"
}

install_cli() {
  [[ "${XPANEL_INSTALL_CLI:-yes}" != "no" ]] || return 0
  local adjacent=""
  [[ -d "$ROOT/../xpanel-cli" ]] && adjacent="$(realpath "$ROOT/../xpanel-cli")"
  if [[ -n "$adjacent" && "$adjacent" != "$CLI_DIR" && ! -e "$CLI_DIR" ]]; then
    install -d "$(dirname "$CLI_DIR")"
    cp -R "$adjacent" "$CLI_DIR"
  elif [[ -d "$CLI_DIR/.git" ]]; then
    [[ -z "$(git -C "$CLI_DIR" status --porcelain --untracked-files=no)" ]] || fail "xpanel-cli contiene cambios locales."
    git -C "$CLI_DIR" pull --ff-only
  elif [[ ! -x "$CLI_DIR/bin/xpanel" ]]; then
    git clone "https://$REPO_CLI" "$CLI_DIR"
  fi
  bash "$CLI_DIR/install.sh"
}

echo "Instalando XPanel VPS de forma nativa..."
if [[ -d "$ROOT/.git" ]]; then
  git -C "$ROOT" config core.fileMode false
fi
write_marker
install_packages
configure_firewall
ensure_node_runtime
command -v php >/dev/null 2>&1 || fail "PHP no está disponible."
command -v composer >/dev/null 2>&1 || fail "Composer no está disponible."

[[ -f "$ROOT/.env" ]] || cp "$ROOT/.env.example" "$ROOT/.env"
set_env_var APP_ENV production
set_env_var APP_DEBUG false
set_env_var XPANEL_DOCKER_APPS "${XPANEL_DOCKER_APPS:-false}"
node_token="$(env_value XPANEL_NODE_TOKEN)"
[[ -n "$node_token" ]] || set_env_var XPANEL_NODE_TOKEN "$(openssl rand -hex 32)"

composer --working-dir="$ROOT" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm --prefix "$ROOT" install --ignore-scripts --no-audit --no-fund
npm --prefix "$ROOT" run build

detect_php_versions
configure_apache
configure_database
install_host_release

[[ -n "$(env_value APP_KEY)" ]] || php "$ROOT/artisan" key:generate --force
php "$ROOT/artisan" migrate --force
php "$ROOT/artisan" db:seed --class=DefaultDataSeeder --force
php "$ROOT/artisan" optimize:clear

install -d -o www-data -g www-data -m 0775 "$ROOT/storage" "$ROOT/bootstrap/cache"
chown -R www-data:www-data "$ROOT/storage" "$ROOT/bootstrap/cache"
configure_helper
configure_panel
configure_scheduler

initial_admin_created=false
if [[ "$(sudo -u www-data php "$ROOT/artisan" xpanel:admin-bootstrap --status-only)" == "missing" ]]; then
  initial_admin_email="${XPANEL_ADMIN_EMAIL:-admin@xpanel.local}"
  initial_admin_password="$(openssl rand -hex 16)"
  printf '%s\n' "$initial_admin_password" | sudo -u www-data php "$ROOT/artisan" xpanel:admin-bootstrap \
    --name="${XPANEL_ADMIN_NAME:-Administrador}" --email="$initial_admin_email" --password-stdin >/dev/null
  initial_admin_created=true
fi

sudo -u www-data php "$ROOT/artisan" optimize
install_cli

echo
echo "============================================================"
echo "XPanel VPS instalado correctamente"
echo "Panel: $(env_value APP_URL)"
echo "Admin: $(env_value APP_URL)/$(env_value XPANEL_ADMIN_LOGIN_PATH)"
if [[ "$initial_admin_created" == "true" ]]; then
  echo "Correo: $initial_admin_email"
  echo "Contraseña: $initial_admin_password"
  echo "Guarda esta contraseña ahora; no volverá a mostrarse."
fi
echo "CLI global: xpanel"
echo "============================================================"
