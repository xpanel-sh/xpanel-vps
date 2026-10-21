#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-account-storage: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"
[[ "${1:-}" == usage && $# -eq 2 ]] || fail "usage: $0 usage INSTANCE_UUID"

uuid="$2"
[[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
uuid_hex="${uuid//-/}"
account_user="xhi${uuid_hex:0:12}"
account_home="/home/$account_user"
instance_root="/var/lib/xpanel-vps/instances/$uuid"
catalog="$instance_root/database/database.sqlite"

for tool in du php stat; do
    command -v "$tool" >/dev/null 2>&1 || fail "missing $tool"
done
for path in "$account_home" "$instance_root"; do
    [[ -d "$path" && ! -L "$path" ]] || fail "missing or unsafe account path: $path"
    [[ "$(stat -c %U -- "$path")" == "$account_user" ]] || fail "unexpected account path owner: $path"
done
[[ -f "$catalog" && ! -L "$catalog" ]] || fail "Host database catalog is unavailable"

bytes_for() {
    local path="$1" result
    [[ ! -L "$path" ]] || fail "unsafe storage path: $path"
    [[ -e "$path" ]] || { printf '0'; return; }
    [[ -d "$path" ]] || fail "storage path is not a directory: $path"
    result="$(du -s -x -B1 -- "$path" | awk '{print $1}')" || fail "could not measure $path"
    [[ "$result" =~ ^[0-9]+$ ]] || fail "invalid measurement for $path"
    printf '%s' "$result"
}

# These roots are disjoint. Mail is a subset of account_home; backups are a
# subset of instance_root, so neither is added twice to the known total.
account_home_bytes="$(bytes_for "$account_home")"
instance_bytes="$(bytes_for "$instance_root")"
mail_bytes="$(bytes_for "$account_home/mail")"
backup_bytes="$(bytes_for "$instance_root/backups")"
legacy_backup_bytes="$(bytes_for "$instance_root/storage/app/backups/sites")"
backup_bytes=$((backup_bytes + legacy_backup_bytes))
database_bytes=0

database_names="$(php -r '
    $db = new PDO("sqlite:".$argv[1], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec("PRAGMA query_only = ON");
    if (!$db->query("SELECT 1 FROM sqlite_master WHERE type = \x27table\x27 AND name = \x27site_databases\x27")->fetchColumn()) exit(0);
    foreach ($db->query("SELECT DISTINCT name FROM site_databases WHERE status = \x27active\x27") as $row) echo $row["name"]."\n";
' "$catalog")" || fail "could not read Host database catalog"
while IFS= read -r database; do
    [[ -n "$database" ]] || continue
    [[ "$database" =~ ^xp_${uuid_hex:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance: $database"
    path="/var/lib/mysql/$database"
    [[ -d "$path" && ! -L "$path" ]] || fail "database directory is unavailable: $database"
    value="$(bytes_for "$path")"
    database_bytes=$((database_bytes + value))
done <<< "$database_names"

printf 'account_home_bytes=%s\n' "$account_home_bytes"
printf 'instance_bytes=%s\n' "$instance_bytes"
printf 'database_bytes=%s\n' "$database_bytes"
printf 'mail_bytes=%s\n' "$mail_bytes"
printf 'backup_bytes=%s\n' "$backup_bytes"
printf 'known_total_bytes=%s\n' "$((account_home_bytes + instance_bytes + database_bytes))"
