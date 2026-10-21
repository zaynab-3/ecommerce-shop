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
if [[ -f "$RUNNER_TEMP/mesh-preview-admin.enc" ]]; then export MESH_PREVIEW_ADMIN_ENABLED=1; fi
nohup php -S 127.0.0.1:8000 -t public scripts/owner-preview-router.php \
    > "$RUNNER_TEMP/mesh-preview-php.log" 2>&1 < /dev/null &
echo "$!" > "$RUNNER_TEMP/mesh-preview-php.pid"
curl --fail --silent --show-error --retry 12 --retry-delay 2 --retry-all-errors "$preview_url/up" > /dev/null
curl --fail --silent --show-error "$preview_url/shop" --output "$RUNNER_TEMP/mesh-preview-shop.html"
grep -q "$preview_url/css/store-scrollbars.css" "$RUNNER_TEMP/mesh-preview-shop.html"
if grep -q 'http://127.0.0.1:8000' "$RUNNER_TEMP/mesh-preview-shop.html"; then echo 'Preview contains a local URL.' >&2; exit 1; fi
printf '{"url":"%s","commit":"%s","run_id":"%s"}\n' "$preview_url" "$GITHUB_SHA" "$GITHUB_RUN_ID" > "$RUNNER_TEMP/mesh-preview-link.json"
python3 - <<'PY'
import base64, json, os
from pathlib import Path
payload = {"ref": os.environ["GITHUB_SHA"], "environment": "owner-preview", "auto_merge": False,
           "required_contexts": [], "transient_environment": True, "production_environment": False,
           "description": "Temporary owner demonstration with isolated demo data"}
encrypted = Path(os.environ["RUNNER_TEMP"]) / "mesh-preview-admin.enc"
if encrypted.exists():
    payload["payload"] = {"admin_credentials": base64.b64encode(encrypted.read_bytes()).decode(), "encryption": "RSA-OAEP-SHA256"}
(Path(os.environ["RUNNER_TEMP"]) / "mesh-preview-deployment.json").write_text(json.dumps(payload))
PY
preview_deployment=$(gh api --method POST "repos/$GITHUB_REPOSITORY/deployments" --input "$RUNNER_TEMP/mesh-preview-deployment.json" --jq .id)
printf '%s\n' "$preview_deployment" > "$RUNNER_TEMP/mesh-preview-deployment-id"
gh api --method POST "repos/$GITHUB_REPOSITORY/deployments/$preview_deployment/statuses" \
    --field state=success --field environment_url="$preview_url" \
    --field log_url="$GITHUB_SERVER_URL/$GITHUB_REPOSITORY/actions/runs/$GITHUB_RUN_ID" \
    --field auto_inactive=true > /dev/null
printf 'url=%s\n' "$preview_url" >> "$GITHUB_OUTPUT"
printf '### Owner preview\n\n[%s](%s)\n\nTemporary demo; expires after the configured lifetime or when this run is cancelled. Starting a new preview stops the previous one.\n' "$preview_url" "$preview_url" >> "$GITHUB_STEP_SUMMARY"
printf '::notice title=Owner preview::%s\n' "$preview_url"
