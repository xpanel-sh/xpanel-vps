#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

fail() { echo "xpanel-host-broker-helper: $*" >&2; exit 1; }

[[ "$(id -u)" == "0" ]] || fail "must run as root"
[[ "${1:-}" == "execute" ]] || fail "unsupported broker command"
shift
[[ $# -ge 5 ]] || fail "missing broker arguments"

UUID="$1"
PANEL_USER="$2"
RELEASE_PATH="$3"
INSTANCE_ROOT="$4"
ACTION="$5"
shift 5

[[ "$UUID" =~ ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ ]] || fail "invalid UUID"
[[ "$PANEL_USER" =~ ^xhi[a-f0-9]{12}$ ]] || fail "invalid panel user"
[[ "$RELEASE_PATH" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ ]] || fail "invalid release path"
[[ "$INSTANCE_ROOT" == "/var/lib/xpanel-vps/instances/$UUID" ]] || fail "invalid instance root"
[[ -f "$INSTANCE_ROOT/runtime.sh" && ! -L "$INSTANCE_ROOT/runtime.sh" ]] || fail "runtime environment unavailable"
[[ -x "/usr/bin/php8.3" || -x "/usr/bin/php8.4" ]] || fail "host PHP runtime unavailable"
[[ -f "$RELEASE_PATH/scripts/xpanel-site-helper.sh" && ! -L "$RELEASE_PATH/scripts/xpanel-site-helper.sh" ]] || fail "host helper unavailable"
id "$PANEL_USER" >/dev/null 2>&1 || fail "panel user unavailable"

case "$ACTION" in
  apply|remove|site-restart|site-diagnose|ssl-issue|ssl-wildcard-issue|ssl-delete|ssl-inspect|database-create|database-password|database-remove|php-profile-remove|access-remove|ownership-fix|ownership-sync-path|ownership-sync-tree) ;;
  *) fail "action is not brokered" ;;
esac

# This file was installed root-owned by xpanel-instance-helper. It exports the
# per-instance Laravel paths and never contains user-controlled shell code.
# shellcheck disable=SC1090
source "$INSTANCE_ROOT/runtime.sh"
[[ "${XPANEL_INSTANCE_ID:-}" == "$UUID" && "${XPANEL_INSTANCE_ROOT:-}" == "$INSTANCE_ROOT" ]] || fail "runtime does not belong to the requested instance"
export XPANEL_INSTANCE_ROOT="$INSTANCE_ROOT"
export XPANEL_SITE_USER="$PANEL_USER"
export XPANEL_SITE_GROUP="$PANEL_USER"

