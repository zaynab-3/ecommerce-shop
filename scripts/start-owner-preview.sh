#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ "${GITHUB_ACTIONS:-}" != true || ! -f .env ]]; then
    echo 'Start the owner preview from its GitHub Actions workflow.' >&2
    exit 1
fi
# Pinned official binary, verified before execution.
curl --fail --silent --show-error --location --retry 3 \
    https://github.com/cloudflare/cloudflared/releases/download/2026.10.3/cloudflared-linux-amd64 \
    --output "$RUNNER_TEMP/mesh-cloudflared"
printf '%s  %s\n' '053b80b0a23031a038fdbf3c073f59f82b8f1f152aa67971d7383ffafc0dbab0' "$RUNNER_TEMP/mesh-cloudflared" | sha256sum --check
chmod 700 "$RUNNER_TEMP/mesh-cloudflared"
export RUNNER_TRACKING_ID=''
nohup "$RUNNER_TEMP/mesh-cloudflared" tunnel --no-autoupdate --url http://127.0.0.1:8000 --protocol http2 \
    > "$RUNNER_TEMP/mesh-preview-tunnel.log" 2>&1 < /dev/null &
echo "$!" > "$RUNNER_TEMP/mesh-preview-tunnel.pid"
preview_url=''
for attempt in {1..45}; do
    preview_url=$(sed -nE 's/.*(https:\/\/[a-z0-9-]+\.trycloudflare\.com).*/\1/p' "$RUNNER_TEMP/mesh-preview-tunnel.log" | head -1)
    if [[ -n "$preview_url" ]]; then break; fi
    sleep 2
done
if [[ -z "$preview_url" ]]; then cat "$RUNNER_TEMP/mesh-preview-tunnel.log"; exit 1; fi
sed -i "s|^APP_URL=.*|APP_URL=$preview_url|" .env
export MESH_PREVIEW_HOST="${preview_url#https://}" PHP_CLI_SERVER_WORKERS=4
nohup php -S 127.0.0.1:8000 -t public scripts/owner-preview-router.php \
    > "$RUNNER_TEMP/mesh-preview-php.log" 2>&1 < /dev/null &
echo "$!" > "$RUNNER_TEMP/mesh-preview-php.pid"
curl --fail --silent --show-error --retry 12 --retry-delay 2 --retry-all-errors "$preview_url/up" > /dev/null
curl --fail --silent --show-error "$preview_url/shop" --output "$RUNNER_TEMP/mesh-preview-shop.html"
grep -q "$preview_url/css/store-scrollbars.css" "$RUNNER_TEMP/mesh-preview-shop.html"
if grep -q 'http://127.0.0.1:8000' "$RUNNER_TEMP/mesh-preview-shop.html"; then echo 'Preview contains a local URL.' >&2; exit 1; fi
printf '{"url":"%s","commit":"%s","run_id":"%s"}\n' "$preview_url" "$GITHUB_SHA" "$GITHUB_RUN_ID" > "$RUNNER_TEMP/mesh-preview-link.json"
printf 'url=%s\n' "$preview_url" >> "$GITHUB_OUTPUT"
printf '### Owner preview\n\n[%s](%s)\n\nTemporary demo; expires after the configured lifetime or when this run is cancelled. Starting a new preview stops the previous one.\n' "$preview_url" "$preview_url" >> "$GITHUB_STEP_SUMMARY"
printf '::notice title=Owner preview::%s\n' "$preview_url"
