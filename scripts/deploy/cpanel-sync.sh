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

# cPanel serves ispluka.online from $HOME/public_html.
# Synchronize only the public tree; keep application source outside the web root.
rsync -a --delete \
  --exclude='.well-known/' \
  --exclude='error_log' \
  "$ROOT/public/" "$DOCROOT/"

# cPanel's document root is separate from the Git checkout. Keep a small
# wrapper in the live document root that loads the real front controller.
# Use POSIX shell syntax so this script works with /bin/sh on cPanel.
cat > "$DOCROOT/index.php" <<PHP
<?php

declare(strict_types=1);

require '$ROOT/public/index.php';
PHP

chmod 0644 "$DOCROOT/index.php"

# Verify the deployed entry point and critical MikroTik asset before reporting success.
if [ ! -s "$DOCROOT/index.php" ]; then
  echo "ERROR: live index.php is empty" >&2
  exit 1
fi

if [ ! -f "$DOCROOT/assets/js/mikrotik-routers.js" ]; then
  echo "ERROR: mikrotik-routers.js was not deployed" >&2
  exit 1
fi

printf '%s\n' "Deployment sync complete: $DOCROOT"
printf '%s\n' "Live front controller: $DOCROOT/index.php -> $ROOT/public/index.php"
printf '%s\n' "Asset check:"
ls -lh "$DOCROOT/assets/js/mikrotik-routers.js"
printf '%s\n' "Index check:"
ls -lh "$DOCROOT/index.php"
