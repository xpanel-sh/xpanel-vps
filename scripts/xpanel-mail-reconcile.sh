#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

fail() { echo "xpanel-mail-reconcile: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"
[[ "${1:-}" == sync || "${1:-}" == outbound-sync ]] || fail "expected sync or outbound-sync"

instances=/var/lib/xpanel-vps/instances
release=/opt/xpanel-host/current
helper="$release/scripts/xpanel-site-helper.sh"
[[ -x "$helper" || -f "$helper" ]] || fail "current Host mail helper is unavailable"
stage="$(mktemp -d /run/xpanel-mail-reconcile.XXXXXX)"
trap 'rm -rf -- "$stage"' EXIT
mkdir -p "$stage/dkim"
for name in domains mailboxes users sender-login aliases send-limits sender-transport dedicated-ips; do : > "$stage/$name"; done
printf 'xpanel\n' > "$stage/dkim-selector"

first_user=""
shopt -s nullglob
for root in "$instances"/*; do
  uuid="${root##*/}"
  [[ "$uuid" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || continue
  [[ ! -f "/var/lib/xpanel-vps/purged/$uuid" ]] || continue
  source="$root/storage/app/mail"
  [[ -d "$source" && ! -L "$source" ]] || continue
  user="xhi${uuid//-/}"; user="${user:0:15}"
  id "$user" >/dev/null 2>&1 || fail "instance user is unavailable for $uuid"
  first_user="${first_user:-$user}"
  for name in domains mailboxes users sender-login aliases send-limits; do
    [[ -f "$source/$name" && ! -L "$source/$name" ]] || fail "instance $uuid is missing staged mail file $name"
    cat -- "$source/$name" >> "$stage/$name"
  done
  for name in sender-transport dedicated-ips; do
    [[ ! -e "$source/$name" || -f "$source/$name" && ! -L "$source/$name" ]] || fail "unsafe outbound mail stage for $uuid"
    [[ -f "$source/$name" ]] && cat -- "$source/$name" >> "$stage/$name"
  done
  if [[ -f "$source/dkim-selector" ]]; then
    selector="$(tr -d '\r\n' < "$source/dkim-selector")"
    [[ "$selector" == xpanel ]] || fail "instances must use the shared xpanel DKIM selector"
  fi
  while read -r domain status; do
    [[ -n "$domain" ]] || continue
    [[ "$status" == OK && "$domain" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || fail "invalid staged mail domain"
    key="$source/dkim/$domain.private"
    [[ -f "$key" && ! -L "$key" ]] || fail "missing DKIM key for $domain"
    [[ ! -e "$stage/dkim/$domain.private" ]] || fail "mail domain $domain belongs to more than one instance"
    cp -- "$key" "$stage/dkim/$domain.private"
  done < "$source/domains"
done

# An empty catalog is valid after the last account is purged. The helper only
# needs an existing service identity; managed mode validates every mailbox
# against its own xhi account instead of this bootstrap identity.
bootstrap_user="${first_user:-www-data}"
if [[ "$1" == sync ]]; then action=mail-sync; else action=mail-outbound-sync; fi
env XPANEL_INSTANCE_ROOT="$release" XPANEL_SITE_USER="$bootstrap_user" XPANEL_SITE_GROUP="$bootstrap_user" \
  XPANEL_MANAGED_MAIL_SYNC=1 XPANEL_MAIL_STAGE_ROOT="$stage" \
  bash "$helper" "$action"

echo mail-reconciled