INSTANCE_HEX="${UUID//-/}"
if [[ "$ACTION" == "apply" || "$ACTION" == "remove" || "$ACTION" == "site-restart" ]]; then
    if [[ "$ACTION" == "apply" ]]; then [[ $# -eq 12 ]] || fail "invalid site argument count"; else [[ $# -eq 6 ]] || fail "invalid site argument count"; fi
  DOMAIN="$1"; ENGINE="$2"; TYPE="$3"; PHP_VERSION="$4"; DOCUMENT_ROOT="$5"; SITE_USER="$6"
  [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid site domain"
  [[ "$ENGINE" == "nginx" || "$ENGINE" == "apache" || "$ENGINE" == "openlitespeed" ]] || fail "invalid web engine"
  [[ "$TYPE" == "php" || "$TYPE" == "static" || "$TYPE" == "node" ]] || fail "invalid site type"
  [[ "$PHP_VERSION" =~ ^8\.[2-4]$ ]] || fail "invalid PHP version"
  [[ "$DOCUMENT_ROOT" == "/home/$PANEL_USER/public_html/"* && "$DOCUMENT_ROOT" != *".."* ]] || fail "site root escaped the account home"
  [[ "$SITE_USER" =~ ^xps${INSTANCE_HEX:0:6}[a-z0-9]{9,20}$ ]] || fail "site user escaped the instance"
  if [[ "$ACTION" == "apply" ]]; then
    [[ "$7" == "$DOCUMENT_ROOT" || "$7" == "$DOCUMENT_ROOT/"* ]] || fail "web root escaped the site"
    if [[ "$TYPE" == "node" ]]; then
      [[ "$8" =~ ^(20|22|24)$ ]] || fail "invalid Node.js version"
      [[ "$9" =~ ^[0-9]{5}$ ]] && (( 10#$9 >= 20000 && 10#$9 <= 49999 )) || fail "invalid runtime port"
    else
      [[ "$8" == "-" && "$9" == "0" ]] || fail "unexpected application runtime"
    fi
    [[ "${10}" == "active" || "${10}" == "suspended" ]] || fail "invalid site status"
    [[ "${11}" == "system" || "${11}" =~ ^i${INSTANCE_HEX:0:12}-p[1-9][0-9]*$ ]] || fail "invalid PHP profile"
    [[ "${12}" == "-" || "${12}" =~ ^(bcmath|curl|gd|imagick|intl|mbstring|mysql|opcache|pgsql|redis|soap|sqlite3|xml|zip)(,(bcmath|curl|gd|imagick|intl|mbstring|mysql|opcache|pgsql|redis|soap|sqlite3|xml|zip))*$ ]] || fail "invalid PHP extensions"
  fi
elif [[ "$ACTION" == "site-diagnose" ]]; then
  [[ $# -eq 8 ]] || fail "invalid diagnostic argument count"
  DOMAIN="$1"; DOCUMENT_ROOT="$2"; SITE_USER="$3"; ENGINE="$4"; TYPE="$5"; PHP_VERSION="$6"; EXPECTED_IP="$7"; RUNTIME_PORT="$8"
  [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid diagnostic domain"
  [[ "$DOCUMENT_ROOT" == "/home/$PANEL_USER/public_html/"* && "$DOCUMENT_ROOT" != *".."* ]] || fail "diagnostic root escaped the account home"
  [[ "$SITE_USER" =~ ^xps${INSTANCE_HEX:0:6}[a-z0-9]{9,20}$ ]] || fail "diagnostic user escaped the instance"
  [[ "$ENGINE" =~ ^(nginx|apache|openlitespeed)$ && "$TYPE" =~ ^(php|static|node)$ && "$PHP_VERSION" =~ ^8\.[2-4]$ ]] || fail "invalid diagnostic runtime"
  [[ "$EXPECTED_IP" == "-" || "$EXPECTED_IP" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ ]] || fail "invalid diagnostic IP"
  if [[ "$TYPE" == "node" ]]; then [[ "$RUNTIME_PORT" =~ ^[0-9]{5}$ ]]; else [[ "$RUNTIME_PORT" == "0" ]]; fi || fail "invalid diagnostic port"
elif [[ "$ACTION" == "ssl-inspect" ]]; then
  [[ $# -eq 1 ]] || fail "invalid certificate inspection argument count"
  [[ "$1" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid certificate inspection domain"
elif [[ "$ACTION" == "ssl-issue" || "$ACTION" == "ssl-wildcard-issue" || "$ACTION" == "ssl-delete" ]]; then
  [[ $# -eq 5 ]] || fail "invalid certificate argument count"
  DOMAIN="$1"; ENGINE="$2"; WEB_ROOT="$3"; EMAIL="$4"; SITE_USER="$5"
  [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid certificate domain"
  [[ "$ENGINE" == "nginx" || "$ENGINE" == "apache" || "$ENGINE" == "openlitespeed" ]] || fail "invalid web engine"
  [[ "$WEB_ROOT" == "/home/$PANEL_USER/public_html/"* && "$WEB_ROOT" != *".."* ]] || fail "certificate root escaped the account home"
  [[ "$SITE_USER" =~ ^xps${INSTANCE_HEX:0:6}[a-z0-9]{9,20}$ ]] || fail "certificate user escaped the instance"
  if [[ "$ACTION" == "ssl-delete" ]]; then
    [[ "$EMAIL" == "-" ]] || fail "invalid certificate deletion"
  else
    [[ "$EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "invalid ACME email"
  fi
elif [[ "$ACTION" == "php-profile-remove" ]]; then
  [[ $# -eq 1 && "$1" =~ ^i${INSTANCE_HEX:0:12}-p[1-9][0-9]*$ ]] || fail "invalid PHP profile removal"
elif [[ "$ACTION" == "access-remove" ]]; then
  [[ $# -eq 2 ]] || fail "invalid access removal argument count"
  SITE_USER="$1"; DOCUMENT_ROOT="$2"
  [[ "$SITE_USER" =~ ^xps${INSTANCE_HEX:0:6}[a-z0-9]{9,20}$ ]] || fail "access user escaped the instance"
  [[ "$DOCUMENT_ROOT" == "/home/$PANEL_USER/public_html/"* && "$DOCUMENT_ROOT" != *".."* && "$DOCUMENT_ROOT" != *'\'* ]] || fail "access root escaped the account home"
  DOMAIN="${DOCUMENT_ROOT##*/}"
  [[ "$DOCUMENT_ROOT" == "/home/$PANEL_USER/public_html/$DOMAIN" && "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid access site root"
  [[ ! -L "$DOCUMENT_ROOT" ]] || fail "access site root is a symlink"
elif [[ "$ACTION" == "ownership-fix" || "$ACTION" == "ownership-sync-path" || "$ACTION" == "ownership-sync-tree" ]]; then
  if [[ "$ACTION" == "ownership-fix" ]]; then [[ $# -eq 3 ]] || fail "invalid ownership argument count"; else [[ $# -eq 4 ]] || fail "invalid ownership argument count"; fi
  DOMAIN="$1"; DOCUMENT_ROOT="$2"; SITE_USER="$3"
  [[ "$DOMAIN" =~ ^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$ ]] || fail "invalid ownership domain"
  [[ "$DOCUMENT_ROOT" == "/home/$PANEL_USER/public_html/$DOMAIN" && -d "$DOCUMENT_ROOT" && ! -L "$DOCUMENT_ROOT" ]] || fail "invalid ownership site root"
  [[ "$SITE_USER" =~ ^xps${INSTANCE_HEX:0:6}[a-z0-9]{9,20}$ ]] || fail "ownership user escaped the instance"
  if [[ "$ACTION" != "ownership-fix" ]]; then
    TARGET="$4"
    [[ "$TARGET" == "$DOCUMENT_ROOT" || "$TARGET" == "$DOCUMENT_ROOT/"* ]] || fail "ownership target escaped the site"
    [[ "$TARGET" != *".."* && "$TARGET" != *'\\'* && -e "$TARGET" && ! -L "$TARGET" ]] || fail "invalid ownership target"
    RESOLVED_ROOT="$(realpath -e -- "$DOCUMENT_ROOT")"
    RESOLVED_TARGET="$(realpath -e -- "$TARGET")"
    [[ "$RESOLVED_TARGET" == "$RESOLVED_ROOT" || "$RESOLVED_TARGET" == "$RESOLVED_ROOT/"* ]] || fail "ownership target follows a link outside the site"
  fi
else
  [[ $# -eq 2 ]] || fail "invalid database argument count"
  DB_PREFIX="xp_${INSTANCE_HEX:0:6}_"
  [[ "$1" =~ ^[a-z0-9_]{1,64}$ && "$1" == "$DB_PREFIX"* ]] || fail "database escaped the instance"
  [[ "$2" =~ ^[a-z0-9_]{1,32}$ && "$2" == "$DB_PREFIX"* ]] || fail "database user escaped the instance"
fi

exec bash "$RELEASE_PATH/scripts/xpanel-site-helper.sh" "$ACTION" "$@"
