#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

[[ "$(id -u)" == 0 ]] || { echo 'Database engine installer requires root.' >&2; exit 1; }
action="${1:-}"
engine="${2:-}"
[[ "$action" == status || "$engine" =~ ^(mariadb|postgresql|mongodb|dbgate)$ ]] \
  || { echo 'Invalid database engine.' >&2; exit 1; }

state_dir=/var/lib/xpanel-vps/database-engine-install
state_file="$state_dir/status"
log_file="$state_dir/install.log"
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
install -d -o root -g root -m 0700 "$state_dir"

field() { sed -n "s/^$1=//p" "$state_file" 2>/dev/null | head -n1; }
write_state() {
  local state="$1" message="${2:-}" temp
  temp="$(mktemp "$state_dir/.status.XXXXXX")"
  printf 'engine=%s\nstate=%s\nunit=%s\nstarted_at=%s\nerror=%s\n' \
    "$engine" "$state" "${unit:-}" "${started_at:-$(date +%s)}" "$message" > "$temp"
  chmod 0600 "$temp"
  mv -f -- "$temp" "$state_file"
}
report() {
  if [[ ! -f "$state_file" ]]; then
    printf 'state=idle\n'
    return
  fi
  local current_state current_unit current_started
  current_state="$(field state)"
  current_unit="$(field unit)"
  current_started="$(field started_at)"
  if [[ "$current_state" =~ ^(queued|running)$ && "$current_started" =~ ^[0-9]+$ ]] \
    && (( $(date +%s) - current_started > 60 )) \
    && ! systemctl is-active --quiet "$current_unit.service"; then
    engine="$(field engine)"; unit="$current_unit"; started_at="$current_started"
    write_state failed 'La instalación se interrumpió. Revisa el registro y vuelve a intentarlo.'
  fi
  cat "$state_file"
}

case "$action" in
  status)
    [[ $# -eq 1 ]] || exit 1
    report
    ;;
  start)
    [[ $# -eq 2 ]] || exit 1
    exec 9>"$state_dir/start.lock"
    flock -x 9
    if [[ -f "$state_file" ]]; then
      current_state="$(field state)"
      if [[ "$current_state" =~ ^(queued|running)$ ]]; then
        report >/dev/null
        current_state="$(field state)"
        if [[ "$current_state" =~ ^(queued|running)$ ]]; then
          echo 'Ya hay una instalación de motor en curso.' >&2
          exit 1
        fi
      fi
    fi
    unit="xpanel-database-engine-$(date +%s)-$$"
    started_at="$(date +%s)"
    write_state queued
    if ! systemd-run --quiet --collect --unit="$unit" \
      /bin/bash "$script_dir/xpanel-database-engine-install.sh" worker "$engine" "$unit" "$started_at"; then
      write_state failed 'No se pudo iniciar el trabajo de instalación.'
      exit 1
    fi
    printf 'state=queued\nengine=%s\n' "$engine"
    ;;
  worker)
    [[ $# -eq 4 && "$3" =~ ^xpanel-database-engine-[0-9]+-[0-9]+$ && "$4" =~ ^[0-9]+$ ]] || exit 1
    unit="$3"; started_at="$4"
    exec 8>"$state_dir/install.lock"
    if ! flock -n 8; then
      write_state failed 'Otra instalación está usando el gestor de paquetes.'
      exit 1
    fi
    write_state running
    if timeout --signal=TERM --kill-after=15s 1800s env DEBIAN_FRONTEND=noninteractive \
      bash "$script_dir/xpanel-instance-helper.sh" database-engine-install "$engine" >"$log_file" 2>&1 \
      && grep -qx 'installed=true' "$log_file"; then
      write_state success
    else
      error="$(tail -n 8 "$log_file" | tr '\r\n' '  ' | cut -c1-500)"
      write_state failed "${error:-La instalación no terminó correctamente.}"
      exit 1
    fi
    ;;
  *) echo 'Invalid installer action.' >&2; exit 1 ;;
esac
