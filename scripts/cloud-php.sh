#!/usr/bin/env bash
# Source this file to activate PHP. The cloud image needs a user-local runtime.
# Existing system PHP is preferred; normal deployments can install PHP normally.
set -euo pipefail
mesh_runtime=/workspace/.runtime
if ! command -v php >/dev/null 2>&1; then
  if [ ! -x "$mesh_runtime/php/usr/bin/php8.4" ]; then
    if [ ! -f /etc/debian_version ] || ! command -v dpkg-deb >/dev/null; then
      printf '%s\n' 'Install PHP 8.2+ with PDO, SQLite or MySQL, GD, mbstring, XML, cURL, ZIP, and fileinfo.' >&2
      return 1
    fi
    mkdir -p "$mesh_runtime/apt/lists/partial" "$mesh_runtime/apt/cache/archives/partial" "$mesh_runtime/php" "$mesh_runtime/bin"
    cat > "$mesh_runtime/apt/sources.list" <<'EOF'
deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://deb.debian.org/debian trixie main
deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] https://deb.debian.org/debian-security trixie-security main
EOF
    mesh_apt=(-o Dir::Etc::main=/dev/null -o Dir::Etc::parts=- -o "Dir::Etc::sourcelist=$mesh_runtime/apt/sources.list" -o Dir::Etc::sourceparts=- -o "Dir::State::lists=$mesh_runtime/apt/lists" -o "Dir::Cache=$mesh_runtime/apt/cache")
    /usr/bin/apt-get "${mesh_apt[@]}" update
    (
      cd "$mesh_runtime/apt/cache/archives"
      /usr/bin/apt-get "${mesh_apt[@]}" download php8.4-cli php8.4-common php8.4-sqlite3 php8.4-mbstring php8.4-xml php8.4-gd php8.4-curl php8.4-zip php8.4-opcache libxml2 libsqlite3-0 libonig5 libsodium23 libargon2-1 libpcre2-8-0 libssl3t64 libcurl4t64 libgd3 libzip5
      for mesh_package in ./*.deb; do dpkg-deb -x "$mesh_package" "$mesh_runtime/php"; done
    )
  fi
  mkdir -p "$mesh_runtime/bin"
  cat > "$mesh_runtime/php/php.ini" <<'EOF'
extension_dir=/workspace/.runtime/php/usr/lib/php/20240924
extension=ctype
extension=curl
extension=dom
extension=exif
extension=fileinfo
extension=gd
extension=iconv
extension=mbstring
extension=pdo
extension=pdo_sqlite
extension=sqlite3
extension=tokenizer
extension=xml
extension=xmlreader
extension=xmlwriter
extension=simplexml
extension=zip
extension=phar
extension=posix
memory_limit=512M
upload_max_filesize=15M
post_max_size=180M
date.timezone=Asia/Beirut
expose_php=Off
EOF
  cat > "$mesh_runtime/bin/php" <<'EOF'
#!/bin/sh
export PHPRC=/workspace/.runtime/php/php.ini
export LD_LIBRARY_PATH=/workspace/.runtime/php/usr/lib/x86_64-linux-gnu${LD_LIBRARY_PATH:+:$LD_LIBRARY_PATH}
exec /workspace/.runtime/php/usr/bin/php8.4 -c /workspace/.runtime/php/php.ini "$@"
EOF
  chmod +x "$mesh_runtime/bin/php"
  export PATH="$mesh_runtime/bin:$PATH"
fi
export COMPOSER_HOME="$mesh_runtime/composer"
export COMPOSER_CACHE_DIR="$mesh_runtime/composer-cache"
if ! command -v composer >/dev/null 2>&1; then
  mkdir -p "$mesh_runtime/bin"
  if [ ! -f "$mesh_runtime/bin/composer.phar" ]; then
    # Fetch the official release over verified TLS. No signature/TLS bypasses.
    curl --fail --location --silent --show-error https://github.com/composer/composer/releases/download/2.8.12/composer.phar -o "$mesh_runtime/bin/composer.phar"
  fi
  cat > "$mesh_runtime/bin/composer" <<'EOF'
#!/bin/sh
exec php /workspace/.runtime/bin/composer.phar "$@"
EOF
  chmod +x "$mesh_runtime/bin/composer"
  export PATH="$mesh_runtime/bin:$PATH"
fi
