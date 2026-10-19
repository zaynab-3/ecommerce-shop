#!/usr/bin/env bash
# Runs only when MySQL initializes a new volume. No application or live data is reset.
set -euo pipefail
if [[ ! "$MYSQL_DATABASE" =~ ^[a-zA-Z0-9_]+$ || ! "$MYSQL_USER" =~ ^[a-zA-Z0-9_]+$ || "$MYSQL_USER" == root ]]; then
  printf '%s\n' 'Local database and user names must contain only letters, numbers, or underscores; use a non-root application user.' >&2
  exit 1
fi
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --user=root <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_race_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_race_test\`.* TO '${MYSQL_USER}'@'%';
SQL
