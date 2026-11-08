#!/usr/bin/env bash
set -Eeuo pipefail
umask 022

[[ "$(id -u)" == "0" ]] || { echo "install-dbgate: must run as root" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "install-dbgate: npm is required" >&2; exit 1; }

executable=""
for candidate in /usr/local/bin/dbgate-serve /usr/bin/dbgate-serve; do
  [[ -x "$candidate" ]] && executable="$candidate" && break
done
if [[ -z "$executable" ]]; then
  echo "Installing DbGate database manager..."
  npm_config_umask=022 npm install --global --prefix /usr/local --omit=dev dbgate-serve@latest
fi

executable=""
for candidate in /usr/local/bin/dbgate-serve /usr/bin/dbgate-serve; do
  [[ -x "$candidate" ]] && executable="$candidate" && break
done
[[ "$executable" == /usr/bin/dbgate-serve || "$executable" == /usr/local/bin/dbgate-serve ]] \
  || { echo "install-dbgate: executable is unavailable or outside an approved path" >&2; exit 1; }

if [[ "$executable" == /usr/local/bin/dbgate-serve ]]; then
  package_dir=/usr/local/lib/node_modules/dbgate-serve
  [[ -d "$package_dir" && ! -L "$package_dir" && "$(stat -c %u "$package_dir")" == 0 ]] \
    || { echo "install-dbgate: unexpected package directory" >&2; exit 1; }
  target="$(readlink -f -- "$executable")"
  [[ "$target" == "$package_dir/"* && -f "$target" && ! -L "$target" && "$(stat -c %u "$target")" == 0 ]] \
    || { echo "install-dbgate: unexpected executable target" >&2; exit 1; }
  # The VPS installer uses umask 027. A globally installed npm package must
  # remain traversable and readable by every isolated, unprivileged Host UID.
  for parent in /usr/local /usr/local/bin /usr/local/lib /usr/local/lib/node_modules; do
    [[ -d "$parent" && ! -L "$parent" && "$(stat -c %u "$parent")" == 0 ]] \
      || { echo "install-dbgate: unsafe npm directory $parent" >&2; exit 1; }
    chmod a+rx "$parent"
  done
  unexpected_owner="$(find -P "$package_dir" -xdev \( -type f -o -type d \) ! -uid 0 -print -quit)"
  [[ -z "$unexpected_owner" ]] || { echo "install-dbgate: unexpected owner in $package_dir" >&2; exit 1; }
  find -P "$package_dir" -xdev -type d -exec chmod a+rx -- {} +
  find -P "$package_dir" -xdev -type f -exec chmod a+rX -- {} +
  chmod a+x "$target"
fi

echo "DbGate available: $executable"
