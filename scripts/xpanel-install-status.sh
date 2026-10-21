#!/usr/bin/env bash
set -euo pipefail

state_dir=/var/lib/xpanel-vps/install
status="$state_dir/status"
credentials="$state_dir/credentials"

[[ "$(id -u)" == 0 ]] || { echo 'Ejecuta este comando como root.' >&2; exit 1; }
[[ -d "$state_dir" && ! -L "$state_dir" && -f "$status" && ! -L "$status" ]] || {
    echo 'No hay estado de instalación registrado.' >&2
    exit 1
}

case "$(<"$status")" in
    ready) echo 'Instalación lista; cuotas de Host verificadas.' ;;
    quota-pending) echo 'Instalación principal terminada; cuotas pendientes del próximo arranque.' ;;
    quota-failed) echo 'La activación de cuotas falló. Revisa: journalctl -u xpanel-quota-firstboot.service -b' ;;
    quota-blocked) echo 'El panel está instalado, pero las cuotas requieren intervención manual; no aprovisiones Host.' ;;
    *) echo 'Estado de instalación desconocido.' >&2; exit 1 ;;
esac

if [[ "${1:-}" == --credentials ]]; then
    [[ $# -eq 1 ]] || exit 2
    [[ -f "$credentials" && ! -L "$credentials" ]] || {
        echo 'No hay credenciales iniciales guardadas (puede ser una reinstalación).' >&2
        exit 1
    }
    cat -- "$credentials"
elif [[ $# -ne 0 ]]; then
    echo 'Uso: xpanel-install-status.sh [--credentials]' >&2
    exit 2
fi
