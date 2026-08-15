#!/bin/sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)"
DOCROOT="${ISPLUKA_DOCROOT:-$HOME/public_html}"
LIVE_INDEX="$DOCROOT/index.php"
LIVE_HTACCESS="$DOCROOT/.htaccess"

if [ ! -d "$ROOT/public" ]; then
  echo "ERROR: public directory not found: $ROOT/public" >&2
  exit 1
fi

if [ ! -f "$ROOT/public/index.php" ]; then
  echo "ERROR: public/index.php not found" >&2
  exit 1
fi

if [ ! -f "$ROOT/bootstrap/app.php" ]; then
  echo "ERROR: bootstrap/app.php not found" >&2
  exit 1
fi

if [ ! -d "$DOCROOT" ]; then
  echo "ERROR: document root not found: $DOCROOT" >&2
  exit 1
fi

# Validate the repository front controller before touching the live document root.
if ! php -l "$ROOT/public/index.php" >/dev/null; then
  echo "ERROR: public/index.php has a PHP syntax error" >&2
  exit 1
fi

# cPanel serves ispluka.online from $HOME/public_html. Synchronize the public
# tree, but do not let rsync temporarily replace the live wrapper index.php.
# This prevents a failed/partial sync from taking the whole site offline.
rsync -a --delete \
  --exclude='.well-known/' \
  --exclude='error_log' \
  --exclude='index.php' \
  "$ROOT/public/" "$DOCROOT/"

# Install the live wrapper atomically. The wrapper loads the real controller
# from the Git checkout, keeping application source outside the web root.
TMP_INDEX="$DOCROOT/.index.php.tmp.$$"
trap 'rm -f "$TMP_INDEX"' EXIT HUP INT TERM

cat > "$TMP_INDEX" <<PHP
<?php

declare(strict_types=1);

require '$ROOT/public/index.php';
PHP

chmod 0644 "$TMP_INDEX"
php -l "$TMP_INDEX" >/dev/null
mv -f "$TMP_INDEX" "$LIVE_INDEX"
trap - EXIT HUP INT TERM

# The public/.htaccess is intentionally deployed to the document root.
# Verify that both the live entry point and the critical asset exist.
if [ ! -s "$LIVE_INDEX" ]; then
  echo "ERROR: live index.php is empty" >&2
  exit 1
fi

if [ ! -f "$LIVE_HTACCESS" ]; then
  echo "ERROR: live .htaccess was not deployed" >&2
  exit 1
fi

if [ ! -f "$DOCROOT/assets/js/mikrotik-routers.js" ]; then
  echo "ERROR: mikrotik-routers.js was not deployed" >&2
  exit 1
fi

printf '%s\n' "Deployment sync complete: $DOCROOT"
printf '%s\n' "Live front controller: $LIVE_INDEX -> $ROOT/public/index.php"
printf '%s\n' "Index check:"
ls -lh "$LIVE_INDEX"
printf '%s\n' "Asset check:"
ls -lh "$DOCROOT/assets/js/mikrotik-routers.js"
printf '%s\n' "Rewrite check:"
ls -lh "$LIVE_HTACCESS"
