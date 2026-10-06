#!/usr/bin/env bash
set -Eeuo pipefail

fail() { echo "xpanel-vps-terminal: $*" >&2; exit 1; }
[[ "$(id -u)" == "0" ]] || fail "run as root"
[[ $# -eq 1 ]] || fail "expected the XPanel Host release path"
RELEASE="$1"
[[ "$RELEASE" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ ]] || fail "invalid Host release path"
[[ -f "$RELEASE/agent/go.mod" && ! -L "$RELEASE/agent" ]] || fail "Host terminal agent source is unavailable"
command -v go >/dev/null 2>&1 || fail "Go is required to build the terminal agent"
command -v sshd >/dev/null 2>&1 || fail "OpenSSH server is required for the terminal"

SERVICE_USER=xpanel-vps-terminal
KEY_ROOT=/var/lib/xpanel-vps/terminal
KEY="$KEY_ROOT/service_terminal"
if ! id "$SERVICE_USER" >/dev/null 2>&1; then
  useradd --system --user-group --home-dir "$KEY_ROOT" --shell /usr/sbin/nologin "$SERVICE_USER"
fi
install -d -o root -g root -m 0755 "$KEY_ROOT"
if [[ ! -f "$KEY" ]]; then
  ssh-keygen -t ed25519 -N '' -C xpanel-vps-terminal -f "$KEY" >/dev/null
fi
ssh-keygen -y -f "$KEY" >/dev/null || fail "terminal service key is invalid"
ssh-keygen -y -f "$KEY" | sed 's/$/ xpanel-vps-terminal/' > "$KEY.pub"
chown root:root "$KEY.pub"
chmod 0644 "$KEY.pub"
chown "$SERVICE_USER:$SERVICE_USER" "$KEY"
chmod 0600 "$KEY"
runuser -u "$SERVICE_USER" -- ssh-keygen -y -f "$KEY" >/dev/null || fail "terminal service user cannot read its key"

BINARY_TMP="$(mktemp /usr/local/bin/.xpanel-vps-terminal-agent.XXXXXX)"
trap 'rm -f -- "$BINARY_TMP"' EXIT
go build -C "$RELEASE/agent" -o "$BINARY_TMP" .
chown root:root "$BINARY_TMP"
chmod 0755 "$BINARY_TMP"
mv -f "$BINARY_TMP" /usr/local/bin/xpanel-vps-terminal-agent
trap - EXIT

# AuthorizedKeysFile is root-owned. It supplies the expected Unix identity and
# the instance's fallback TLS port; neither value comes from the browser.
cat > /usr/local/bin/xpanel-vps-terminal-authorize <<'AUTHORIZE'
#!/usr/bin/env bash
set -Eeuo pipefail
expected_user="${1:-}"
panel_port="${2:-}"
[[ "$expected_user" =~ ^(xhi[a-f0-9]{12}|xps[a-z0-9]{9,29})$ ]] || exit 1
[[ "$panel_port" =~ ^[0-9]{4,5}$ ]] && (( panel_port >= 1024 && panel_port <= 65535 && panel_port != 7093 )) || exit 1
[[ "$(id -un)" == "$expected_user" ]] || exit 1
[[ "${SSH_ORIGINAL_COMMAND:-}" =~ ^xpanel-terminal\ ([A-Za-z0-9]{64})$ ]] || exit 1
token="${BASH_REMATCH[1]}"
response="$(/usr/bin/curl --insecure --silent --show-error --fail --max-time 5 --noproxy '*' \
  -H 'Accept: application/json' --data-urlencode "token=$token" \
  "https://127.0.0.1:$panel_port/internal/terminal/consume")" || exit 1
actual_user="$(printf '%s' "$response" | /usr/bin/php -r '$d=json_decode(file_get_contents("php://stdin"),true); if(is_array($d)&&is_string($d["system_user"]??null)) echo $d["system_user"];')"
[[ "$actual_user" == "$expected_user" ]] || exit 1
workspace_home="$(printf '%s' "$response" | /usr/bin/php -r '$d=json_decode(file_get_contents("php://stdin"),true); if(is_array($d)&&is_string($d["home"]??null)) echo $d["home"];')"
XPANEL_RUNTIME_TOKEN="$(printf '%s' "$response" | /usr/bin/php -r '$d=json_decode(file_get_contents("php://stdin"),true); if(is_array($d)&&is_string($d["runtime_token"]??null)) echo $d["runtime_token"];')"
[[ "$XPANEL_RUNTIME_TOKEN" =~ ^[A-Za-z0-9]{64}$ ]] || exit 1
export XPANEL_RUNTIME_TOKEN
XPANEL_TERMINAL_COLORS="$(printf '%s' "$response" | /usr/bin/php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo is_array($d)&&($d["colorize_terminal"]??false)===true ? "1" : "0";')"
export XPANEL_TERMINAL_COLORS
if [[ -n "$workspace_home" ]]; then
  [[ "$workspace_home" == "/home/$expected_user" ]] || exit 1
  cd "$workspace_home"
  export HOME="$workspace_home"
fi
unset SSH_ORIGINAL_COMMAND token response actual_user workspace_home
exec /bin/bash -l
AUTHORIZE
chown root:root /usr/local/bin/xpanel-vps-terminal-authorize
chmod 0755 /usr/local/bin/xpanel-vps-terminal-authorize

# Existing site jails keep their generated /etc/profile across Host releases.
# Add the guarded alias during VPS updates so the preference works without
# forcing a costly recursive access synchronization for every tenant site.
for jail_profile in /var/lib/xpanel-host/jails/xhi*/etc/profile /var/lib/xpanel-host/jails/xps*/etc/profile; do
  [[ -f "$jail_profile" && ! -L "$jail_profile" && ! -L "$(dirname -- "$jail_profile")" && ! -L "$(dirname -- "$(dirname -- "$jail_profile")")" ]] || continue
  [[ "$jail_profile" =~ ^/var/lib/xpanel-host/jails/(xhi[a-f0-9]{12}|xps[a-z0-9]{9,29})/etc/profile$ ]] || continue
  [[ "$(stat -c %U -- "$jail_profile")" == root ]] || continue
  grep -q 'XPANEL_TERMINAL_STYLE_V2' "$jail_profile" && continue
  cat >> "$jail_profile" <<'TERMINAL_COLORS'
# XPANEL_TERMINAL_STYLE_V2
if [[ "${XPANEL_TERMINAL_COLORS:-0}" == "1" ]]; then
  alias ls='ls --color=auto'
  alias grep='grep --color=auto'
  alias diff='diff --color=auto'
  PS1="\[\e[36m\]${PS1}\[\e[0m\]"
fi
TERMINAL_COLORS
done

cat > /etc/systemd/system/xpanel-vps-terminal-agent.service <<EOF
[Unit]
Description=XPanel VPS managed Host terminal agent
After=network.target ssh.service

[Service]
Type=simple
User=$SERVICE_USER
Group=$SERVICE_USER
Environment=XPANEL_TERMINAL_LISTEN=127.0.0.1:7093
Environment=XPANEL_TERMINAL_SSH_KEY_PATH=$KEY
Environment=XPANEL_TERMINAL_SSH_HOST=127.0.0.1:22
ExecStart=/usr/local/bin/xpanel-vps-terminal-agent
Restart=on-failure
RestartSec=2
NoNewPrivileges=yes
ProtectSystem=strict
ProtectHome=yes
PrivateTmp=yes

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable --now xpanel-vps-terminal-agent.service
systemctl restart xpanel-vps-terminal-agent.service
systemctl is-active --quiet xpanel-vps-terminal-agent.service || fail "terminal agent did not start"
