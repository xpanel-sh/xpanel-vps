#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-project-quota: $*" >&2; exit 1; }

[[ "$(id -u)" == 0 ]] || fail "root is required"

quota_mount() {
    local home_mount instance_mount database_mount filesystem options features source
    for tool in findmnt tune2fs chattr lsattr setquota quotaon; do
        command -v "$tool" >/dev/null 2>&1 || fail "missing $tool; install quota and e2fsprogs"
    done
    home_mount="$(findmnt -n -o TARGET -T /home)"
    instance_mount="$(findmnt -n -o TARGET -T /var/lib/xpanel-vps)"
    database_mount="$(findmnt -n -o TARGET -T /var/lib/mysql)"
    [[ -n "$home_mount" && "$home_mount" == "$instance_mount" && "$home_mount" == "$database_mount" ]] || fail "/home, /var/lib/xpanel-vps and /var/lib/mysql must be on the same quota-enabled filesystem"
    filesystem="$(findmnt -n -o FSTYPE -T /home)"
    [[ "$filesystem" == ext4 ]] || fail "project quotas currently require ext4 (found $filesystem)"
    options="$(findmnt -n -o OPTIONS -T /home)"
    [[ ",$options," == *,prjquota,* ]] || fail "ext4 is not mounted with prjquota; configure it from rescue and reboot"
    source="$(findmnt -n -o SOURCE -T /home)"
    [[ -b "$source" ]] || fail "could not resolve the ext4 block device"
    features="$(tune2fs -l "$source" | sed -n 's/^Filesystem features:[[:space:]]*//p')"
    [[ " $features " == *' project '* && " $features " == *' quota '* ]] || fail "ext4 project and quota features are missing; enable them while unmounted in rescue mode"
    quota_state="$(quotaon -P -p "$home_mount")" || fail "could not inspect project quota state"
    [[ "$quota_state" == *'is on'* ]] || fail "project quota accounting is not active; enable it before provisioning"
    [[ "$(mariadb --protocol=socket --batch --skip-column-names -e 'SELECT @@innodb_file_per_table')" == 1 ]] || fail "MariaDB innodb_file_per_table is required"
    printf '%s\n' "$home_mount"
}

