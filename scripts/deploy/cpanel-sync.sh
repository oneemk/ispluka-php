#!/bin/sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)"
DOCROOT="${ISPLUKA_DOCROOT:-$HOME/public_html}"

if [ ! -d "$ROOT/public" ]; then
  echo "ERROR: public directory not found: $ROOT/public" >&2
  exit 1
fi

if [ ! -d "$DOCROOT" ]; then
  echo "ERROR: document root not found: $DOCROOT" >&2
  exit 1
fi

# cPanel currently serves ispluka.online from $HOME/public_html.
# Keep the live document root synchronized with the repository's public tree.
# Preserve cPanel/Let's Encrypt files that are not part of the application.
rsync -a --delete \
  --exclude='.well-known/' \
  --exclude='error_log' \
  "$ROOT/public/" "$DOCROOT/"

printf '%s\n' "Deployment sync complete: $DOCROOT"
printf '%s\n' "Asset check:"
if [ -f "$DOCROOT/assets/js/mikrotik-routers.js" ]; then
  ls -lh "$DOCROOT/assets/js/mikrotik-routers.js"
else
  echo "ERROR: mikrotik-routers.js was not deployed" >&2
  exit 1
fi
