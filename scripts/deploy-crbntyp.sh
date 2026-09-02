#!/usr/bin/env bash
# Deploy shine festivals to OUR crbntyp mirror. This is the staging step.
# The client target is deploy-client.sh — run this one first, always.
set -euo pipefail

VPS=root@148.230.122.104
REMOTE=/var/www/crbntyp/blsnc
URL=https://crbntyp.com/blsnc/
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT"

echo "==> building"
npm run build

[ -d dist ] || { echo "FAIL: no dist/ after build"; exit 1; }
[ -n "$(find dist -newer package.json -type f -print -quit)" ] \
  || { echo "FAIL: dist/ looks stale - build did not regenerate"; exit 1; }

echo "==> rsync -> $REMOTE"
rsync -avz --exclude '.git/' --exclude 'node_modules/' --exclude '.DS_Store' \
  --exclude '.env' --exclude '.env.*' --exclude '*.sql' \
  ./dist/ "$VPS:$REMOTE/"

echo "==> ownership (www-data:www-data, dirs 755, files 644)"
ssh "$VPS" "chown -R www-data:www-data $REMOTE \
  && find $REMOTE -type d -exec chmod 755 {} \; \
  && find $REMOTE -type f -exec chmod 644 {} \;"

echo "==> verify"
code=$(curl -s -o /dev/null -w '%{http_code}' "$URL")
echo "    $URL -> $code"
[ "$code" = "200" ] || { echo "FAIL: not 200"; exit 1; }

cat <<EOF

Staged on crbntyp. Check it at $URL

When you are happy with it, and only then:
    scripts/deploy-client.sh --live
EOF