case "${1:-}" in
    status)
        [[ $# -eq 1 ]] || fail "status takes no arguments"
        quota_mount >/dev/null
        echo ready
        ;;
    apply)
        [[ $# -eq 6 ]] || fail "apply expects UUID, system user, project ID, MiB and inode limit"
        uuid="$2" user="$3" project_id="$4" storage_mib="$5" inode_limit="$6"
        [[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
        [[ "$user" =~ ^xhi[a-f0-9]{12}$ ]] || fail "invalid system user"
        [[ "$project_id" =~ ^[0-9]+$ ]] && (( project_id >= 100000 && project_id <= 4294967294 )) || fail "invalid project ID"
        [[ "$storage_mib" =~ ^[0-9]+$ ]] && (( storage_mib >= 1 && storage_mib <= 100000000 )) || fail "invalid storage limit"
        [[ "$inode_limit" =~ ^[0-9]+$ ]] && (( inode_limit >= 1 && inode_limit <= 1000000000 )) || fail "invalid inode limit"
        mountpoint="$(quota_mount)"
        for path in "/home/$user" "/var/lib/xpanel-vps/instances/$uuid"; do
            [[ -d "$path" && ! -L "$path" ]] || fail "missing or unsafe quota directory: $path"
            [[ "$(findmnt -n -o TARGET -T "$path")" == "$mountpoint" ]] || fail "quota directory has a different filesystem: $path"
            [[ "$(stat -c %U -- "$path")" == "$user" ]] || fail "quota directory has an unexpected owner: $path"
        done
        marker_dir="/etc/xpanel-vps/instances/$uuid"
        marker="$marker_dir/project-quota-applied"
        [[ ! -L "$marker_dir" && ! -L "$marker" ]] || fail "unsafe project quota marker"
        install -d -o root -g root -m 0755 "$marker_dir"
        if [[ -f "$marker" ]]; then
            [[ "$(<"$marker")" == "$project_id" ]] || fail "project quota marker belongs to another project"
        fi
        # Existing trees are migrated once. The marker is written only after
        # all tagging and the hard limit succeed, so interrupted applies retry.
        for path in "/home/$user" "/var/lib/xpanel-vps/instances/$uuid"; do
            current_id="$(lsattr -pd -- "$path" | awk '{print $1}')"
            [[ "$current_id" == 0 || "$current_id" == "$project_id" ]] || fail "quota directory belongs to another project: $path"
            if [[ ! -f "$marker" ]]; then
                find "$path" -xdev \( -type f -o -type d \) -exec chattr -p "$project_id" {} +
                find "$path" -xdev -type d -exec chattr +P {} +
            else
                [[ "$current_id" == "$project_id" ]] || fail "project quota directory lost its project ID: $path"
            fi
        done
        # setquota block limits use KiB; a hard limit is immediate (no grace).
        hard_kib=$((storage_mib * 1024))
        setquota -P "$project_id" 0 "$hard_kib" 0 "$inode_limit" "$mountpoint"
        printf '%s\n' "$project_id" > "$marker"
        chmod 0600 "$marker"
        # An update may introduce quotas after databases already exist. The
        # Host SQLite catalog, not a short name prefix, identifies ownership.
        "$0" reconcile-databases "$uuid" "$user" "$project_id"
        echo "quota-applied"
        ;;
    reconcile-databases|check-databases)
        [[ $# -eq 4 ]] || fail "database reconciliation expects UUID, system user and project ID"
        uuid="$2" user="$3" project_id="$4"
        [[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
        uuid_hex="${uuid//-/}"
        [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "invalid instance user"
        [[ "$project_id" =~ ^[0-9]+$ ]] && (( project_id >= 100000 && project_id <= 4294967294 )) || fail "invalid project ID"
        database_file="/var/lib/xpanel-vps/instances/$uuid/database/database.sqlite"
        [[ ! -L "$database_file" ]] || fail "unsafe Host database catalog"
        [[ -f "$database_file" ]] || { echo "database-catalog-pending"; exit 0; }
        database_names="$(php -r '
            $db = new PDO("sqlite:".$argv[1]);
            if (!$db->query("SELECT 1 FROM sqlite_master WHERE type = \x27table\x27 AND name = \x27site_databases\x27")->fetchColumn()) exit(0);
            foreach ($db->query("SELECT name FROM site_databases WHERE status = \x27active\x27") as $row) echo $row["name"]."\n";
        ' "$database_file")" || fail "could not read Host database catalog"
        while IFS= read -r database; do
            [[ -n "$database" ]] || continue
            if [[ "$1" == check-databases ]]; then
                [[ "$database" =~ ^xp_${uuid_hex:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance"
                path="/var/lib/mysql/$database"
                [[ -d "$path" && ! -L "$path" ]] || fail "database directory is missing or unsafe"
                [[ "$(lsattr -pd -- "$path" | awk '{print $1}')" == "$project_id" ]] || fail "database $database is not assigned to the instance quota"
                [[ "$(lsattr -d -- "$path" | awk '{print $1}')" == *P* ]] || fail "database $database does not inherit the instance quota"
            else
                "$0" tag-database "$uuid" "$user" "$project_id" "$database"
            fi
        done <<< "$database_names"
        [[ "$1" == check-databases ]] && echo databases-verified || echo databases-reconciled
        ;;
    tag-database)
        [[ $# -eq 5 ]] || fail "tag-database expects UUID, system user, project ID and database name"
        uuid="$2" user="$3" project_id="$4" database="$5"
        [[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
        uuid_hex="${uuid//-/}"
        [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "database does not belong to the instance"
        [[ "$project_id" =~ ^[0-9]+$ ]] && (( project_id >= 100000 && project_id <= 4294967294 )) || fail "invalid project ID"
        [[ "$database" =~ ^xp_${uuid_hex:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance"
        mountpoint="$(quota_mount)"
        marker="/etc/xpanel-vps/instances/$uuid/project-quota-applied"
        [[ -f "$marker" && ! -L "$marker" && "$(<"$marker")" == "$project_id" ]] || fail "instance quota is not applied"
        [[ "$(mariadb --protocol=socket --batch --skip-column-names -e 'SELECT @@innodb_file_per_table')" == 1 ]] || fail "MariaDB innodb_file_per_table is required"
        datadir="$(mariadb --protocol=socket --batch --skip-column-names -e 'SELECT @@datadir')"
        [[ "$(realpath -e -- "$datadir")" == /var/lib/mysql ]] || fail "unexpected MariaDB data directory"
        path="/var/lib/mysql/$database"
        [[ -d "$path" && ! -L "$path" ]] || fail "database directory is missing or unsafe"
        [[ "$(findmnt -n -o TARGET -T "$path")" == "$mountpoint" ]] || fail "MariaDB data directory is outside the quota filesystem"
        [[ "$(stat -c %U -- "$path")" == mysql ]] || fail "unexpected database directory owner"
        current_id="$(lsattr -pd -- "$path" | awk '{print $1}')"
        [[ "$current_id" == 0 || "$current_id" == "$project_id" ]] || fail "database directory belongs to another project"
        find "$path" -xdev \( -type f -o -type d \) -exec chattr -p "$project_id" {} +
        find "$path" -xdev -type d -exec chattr +P {} +
        echo database-quota-applied
        ;;
    *) fail "unsupported action" ;;
esac
