#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-instance-helper: $*" >&2; exit 1; }
[[ "$(id -u)" == "0" ]] || fail "must run as root"

terminal_agent_ready() {
    ss -H -ltn 2>/dev/null | awk '$4 == "127.0.0.1:7093" { found=1 } END { exit(found ? 0 : 1) }'
}

ensure_terminal_agent() {
    [[ "${XPANEL_TERMINAL_ENABLED:-false}" == "true" ]] || return 0
    if ! systemctl is-active --quiet xpanel-vps-terminal-agent.service || ! terminal_agent_ready; then
        systemctl restart xpanel-vps-terminal-agent.service || fail "terminal agent could not be restarted"
    fi
    local attempt
    for attempt in {1..20}; do
        terminal_agent_ready && return 0
        sleep 0.25
    done
    fail "terminal agent did not open its loopback listener"
}

validate_uuid() {
    [[ "$1" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid instance UUID"
}

system_revision() {
    local app_root
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    sha256sum "$app_root/scripts/xpanel-instance-helper.sh" \
        "$app_root/scripts/xpanel-project-quota.sh" \
        "$app_root/scripts/xpanel-user-quota.sh" \
        "$app_root/scripts/xpanel-account-storage.sh" \
        "$app_root/scripts/xpanel-mail-reconcile.sh" \
        "$app_root/scripts/xpanel-traffic-rate.sh" \
        "$app_root/app/Services/HostInstanceConfigGenerator.php" | sha256sum | awk '{print $1}'
}

if [[ "${1:-}" == "verify-instance" ]]; then
    [[ $# -eq 7 ]] || fail "verify-instance expects UUID, system user, project ID, staged directory and disk limits"
    uuid="$2" user="$3" project_id="$4" staged_dir="$5" storage_mib="$6" inode_limit="$7"
    validate_uuid "$uuid"
    uuid_hex="${uuid//-/}"
    [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "invalid instance user"
    [[ "$project_id" =~ ^[0-9]+$ ]] && (( project_id >= 100000 && project_id <= 4294967294 )) || fail "invalid project ID"
    [[ "$staged_dir" == "/opt/xpanel-vps/storage/app/native/host-instances/$uuid" ]] || fail "invalid staged directory"
    instance_root="/var/lib/xpanel-vps/instances/$uuid"
    config_root="/etc/xpanel-vps/instances/$uuid"
    [[ -f "$instance_root/.env" && ! -L "$instance_root/.env" ]] || fail "instance environment is unavailable"
    [[ -f "$instance_root/runtime.sh" && ! -L "$instance_root/runtime.sh" ]] || fail "instance runtime is unavailable"
    [[ -f "/etc/nginx/snippets/xpanel-traffic-$uuid.conf" && ! -L "/etc/nginx/snippets/xpanel-traffic-$uuid.conf" ]] || fail "instance traffic configuration is unavailable"
    [[ -f "$config_root/system-revision" && ! -L "$config_root/system-revision" ]] || fail "system revision has not been applied"
    [[ "$(<"$config_root/system-revision")" == "$(system_revision)" ]] || fail "instance system revision is outdated"
    if [[ -f "$config_root/user-quota-applied" ]]; then
        bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-user-quota.sh" verify "$uuid" "$user" "$project_id" "$storage_mib" "$inode_limit" >/dev/null
    else
        [[ -f "$config_root/project-quota-applied" && "$(<"$config_root/project-quota-applied")" == "$project_id" ]] || fail "instance disk quota is not applied"
        "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" status >/dev/null
        for quota_path in "/home/$user" "$instance_root"; do
            [[ -d "$quota_path" && ! -L "$quota_path" ]] || fail "instance quota path is missing or unsafe"
            [[ "$(lsattr -pd -- "$quota_path" | awk '{print $1}')" == "$project_id" ]] || fail "instance quota path lost its project ID"
        done
    fi
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

if [[ "${1:-}" == "database-engine-install-start" || "${1:-}" == "database-engine-install-status" ]]; then
    if [[ "$1" == "database-engine-install-start" ]]; then
        [[ $# -eq 2 && "$2" =~ ^(mariadb|postgresql|mongodb|dbgate)$ ]] || fail "invalid database engine"
        exec bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-database-engine-install.sh" start "$2"
    fi
    [[ $# -eq 1 ]] || fail "invalid database engine status request"
    exec bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-database-engine-install.sh" status
fi

if [[ "${1:-}" == "database-engine-status" || "${1:-}" == "database-engine-install" ]]; then
    [[ $# -eq 2 && "$2" =~ ^(mariadb|postgresql|mongodb|dbgate)$ ]] || fail "invalid database engine action"
    release="$(readlink -f /opt/xpanel-host/current 2>/dev/null || true)"
    [[ "$release" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ && -f "$release/scripts/xpanel-site-helper.sh" ]] || fail "Host release is unavailable"
    exec env XPANEL_MANAGEMENT_MODE=vps-control XPANEL_INSTANCE_ROOT=/opt/xpanel-vps \
        bash "$release/scripts/xpanel-site-helper.sh" "$1" "$2"
fi

if [[ "${1:-}" == "dbgate-set-enabled" ]]; then
    [[ $# -eq 2 && "$2" =~ ^(true|false)$ ]] || fail "invalid DbGate state"
    release="$(readlink -f /opt/xpanel-host/current 2>/dev/null || true)"
    [[ "$release" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ && -f "$release/scripts/xpanel-site-helper.sh" ]] || fail "Host release is unavailable"
    exec env XPANEL_MANAGEMENT_MODE=vps-control XPANEL_INSTANCE_ROOT=/opt/xpanel-vps \
        bash "$release/scripts/xpanel-site-helper.sh" dbgate-set-enabled "$2"
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
    sleep 0.25
    if systemctl is-failed --quiet "$unit.service"; then
        journalctl -u "$unit.service" --no-pager -n 20 >&2 || true
        fail "Host update service failed to start"
    fi
    printf 'started=%s\n' "$unit"
    exit 0
fi

if [[ "${1:-}" == "host-archive-worker-start" ]]; then
    [[ $# -eq 2 ]] || fail "host-archive-worker-start expects an instance UUID"
    validate_uuid "$2"
    [[ -f "/var/lib/xpanel-vps/instances/$2/.env" ]] || fail "instance environment unavailable"
    systemctl enable --now "xpanel-instance-$2-scheduler.timer"
    systemctl --no-block start "xpanel-instance-$2-scheduler.service"
    echo started
    exit 0
fi

if [[ "${1:-}" == "archive-start" ]]; then
    [[ $# -eq 2 ]] || fail "archive-start expects an instance UUID"
    validate_uuid "$2"
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    [[ -f "$app_root/artisan" ]] || fail "VPS artisan is unavailable"
    unit="xpanel-hosting-archive-${2//-/}-$(date +%s)"
    systemd-run --quiet --collect --unit="$unit" \
        --property=User=root --property="WorkingDirectory=$app_root" \
        /usr/bin/php "$app_root/artisan" xpanel:hosting-archive "$2"
    printf 'started=%s\n' "$unit"
    exit 0
fi

if [[ "${1:-}" == "purge-start" ]]; then
    [[ $# -eq 3 ]] || fail "purge-start expects an instance UUID and project ID"
    validate_uuid "$2"
    [[ "$3" =~ ^[0-9]+$ ]] && (( "$3" >= 100000 && "$3" <= 4294967294 )) || fail "invalid purge project ID"
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    [[ -f "$app_root/artisan" ]] || fail "VPS artisan is unavailable"
    unit="xpanel-hosting-purge-${2//-/}-$(date +%s)"
    systemd-run --quiet --collect --unit="$unit" \
        --property=User=root --property="WorkingDirectory=$app_root" \
        /usr/bin/php "$app_root/artisan" xpanel:hosting-purge "$2"
    printf 'started=%s\n' "$unit"
    exit 0
fi

if [[ "${1:-}" == "restore-start" ]]; then
    [[ $# -eq 3 ]] || fail "restore-start expects an instance UUID and project ID"
    validate_uuid "$2"
    [[ "$3" =~ ^[0-9]+$ ]] && (( "$3" >= 100000 && "$3" <= 4294967294 )) || fail "invalid restore project ID"
    app_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    [[ -f "$app_root/artisan" ]] || fail "VPS artisan is unavailable"
    unit="xpanel-hosting-restore-${2//-/}-$(date +%s)"
    systemd-run --quiet --collect --unit="$unit" \
        --property=User=root --property="WorkingDirectory=$app_root" \
        /usr/bin/php "$app_root/artisan" xpanel:hosting-restore "$2"
    printf 'started=%s\n' "$unit"
    exit 0
fi

if [[ "${1:-}" == "instance-metrics" ]]; then
    [[ $# -eq 3 ]] || fail "instance-metrics expects UUID and system user"
    uuid="$2"; user="$3"
    validate_uuid "$uuid"
    uuid_hex="${uuid//-/}"
    [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "invalid instance user"
    instance_root="/var/lib/xpanel-vps/instances/$uuid"
    account_home="/home/$user"
    [[ -d "$instance_root" && ! -L "$instance_root" ]] || fail "instance root is unavailable"
    [[ -d "$account_home" && ! -L "$account_home" ]] || fail "account home is unavailable"
    slice="xpanel-instance-$uuid.slice"
    memory="$(systemctl show "$slice" --property=MemoryCurrent --value 2>/dev/null || true)"
    tasks="$(systemctl show "$slice" --property=TasksCurrent --value 2>/dev/null || true)"
    cpu="$(systemctl show "$slice" --property=CPUUsageNSec --value 2>/dev/null || true)"
    [[ "$memory" =~ ^[0-9]+$ ]] || memory=0
    [[ "$tasks" =~ ^[0-9]+$ ]] || tasks=0
    [[ "$cpu" =~ ^[0-9]+$ ]] || cpu=0
    storage="$(bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-account-storage.sh" usage "$uuid")"
    disk="$(awk -F= '$1 == "known_total_bytes" { print $2 }' <<< "$storage")"
    [[ "$disk" =~ ^[0-9]+$ ]] || fail "combined storage measurement is unavailable"
    printf 'memory_bytes=%s\ntasks=%s\ncpu_nsec=%s\ndisk_bytes=%s\n' "$memory" "$tasks" "$cpu" "$disk"
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

if [[ "$ACTION" == "reconcile-disk-quota" ]]; then
    [[ $# -eq 3 ]] || fail "reconcile-disk-quota expects UUID, system user and project ID"
    exec "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" reconcile-total "$@"
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

if [[ "$ACTION" == "archive" ]]; then
    [[ $# -eq 2 ]] || fail "archive expects an instance UUID and system user"
    UUID="$1"
    SYSTEM_USER="$2"
    validate_uuid "$UUID"
    UUID_HEX="${UUID//-/}"
    [[ "$SYSTEM_USER" == "xhi${UUID_HEX:0:12}" ]] || fail "invalid instance user"

    INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
    ACCOUNT_HOME="/home/$SYSTEM_USER"
    DATABASE_FILE="$INSTANCE_ROOT/database/database.sqlite"
    [[ -d "$INSTANCE_ROOT" && ! -L "$INSTANCE_ROOT" ]] || fail "instance root is unavailable or unsafe"
    [[ -d "$ACCOUNT_HOME" && ! -L "$ACCOUNT_HOME" ]] || fail "account home is unavailable or unsafe"
    [[ -f "$DATABASE_FILE" && ! -L "$DATABASE_FILE" ]] || fail "instance database catalog is unavailable"
    ! systemctl is-active --quiet "xpanel-instance-$UUID-fpm.service" || fail "instance must remain suspended while it is archived"

    ARCHIVE_ROOT="/var/backups/xpanel/hosting-retention/$UUID"
    STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
    WORK_DIR="$ARCHIVE_ROOT/.$STAMP.tmp"
    FINAL_ARCHIVE="$ARCHIVE_ROOT/$STAMP.tar.gz"
    install -d -o root -g root -m 0700 "$ARCHIVE_ROOT"
    [[ ! -e "$WORK_DIR" && ! -e "$FINAL_ARCHIVE" ]] || fail "retention archive target already exists"
    install -d -o root -g root -m 0700 "$WORK_DIR" "$WORK_DIR/databases"
    cleanup_archive() { rm -rf -- "$WORK_DIR" "$FINAL_ARCHIVE.tmp"; }
    trap cleanup_archive ERR INT TERM

    DATABASE_LIST="$(php -r '
        $db = new PDO("sqlite:".$argv[1]);
        if (!$db->query("SELECT 1 FROM sqlite_master WHERE type = \x27table\x27 AND name = \x27site_databases\x27")->fetchColumn()) exit(0);
        $columns = array_column($db->query("PRAGMA table_info(site_databases)")->fetchAll(PDO::FETCH_ASSOC), "name");
        $engine = in_array("engine", $columns, true) ? "COALESCE(engine, \x27mariadb\x27)" : "\x27mariadb\x27";
        foreach ($db->query("SELECT DISTINCT ".$engine." AS engine, name, username FROM site_databases WHERE status = \x27active\x27 ORDER BY engine, name") as $row) echo $row["engine"]."\t".$row["name"]."\t".$row["username"]."\n";
    ' "$DATABASE_FILE")" || fail "could not read the instance database catalog"
    DATABASE_ENGINES=()
    DATABASES=()
    DATABASE_USERS=()
    while IFS=$'\t' read -r DB_ENGINE DATABASE DB_USER; do
        if [[ -n "$DATABASE" ]]; then
            [[ "$DB_ENGINE" == mariadb || "$DB_ENGINE" == postgresql || "$DB_ENGINE" == mongodb ]] || fail "unsupported database engine: $DB_ENGINE"
            DATABASE_ENGINES+=("$DB_ENGINE")
            DATABASES+=("$DATABASE")
            DATABASE_USERS+=("$DB_USER")
        fi
    done <<< "$DATABASE_LIST"

    SOURCE_BYTES="$(du -sb --one-file-system "$ACCOUNT_HOME" "$INSTANCE_ROOT" | awk '{total += $1} END {print total + 0}')"
    for INDEX in "${!DATABASES[@]}"; do
        DB_ENGINE="${DATABASE_ENGINES[$INDEX]}"
        DATABASE="${DATABASES[$INDEX]}"
        [[ "$DATABASE" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance: $DATABASE"
        case "$DB_ENGINE" in
            mariadb)
                DATABASE_PATH="/var/lib/mysql/$DATABASE"
                [[ -d "$DATABASE_PATH" && ! -L "$DATABASE_PATH" ]] || fail "database directory is missing or unsafe: $DATABASE"
                DB_BYTES="$(du -sb --one-file-system "$DATABASE_PATH" | awk '{print $1}')"
                ;;
            postgresql) DB_BYTES="$(runuser -u postgres -- psql -Atqc "SELECT pg_database_size('$DATABASE')")" ;;
            mongodb)
                [[ -f /etc/xpanel-host/mongodb-admin.secret && ! -L /etc/xpanel-host/mongodb-admin.secret ]] || fail "MongoDB management credentials are unavailable"
                DB_BYTES="$(mongosh --quiet --host 127.0.0.1 --username xpanel_system --password "$(cat /etc/xpanel-host/mongodb-admin.secret)" --authenticationDatabase admin --eval "db.getSiblingDB('$DATABASE').stats().storageSize" | tail -n1)"
                ;;
        esac
        [[ "$DB_BYTES" =~ ^[0-9]+$ ]] || fail "could not measure $DB_ENGINE database: $DATABASE"
        SOURCE_BYTES=$((SOURCE_BYTES + DB_BYTES))
    done
    AVAILABLE_BYTES="$(df -PB1 --output=avail "$ARCHIVE_ROOT" | tail -n 1 | tr -d ' ')"
    [[ "$AVAILABLE_BYTES" =~ ^[0-9]+$ ]] || fail "could not inspect free archive space"
    (( AVAILABLE_BYTES > SOURCE_BYTES + 536870912 )) || fail "insufficient free space to create a safe retention archive"

    tar --one-file-system --numeric-owner -C /home -czf "$WORK_DIR/account-home.tar.gz" "$SYSTEM_USER"
    tar --one-file-system --numeric-owner -C /var/lib/xpanel-vps/instances -czf "$WORK_DIR/instance-state.tar.gz" "$UUID"
    : > "$WORK_DIR/postgresql-roles.tsv"
    for INDEX in "${!DATABASES[@]}"; do
        DB_ENGINE="${DATABASE_ENGINES[$INDEX]}"; DATABASE="${DATABASES[$INDEX]}"; DB_USER="${DATABASE_USERS[$INDEX]}"
        case "$DB_ENGINE" in
            mariadb)
                mariadb-dump --protocol=socket --single-transaction --routines --events --triggers --databases "$DATABASE" \
                    | gzip -9 > "$WORK_DIR/databases/mariadb--$DATABASE.sql.gz"
                ;;
            postgresql)
                runuser -u postgres -- pg_dump --format=custom "$DATABASE" > "$WORK_DIR/databases/postgresql--$DATABASE.dump"
                ROLE_HASH="$(runuser -u postgres -- psql -Atqc "SELECT rolpassword FROM pg_authid WHERE rolname='$DB_USER'")"
                [[ "$ROLE_HASH" =~ ^SCRAM-SHA-256\$[0-9]+:[A-Za-z0-9+/=]+\$[A-Za-z0-9+/=]+:[A-Za-z0-9+/=]+$ ]] || fail "PostgreSQL role has an unsupported password representation"
                printf '%s\t%s\n' "$DB_USER" "$(printf %s "$ROLE_HASH" | base64 -w0)" >> "$WORK_DIR/postgresql-roles.tsv"
                ;;
            mongodb)
                mongodump --quiet --host 127.0.0.1 --username xpanel_system --password "$(cat /etc/xpanel-host/mongodb-admin.secret)" --authenticationDatabase admin \
                    --db "$DATABASE" --dumpDbUsersAndRoles --archive="$WORK_DIR/databases/mongodb--$DATABASE.archive" --gzip
                ;;
        esac
    done
    : > "$WORK_DIR/database-users.sql"
    for INDEX in "${!DATABASE_USERS[@]}"; do
        [[ "${DATABASE_ENGINES[$INDEX]}" == mariadb ]] || continue
        DB_USER="${DATABASE_USERS[$INDEX]}"
        [[ "$DB_USER" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,22}$ ]] || fail "database user escaped the instance: $DB_USER"
        while IFS= read -r DB_HOST; do
            [[ -n "$DB_HOST" ]] || continue
            [[ "$DB_HOST" =~ ^[A-Za-z0-9.%:_-]+$ ]] || fail "unsafe MariaDB host for instance user"
            mariadb --protocol=socket --batch --skip-column-names -e "SHOW CREATE USER '$DB_USER'@'$DB_HOST'" | cut -f2- | sed 's/^CREATE USER /CREATE OR REPLACE USER /; s/;[[:space:]]*$//; s/$/;/' >> "$WORK_DIR/database-users.sql"
            mariadb --protocol=socket --batch --skip-column-names -e "SHOW GRANTS FOR '$DB_USER'@'$DB_HOST'" | sed 's/;[[:space:]]*$//; s/$/;/' >> "$WORK_DIR/database-users.sql"
        done < <(mariadb --protocol=socket --batch --skip-column-names -e "SELECT Host FROM mysql.user WHERE User='$DB_USER' ORDER BY Host")
    done
    sort -u -o "$WORK_DIR/postgresql-roles.tsv" "$WORK_DIR/postgresql-roles.tsv"
    gzip -9 "$WORK_DIR/database-users.sql"
    {
        printf 'format=3\n'
        printf 'uuid=%s\n' "$UUID"
        printf 'system_user=%s\n' "$SYSTEM_USER"
        printf 'created_at=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
        printf 'database_count=%s\n' "${#DATABASES[@]}"
    } > "$WORK_DIR/metadata.txt"
    (
        cd "$WORK_DIR"
        find . -type f ! -name SHA256SUMS -print0 | sort -z | xargs -0 sha256sum > SHA256SUMS
        sha256sum --check SHA256SUMS >/dev/null
    )
    tar -C "$WORK_DIR" -czf "$FINAL_ARCHIVE.tmp" .
    ARCHIVE_SHA256="$(sha256sum "$FINAL_ARCHIVE.tmp" | awk '{print $1}')"
    mv "$FINAL_ARCHIVE.tmp" "$FINAL_ARCHIVE"
    chmod 0600 "$FINAL_ARCHIVE"
    rm -rf -- "$WORK_DIR"
    trap - ERR INT TERM
    printf 'archive_path=%s\narchive_sha256=%s\ndatabase_count=%s\n' "$FINAL_ARCHIVE" "$ARCHIVE_SHA256" "${#DATABASES[@]}"
    exit 0
fi

if [[ "$ACTION" == "archive-verify" ]]; then
    [[ $# -eq 3 ]] || fail "archive-verify expects instance UUID, archive path and SHA-256"
    UUID="$1"
    ARCHIVE_PATH="$2"
    EXPECTED_SHA256="$3"
    validate_uuid "$UUID"
    [[ "$EXPECTED_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "invalid archive SHA-256"
    ARCHIVE_ROOT="/var/backups/xpanel/hosting-retention/$UUID"
    [[ "$ARCHIVE_PATH" =~ ^$ARCHIVE_ROOT/[0-9]{8}T[0-9]{6}Z\.tar\.gz$ ]] || fail "archive path escaped its retention directory"
    [[ -f "$ARCHIVE_PATH" && ! -L "$ARCHIVE_PATH" ]] || fail "retention archive is missing or unsafe"
    [[ "$(stat -c %U:%G -- "$ARCHIVE_PATH")" == root:root ]] || fail "retention archive has an unexpected owner"
    ACTUAL_SHA256="$(sha256sum "$ARCHIVE_PATH" | awk '{print $1}')"
    [[ "$ACTUAL_SHA256" == "$EXPECTED_SHA256" ]] || fail "retention archive SHA-256 mismatch"

    VERIFY_DIR="$(mktemp -d "$ARCHIVE_ROOT/.verify.XXXXXX")"
    cleanup_verify() { rm -rf -- "$VERIFY_DIR"; }
    trap cleanup_verify EXIT INT TERM
    tar --quoting-style=literal -tzf "$ARCHIVE_PATH" | awk '
        {
            name=$0; sub(/^\.\//, "", name)
            if (name == "" || name == "databases/") next
            if (name == "account-home.tar.gz" || name == "instance-state.tar.gz" || name == "database-users.sql.gz" || name == "postgresql-roles.tsv" || name == "metadata.txt" || name == "SHA256SUMS") next
            if (name ~ /^databases\/mariadb--xp_[a-f0-9]{6}_[a-z0-9_]{1,54}\.sql\.gz$/) next
            if (name ~ /^databases\/postgresql--xp_[a-f0-9]{6}_[a-z0-9_]{1,54}\.dump$/) next
            if (name ~ /^databases\/mongodb--xp_[a-f0-9]{6}_[a-z0-9_]{1,54}\.archive$/) next
            bad=1
        }
        END { exit bad ? 1 : 0 }
    ' || fail "retention archive contains an unexpected path"
    tar -tvzf "$ARCHIVE_PATH" | awk '$1 !~ /^[d-]/ { bad=1 } END { exit bad ? 1 : 0 }' \
        || fail "retention archive contains links or special files"
    tar --no-same-owner --no-same-permissions -C "$VERIFY_DIR" -xzf "$ARCHIVE_PATH"
    [[ -f "$VERIFY_DIR/account-home.tar.gz" && -f "$VERIFY_DIR/instance-state.tar.gz" && -f "$VERIFY_DIR/database-users.sql.gz" && -f "$VERIFY_DIR/postgresql-roles.tsv" ]] || fail "retention archive is incomplete"
    [[ -f "$VERIFY_DIR/metadata.txt" && -f "$VERIFY_DIR/SHA256SUMS" ]] || fail "retention archive metadata is missing"
    grep -qxF "uuid=$UUID" "$VERIFY_DIR/metadata.txt" || fail "retention archive belongs to another instance"
    grep -qxF "format=3" "$VERIFY_DIR/metadata.txt" || fail "retention archive format cannot restore all database identities"
    (
        cd "$VERIFY_DIR"
        sha256sum --check SHA256SUMS >/dev/null
    ) || fail "an internal retention component failed its checksum"
    gzip -t "$VERIFY_DIR/account-home.tar.gz" "$VERIFY_DIR/instance-state.tar.gz" "$VERIFY_DIR/database-users.sql.gz"
    while IFS= read -r -d '' DATABASE_DUMP; do gzip -t "$DATABASE_DUMP"; done < <(find "$VERIFY_DIR/databases" -type f -name '*.sql.gz' -print0)
    while IFS= read -r -d '' DATABASE_DUMP; do runuser -u postgres -- pg_restore --list < "$DATABASE_DUMP" >/dev/null; done < <(find "$VERIFY_DIR/databases" -type f -name 'postgresql--*.dump' -print0)
    while IFS= read -r -d '' DATABASE_DUMP; do gzip -t "$DATABASE_DUMP"; done < <(find "$VERIFY_DIR/databases" -type f -name 'mongodb--*.archive' -print0)
    awk -F '\t' 'NF != 2 || $1 !~ /^xp_[a-f0-9]{6}_[a-z0-9_]{1,22}$/ || $2 !~ /^[A-Za-z0-9+\/=]+$/ { bad=1 } END { exit bad ? 1 : 0 }' "$VERIFY_DIR/postgresql-roles.tsv" \
        || fail "retention archive contains invalid PostgreSQL role metadata"
    printf 'verified=%s\n' "$ACTUAL_SHA256"
    exit 0
fi

if [[ "$ACTION" == "purge" ]]; then
    [[ $# -eq 5 ]] || fail "purge expects UUID, system user, project ID, archive path and SHA-256"
    UUID="$1"; SYSTEM_USER="$2"; PROJECT_ID="$3"; ARCHIVE_PATH="$4"; ARCHIVE_SHA256="$5"
    validate_uuid "$UUID"
    UUID_HEX="${UUID//-/}"
    [[ "$SYSTEM_USER" == "xhi${UUID_HEX:0:12}" ]] || fail "invalid instance user"
    [[ "$PROJECT_ID" =~ ^[0-9]+$ ]] && (( PROJECT_ID >= 100000 && PROJECT_ID <= 4294967294 )) || fail "invalid project ID"
    [[ "$ARCHIVE_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "invalid archive SHA-256"
    COMPLETED_ROOT="/var/lib/xpanel-vps/purged"
    COMPLETED_MARKER="$COMPLETED_ROOT/$UUID"
    if [[ -f "$COMPLETED_MARKER" && ! -L "$COMPLETED_MARKER" ]]; then
        grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$COMPLETED_MARKER" || fail "purge marker belongs to a different archive"
        printf 'purged=%s\n' "$UUID"
        exit 0
    fi

    "$0" archive-verify "$UUID" "$ARCHIVE_PATH" "$ARCHIVE_SHA256" >/dev/null
    INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
    ACCOUNT_HOME="/home/$SYSTEM_USER"
    DATABASE_FILE="$INSTANCE_ROOT/database/database.sqlite"
    PURGE_STATE="/var/lib/xpanel-vps/purge-state/$UUID"
    install -d -o root -g root -m 0700 /var/lib/xpanel-vps/purge-state "$PURGE_STATE"

    if [[ ! -f "$PURGE_STATE/prepared" ]]; then
        [[ -d "$INSTANCE_ROOT" && ! -L "$INSTANCE_ROOT" ]] || fail "instance root is unavailable before purge preparation"
        [[ -f "$DATABASE_FILE" && ! -L "$DATABASE_FILE" ]] || fail "instance database catalog is unavailable before purge"
        [[ -f "$INSTANCE_ROOT/runtime.sh" && ! -L "$INSTANCE_ROOT/runtime.sh" ]] || fail "instance runtime is unavailable before purge"
        install -o root -g root -m 0600 "$INSTANCE_ROOT/runtime.sh" "$PURGE_STATE/runtime.sh"
        php -r '
            $db = new PDO("sqlite:".$argv[1]);
            if (!$db->query("SELECT 1 FROM sqlite_master WHERE type = \x27table\x27 AND name = \x27sites\x27")->fetchColumn()) exit(0);
            foreach ($db->query("SELECT domain, web_server, type, php_version, document_root, system_user FROM sites ORDER BY id DESC") as $row) {
                echo implode("\t", array_map("base64_encode", [$row["domain"], $row["web_server"], $row["type"], $row["php_version"], $row["document_root"], $row["system_user"]]))."\n";
            }
        ' "$DATABASE_FILE" > "$PURGE_STATE/sites.tsv" || fail "could not inventory instance sites"
        php -r '
            $db = new PDO("sqlite:".$argv[1]);
            if (!$db->query("SELECT 1 FROM sqlite_master WHERE type = \x27table\x27 AND name = \x27site_databases\x27")->fetchColumn()) exit(0);
            $columns = array_column($db->query("PRAGMA table_info(site_databases)")->fetchAll(PDO::FETCH_ASSOC), "name");
            $engine = in_array("engine", $columns, true) ? "COALESCE(engine, \x27mariadb\x27)" : "\x27mariadb\x27";
            foreach ($db->query("SELECT ".$engine." AS engine, name, username FROM site_databases ORDER BY id") as $row) {
                echo base64_encode($row["engine"])."\t".base64_encode($row["name"])."\t".base64_encode($row["username"])."\n";
            }
        ' "$DATABASE_FILE" > "$PURGE_STATE/databases.tsv" || fail "could not inventory instance databases"
        chmod 0600 "$PURGE_STATE/runtime.sh" "$PURGE_STATE/sites.tsv" "$PURGE_STATE/databases.tsv"
        printf 'uuid=%s\nsystem_user=%s\nproject_id=%s\narchive_sha256=%s\n' "$UUID" "$SYSTEM_USER" "$PROJECT_ID" "$ARCHIVE_SHA256" > "$PURGE_STATE/prepared"
        chmod 0600 "$PURGE_STATE/prepared"
    else
        grep -qxF "uuid=$UUID" "$PURGE_STATE/prepared" || fail "purge state belongs to another instance"
        grep -qxF "system_user=$SYSTEM_USER" "$PURGE_STATE/prepared" || fail "purge state has another system user"
        grep -qxF "project_id=$PROJECT_ID" "$PURGE_STATE/prepared" || fail "purge state has another project ID"
        grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$PURGE_STATE/prepared" || fail "purge state belongs to another archive"
    fi

    # shellcheck disable=SC1090
    source "$PURGE_STATE/runtime.sh"
    [[ "${XPANEL_INSTANCE_ID:-}" == "$UUID" && "${XPANEL_ACCOUNT_USER:-}" == "$SYSTEM_USER" && "${XPANEL_ACCOUNT_HOME:-}" == "$ACCOUNT_HOME" ]] \
        || fail "purge runtime does not belong to this instance"
    HOST_HELPER="/opt/xpanel-host/current/scripts/xpanel-site-helper.sh"
    [[ -f "$HOST_HELPER" && ! -L "$HOST_HELPER" ]] || fail "current Host helper is unavailable"

    while IFS=$'\t' read -r B_DOMAIN B_ENGINE B_TYPE B_PHP B_ROOT B_USER; do
        [[ -n "$B_DOMAIN" ]] || continue
        DOMAIN="$(printf %s "$B_DOMAIN" | base64 -d)"
        ENGINE="$(printf %s "$B_ENGINE" | base64 -d)"
        TYPE="$(printf %s "$B_TYPE" | base64 -d)"
        PHP_VERSION="$(printf %s "$B_PHP" | base64 -d)"
        DOCUMENT_ROOT="$(printf %s "$B_ROOT" | base64 -d)"
        SITE_USER="$(printf %s "$B_USER" | base64 -d)"
        [[ "$DOMAIN" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ && "$DOMAIN" == *.* && "$DOMAIN" != *..* ]] || fail "unsafe site domain in purge state"
        [[ "$ENGINE" =~ ^(nginx|apache|openlitespeed)$ && "$TYPE" =~ ^(php|static|node)$ && "$PHP_VERSION" =~ ^8\.[1-4]$ ]] || fail "unsafe site runtime in purge state"
        [[ "$DOCUMENT_ROOT" == "$ACCOUNT_HOME/public_html/"* && "$DOCUMENT_ROOT" != *".."* ]] || fail "site root escaped the account during purge"
        [[ "$SITE_USER" =~ ^xps${UUID_HEX:0:6}[a-z0-9]{9,23}$ ]] || fail "site identity escaped the instance during purge"
        bash "$HOST_HELPER" remove "$DOMAIN" "$ENGINE" "$TYPE" "$PHP_VERSION" "$DOCUMENT_ROOT" "$SITE_USER"
        bash "$HOST_HELPER" access-remove "$SITE_USER" "$DOCUMENT_ROOT"
        if [[ -f "/etc/letsencrypt/renewal/$DOMAIN.conf" && ! -L "/etc/letsencrypt/renewal/$DOMAIN.conf" ]]; then
            certbot delete --non-interactive --cert-name "$DOMAIN" >/dev/null
        fi
        rm -f -- "/var/log/nginx/$DOMAIN-access.log" "/var/log/nginx/$DOMAIN-error.log"
    done < "$PURGE_STATE/sites.tsv"

    while IFS=$'\t' read -r B_DB_ENGINE B_DATABASE B_USERNAME; do
        [[ -n "$B_DATABASE" ]] || continue
        DB_ENGINE="$(printf %s "$B_DB_ENGINE" | base64 -d)"
        DATABASE="$(printf %s "$B_DATABASE" | base64 -d)"
        DB_USER="$(printf %s "$B_USERNAME" | base64 -d)"
        [[ "$DB_ENGINE" == mariadb || "$DB_ENGINE" == postgresql || "$DB_ENGINE" == mongodb ]] || fail "unsupported database engine during purge"
        [[ "$DATABASE" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance during purge"
        [[ "$DB_USER" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,22}$ ]] || fail "database user escaped the instance during purge"
        bash "$HOST_HELPER" database-remove "$DB_ENGINE" "$DATABASE" "$DB_USER"
    done < "$PURGE_STATE/databases.tsv"

    # Publish an empty allowlist before removing the instance state so stale
    # database ports are not left open for a purged account.
    install -d -o "$SYSTEM_USER" -g "$SYSTEM_USER" -m 0750 "$INSTANCE_ROOT/storage/app/databases"
    : > "$INSTANCE_ROOT/storage/app/databases/remote-hosts"
    chown "$SYSTEM_USER:$SYSTEM_USER" "$INSTANCE_ROOT/storage/app/databases/remote-hosts"
    chmod 0640 "$INSTANCE_ROOT/storage/app/databases/remote-hosts"
    bash "$HOST_HELPER" database-remote-sync

    for UNIT in "xpanel-instance-$UUID-scheduler.timer" "xpanel-instance-$UUID-scheduler.service" "xpanel-instance-$UUID-apache.service" "xpanel-instance-$UUID-fpm.service"; do
        systemctl disable --now "$UNIT" >/dev/null 2>&1 || true
        rm -f -- "/etc/systemd/system/$UNIT"
    done
    TLS_SNIPPET="/etc/nginx/snippets/xpanel-instance-$UUID-tls.conf"
    if [[ -f "$TLS_SNIPPET" && ! -L "$TLS_SNIPPET" ]]; then
        PANEL_CERT_NAME="$(sed -n 's#^[[:space:]]*ssl_certificate /etc/letsencrypt/live/\([^/]*\)/fullchain\.pem;[[:space:]]*$#\1#p' "$TLS_SNIPPET" | head -n1)"
        if [[ -n "$PANEL_CERT_NAME" ]]; then
            [[ "$PANEL_CERT_NAME" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ && "$PANEL_CERT_NAME" == *.* && "$PANEL_CERT_NAME" != *..* ]] || fail "unsafe panel certificate name"
            [[ ! -f "/etc/letsencrypt/renewal/$PANEL_CERT_NAME.conf" ]] || certbot delete --non-interactive --cert-name "$PANEL_CERT_NAME" >/dev/null
        fi
    fi
    rm -f -- "/etc/nginx/sites-enabled/xpanel-instance-$UUID.conf" "/etc/nginx/sites-available/xpanel-instance-$UUID.conf" "/etc/nginx/snippets/xpanel-instance-$UUID-tls.conf" "/etc/nginx/snippets/xpanel-traffic-$UUID.conf"
    systemctl disable --now "xpanel-dbgate-$UUID.service" >/dev/null 2>&1 || true
    rm -f -- "/etc/systemd/system/xpanel-dbgate-$UUID.service" "/etc/xpanel-host/remote-databases/$UUID.tsv"
    rm -rf -- "/etc/systemd/system/xpanel-instance-$UUID.slice.d"

    ACCOUNT_JAIL="/var/lib/xpanel-host/jails/$SYSTEM_USER"
    ACCOUNT_JAIL_UNIT="xpanel-host-jail-$SYSTEM_USER.service"
    systemctl stop "$ACCOUNT_JAIL_UNIT" >/dev/null 2>&1 || true
    if [[ -f "/opt/xpanel-host/current/scripts/xpanel-jail-unmount.sh" ]]; then bash "/opt/xpanel-host/current/scripts/xpanel-jail-unmount.sh" "$ACCOUNT_JAIL"; fi
    systemctl disable "$ACCOUNT_JAIL_UNIT" >/dev/null 2>&1 || true
    rm -f -- "/etc/systemd/system/$ACCOUNT_JAIL_UNIT" "/etc/ssh/sshd_config.d/90-xpanel-$SYSTEM_USER.conf"
    if [[ -d "$ACCOUNT_JAIL" ]]; then
        while IFS= read -r MOUNT_TARGET; do
            [[ "$MOUNT_TARGET" != "$ACCOUNT_JAIL" && "$MOUNT_TARGET" != "$ACCOUNT_JAIL/"* ]] || fail "account jail still has live mounts"
        done < <(findmnt -n -o TARGET 2>/dev/null)
    fi
    rm -rf -- "/var/lib/xpanel-host/ssh/$SYSTEM_USER" "$ACCOUNT_JAIL"
    if [[ -f /etc/xpanel-host/ftp-users ]]; then
        grep -vxF "$SYSTEM_USER" /etc/xpanel-host/ftp-users > /etc/xpanel-host/ftp-users.tmp || true
        mv /etc/xpanel-host/ftp-users.tmp /etc/xpanel-host/ftp-users
        chmod 0600 /etc/xpanel-host/ftp-users
    fi

    bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-project-quota.sh" remove "$UUID" "$SYSTEM_USER" "$PROJECT_ID"
    rm -rf -- "$ACCOUNT_HOME" "$INSTANCE_ROOT" "/etc/xpanel-vps/instances/$UUID" "/var/lib/xpanel-vps/metrics/$UUID" "/var/www/xpanel-instances/$UUID"
    id "$SYSTEM_USER" >/dev/null 2>&1 && userdel "$SYSTEM_USER"
    getent group "$SYSTEM_USER" >/dev/null 2>&1 && groupdel "$SYSTEM_USER"
    systemctl daemon-reload
    nginx -t
    systemctl reload nginx
    sshd -t
    systemctl reload ssh 2>/dev/null || systemctl reload sshd
    install -d -o root -g root -m 0700 "$COMPLETED_ROOT"
    rm -f -- "/var/lib/xpanel-vps/restored/$UUID"
    printf 'uuid=%s\narchive_sha256=%s\npurged_at=%s\n' "$UUID" "$ARCHIVE_SHA256" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$COMPLETED_MARKER"
    chmod 0600 "$COMPLETED_MARKER"
    bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-mail-reconcile.sh" sync
    bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-mail-reconcile.sh" outbound-sync
    sudo -u "$SYSTEM_USER" env HOME="$INSTANCE_ROOT" "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:mail-sync --mark-active --no-interaction
    rm -rf -- "$PURGE_STATE"
    printf 'purged=%s\n' "$UUID"
    exit 0
fi

if [[ "$ACTION" == "restore" ]]; then
    [[ $# -eq 5 ]] || fail "restore expects UUID, system user, project ID, archive path and SHA-256"
    UUID="$1"; SYSTEM_USER="$2"; PROJECT_ID="$3"; ARCHIVE_PATH="$4"; ARCHIVE_SHA256="$5"
    validate_uuid "$UUID"
    UUID_HEX="${UUID//-/}"
    [[ "$SYSTEM_USER" == "xhi${UUID_HEX:0:12}" ]] || fail "invalid instance user"
    [[ "$PROJECT_ID" =~ ^[0-9]+$ ]] && (( PROJECT_ID >= 100000 && PROJECT_ID <= 4294967294 )) || fail "invalid project ID"
    [[ "$ARCHIVE_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "invalid archive SHA-256"

    COMPLETED_MARKER="/var/lib/xpanel-vps/purged/$UUID"
    RESTORED_MARKER="/var/lib/xpanel-vps/restored/$UUID"
    if [[ -f "$RESTORED_MARKER" && ! -L "$RESTORED_MARKER" ]]; then
        grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$RESTORED_MARKER" || fail "restore marker belongs to a different archive"
        printf 'restored=%s\n' "$UUID"
        exit 0
    fi
    [[ -f "$COMPLETED_MARKER" && ! -L "$COMPLETED_MARKER" ]] || fail "the instance has no completed purge marker"
    grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$COMPLETED_MARKER" || fail "purge marker belongs to a different archive"
    "$0" archive-verify "$UUID" "$ARCHIVE_PATH" "$ARCHIVE_SHA256" >/dev/null

    INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
    ACCOUNT_HOME="/home/$SYSTEM_USER"
    RESTORE_ROOT="/var/lib/xpanel-vps/restore-state"
    RESTORE_STATE="$RESTORE_ROOT/$UUID"
    install -d -o root -g root -m 0700 "$RESTORE_ROOT" "$RESTORE_STATE"
    if [[ -f "$RESTORE_STATE/extracted" && ! -L "$RESTORE_STATE/extracted" ]]; then
        grep -qxF "uuid=$UUID" "$RESTORE_STATE/extracted" || fail "restore state belongs to another instance"
        grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$RESTORE_STATE/extracted" || fail "restore state belongs to another archive"
        printf 'restored=%s\n' "$UUID"
        exit 0
    fi

    # A failed extraction may leave only these exact, validated identities.
    # Remove them before retrying; never follow paths supplied by archive data.
    rm -rf -- "$ACCOUNT_HOME" "$INSTANCE_ROOT"
    if id "$SYSTEM_USER" >/dev/null 2>&1; then
        pkill -KILL -u "$SYSTEM_USER" >/dev/null 2>&1 || true
        userdel "$SYSTEM_USER" >/dev/null 2>&1 || true
    fi
    getent group "$SYSTEM_USER" >/dev/null 2>&1 && groupdel "$SYSTEM_USER" >/dev/null 2>&1 || true

    WORK_DIR="$(mktemp -d "$RESTORE_STATE/.work.XXXXXX")"
    HOME_STAGE="$WORK_DIR/home"
    INSTANCE_STAGE="$WORK_DIR/instance"
    OUTER_STAGE="$WORK_DIR/archive"
    install -d -o root -g root -m 0700 "$HOME_STAGE" "$INSTANCE_STAGE" "$OUTER_STAGE"
    cleanup_restore_work() { rm -rf -- "$WORK_DIR"; }
    trap cleanup_restore_work EXIT INT TERM
    tar --no-same-owner --no-same-permissions -C "$OUTER_STAGE" -xzf "$ARCHIVE_PATH"

    validate_inner_archive() {
        local inner_archive="$1" expected_root="$2"
        python3 - "$inner_archive" "$expected_root" <<'PY' || fail "retention component contains an unsafe path, link or file type"
import posixpath
import sys
import tarfile

archive, expected_root = sys.argv[1:]
with tarfile.open(archive, "r:gz") as bundle:
    for member in bundle.getmembers():
        name = member.name
        while name.startswith("./"):
            name = name[2:]
        name = name.rstrip("/")
        if (not name or name.startswith("/") or ".." in name.split("/")):
            raise SystemExit(1)
        if name != expected_root and not name.startswith(expected_root + "/"):
            raise SystemExit(1)
        if not (member.isfile() or member.isdir() or member.issym()):
            raise SystemExit(1)
        if member.issym():
            target = member.linkname
            if not target or target.startswith("/"):
                raise SystemExit(1)
            resolved = posixpath.normpath(posixpath.join(posixpath.dirname(name), target))
            if resolved != expected_root and not resolved.startswith(expected_root + "/"):
                raise SystemExit(1)
PY
    }
    validate_inner_archive "$OUTER_STAGE/account-home.tar.gz" "$SYSTEM_USER"
    validate_inner_archive "$OUTER_STAGE/instance-state.tar.gz" "$UUID"
    tar --no-same-owner --no-same-permissions --delay-directory-restore -C "$HOME_STAGE" -xzf "$OUTER_STAGE/account-home.tar.gz"
    tar --no-same-owner --no-same-permissions --delay-directory-restore -C "$INSTANCE_STAGE" -xzf "$OUTER_STAGE/instance-state.tar.gz"

    [[ -d "$HOME_STAGE/$SYSTEM_USER" && ! -L "$HOME_STAGE/$SYSTEM_USER" ]] || fail "restored account home is unavailable or unsafe"
    [[ -d "$INSTANCE_STAGE/$UUID" && ! -L "$INSTANCE_STAGE/$UUID" ]] || fail "restored instance root is unavailable or unsafe"
    while IFS= read -r -d '' RESTORED_LINK; do
        LINK_ROOT="$HOME_STAGE/$SYSTEM_USER"
        [[ "$RESTORED_LINK" == "$INSTANCE_STAGE/$UUID/"* ]] && LINK_ROOT="$INSTANCE_STAGE/$UUID"
        LINK_TARGET="$(readlink -- "$RESTORED_LINK")"
        [[ "$LINK_TARGET" != /* ]] || fail "retention component contains an absolute symbolic link"
        RESOLVED_TARGET="$(realpath -m -- "$(dirname -- "$RESTORED_LINK")/$LINK_TARGET")"
        [[ "$RESOLVED_TARGET" == "$LINK_ROOT" || "$RESOLVED_TARGET" == "$LINK_ROOT/"* ]] \
            || fail "retention component contains a symbolic link outside its root"
    done < <(find -P "$HOME_STAGE/$SYSTEM_USER" "$INSTANCE_STAGE/$UUID" -xdev -type l -print0)

    if ! getent group "$SYSTEM_USER" >/dev/null 2>&1; then
        groupadd --system "$SYSTEM_USER"
    fi
    if ! id "$SYSTEM_USER" >/dev/null 2>&1; then
        useradd --system --home-dir "$INSTANCE_ROOT" --shell /usr/sbin/nologin --gid "$SYSTEM_USER" "$SYSTEM_USER"
    fi
    mv -- "$HOME_STAGE/$SYSTEM_USER" "$ACCOUNT_HOME"
    mv -- "$INSTANCE_STAGE/$UUID" "$INSTANCE_ROOT"
    chown -R "$SYSTEM_USER:$SYSTEM_USER" "$ACCOUNT_HOME" "$INSTANCE_ROOT"
    chmod 0750 "$ACCOUNT_HOME" "$INSTANCE_ROOT"

    # Recreate PostgreSQL identities before restoring their owned databases.
    while IFS=$'\t' read -r DB_USER ROLE_HASH_B64; do
        [[ -n "$DB_USER" ]] || continue
        [[ "$DB_USER" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,22}$ && "$ROLE_HASH_B64" =~ ^[A-Za-z0-9+/=]+$ ]] || fail "PostgreSQL identity escaped the instance"
        ROLE_HASH="$(printf %s "$ROLE_HASH_B64" | base64 -d)"
        [[ "$ROLE_HASH" =~ ^SCRAM-SHA-256\$[0-9]+:[A-Za-z0-9+/=]+\$[A-Za-z0-9+/=]+:[A-Za-z0-9+/=]+$ ]] || fail "invalid PostgreSQL password representation"
        if runuser -u postgres -- psql -Atqc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -qx 1; then
            runuser -u postgres -- psql --set=ON_ERROR_STOP=1 --dbname=postgres -c "ALTER ROLE \"$DB_USER\" PASSWORD '$ROLE_HASH';"
        else
            runuser -u postgres -- psql --set=ON_ERROR_STOP=1 --dbname=postgres -c "CREATE ROLE \"$DB_USER\" LOGIN PASSWORD '$ROLE_HASH' NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;"
        fi
    done < "$OUTER_STAGE/postgresql-roles.tsv"

    # Database names are derived from the trusted archive layout and constrained
    # to this UUID namespace. Drop first so retries never merge partial data.
    while IFS= read -r -d '' DATABASE_DUMP; do
        DATABASE="$(basename -- "$DATABASE_DUMP" .sql.gz)"
        DATABASE="${DATABASE#mariadb--}"
        [[ "$DATABASE" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance during restore"
        mariadb --protocol=socket -e "DROP DATABASE IF EXISTS \`$DATABASE\`;"
        gzip -dc -- "$DATABASE_DUMP" | mariadb --protocol=socket
    done < <(find "$OUTER_STAGE/databases" -maxdepth 1 -type f -name '*.sql.gz' -print0 | sort -z)
    while IFS= read -r -d '' DATABASE_DUMP; do
        DATABASE="$(basename -- "$DATABASE_DUMP" .dump)"; DATABASE="${DATABASE#postgresql--}"
        [[ "$DATABASE" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,54}$ ]] || fail "PostgreSQL database escaped the instance during restore"
        DB_USER="$(php -r '
            $db=new PDO("sqlite:".$argv[1]);
            $s=$db->prepare("SELECT username FROM site_databases WHERE name=:name LIMIT 1");
            $s->execute(["name"=>$argv[2]]); echo $s->fetchColumn() ?: "";
        ' "$INSTANCE_ROOT/database/database.sqlite" "$DATABASE")"
        [[ "$DB_USER" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,22}$ ]] || fail "PostgreSQL owner escaped the instance during restore"
        runuser -u postgres -- psql --dbname=postgres -Atqc "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='$DATABASE' AND pid <> pg_backend_pid()" >/dev/null
        runuser -u postgres -- dropdb --if-exists "$DATABASE"
        runuser -u postgres -- createdb --owner="$DB_USER" "$DATABASE"
        runuser -u postgres -- pg_restore --exit-on-error --no-owner --role="$DB_USER" --dbname="$DATABASE" < "$DATABASE_DUMP"
    done < <(find "$OUTER_STAGE/databases" -maxdepth 1 -type f -name 'postgresql--*.dump' -print0 | sort -z)
    while IFS= read -r -d '' DATABASE_DUMP; do
        DATABASE="$(basename -- "$DATABASE_DUMP" .archive)"; DATABASE="${DATABASE#mongodb--}"
        [[ "$DATABASE" =~ ^xp_${UUID_HEX:0:6}_[a-z0-9_]{1,54}$ ]] || fail "MongoDB database escaped the instance during restore"
        [[ -f /etc/xpanel-host/mongodb-admin.secret && ! -L /etc/xpanel-host/mongodb-admin.secret ]] || fail "MongoDB management credentials are unavailable"
        mongorestore --quiet --host 127.0.0.1 --username xpanel_system --password "$(cat /etc/xpanel-host/mongodb-admin.secret)" --authenticationDatabase admin \
            --drop --gzip --restoreDbUsersAndRoles --archive="$DATABASE_DUMP" --nsInclude="$DATABASE.*"
    done < <(find "$OUTER_STAGE/databases" -maxdepth 1 -type f -name 'mongodb--*.archive' -print0 | sort -z)

    DATABASE_USERS_SQL="$WORK_DIR/database-users.sql"
    gzip -dc -- "$OUTER_STAGE/database-users.sql.gz" > "$DATABASE_USERS_SQL"
    awk '
        /^[[:space:]]*$/ { next }
        /^CREATE OR REPLACE USER / { next }
        /^GRANT / { next }
        { bad=1 }
        END { exit bad ? 1 : 0 }
    ' "$DATABASE_USERS_SQL" || fail "database identity archive contains an unexpected statement"
    [[ ! -s "$DATABASE_USERS_SQL" ]] || mariadb --protocol=socket < "$DATABASE_USERS_SQL"

    if [[ -d "$ACCOUNT_HOME/mail" ]] && id vmail >/dev/null 2>&1; then
        chown -R vmail:vmail "$ACCOUNT_HOME/mail"
        setfacl -R -m "u:$SYSTEM_USER:rwX" "$ACCOUNT_HOME/mail"
        find -P "$ACCOUNT_HOME/mail" -xdev -type d -exec setfacl -m "m::rwx,d:u:$SYSTEM_USER:rwx,d:m::rwx" {} +
    fi
    printf 'uuid=%s\narchive_sha256=%s\nextracted_at=%s\n' "$UUID" "$ARCHIVE_SHA256" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$RESTORE_STATE/extracted"
    chmod 0600 "$RESTORE_STATE/extracted"
    printf 'restored=%s\n' "$UUID"
    exit 0
fi

if [[ "$ACTION" == "restore-sync" ]]; then
    [[ $# -eq 4 ]] || fail "restore-sync expects UUID, system user, release path and PHP version"
    UUID="$1"; SYSTEM_USER="$2"; RELEASE_PATH="$3"; PHP_VERSION="$4"
    validate_uuid "$UUID"
    UUID_HEX="${UUID//-/}"
    [[ "$SYSTEM_USER" == "xhi${UUID_HEX:0:12}" ]] || fail "invalid instance user"
    [[ "$RELEASE_PATH" =~ ^/opt/xpanel-host/(current|releases/[A-Za-z0-9._-]+)$ ]] || fail "invalid release path"
    [[ "$PHP_VERSION" =~ ^8\.[2-4]$ ]] || fail "unsupported PHP version"
    PHP_BIN="/usr/bin/php$PHP_VERSION"
    INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
    [[ -x "$PHP_BIN" && -f "$RELEASE_PATH/artisan" ]] || fail "Host runtime is unavailable"
    [[ -f "$INSTANCE_ROOT/runtime.sh" && ! -L "$INSTANCE_ROOT/runtime.sh" ]] || fail "restored instance runtime is unavailable"
    if [[ -f "/var/lib/xpanel-vps/restored/$UUID" && ! -L "/var/lib/xpanel-vps/restored/$UUID" ]]; then
        printf 'synchronized=%s\n' "$UUID"
        exit 0
    fi
    [[ -f "/var/lib/xpanel-vps/restore-state/$UUID/extracted" ]] || fail "instance extraction has not completed"
    [[ -f "/var/lib/xpanel-vps/purged/$UUID" || -f "/var/lib/xpanel-vps/restored/$UUID" ]] \
        || fail "lifecycle marker disappeared before synchronization"
    # shellcheck disable=SC1090
    source "$INSTANCE_ROOT/runtime.sh"
    [[ "${XPANEL_INSTANCE_ID:-}" == "$UUID" && "${XPANEL_ACCOUNT_USER:-}" == "$SYSTEM_USER" ]] \
        || fail "restored runtime does not belong to this instance"

    sudo -u "$SYSTEM_USER" env HOME="$INSTANCE_ROOT" "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:sites-sync --no-interaction
    sudo -u "$SYSTEM_USER" env HOME="$INSTANCE_ROOT" "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:access-sync --no-interaction
    sudo -u "$SYSTEM_USER" env HOME="$INSTANCE_ROOT" "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:ssl-sync --no-interaction
    sudo -u "$SYSTEM_USER" env HOME="$INSTANCE_ROOT" "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:mail-sync --stage-only --no-interaction
    HOST_HELPER="$RELEASE_PATH/scripts/xpanel-site-helper.sh"
    [[ -f "$HOST_HELPER" && ! -L "$HOST_HELPER" ]] || fail "Host helper is unavailable after restore"
    if [[ -f "$INSTANCE_ROOT/storage/app/databases/remote-hosts" ]]; then
        bash "$HOST_HELPER" database-remote-sync
    fi
    if [[ -f "$INSTANCE_ROOT/storage/app/dbgate/runtime.env" ]]; then
        bash "$HOST_HELPER" dbgate-sync
    fi
    bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-mail-reconcile.sh" sync
    bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-mail-reconcile.sh" outbound-sync
    printf 'synchronized=%s\n' "$UUID"
    exit 0
fi

if [[ "$ACTION" == "restore-complete" ]]; then
    [[ $# -eq 2 ]] || fail "restore-complete expects UUID and archive SHA-256"
    UUID="$1"; ARCHIVE_SHA256="$2"
    validate_uuid "$UUID"
    [[ "$ARCHIVE_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "invalid archive SHA-256"
    COMPLETED_MARKER="/var/lib/xpanel-vps/purged/$UUID"
    RESTORE_MARKER="/var/lib/xpanel-vps/restore-state/$UUID/extracted"
    RESTORED_ROOT="/var/lib/xpanel-vps/restored"
    RESTORED_MARKER="$RESTORED_ROOT/$UUID"
    if [[ -f "$RESTORED_MARKER" && ! -L "$RESTORED_MARKER" ]]; then
        grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$RESTORED_MARKER" || fail "restore marker belongs to another archive"
        printf 'completed=%s\n' "$UUID"
        exit 0
    fi
    [[ -f "$COMPLETED_MARKER" && ! -L "$COMPLETED_MARKER" ]] || fail "purge marker is unavailable"
    [[ -f "$RESTORE_MARKER" && ! -L "$RESTORE_MARKER" ]] || fail "restore marker is unavailable"
    grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$COMPLETED_MARKER" || fail "purge marker belongs to another archive"
    grep -qxF "archive_sha256=$ARCHIVE_SHA256" "$RESTORE_MARKER" || fail "restore marker belongs to another archive"
    install -d -o root -g root -m 0700 "$RESTORED_ROOT"
    {
        printf 'uuid=%s\narchive_sha256=%s\nrestored_at=%s\n' "$UUID" "$ARCHIVE_SHA256" "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    } > "$RESTORED_MARKER.tmp"
    chmod 0600 "$RESTORED_MARKER.tmp"
    mv -f -- "$RESTORED_MARKER.tmp" "$RESTORED_MARKER"
    rm -f -- "$COMPLETED_MARKER"
    rm -rf -- "/var/lib/xpanel-vps/restore-state/$UUID"
    printf 'completed=%s\n' "$UUID"
    exit 0
fi

if [[ "$ACTION" == "initial-site-create" ]]; then
    [[ $# -eq 3 ]] || fail "initial-site-create expects UUID, domain and release"
    UUID="$1"
    DOMAIN="$2"
    validate_uuid "$UUID"
    [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid initial site domain"
    INSTANCE_ROOT="/var/lib/xpanel-vps/instances/$UUID"
    [[ -f "$INSTANCE_ROOT/runtime.sh" && ! -L "$INSTANCE_ROOT/runtime.sh" ]] || fail "instance runtime unavailable"
    # shellcheck disable=SC1090
    source "$INSTANCE_ROOT/runtime.sh"
    [[ "${XPANEL_INSTANCE_ID:-}" == "$UUID" && "${XPANEL_MANAGEMENT_MODE:-}" == "vps-instance" ]] || fail "instance identity mismatch"
    [[ "${XPANEL_ACCOUNT_USER:-}" =~ ^xhi[a-f0-9]{12}$ ]] || fail "invalid instance user"
    [[ "${XPANEL_ACCOUNT_HOME:-}" == "/home/$XPANEL_ACCOUNT_USER" ]] || fail "invalid account home"
    RELEASE_PATH="$(readlink -f "$3" 2>/dev/null || true)"
    [[ "$RELEASE_PATH" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ ]] || fail "Host release unavailable"
    [[ "$RELEASE_PATH" == "${XPANEL_HOST_RELEASE_PATH:-}" ]] || fail "Host release differs from the instance"
    [[ -f "$RELEASE_PATH/artisan" ]] || fail "Host artisan unavailable"
    [[ "${XPANEL_HOST_PHP_VERSION:-}" =~ ^8\.[0-9]+$ ]] || fail "invalid Host PHP version"
    PHP_BIN="/usr/bin/php$XPANEL_HOST_PHP_VERSION"
    [[ -x "$PHP_BIN" ]] || fail "Host PHP CLI unavailable"
    exec runuser -u "$XPANEL_ACCOUNT_USER" --preserve-environment -- "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:initial-site "$DOMAIN" --no-interaction
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
# Repair installations created with a restrictive root umask. Host code is
# immutable and world-readable by design; isolated users need only traversal.
install -d -o root -g root -m 0755 /opt/xpanel-host /opt/xpanel-host/releases
chmod 0755 /opt/xpanel-host /opt/xpanel-host/releases "$RELEASE_PATH"
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
bash "$(dirname "${BASH_SOURCE[0]}")/xpanel-traffic-rate.sh" ensure "$UUID" normal
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
# A pinned Host release can change the site jail profile. Refresh existing
# site terminals as well, otherwise they keep an old HOME until each site's
# SSH setting is saved again.
runuser -u "$SYSTEM_USER" --preserve-environment -- "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:access-sync --no-interaction

if [[ -n "$INITIAL_PASSWORD" ]]; then
    printf '%s\n' "$INITIAL_PASSWORD" | runuser -u "$SYSTEM_USER" --preserve-environment -- \
        "$PHP_BIN" "$RELEASE_PATH/artisan" xpanel:admin-bootstrap \
        --name="$OWNER_NAME" --email="$OWNER_EMAIL" --password-stdin --no-interaction
fi

php-fpm"$PHP_VERSION" -t -y "$FPM_GLOBAL_TARGET"
nginx -t
systemctl daemon-reload
ensure_terminal_agent
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
