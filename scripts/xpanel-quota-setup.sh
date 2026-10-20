#!/usr/bin/env bash
set -euo pipefail

fail() { echo "xpanel-quota-setup: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"
[[ "${1:-}" == prepare && $# -eq 1 ]] || fail "usage: $0 prepare"

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if bash "$script_dir/xpanel-project-quota.sh" status >/dev/null 2>&1; then
    echo "Cuotas de hosting activas."
    exit 0
fi

# Only prepare the simple, known layout. Other filesystems require explicit
# administration; never rewrite a mount entry whose target is ambiguous.
[[ "$(findmnt -n -o FSTYPE -T /home)" == ext4 ]] || fail "automatic setup currently supports ext4 only"
[[ "$(findmnt -n -o TARGET -T /home)" == / ]] || fail "/home must be on the root filesystem"
[[ "$(findmnt -n -o TARGET -T /var/lib/xpanel-vps)" == / ]] || fail "Host instances must be on the root filesystem"
[[ -f /etc/fstab && ! -L /etc/fstab ]] || fail "unsafe /etc/fstab"

root_entries="$(awk '$1 !~ /^#/ && $2 == "/" && $3 == "ext4" { n++ } END { print n+0 }' /etc/fstab)"
[[ "$root_entries" == 1 ]] || fail "expected exactly one ext4 root entry in /etc/fstab"
if awk '$1 !~ /^#/ && $2 == "/" && $3 == "ext4" && ("," $4 ",") ~ /,usrquota,/ { found=1 } END { exit !found }' /etc/fstab; then
    if [[ ",$(findmnt -n -o OPTIONS -T /)," != *,usrquota,* ]]; then
        fail "usrquota figura en /etc/fstab pero no en el montaje activo; reinicia para aplicar la opción"
    fi
    if [[ ! -f /aquota.user && ! -f /quota.user ]]; then
        fail "usrquota está montado, pero falta /aquota.user: quotaon no puede activarse. Inicializa el archivo de cuotas antes de repetir la actualización (consulta README.md). No sirve reiniciar otra vez"
    fi
    fail "usrquota figura en /etc/fstab y existe un archivo de cuotas, pero sigue inactivo. Revisa quotaon.service y su registro; no reinicies a ciegas"
fi

tmp="$(mktemp /etc/.xpanel-fstab.XXXXXX)"
trap 'rm -f -- "$tmp"' EXIT
awk '$1 !~ /^#/ && $2 == "/" && $3 == "ext4" { $4=$4 ",usrquota" } { print }' /etc/fstab > "$tmp"
findmnt --verify --tab-file "$tmp" >/dev/null || fail "the proposed /etc/fstab did not pass validation"
backup="/etc/fstab.xpanel-$(date -u +%Y%m%dT%H%M%SZ).bak"
cp -p -- /etc/fstab "$backup"
install -o root -g root -m 0644 "$tmp" /etc/fstab
echo "Preparado usrquota en /etc/fstab (respaldo: $backup). Falta inicializar /aquota.user y activar las cuotas antes de crear cuentas Host; consulta README.md."
