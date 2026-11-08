#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == "0" ]] || { echo "install-dbgate: must run as root" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "install-dbgate: npm is required" >&2; exit 1; }

executable=""
for candidate in /usr/local/bin/dbgate-serve /usr/bin/dbgate-serve; do
  [[ -x "$candidate" ]] && executable="$candidate" && break
done
if [[ -z "$executable" ]]; then
  echo "Installing DbGate database manager..."
  npm install --global --omit=dev dbgate-serve
fi

executable=""
for candidate in /usr/local/bin/dbgate-serve /usr/bin/dbgate-serve; do
  [[ -x "$candidate" ]] && executable="$candidate" && break
done
[[ "$executable" == /usr/bin/dbgate-serve || "$executable" == /usr/local/bin/dbgate-serve ]] \
  || { echo "install-dbgate: executable is unavailable or outside an approved path" >&2; exit 1; }

echo "DbGate available: $executable"
