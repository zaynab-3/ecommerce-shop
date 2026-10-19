# Production deployment

## Runtime

Use a maintained PHP 8.2+ release with Composer and these extensions: PDO plus your database driver, mbstring, OpenSSL, tokenizer, XML/DOM, cURL, GD with WebP, fileinfo, ZIP, EXIF, and the normal Laravel prerequisites. The cloud-local runtime is for this Debian 13 development machine; it is not a replacement for a managed production PHP installation.

Point Nginx/Apache to `public/`, never the repository root. Deny script execution inside uploaded image storage. Configure request/body upload limits to match PHP (`15M` upload size, `180M` post size in the cloud runtime; the application accepts at most ten 12 MB files). Product photos are decoded and rewritten as WebP; SVG product photos are refused. Category/subcategory SVG icons are sanitized with a restricted shape/attribute allowlist, stored outside public storage, and served through a dedicated UUID route with a sandbox CSP.

Set production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-store.example
APP_TIMEZONE=Asia/Beirut
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
ADMIN_PATH=choose-a-private-route
```

Create and retain a unique `APP_KEY` securely. Do not rotate it during routine deployments. Preserve private database credentials outside source control. WhatsApp checkout only needs the phone number in admin Store settings; it has no API credentials.

Use MySQL 8.4 with `pdo_mysql`; it is the configured default and the database exercised by the feature suite and synchronized checkout race. Configure a dedicated application database/user and least-privilege access on the target server. For multiple web servers or substantial traffic, use shared Redis cache and sessions, with the appropriate extension or supported client. Checkout uses transactions, row locks, guarded stock decrements, and deadlock retries. Verify the production database, Redis, PHP-FPM, backups, and capacity on the target infrastructure before launch.

The bundled Compose stack is for development. MySQL and phpMyAdmin bind to loopback, and phpMyAdmin requires login. Do not expose phpMyAdmin publicly or deploy development/root database passwords. On production keep the app user confined to the shop database; the `_test` and `_race_test` databases and their destructive test permissions belong only on development/CI servers.

## Deploy process

1. Provision the database, HTTPS virtual host, writable `storage/` and `bootstrap/cache/`, and private runtime variables.
2. Run locked Composer installation (`composer install --no-dev --prefer-dist --optimize-autoloader`) and `php artisan migrate --force`. Run the normal `DatabaseSeeder` to insert default settings and delivery-area names if needed; **do not run `DemoCatalogSeeder` in production**.
3. Link public image storage and use `php artisan config:cache`, `route:cache`, and `view:cache` after environment values are configured.
4. Create an owner with `php artisan shop:admin`, or migrate your intended owner account securely. Delete/disable the cloud demo owner and never deploy `.local-admin.json`.
5. Run PHP-FPM with OPcache and sensible worker limits for the available RAM. WhatsApp checkout needs no queue worker. File sessions/cache are for single-machine development.
6. Configure gzip/Brotli and cache headers for static assets, with deployment cache invalidation. Keep all admin, cart, checkout, and customer/order responses private and uncached. The app marks those paths `no-store`.
7. Schedule database/storage backups, test restoration, restrict operator access, monitor errors and stock discrepancies, and keep PHP and Composer packages patched.

The repository uses exact-commit official `codeload.github.com` dist URLs because this cloud machine blocks the GitHub API archive endpoint. Composer lock references and versions are unchanged. A future `composer update` may restore standard `api.github.com` dist URLs; either allow that official endpoint or use the corresponding official codeload archive for the same commit when refreshing the lock. Do not disable TLS or artifact verification.

## Security and readiness

Implemented controls include server-side validation and role checks, hashed passwords, login rate limits, CSRF tokens, bound SQL parameters, escaped Blade templates, strict same-origin CORS, CSP without inline scripts, anti-framing and MIME-sniffing headers, HTTPS-only HSTS when the app sees HTTPS, session-scoped order confirmations, and audit records. If running behind a reverse proxy, configure trusted proxies explicitly so Laravel correctly recognizes HTTPS and secure cookies. Use a private network/VPN or an identity-aware gateway for the admin if appropriate. Two-factor authentication is not implemented.

An unlisted admin URL does not make authentication unnecessary, and no software can be guaranteed completely secure. Configure appropriate infrastructure limits, backups, log retention, and business policies, then test on the actual hosting stack. Don’t expose the PHP development server to production traffic.

Stock is synchronized by polling, with up to roughly 20 seconds of display staleness from the poll interval plus cache TTL. Checkout validates current stock regardless of what a shopper saw. To support instant push updates, add a broadcast service as a separate enhancement.

Load results on the four-worker development server do not establish how many sustained shoppers your production host will support. Run a representative staged workload including browse/search, images, carts, and checkout at the intended database size, then size PHP-FPM, Redis, and SQL connections from those measurements.
