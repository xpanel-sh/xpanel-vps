#!/usr/bin/env bash
set -Eeuo pipefail

[[ "$(id -u)" == "0" ]] || { echo "install-dbgate: must run as root" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "install-dbgate: npm is required" >&2; exit 1; }

if ! command -v dbgate-serve >/dev/null 2>&1; then
  echo "Installing DbGate database manager..."
  npm install --global --omit=dev dbgate-serve
fi

executable="$(command -v dbgate-serve || true)"
[[ "$executable" == /usr/bin/dbgate-serve || "$executable" == /usr/local/bin/dbgate-serve ]] \
  || { echo "install-dbgate: executable is unavailable or outside an approved path" >&2; exit 1; }

echo "DbGate available: $executable"
