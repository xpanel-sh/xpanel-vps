#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

ROOT="/opt/xpanel-vps"
NGINX_CONFIG="/etc/nginx/sites-available/xpanel-vps-panel.conf"
FALLBACK_CERT="/etc/xpanel/tls/fallback.crt"
FALLBACK_KEY="/etc/xpanel/tls/fallback.key"

fail() { printf '%s\n' "$1" >&2; exit 1; }

set_env_var() {
  local key="$1" value="$2"
  if grep -q "^${key}=" "$ROOT/.env"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ROOT/.env"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ROOT/.env"
  fi
}

write_config() {
  local domain="$1" panel_port="$2" php_version="$3" certificate="$4" certificate_key="$5"
  cat > "$NGINX_CONFIG" <<EOF
server {
    listen ${panel_port} ssl;
    listen [::]:${panel_port} ssl;
    server_name _;
    ssl_certificate ${FALLBACK_CERT};
    ssl_certificate_key ${FALLBACK_KEY};
    ssl_protocols TLSv1.2 TLSv1.3;
    root ${ROOT}/public;
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

server {
    listen 80;
    listen [::]:80;
    server_name ${domain};
    root ${ROOT}/public;
    location ^~ /.well-known/acme-challenge/ { try_files \$uri =404; }
    location / { return 301 https://\$host\$request_uri; }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name ${domain};
    ssl_certificate ${certificate};
    ssl_certificate_key ${certificate_key};
    ssl_protocols TLSv1.2 TLSv1.3;
    root ${ROOT}/public;
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
}

[[ "${1:-}" == "set-domain" ]] || fail "unsupported action"
[[ "$#" == 6 ]] || fail "usage: set-domain DOMAIN EMAIL SERVER_IP PANEL_PORT PHP_VERSION"

domain="${2,,}"
email="${3,,}"
server_ip="$4"
panel_port="$5"
php_version="$6"

[[ "$domain" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "El dominio no es válido."
[[ "$email" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "El correo para Let's Encrypt no es válido."
[[ "$server_ip" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]] || fail "La IP pública no es válida."
[[ "$panel_port" =~ ^[0-9]{2,5}$ ]] && (( panel_port >= 1024 && panel_port <= 65535 )) || fail "El puerto de recuperación no es válido."
[[ "$php_version" =~ ^8\.[1-4]$ ]] || fail "La versión PHP no es válida."
[[ -f "$ROOT/artisan" && -f "$ROOT/.env" ]] || fail "XPanel VPS no está instalado en $ROOT."

mapfile -t resolved_ips < <(getent ahostsv4 "$domain" | awk '{print $1}' | sort -u)
printf '%s\n' "${resolved_ips[@]:-}" | grep -Fxq "$server_ip" || fail "El dominio todavía no apunta a $server_ip."
mapfile -t wildcard_ips < <(getent ahostsv4 "xpanel-dns-check.$domain" | awk '{print $1}' | sort -u)
printf '%s\n' "${wildcard_ips[@]:-}" | grep -Fxq "$server_ip" || fail "El wildcard *.$domain todavía no apunta a $server_ip."

install -d -o www-data -g www-data -m 0755 "$ROOT/public/.well-known/acme-challenge"
write_config "$domain" "$panel_port" "$php_version" "$FALLBACK_CERT" "$FALLBACK_KEY"
nginx -t
systemctl reload nginx

certbot certonly --webroot -w "$ROOT/public" --cert-name "$domain" -d "$domain" \
  --email "$email" --agree-tos --no-eff-email --non-interactive --keep-until-expiring

write_config "$domain" "$panel_port" "$php_version" \
  "/etc/letsencrypt/live/$domain/fullchain.pem" "/etc/letsencrypt/live/$domain/privkey.pem"
nginx -t
systemctl reload nginx

set_env_var APP_URL "https://$domain"
set_env_var XPANEL_PANEL_DOMAIN "$domain"
set_env_var XPANEL_CLOUD_DOMAIN "$domain"
set_env_var XPANEL_CONTROL_PLANE_URL "https://$domain"
set_env_var XPANEL_BROKER_URL "https://$domain/api/internal/host-broker"
runuser -u www-data -- php "$ROOT/artisan" optimize:clear >/dev/null

printf 'Dominio principal activado: https://%s\n' "$domain"
