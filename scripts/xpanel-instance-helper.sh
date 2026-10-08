#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-instance-helper: $*" >&2; exit 1; }
[[ "$(id -u)" == "0" ]] || fail "must run as root"

validate_uuid() {
    [[ "$1" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid instance UUID"
}

system_revision() {
    local app_root
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    sha256sum "$app_root/scripts/xpanel-instance-helper.sh" \
        "$app_root/scripts/xpanel-project-quota.sh" \
        "$app_root/app/Services/HostInstanceConfigGenerator.php" | sha256sum | awk '{print $1}'
}

if [[ "${1:-}" == "verify-instance" ]]; then
    [[ $# -eq 5 ]] || fail "verify-instance expects UUID, system user, project ID and staged directory"
    uuid="$2" user="$3" project_id="$4" staged_dir="$5"
    validate_uuid "$uuid"
    uuid_hex="${uuid//-/}"
    [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "invalid instance user"
    [[ "$project_id" =~ ^[0-9]+$ ]] && (( project_id >= 100000 && project_id <= 4294967294 )) || fail "invalid project ID"
    [[ "$staged_dir" == "/opt/xpanel-vps/storage/app/native/host-instances/$uuid" ]] || fail "invalid staged directory"
    instance_root="/var/lib/xpanel-vps/instances/$uuid"
    config_root="/etc/xpanel-vps/instances/$uuid"
    [[ -f "$instance_root/.env" && ! -L "$instance_root/.env" ]] || fail "instance environment is unavailable"
    [[ -f "$instance_root/runtime.sh" && ! -L "$instance_root/runtime.sh" ]] || fail "instance runtime is unavailable"
    [[ -f "$config_root/system-revision" && ! -L "$config_root/system-revision" ]] || fail "system revision has not been applied"
    [[ "$(<"$config_root/system-revision")" == "$(system_revision)" ]] || fail "instance system revision is outdated"
    [[ -f "$config_root/project-quota-applied" && "$(<"$config_root/project-quota-applied")" == "$project_id" ]] || fail "instance disk quota is not applied"
    "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" status >/dev/null
    for quota_path in "/home/$user" "$instance_root"; do
        [[ -d "$quota_path" && ! -L "$quota_path" ]] || fail "instance quota path is missing or unsafe"
        [[ "$(lsattr -pd -- "$quota_path" | awk '{print $1}')" == "$project_id" ]] || fail "instance quota path lost its project ID"
    done
    "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" check-databases "$uuid" "$user" "$project_id" >/dev/null
    for staged_file in runtime.sh nginx.conf; do
        [[ -f "$staged_dir/$staged_file" && ! -L "$staged_dir/$staged_file" ]] || fail "staged instance configuration is unsafe"
    done
    cmp -s "$staged_dir/runtime.sh" "$instance_root/runtime.sh" || fail "instance runtime differs from its plan"
    cmp -s "$staged_dir/nginx.conf" "/etc/nginx/sites-available/xpanel-instance-$uuid.conf" || fail "instance Nginx configuration is outdated"
    systemctl is-active --quiet "xpanel-instance-$uuid-fpm.service" || fail "instance PHP-FPM is not active"
    systemctl is-active --quiet "xpanel-instance-$uuid-scheduler.timer" || fail "instance scheduler is not active"
    systemctl is-active --quiet "xpanel-instance-$uuid.slice" || fail "instance resource slice is not active"
    echo verified
    exit 0
fi

if [[ "${1:-}" == "host-release-prepare" ]]; then
    [[ $# -eq 1 ]] || fail "host-release-prepare expects no arguments"
    exec bash "$(dirname "${BASH_SOURCE[0]}")/prepare-host-release.sh"
fi

if [[ "${1:-}" == "host-update-start" ]]; then
    [[ $# -eq 2 ]] || fail "host-update-start expects an instance UUID"
    validate_uuid "$2"
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    [[ -f "$app_root/artisan" ]] || fail "VPS artisan is unavailable"
    unit="xpanel-host-update-${2//-/}-$(date +%s)"
    systemd-run --quiet --collect --unit="$unit" \
        --property=User=www-data --property="WorkingDirectory=$app_root" \
        /usr/bin/php "$app_root/artisan" xpanel:host-update "$2"
    printf 'started=%s\n' "$unit"
    exit 0
fi

write_slice_limits() {
    local uuid="$1" memory_high="$2" memory_max="$3" swap_max="$4" cpu_percent="$5" tasks_max="$6"
    validate_uuid "$uuid"
    [[ "$memory_high" =~ ^[0-9]+$ && "$memory_max" =~ ^[0-9]+$ && "$swap_max" =~ ^[0-9]+$ ]] || fail "invalid memory limits"
    [[ "$cpu_percent" =~ ^[0-9]+$ && "$tasks_max" =~ ^[0-9]+$ ]] || fail "invalid process limits"
    (( memory_high >= 64 && memory_max >= 128 && memory_high <= memory_max && memory_max <= 1048576 )) || fail "memory limits out of range"
    (( swap_max <= 1048576 && cpu_percent >= 10 && cpu_percent <= 65535 && tasks_max >= 32 && tasks_max <= 1000000 )) || fail "resource limits out of range"

    local dropin="/etc/systemd/system/xpanel-instance-$uuid.slice.d"
    install -d -o root -g root -m 0755 "$dropin"
    cat > "$dropin/limits.conf" <<EOF
[Slice]
CPUAccounting=yes
CPUQuota=${cpu_percent}%
MemoryAccounting=yes
MemoryHigh=${memory_high}M
MemoryMax=${memory_max}M
MemorySwapMax=${swap_max}M
TasksAccounting=yes
TasksMax=${tasks_max}
EOF
    chmod 0644 "$dropin/limits.conf"
    systemctl daemon-reload
    systemctl start "xpanel-instance-$uuid.slice"
    systemctl set-property --runtime "xpanel-instance-$uuid.slice" \
        "CPUQuota=${cpu_percent}%" \
        "MemoryHigh=${memory_high}M" \
        "MemoryMax=${memory_max}M" \
        "MemorySwapMax=${swap_max}M" \
        "TasksMax=${tasks_max}"
}

[[ $# -ge 1 ]] || fail "missing action"
ACTION="$1"
shift

if [[ "$ACTION" == "set-limits" ]]; then
    [[ $# -eq 6 ]] || fail "set-limits expects 6 arguments"
    write_slice_limits "$@"
    echo "limits-applied"
    exit 0
fi

if [[ "$ACTION" == "quota-status" ]]; then
    [[ $# -eq 0 ]] || fail "quota-status takes no arguments"
    exec "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" status
fi

if [[ "$ACTION" == "set-disk-quota" ]]; then
    [[ $# -eq 5 ]] || fail "set-disk-quota expects 5 arguments"
    exec "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" apply "$@"
fi

if [[ "$ACTION" == "set-status" ]]; then
    [[ $# -eq 2 ]] || fail "set-status expects 2 arguments"
    UUID="$1"
    STATUS="$2"
    validate_uuid "$UUID"
    [[ "$STATUS" == "active" || "$STATUS" == "suspended" ]] || fail "invalid status"
    NGINX_TARGET="/etc/nginx/sites-available/xpanel-instance-$UUID.conf"
    [[ -f "$NGINX_TARGET" && ! -L "$NGINX_TARGET" ]] || fail "instance vhost does not exist"
    if [[ "$STATUS" == "active" ]]; then
        systemctl start "xpanel-instance-$UUID-fpm.service"
        if [[ -f "/etc/systemd/system/xpanel-instance-$UUID-scheduler.timer" ]]; then
            systemctl enable --now "xpanel-instance-$UUID-scheduler.timer"
        fi
        ln -sfn "$NGINX_TARGET" "/etc/nginx/sites-enabled/xpanel-instance-$UUID.conf"
    else
        unlink "/etc/nginx/sites-enabled/xpanel-instance-$UUID.conf" 2>/dev/null || true
        if [[ -f "/etc/systemd/system/xpanel-instance-$UUID-scheduler.timer" ]]; then
            systemctl disable --now "xpanel-instance-$UUID-scheduler.timer"
        fi
        systemctl stop "xpanel-instance-$UUID-fpm.service"
    fi
    nginx -t
    systemctl reload nginx
    echo "$STATUS"
    exit 0
fi

if [[ "$ACTION" == "ssl-issue" ]]; then
    [[ $# -eq 3 || $# -eq 4 ]] || fail "ssl-issue expects 3 or 4 arguments"
    UUID="$1"
    PANEL_DOMAIN="$2"
    ADMIN_EMAIL="$3"
    CUSTOM_DOMAIN="${4:-}"
    validate_uuid "$UUID"
    [[ "$PANEL_DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid panel domain"
    [[ "$ADMIN_EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "invalid admin email"
    if [[ -n "$CUSTOM_DOMAIN" ]]; then
        [[ "$CUSTOM_DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid custom panel domain"
    fi
    NGINX_TARGET="/etc/nginx/sites-available/xpanel-instance-$UUID.conf"
    TLS_SNIPPET="/etc/nginx/snippets/xpanel-instance-$UUID-tls.conf"
    [[ -f "$NGINX_TARGET" && ! -L "$NGINX_TARGET" ]] || fail "instance vhost does not exist"
    CERTBOT_DOMAINS=(-d "$PANEL_DOMAIN")
    if [[ -n "$CUSTOM_DOMAIN" ]]; then
        CERTBOT_DOMAINS+=(-d "$CUSTOM_DOMAIN")
    fi
    certbot certonly --non-interactive --agree-tos --no-eff-email --expand \
        --cert-name "$PANEL_DOMAIN" --email "$ADMIN_EMAIL" --webroot -w /var/lib/letsencrypt "${CERTBOT_DOMAINS[@]}"
    cat > "$TLS_SNIPPET" <<EOF
listen 443 ssl;
listen [::]:443 ssl;
ssl_certificate /etc/letsencrypt/live/$PANEL_DOMAIN/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/$PANEL_DOMAIN/privkey.pem;
ssl_protocols TLSv1.2 TLSv1.3;
EOF
    chmod 0644 "$TLS_SNIPPET"
    nginx -t
    systemctl reload nginx
    echo "active"
    exit 0
fi

[[ "$ACTION" == "apply" ]] || fail "unsupported action"
[[ $# -eq 17 ]] || fail "apply expects 17 arguments"

UUID="$1"
SYSTEM_USER="$2"
PANEL_DOMAIN="$3"
PHP_VERSION="$4"
RELEASE_PATH="$5"
STAGED_DIR="$6"
OWNER_NAME="$7"
OWNER_EMAIL="$8"
MEMORY_HIGH_MB="$9"
MEMORY_MAX_MB="${10}"
SWAP_MAX_MB="${11}"
CPU_PERCENT="${12}"
TASKS_MAX="${13}"
RESTART_MODE="${14}"
PROJECT_ID="${15}"
STORAGE_MIB="${16}"
INODE_LIMIT="${17}"

validate_uuid "$UUID"
[[ "$SYSTEM_USER" =~ ^xhi[a-f0-9]{12}$ ]] || fail "invalid system user"
[[ "$PANEL_DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid panel domain"
[[ "$PHP_VERSION" =~ ^8\.[2-4]$ ]] || fail "unsupported PHP version"
PHP_BIN="/usr/bin/php$PHP_VERSION"
[[ -x "$PHP_BIN" ]] || fail "PHP $PHP_VERSION CLI is not installed"
[[ "$RELEASE_PATH" =~ ^/opt/xpanel-host/(current|releases/[A-Za-z0-9._-]+)$ ]] || fail "invalid release path"
[[ "$RESTART_MODE" == "immediate" || "$RESTART_MODE" == "deferred" || "$RESTART_MODE" == "skip" ]] || fail "invalid restart mode"
[[ "$STAGED_DIR" == "/opt/xpanel-vps/storage/app/native/host-instances/$UUID" ]] || fail "invalid staged directory"
[[ "$PROJECT_ID" =~ ^[0-9]+$ && "$STORAGE_MIB" =~ ^[0-9]+$ && "$INODE_LIMIT" =~ ^[0-9]+$ ]] || fail "invalid disk quota arguments"
if (( STORAGE_MIB > 0 && INODE_LIMIT > 0 )); then
    "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" status >/dev/null
fi
[[ -f "$RELEASE_PATH/artisan" && -f "$RELEASE_PATH/public/index.php" ]] || fail "XPanel Host release is incomplete"
for file in instance.env runtime.sh php-fpm.conf php-fpm-global.conf php-fpm.service nginx.conf apache.conf apache.service; do
    [[ -f "$STAGED_DIR/$file" && ! -L "$STAGED_DIR/$file" ]] || fail "missing staged $file"
done

INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
FPM_TARGET="/etc/php/$PHP_VERSION/fpm/pool.d/xpanel-instance-$UUID.conf"
FPM_CONFIG_ROOT="/etc/xpanel-vps/instances/$UUID"
FPM_POOL_ROOT="$FPM_CONFIG_ROOT/php-fpm-pools"
FPM_GLOBAL_TARGET="$FPM_CONFIG_ROOT/php-fpm.conf"
FPM_POOL_TARGET="$FPM_POOL_ROOT/panel.conf"
FPM_SERVICE_TARGET="/etc/systemd/system/xpanel-instance-$UUID-fpm.service"
APACHE_CONFIG_TARGET="$FPM_CONFIG_ROOT/apache.conf"
APACHE_SERVICE_TARGET="/etc/systemd/system/xpanel-instance-$UUID-apache.service"
SCHEDULER_SERVICE_TARGET="/etc/systemd/system/xpanel-instance-$UUID-scheduler.service"
SCHEDULER_TIMER_TARGET="/etc/systemd/system/xpanel-instance-$UUID-scheduler.timer"
NGINX_TARGET="/etc/nginx/sites-available/xpanel-instance-$UUID.conf"
TLS_SNIPPET="/etc/nginx/snippets/xpanel-instance-$UUID-tls.conf"

IFS= read -r INITIAL_PASSWORD || true

if ! id "$SYSTEM_USER" >/dev/null 2>&1; then
    useradd --system --home-dir "$INSTANCE_ROOT" --shell /usr/sbin/nologin --user-group "$SYSTEM_USER"
fi

# The panel process runs as SYSTEM_USER, while site provisioning runs through
# the privileged broker. Prepare the account home before either process uses it;
# otherwise a first site can leave /home/$SYSTEM_USER root-owned and iKode
# cannot create the rest of the account layout.
ACCOUNT_HOME="/home/$SYSTEM_USER"
for account_path in "$ACCOUNT_HOME" "$ACCOUNT_HOME/public_html"; do
    [[ ! -L "$account_path" ]] || fail "account workspace is a symlink"
    if [[ -e "$account_path" ]]; then
        [[ -d "$account_path" ]] || fail "account workspace is not a directory"
        owner="$(stat -c %U -- "$account_path")"
        [[ "$owner" == root || "$owner" == "$SYSTEM_USER" ]] || fail "account workspace has an unexpected owner"
    fi
    install -d -m 0750 -o "$SYSTEM_USER" -g "$SYSTEM_USER" "$account_path"
done

install -d -m 0750 -o "$SYSTEM_USER" -g "$SYSTEM_USER" "$INSTANCE_ROOT" "$INSTANCE_ROOT/database"
if (( STORAGE_MIB > 0 && INODE_LIMIT > 0 )); then
    "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" apply "$UUID" "$SYSTEM_USER" "$PROJECT_ID" "$STORAGE_MIB" "$INODE_LIMIT"
fi
for storage_path in "$INSTANCE_ROOT/storage" "$INSTANCE_ROOT/storage/app" "$INSTANCE_ROOT/storage/app/access" "$INSTANCE_ROOT/storage/framework"; do
    [[ ! -L "$storage_path" ]] || fail "instance storage is a symlink"
    if [[ -e "$storage_path" ]]; then
        [[ -d "$storage_path" ]] || fail "instance storage is not a directory"
        owner="$(stat -c %U -- "$storage_path")"
        [[ "$owner" == root || "$owner" == "$SYSTEM_USER" ]] || fail "instance storage has an unexpected owner"
    fi
    install -d -m 0750 -o "$SYSTEM_USER" -g "$SYSTEM_USER" "$storage_path"
    chown "$SYSTEM_USER:$SYSTEM_USER" "$storage_path"
    chmod 0750 "$storage_path"
done
install -d -m 0750 -o "$SYSTEM_USER" -g "$SYSTEM_USER" \
    "$INSTANCE_ROOT/storage/app/private" \
    "$INSTANCE_ROOT/storage/framework/cache" \
    "$INSTANCE_ROOT/storage/framework/sessions" \
    "$INSTANCE_ROOT/storage/framework/testing" \
    "$INSTANCE_ROOT/storage/framework/views" \
    "$INSTANCE_ROOT/storage/logs"
install -m 0600 -o "$SYSTEM_USER" -g "$SYSTEM_USER" "$STAGED_DIR/instance.env" "$INSTANCE_ROOT/.env"
install -m 0600 -o root -g root "$STAGED_DIR/runtime.sh" "$INSTANCE_ROOT/runtime.sh"
touch "$INSTANCE_ROOT/database/database.sqlite"
chown "$SYSTEM_USER:$SYSTEM_USER" "$INSTANCE_ROOT/database/database.sqlite"

write_slice_limits "$UUID" "$MEMORY_HIGH_MB" "$MEMORY_MAX_MB" "$SWAP_MAX_MB" "$CPU_PERCENT" "$TASKS_MAX"
install -d -o root -g root -m 0755 "$FPM_CONFIG_ROOT" "$FPM_POOL_ROOT"
rm -f "$FPM_TARGET"
install -m 0640 -o root -g root "$STAGED_DIR/php-fpm.conf" "$FPM_POOL_TARGET"
install -m 0644 -o root -g root "$STAGED_DIR/php-fpm-global.conf" "$FPM_GLOBAL_TARGET"
install -m 0644 -o root -g root "$STAGED_DIR/php-fpm.service" "$FPM_SERVICE_TARGET"
cat > "$SCHEDULER_SERVICE_TARGET" <<EOF
[Unit]
Description=XPanel Host scheduled tasks $UUID
After=network.target

[Service]
Type=oneshot
Slice=xpanel-instance-$UUID.slice
WorkingDirectory=$RELEASE_PATH
ExecStart=/bin/bash -c '. $INSTANCE_ROOT/runtime.sh; exec /usr/sbin/runuser -u $SYSTEM_USER --preserve-environment -- $PHP_BIN $RELEASE_PATH/artisan schedule:run --no-interaction'
TimeoutStartSec=300
Nice=10
EOF
cat > "$SCHEDULER_TIMER_TARGET" <<EOF
[Unit]
Description=Run XPanel Host scheduler $UUID each minute

[Timer]
OnCalendar=*-*-* *:*:00
Persistent=true
Unit=xpanel-instance-$UUID-scheduler.service

[Install]
WantedBy=timers.target
EOF
chown root:root "$SCHEDULER_SERVICE_TARGET" "$SCHEDULER_TIMER_TARGET"
chmod 0644 "$SCHEDULER_SERVICE_TARGET" "$SCHEDULER_TIMER_TARGET"
install -d -m 0755 -o root -g root "$FPM_CONFIG_ROOT/apache" "$FPM_CONFIG_ROOT/apache/sites"
install -m 0644 -o root -g root "$STAGED_DIR/apache.conf" "$APACHE_CONFIG_TARGET"
install -m 0644 -o root -g root "$STAGED_DIR/apache.service" "$APACHE_SERVICE_TARGET"
install -m 0644 -o root -g root "$STAGED_DIR/nginx.conf" "$NGINX_TARGET"
install -d -m 0755 /etc/nginx/snippets
touch "$TLS_SNIPPET"
chown root:root "$TLS_SNIPPET"
chmod 0644 "$TLS_SNIPPET"
ln -sfn "$NGINX_TARGET" "/etc/nginx/sites-enabled/xpanel-instance-$UUID.conf"

# shellcheck disable=SC1090
source "$INSTANCE_ROOT/runtime.sh"
runuser -u "$SYSTEM_USER" --preserve-environment -- "$PHP_BIN" "$RELEASE_PATH/artisan" migrate --force --no-interaction
runuser -u "$SYSTEM_USER" --preserve-environment -- "$PHP_BIN" "$RELEASE_PATH/artisan" optimize

# Account-wide iKode terminal has its own jail and can see only this hosting's
# /home directory. Site terminals are synchronized through the signed broker.
ACCESS_STAGE="$INSTANCE_ROOT/storage/app/access/$SYSTEM_USER"
install -d -o "$SYSTEM_USER" -g "$SYSTEM_USER" -m 0750 "$ACCESS_STAGE"
if [[ ! -f "$ACCESS_STAGE/authorized_keys" ]]; then
    install -o "$SYSTEM_USER" -g "$SYSTEM_USER" -m 0640 /dev/null "$ACCESS_STAGE/authorized_keys"
fi
bash "$RELEASE_PATH/scripts/xpanel-site-helper.sh" access-sync "$SYSTEM_USER" "$ACCOUNT_HOME" 0 0 0 1 </dev/null

if [[ -n "$INITIAL_PASSWORD" ]]; then
    printf '%s\n' "$INITIAL_PASSWORD" | runuser -u "$SYSTEM_USER" --preserve-environment -- \
        "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:admin-bootstrap \
        --name="$OWNER_NAME" --email="$OWNER_EMAIL" --password-stdin --no-interaction
fi

php-fpm"$PHP_VERSION" -t -y "$FPM_GLOBAL_TARGET"
nginx -t
systemctl daemon-reload
systemctl enable "xpanel-instance-$UUID-fpm.service"
systemctl enable --now "xpanel-instance-$UUID-scheduler.timer"
if [[ "$RESTART_MODE" == "immediate" ]]; then
    systemctl restart "xpanel-instance-$UUID-fpm.service"
elif [[ "$RESTART_MODE" == "deferred" ]]; then
    systemd-run --quiet --collect --on-active=5s /bin/systemctl restart "xpanel-instance-$UUID-fpm.service"
fi
systemctl reload nginx
printf '%s\n' "$(system_revision)" > "$FPM_CONFIG_ROOT/system-revision"
chmod 0600 "$FPM_CONFIG_ROOT/system-revision"
echo "active"
