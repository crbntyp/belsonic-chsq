#!/usr/bin/env bash
# Deploy shine festivals to the CLIENT's live site over FTPS.
#
# This publishes to a client-facing site. Staging is deploy-crbntyp.sh and it
# comes first, every time. Dry-run unless you pass --live.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

LIVE=0
[ "${1:-}" = "--live" ] && LIVE=1

# ---- credentials -------------------------------------------------------------
[ -f .env ] || { echo "FAIL: no .env"; exit 1; }
get() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '\r'; }
HOST=$(get FTP_HOST); USER=$(get FTP_USER); PASS=$(get FTP_PASS)
[ -n "$HOST" ] && [ -n "$USER" ] && [ -n "$PASS" ] \
  || { echo "FAIL: FTP_HOST / FTP_USER / FTP_PASS missing from .env"; exit 1; }

# ---- FTPS transport ----------------------------------------------------------
# The shine.net FTP server presents a certificate for CN=mail.shine.net with no
# SAN, so connecting as ftp.shine.net / www.shine.net fails verification
# (curl exit 60) even though the cert itself is a valid Let's Encrypt one.
# Prefer the hostname the cert actually matches; only fall back to skipping
# verification, loudly, if that is not reachable.
TLS_HOST="$HOST"
# Capture first: piping straight into `grep -q` SIGPIPEs openssl, and with
# `set -o pipefail` that reads as a failed probe and silently costs you TLS
# verification.
tls_probe=$(openssl s_client -connect "mail.shine.net:21" -starttls ftp </dev/null 2>&1 || true)
if printf '%s' "$tls_probe" | grep -q "Verify return code: 0"; then
  TLS_HOST="mail.shine.net"
  CURL_TLS=(--ssl-reqd --ftp-ssl)
else
  CURL_TLS=(--ssl-reqd --ftp-ssl -k)
  echo "WARNING: falling back to -k (no certificate verification)."
fi

# ---- payload -----------------------------------------------------------------
[ -d dist ] || { echo "FAIL: no dist/ - run scripts/deploy-crbntyp.sh first"; exit 1; }
# NEVER ship secrets. The build copies .env into dist/; it holds both DB
# passwords, the admin password, the Mapbox token and this FTP password.
# crbntyp survives it only because .htaccess denies ^\.env - do not assume
# the client's server does the same.
FILES=$(cd dist && find . -type f \
  ! -name '.DS_Store' \
  ! -name '.env' ! -name '.env.*' \
  ! -name '*.sql' ! -name '*.md' \
  | sed 's|^\./||' | sort)

if (cd dist && ls .env >/dev/null 2>&1); then
  echo "note: dist/.env present and excluded from upload"
fi
COUNT=$(printf '%s\n' "$FILES" | grep -c . || true)

cat <<EOF

  Target      CLIENT LIVE SITE
  Host        $TLS_HOST  (FTPS, explicit TLS)
  User        $USER
  Remote      / (account root - dist/ contents land here)
  Payload     $COUNT files from dist/

EOF

if [ "$LIVE" -eq 0 ]; then
  echo "DRY RUN. Files that would be uploaded:"
  printf '%s\n' "$FILES" | sed 's/^/    /' | head -40
  [ "$COUNT" -gt 40 ] && echo "    ... and $((COUNT - 40)) more"
  echo
  echo "Re-run with --live to actually upload."
  exit 0
fi

# ---- confirmation ------------------------------------------------------------
echo "This uploads to the CLIENT's live site."
echo "Have you already checked this build on crbntyp/blsnc/? "
printf 'Type the word  client  to proceed: '
read -r answer
[ "$answer" = "client" ] || { echo "Aborted."; exit 1; }

# ---- upload ------------------------------------------------------------------
fail=0
while IFS= read -r f; do
  [ -n "$f" ] || continue
  dir=$(dirname "$f")
  if curl -sS "${CURL_TLS[@]}" --ftp-create-dirs \
       -T "dist/$f" "ftp://$TLS_HOST/$f" --user "$USER:$PASS"; then
    echo "  ok   $f"
  else
    echo "  FAIL $f"; fail=$((fail + 1))
  fi
done <<< "$FILES"

echo
if [ "$fail" -eq 0 ]; then
  echo "Uploaded $COUNT files. Check the client URL before telling anyone it is done."
else
  echo "$fail of $COUNT files FAILED. The site may be in a mixed state - resolve now."
  exit 1
fi
