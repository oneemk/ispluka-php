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
# Synchronize the public tree, but keep the application source in the Git
# repository outside the web document root.
rsync -a --delete \
  --exclude='.well-known/' \
  --exclude='error_log' \
  "$ROOT/public/" "$DOCROOT/"

# The public front controller normally expects bootstrap/app.php one level
# above public/. Because cPanel serves from $HOME/public_html while the Git
# checkout lives elsewhere, make the live index a tiny wrapper that loads the
# repository's real public/index.php. This keeps app/, config/, database/,
# routes/ and vendor/ outside the web root.
cat > "$DOCROOT/index.php" <<PHP
<?php

declare(strict_types=1);

require ${ROOT@Q}/public/index.php;
PHP

chmod 0644 "$DOCROOT/index.php"

printf '%s\n' "Deployment sync complete: $DOCROOT"
printf '%s\n' "Live front controller: $DOCROOT/index.php -> $ROOT/public/index.php"
printf '%s\n' "Asset check:"
if [ -f "$DOCROOT/assets/js/mikrotik-routers.js" ]; then
  ls -lh "$DOCROOT/assets/js/mikrotik-routers.js"
else
  echo "ERROR: mikrotik-routers.js was not deployed" >&2
  exit 1
fi
