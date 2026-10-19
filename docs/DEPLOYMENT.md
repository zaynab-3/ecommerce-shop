# Production deployment

## Runtime

Use a maintained PHP 8.2+ release with Composer and these extensions: PDO plus your database driver, mbstring, OpenSSL, tokenizer, XML/DOM, cURL, GD with WebP, fileinfo, ZIP, EXIF, and the normal Laravel prerequisites. The cloud-local runtime is for this Debian 13 development machine; it is not a replacement for a managed production PHP installation.

Point Nginx/Apache to `public/`, never the repository root. Deny script execution inside uploaded image storage. Configure request/body upload limits to match PHP (`15M` upload size, `180M` post size in the cloud runtime; the application accepts at most ten 12 MB files). All uploads are decoded and rewritten as WebP, and user-supplied SVG files are refused.

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

For substantial concurrency and a large catalog, use MySQL 8+/MariaDB or PostgreSQL and shared Redis cache and sessions. Install the appropriate PDO driver and Redis extension or supported client. The local runtime currently supplies SQLite, and the test suite was run against SQLite; the production database and Redis configuration must be verified on the target infrastructure before launch. Local SQLite uses WAL and a busy timeout. Laravel honors immediate transactions on PHP 8.4+; checkout and fulfillment also use bounded retries for SQLite lock conflicts, including PHP 8.2/8.3 deferred transactions. The guarded stock update and transaction service also use row locks where supported.

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
