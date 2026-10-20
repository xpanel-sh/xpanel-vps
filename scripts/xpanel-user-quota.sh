#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-user-quota: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"

validate_account() {
    local uuid="$1" user="$2" uuid_hex
    [[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
    uuid_hex="${uuid//-/}"
    [[ "$user" == "xhi${uuid_hex:0:12}" ]] || fail "invalid instance user"
}

quota_mount() {
    local home_mount instance_mount filesystem state
    for tool in findmnt quotaon setquota; do
        command -v "$tool" >/dev/null 2>&1 || fail "missing $tool; install quota and util-linux"
    done
    home_mount="$(findmnt -n -o TARGET -T /home)"
    instance_mount="$(findmnt -n -o TARGET -T /var/lib/xpanel-vps)"
    [[ -n "$home_mount" && "$home_mount" == "$instance_mount" ]] || fail "/home and Host instances must use the same quota-enabled filesystem"
    filesystem="$(findmnt -n -o FSTYPE -T /home)"
    [[ "$filesystem" == ext4 || "$filesystem" == xfs ]] || fail "user quotas require ext4 or XFS (found $filesystem)"
    state="$(quotaon -u -p "$home_mount")" || fail "could not inspect user quota state"
    [[ "$state" == *'is on'* ]] || fail "user quotas are not active on $home_mount; reboot after preparing quotas"
    printf '%s\n' "$home_mount"
}

marker_for() { printf '/etc/xpanel-vps/instances/%s/user-quota-applied\n' "$1"; }

case "${1:-}" in
    status)
        [[ $# -eq 1 ]] || fail "status takes no arguments"
        quota_mount >/dev/null
        echo user-ready
        ;;
    apply)
        [[ $# -eq 6 ]] || fail "apply expects UUID, system user, project ID, MiB and inode limit"
        uuid="$2" user="$3" storage_mib="$5" inode_limit="$6"
        validate_account "$uuid" "$user"
        [[ "$storage_mib" =~ ^[0-9]+$ ]] && (( storage_mib >= 1 && storage_mib <= 100000000 )) || fail "invalid storage limit"
        [[ "$inode_limit" =~ ^[0-9]+$ ]] && (( inode_limit >= 1 && inode_limit <= 1000000000 )) || fail "invalid inode limit"
        id "$user" >/dev/null 2>&1 || fail "instance user does not exist"
        mountpoint="$(quota_mount)"
        for path in "/home/$user" "/var/lib/xpanel-vps/instances/$uuid"; do
            [[ -d "$path" && ! -L "$path" ]] || fail "missing or unsafe quota directory: $path"
            [[ "$(findmnt -n -o TARGET -T "$path")" == "$mountpoint" ]] || fail "quota directory has a different filesystem: $path"
            [[ "$(stat -c %U -- "$path")" == "$user" ]] || fail "quota directory has an unexpected owner: $path"
        done
        marker="$(marker_for "$uuid")"
        marker_dir="$(dirname "$marker")"
        [[ ! -L "$marker_dir" && ! -L "$marker" ]] || fail "unsafe user quota marker"
        install -d -o root -g root -m 0755 "$marker_dir"
        hard_kib=$((storage_mib * 1024))
        setquota -u "$user" 0 "$hard_kib" 0 "$inode_limit" "$mountpoint"
        printf '%s:%s:%s\n' "$user" "$storage_mib" "$inode_limit" > "$marker"
        chmod 0600 "$marker"
        echo user-quota-applied
        ;;
    verify)
        [[ $# -eq 6 ]] || fail "verify expects UUID, system user, project ID, MiB and inode limit"
        uuid="$2" user="$3" storage_mib="$5" inode_limit="$6"
        validate_account "$uuid" "$user"
        marker="$(marker_for "$uuid")"
        [[ -f "$marker" && ! -L "$marker" ]] || fail "user quota has not been applied"
        [[ "$(<"$marker")" == "$user:$storage_mib:$inode_limit" ]] || fail "user quota differs from the assigned plan"
        quota_mount >/dev/null
        echo user-quota-verified
        ;;
    tag-database|check-databases|reconcile-databases)
        if [[ "$1" == tag-database ]]; then
            [[ $# -eq 5 ]] || fail "tag-database expects UUID, system user, project ID and database name"
        else
            [[ $# -eq 4 ]] || fail "database action expects UUID, system user and project ID"
        fi
        uuid="$2" user="$3"
        validate_account "$uuid" "$user"
        if [[ "$1" == tag-database ]]; then
            uuid_hex="${uuid//-/}"
            [[ "$5" =~ ^xp_${uuid_hex:0:6}_[a-z0-9_]{1,54}$ ]] || fail "database escaped the instance"
        fi
        marker="$(marker_for "$uuid")"
        [[ -f "$marker" && ! -L "$marker" ]] || fail "user quota has not been applied"
        # MariaDB files are owned by mysql, not the instance user. They must
        # remain explicitly excluded from the user-owned file quota.
        echo database-excluded-from-user-quota
        ;;
    *) fail "unsupported action" ;;
esac
