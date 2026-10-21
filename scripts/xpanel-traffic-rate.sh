#!/usr/bin/env bash
set -Eeuo pipefail
umask 022

fail() { echo "xpanel-traffic-rate: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"
[[ $# -eq 3 ]] || fail "usage: $0 ensure|apply UUID normal|reduced"
action="$1" uuid="$2" mode="$3"
[[ "$action" == ensure || "$action" == apply ]] || fail "invalid action"
[[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
[[ "$mode" == normal || "$mode" == reduced ]] || fail "invalid rate mode"
[[ -d "/var/lib/xpanel-vps/instances/$uuid" && ! -L "/var/lib/xpanel-vps/instances/$uuid" ]] || fail "instance unavailable"

snippet="/etc/nginx/snippets/xpanel-traffic-$uuid.conf"
rate=0
[[ "$mode" == reduced ]] && rate=128k
desired="    limit_rate $rate;"

install -d -o root -g root -m 0755 /etc/nginx/snippets
exec 9>/run/lock/xpanel-traffic-rate.lock
flock -x 9
[[ ! -L "$snippet" ]] || fail "unsafe Nginx snippet"
if [[ -f "$snippet" ]]; then
    current="$(<"$snippet")"
    [[ "$current" == '    limit_rate 0;' || "$current" == '    limit_rate 128k;' ]] || fail "unexpected Nginx rate configuration"
    if [[ "$action" == ensure || "$current" == "$desired" ]]; then
        [[ "$action" == apply ]] && echo "$mode"
        exit 0
    fi
fi
tmp="$(mktemp /etc/nginx/snippets/.xpanel-traffic.XXXXXX)"
backup="$(mktemp /etc/nginx/snippets/.xpanel-traffic-backup.XXXXXX)"
cleanup() { rm -f -- "$tmp" "$backup"; }
trap cleanup EXIT
had_previous=false
if [[ -f "$snippet" ]]; then
    cp -p -- "$snippet" "$backup"
    had_previous=true
fi
printf '%s\n' "$desired" > "$tmp"
chmod 0644 "$tmp"
mv -f -- "$tmp" "$snippet"
if [[ "$action" == ensure ]]; then
    exit 0
fi
if ! nginx -t || ! systemctl reload nginx; then
    if [[ "$had_previous" == true ]]; then
        cp -p -- "$backup" "$snippet"
    else
        rm -- "$snippet"
    fi
    nginx -t >/dev/null 2>&1 && systemctl reload nginx || true
    fail "Nginx rejected rate change; previous configuration restored"
fi
echo "$mode"
