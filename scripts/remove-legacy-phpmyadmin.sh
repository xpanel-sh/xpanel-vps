#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == "0" ]] || { echo "remove-legacy-phpmyadmin: must run as root" >&2; exit 1; }

owned=false
[[ -f /etc/phpmyadmin/conf.d/xpanel-host.php ]] && owned=true
[[ -f /etc/xpanel-host/phpmyadmin.secret ]] && owned=true
[[ -f /etc/nginx/snippets/xpanel-phpmyadmin.conf ]] && owned=true
[[ "$owned" == true ]] || exit 0

echo "Removing the legacy phpMyAdmin installation managed by XPanel..."
for vhost in /etc/nginx/sites-available/xpanel-host.conf /etc/nginx/sites-available/xpanel-host-panel.conf; do
  [[ -f "$vhost" && ! -L "$vhost" ]] || continue
  sed -i '\|include /etc/nginx/snippets/xpanel-phpmyadmin.conf;|d' "$vhost"
done
rm -f -- /etc/nginx/snippets/xpanel-phpmyadmin.conf \
  /etc/phpmyadmin/conf.d/xpanel-host.php /etc/xpanel-host/phpmyadmin.secret

if command -v dpkg-query >/dev/null 2>&1 && dpkg-query -W -f='${Status}' phpmyadmin 2>/dev/null | grep -q 'install ok installed'; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get purge -y phpmyadmin
fi
if command -v nginx >/dev/null 2>&1; then
  nginx -t
  systemctl reload nginx 2>/dev/null || true
fi
