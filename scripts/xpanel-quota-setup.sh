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
    if ! modprobe quota_v2 2>/dev/null; then
        module_package="linux-modules-extra-$(uname -r)"
        apt-get update -y
        if apt-cache show "$module_package" >/dev/null 2>&1; then
            echo "Instalando $module_package para habilitar quota_v2..."
            DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends "$module_package"
            modprobe quota_v2 || fail "quota_v2 no se pudo cargar tras instalar $module_package"
        else
            fail "el kernel no dispone de quota_v2. Comprueba uname -r y modinfo quota_v2; instala los módulos extra del kernel en uso"
        fi
    fi
    if ! quota_output="$(quotaon -u / 2>&1)"; then
        if ! bash "$script_dir/xpanel-user-quota.sh" status >/dev/null 2>&1; then
            printf '%s\n' "$quota_output" >&2
            fail "existe el archivo de cuotas, pero quotaon no pudo activarlo; revisa quotaon.service y dmesg"
        fi
    fi
    bash "$script_dir/xpanel-user-quota.sh" status >/dev/null || fail "quotaon terminó, pero XPanel no confirmó la cuota de usuario"
    module_config=/etc/modules-load.d/xpanel-quota.conf
    if [[ ! -e "$module_config" ]]; then
        printf 'quota_v2\n' > "$module_config"
        chmod 0644 "$module_config"
    else
        [[ ! -L "$module_config" ]] || fail "unsafe quota module configuration"
        grep -qx 'quota_v2' "$module_config" || fail "$module_config exists but does not load quota_v2"
    fi
    echo "Cuotas por usuario activas."
    exit 0
fi

tmp="$(mktemp /etc/.xpanel-fstab.XXXXXX)"
trap 'rm -f -- "$tmp"' EXIT
awk '$1 !~ /^#/ && $2 == "/" && $3 == "ext4" { $4=$4 ",usrquota" } { print }' /etc/fstab > "$tmp"
findmnt --verify --tab-file "$tmp" >/dev/null || fail "the proposed /etc/fstab did not pass validation"
backup="/etc/fstab.xpanel-$(date -u +%Y%m%dT%H%M%SZ).bak"
cp -p -- /etc/fstab "$backup"
install -o root -g root -m 0644 "$tmp" /etc/fstab
echo "Preparado usrquota en /etc/fstab (respaldo: $backup). Falta inicializar /aquota.user y activar las cuotas antes de crear cuentas Host; consulta README.md."
