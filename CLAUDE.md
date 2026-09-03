# shine festivals

## This is NOT `shine.net/belsonic`

Two projects answer to "shine"/"belsonic". This one is the multi-venue CMS
deploying to crbntyp `/blsnc/`. `shine.net/belsonic` is the live
**www.belsonic.com** client site — different folder, container and FTP account.
Confusing them is the established failure mode.

## The database is LOCKED

No schema changes, no migrations, no writes. Client system, read-only. `.env`
holds two — `LOCAL_DB_*` (crbntyp) and `PROD_DB_*` (client live). Both locked.

## Deploy — TWO targets, crbntyp is STAGING. Order is not optional.

1. Build and test locally.
2. `scripts/deploy-crbntyp.sh` → `/var/www/crbntyp/blsnc/`
3. Check https://crbntyp.com/blsnc/ — **all iteration happens here.**
4. **Jonny signs off.** Never assumed, never skipped.
5. `scripts/deploy-client.sh --live` → FTPS to the client.

If you are unsure which step you are on, you are on step 3.

Client target: FTPS to `ftp.shine.net` as `jonny_shinefestivals`, landing in
the site root — `dist/*` goes to root, not `dist/` itself. **Connect to
`mail.shine.net`**: the cert has no SAN so the `ftp.` name fails verification.
**Never ship `.env`** — the build copies it into `dist/` and it holds every
credential this project has. Both scripts exclude `.env*`, `*.sql`, `*.md`.

## Branch, build, local dev

Branch `fix/location-leaflet-maps`, not master. `npm run build` is sass + cpx
copies, not a bundler. **There is no PHP on this machine** — `npm run serve`
cannot work. Local dev is `docker start shine-festivals-web` (php:8.1-apache,
port 8080, currently stopped). Port 8080 is also claimed by
`definitive-leagues-php-1`; only one can run.

## Landmines

- `_tools/deploy/deploy.sh` deliberately refuses this project — use the two
  scripts above.
- This file is tracked. Never put credentials in it — a hardcoded admin login
  was stripped from it 2 Sep. Admin creds live in `.env`.
- The previous 607-line architecture dump is in git history.
