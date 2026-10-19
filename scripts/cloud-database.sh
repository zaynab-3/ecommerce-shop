#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
source scripts/cloud-php.sh
mkdir -p /workspace/.runtime/mysql-data
php scripts/local-database.php
docker compose -f compose.yaml -f compose.cloud.yaml up -d --wait
