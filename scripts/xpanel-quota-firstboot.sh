#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
state_dir=/var/lib/xpanel-vps/install
pending="$state_dir/quota-firstboot.pending"
status="$state_dir/status"
fail() {
    printf 'quota-failed\n' > "$status"
    echo "xpanel-quota-firstboot: $*" >&2
    exit 1
}

[[ "$(id -u)" == 0 ]] || { echo 'xpanel-quota-firstboot: root is required' >&2; exit 1; }
[[ -f "$pending" && ! -L "$pending" && -d "$state_dir" && ! -L "$state_dir" ]] || {
    echo 'xpanel-quota-firstboot: no pending first-boot quota setup' >&2
    exit 1
}
trap 'printf "quota-failed\n" > "$status"' ERR

# This path is exclusively for a fresh VPS install. Never scan a mounted root
# that already contains Host accounts or while the shared services are active.
[[ "$(findmnt -n -o TARGET -T /home)" == / ]] || fail "/home is not on the root filesystem"
[[ "$(findmnt -n -o FSTYPE -T /home)" == ext4 ]] || fail "expected ext4 root filesystem"
[[ ",$(findmnt -n -o OPTIONS -T /)," == *,usrquota,* ]] || fail "usrquota is not active after reboot"
[[ -d /var/lib/xpanel-vps/instances ]] || fail "instance directory is unavailable"
[[ -z "$(find /var/lib/xpanel-vps/instances -mindepth 1 -maxdepth 1 -print -quit)" ]] || fail "Host accounts already exist; refusing quota bootstrap"
[[ -z "$(find /home -mindepth 1 -maxdepth 1 -name 'xhi*' -print -quit)" ]] || fail "Host homes already exist; refusing quota bootstrap"
if ! bash "$root/scripts/xpanel-project-quota.sh" status >/dev/null 2>&1; then
    if [[ ! -e /aquota.user && ! -e /quota.user ]]; then
        for service in mariadb nginx cron php8.3-fpm php8.4-fpm; do
            if systemctl is-active --quiet "$service"; then
                fail "$service is already running; refusing live quota scan"
            fi
        done
        echo "Inicializando cuotas de usuario con remount de solo lectura; no se forzará un escaneo de la raíz activa..."
        quotacheck -cu / || fail "no se pudo escanear la raíz en solo lectura; usa modo rescate y revisa el journal antes de crear Host"
    fi
    bash "$root/scripts/xpanel-quota-setup.sh" prepare
fi
bash "$root/scripts/xpanel-project-quota.sh" status >/dev/null || fail "quota verification failed"
printf 'ready\n' > "$status"
rm -- "$pending"
echo "Cuotas de Host activas y verificadas. XPanel VPS está listo."
