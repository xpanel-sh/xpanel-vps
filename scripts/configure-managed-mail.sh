#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

fail() { echo "xpanel-managed-mail: $*" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail "root is required"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

configure=true
if [[ "${1:-}" == --install-packages || "${1:-}" == --packages-only ]]; then
  export DEBIAN_FRONTEND=noninteractive
  echo 'postfix postfix/mailname string localhost' | debconf-set-selections
  echo 'postfix postfix/main_mailer_type select Internet Site' | debconf-set-selections
  apt-get update -y
  apt-get install -y --no-install-recommends \
    postfix dovecot-core dovecot-imapd dovecot-lmtpd opendkim opendkim-tools \
    ssl-cert swaks nftables python3
  [[ "${1:-}" != --packages-only ]] || configure=false
elif [[ $# -ne 0 ]]; then
  fail "usage: $0 [--install-packages|--packages-only]"
fi

[[ "$configure" == true ]] || exit 0

release="$(realpath /opt/xpanel-host/current 2>/dev/null || true)"
[[ "$release" =~ ^/opt/xpanel-host/releases/[A-Za-z0-9._-]+$ ]] \
  || fail "the current Host release is unavailable"

for command in postconf postmap doveconf opendkim nft; do
  command -v "$command" >/dev/null 2>&1 || fail "missing $command; install the managed mail packages"
done

mail_hostname="${XPANEL_MAIL_HOSTNAME:-}"
if [[ -z "$mail_hostname" && -f "$root/.env" ]]; then
  mail_hostname="$(sed -n 's/^XPANEL_MAIL_HOSTNAME=//p' "$root/.env" | tail -n1 | tr -d '"')"
  panel_domain="$(sed -n 's/^XPANEL_PANEL_DOMAIN=//p' "$root/.env" | tail -n1 | tr -d '"')"
  [[ -n "$mail_hostname" ]] || [[ -z "$panel_domain" ]] || mail_hostname="mail.$panel_domain"
fi
[[ -n "$mail_hostname" ]] || mail_hostname="$(hostname -f 2>/dev/null || hostname)"
mail_hostname="${mail_hostname,,}"
[[ "$mail_hostname" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || fail "invalid mail hostname"

if ! getent group vmail >/dev/null; then groupadd --system --gid 5000 vmail; fi
if ! id vmail >/dev/null 2>&1; then
  useradd --system --uid 5000 --gid vmail --home-dir /var/lib/xpanel-host/mail --shell /usr/sbin/nologin vmail
fi
mail_uid="$(id -u vmail)"
mail_gid="$(id -g vmail)"
install -d -o vmail -g vmail -m 2770 /var/lib/xpanel-host/mail
install -d -o root -g root -m 0755 /etc/xpanel-host/mail
for map in users domains mailboxes sender-login aliases send-limits; do touch "/etc/xpanel-host/mail/$map"; done
chown root:dovecot /etc/xpanel-host/mail/users
chmod 0640 /etc/xpanel-host/mail/users
for map in domains mailboxes sender-login aliases; do
  chmod 0644 "/etc/xpanel-host/mail/$map"
  postmap "/etc/xpanel-host/mail/$map"
done

certificate=/etc/ssl/certs/ssl-cert-snakeoil.pem
private_key=/etc/ssl/private/ssl-cert-snakeoil.key
if [[ -s "/etc/letsencrypt/live/$mail_hostname/fullchain.pem" && -s "/etc/letsencrypt/live/$mail_hostname/privkey.pem" ]]; then
  certificate="/etc/letsencrypt/live/$mail_hostname/fullchain.pem"
  private_key="/etc/letsencrypt/live/$mail_hostname/privkey.pem"
fi
cat > /etc/dovecot/conf.d/99-xpanel-host.conf <<EOF
protocols = imap lmtp
mail_location = maildir:~/Maildir
mail_uid = $mail_uid
mail_gid = $mail_gid
first_valid_uid = $mail_uid
last_valid_uid = $mail_uid
auth_mechanisms = plain login
disable_plaintext_auth = yes
ssl = required
ssl_cert = <$certificate
ssl_key = <$private_key
passdb {
  driver = passwd-file
  args = username_format=%u /etc/xpanel-host/mail/users
}
userdb {
  driver = passwd-file
  args = username_format=%u /etc/xpanel-host/mail/users
}
service lmtp {
  unix_listener /var/spool/postfix/private/dovecot-lmtp {
    mode = 0660
    user = postfix
    group = postfix
  }
}
service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0660
    user = postfix
    group = postfix
  }
}
protocol lmtp {
  postmaster_address = postmaster@$mail_hostname
  mail_plugins = \$mail_plugins quota
}
protocol imap {
  mail_plugins = \$mail_plugins quota imap_quota
}
plugin {
  quota = maildir:User quota
}
EOF

postconf -e "myhostname = $mail_hostname"
postconf -e 'mydestination = $myhostname, localhost.localdomain, localhost'
postconf -e 'virtual_mailbox_domains = hash:/etc/xpanel-host/mail/domains'
postconf -e 'virtual_mailbox_maps = hash:/etc/xpanel-host/mail/mailboxes'
postconf -e 'virtual_alias_maps = hash:/etc/xpanel-host/mail/aliases'
postconf -e 'virtual_transport = lmtp:unix:private/dovecot-lmtp'
postconf -e 'smtpd_recipient_restrictions = reject_non_fqdn_recipient, reject_unknown_recipient_domain, permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination'
postconf -e 'smtpd_tls_security_level = may'
postconf -e 'smtp_tls_security_level = may'
postconf -e "smtpd_tls_cert_file = $certificate"
postconf -e "smtpd_tls_key_file = $private_key"
postconf -e 'smtpd_sasl_type = dovecot'
postconf -e 'smtpd_sasl_path = private/auth'
postconf -e 'smtpd_sender_login_maps = hash:/etc/xpanel-host/mail/sender-login'
postconf -e 'disable_vrfy_command = yes'
postconf -M 'submission/inet=submission inet n - y - - smtpd'
postconf -P 'submission/inet/syslog_name=postfix/submission'
postconf -P 'submission/inet/smtpd_tls_security_level=encrypt'
postconf -P 'submission/inet/smtpd_sasl_auth_enable=yes'
postconf -P 'submission/inet/smtpd_sasl_type=dovecot'
postconf -P 'submission/inet/smtpd_sasl_path=private/auth'
postconf -P 'submission/inet/smtpd_client_restrictions=permit_sasl_authenticated,reject'
postconf -P 'submission/inet/smtpd_sender_restrictions=reject_sender_login_mismatch'
postconf -P 'submission/inet/smtpd_relay_restrictions=permit_sasl_authenticated,reject'

install -d -o opendkim -g opendkim -m 0750 /etc/xpanel-host/dkim
touch /etc/xpanel-host/dkim/key.table /etc/xpanel-host/dkim/signing.table /etc/xpanel-host/dkim/trusted.hosts
chown opendkim:opendkim /etc/xpanel-host/dkim/key.table /etc/xpanel-host/dkim/signing.table /etc/xpanel-host/dkim/trusted.hosts
chmod 0640 /etc/xpanel-host/dkim/key.table /etc/xpanel-host/dkim/signing.table /etc/xpanel-host/dkim/trusted.hosts
printf '127.0.0.1\nlocalhost\n' > /etc/xpanel-host/dkim/trusted.hosts
cat > /etc/opendkim.conf <<'EOF'
Syslog                  yes
UMask                   007
Canonicalization        relaxed/simple
Mode                    sv
OversignHeaders         From
Socket                  inet:8891@127.0.0.1
UserID                  opendkim
PidFile                 /run/opendkim/opendkim.pid
KeyTable                refile:/etc/xpanel-host/dkim/key.table
SigningTable            refile:/etc/xpanel-host/dkim/signing.table
ExternalIgnoreList      refile:/etc/xpanel-host/dkim/trusted.hosts
InternalHosts           refile:/etc/xpanel-host/dkim/trusted.hosts
EOF
cat > /etc/tmpfiles.d/xpanel-host-opendkim.conf <<'EOF'
d /run/opendkim 0750 opendkim opendkim - -
EOF
systemd-tmpfiles --create /etc/tmpfiles.d/xpanel-host-opendkim.conf
postconf -e 'milter_default_action = accept'
postconf -e 'milter_protocol = 6'
postconf -e 'smtpd_milters = inet:127.0.0.1:8891'
postconf -e 'non_smtpd_milters = inet:127.0.0.1:8891'

XPANEL_SITE_USER=www-data bash "$release/scripts/configure-mail-rate-policy.sh"
doveconf -n >/dev/null
postfix check
opendkim -n -x /etc/opendkim.conf
systemctl enable dovecot postfix opendkim
systemctl restart dovecot postfix opendkim
systemctl is-active --quiet dovecot
systemctl is-active --quiet postfix
systemctl is-active --quiet opendkim
echo "Correo administrado preparado: $mail_hostname"
