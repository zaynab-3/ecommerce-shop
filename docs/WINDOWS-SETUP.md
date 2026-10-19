# Download, run, and test on Windows

Use PowerShell for the commands below. The store uses Laravel Blade, PHP, Composer, and **MySQL**. **phpMyAdmin** lets you inspect/manage that database in your browser. No Node, npm, or Vite build is needed.

## 1. Install the tools once

- Install [Git for Windows](https://git-scm.com/downloads/win).
- Install PHP 8.2 or newer and Composer 2. A convenient option is [Laravel Herd for Windows](https://herd.laravel.com/windows), which supplies PHP and Composer. PHP 8.4 is the version tested in the cloud workspace.
- For the bundled MySQL 8.4 + phpMyAdmin services, install [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/), enable its WSL 2 backend, and start Docker Desktop. If you already have MySQL and phpMyAdmin, you can skip Docker and use option B below.
- Close and reopen PowerShell after installation so PATH updates take effect.

Check that the commands work:

```powershell
git --version
php -v
composer --version
php --ini
php -m
php -r "echo 'WebP: ', function_exists('imagewebp') ? 'yes' : 'no', PHP_EOL;"
```

Required PHP features include `ctype`, `dom`, `fileinfo`, `gd` with WebP, `mbstring`, `pdo`, `pdo_mysql`, `xml`, and `xmlwriter`. Enable `exif` for camera orientation, and `curl` and `zip` for normal Composer downloads. If setup reports a missing extension, edit the **Loaded Configuration File** printed by `php --ini`, enable the corresponding extension, and restart the PHP development server. Don't add duplicate extension lines.

For the advertised large image uploads, use these values in that active `php.ini`:

```ini
upload_max_filesize=15M
post_max_size=180M
memory_limit=512M
```

The application accepts up to ten 12 MB images per product, and rewrites supported images as WebP. It does not guarantee a fixed compressed file size.

## 2. Download the repository

Open PowerShell in the folder where you want the project:

```powershell
git clone https://github.com/zaynab-3/ecommerce-shop.git
cd ecommerce-shop
```

If you already cloned it, open that folder and update instead:

```powershell
git pull --ff-only
```

Keep your existing `.env`, database, and uploaded images. Do not delete them when updating.

## 3. Start MySQL and prepare the application

Choose **one** database option. Option A supplies actual MySQL 8.4 and phpMyAdmin without installing a database server manually.

### Option A: bundled MySQL + phpMyAdmin

For a fresh clone:

```powershell
php scripts/local-database.php
docker compose up -d --wait
php scripts/setup.php
```

The first command creates private `.env` and `.env.testing` files, generates separate random application/root database passwords, and creates an application key. It preserves existing passwords, keys, and test configuration on repeat runs. If you previously used SQLite, first keep a private backup of your existing `.env` and database, edit the `DB_*` settings to the MySQL values in `.env.example`, and use empty password fields only for this initial local Docker preparation. This changes connections; it does not automatically import old database contents. Never overwrite an existing MySQL password or discard the old database.

Docker creates these three databases:

- `ecommerce_shop`: your catalog, accounts, settings, and orders.
- `ecommerce_shop_test`: disposable automated-test data.
- `ecommerce_shop_race_test`: disposable concurrent-checkout test data.

Open `http://127.0.0.1:8081` on **your own computer** for phpMyAdmin. Open your local `.env` privately and use its `DB_USERNAME` and `DB_PASSWORD` to sign in. These are database credentials; they are separate from the shop admin login. You can browse tables after setup. Laravel reads and writes MySQL directly; phpMyAdmin is the management interface.

The database and phpMyAdmin ports bind to your computer only. Stop them with `docker compose stop`; start them again with `docker compose up -d --wait`. Data stays in a Docker named volume. **Do not use `docker compose down -v`: it deletes that database volume.** Initialization creates test databases only on the first start; changing passwords in `.env` does not change accounts inside an existing volume. Keep passwords unchanged or update the MySQL account through phpMyAdmin too.

If MySQL already occupies port 3306, use option B or set `DB_PORT=3307` in **both** `.env` and `.env.testing` before starting Docker. If port 8081 is occupied, change `PHPMYADMIN_PORT` in `.env` and use that port in your browser.

### Option B: your existing MySQL + phpMyAdmin

Start your MySQL server and open your installed phpMyAdmin. For example, XAMPP usually provides phpMyAdmin at `http://localhost/phpmyadmin`. **XAMPP often bundles MariaDB despite labeling its button “MySQL”; this repository's automated validation targets actual MySQL 8.4.** Use MySQL 8.4 if you want the tested database version.

Sign into phpMyAdmin with your database administrator account. In its SQL tab, run the following on your **local development server only**, replacing the placeholder password first:

```sql
CREATE DATABASE ecommerce_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE ecommerce_shop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE ecommerce_shop_race_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mesh'@'localhost' IDENTIFIED BY 'REPLACE_WITH_YOUR_OWN_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON ecommerce_shop.* TO 'mesh'@'localhost';
GRANT ALL PRIVILEGES ON ecommerce_shop_test.* TO 'mesh'@'localhost';
GRANT ALL PRIVILEGES ON ecommerce_shop_race_test.* TO 'mesh'@'localhost';
```

On a fresh clone, create your local configuration:

```powershell
Copy-Item .env.example .env
notepad .env
```

Set the database values to match the server/account you just created:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_shop
DB_USERNAME=mesh
DB_PASSWORD="YOUR_CHOSEN_PASSWORD"
```

Leave `MYSQL_ROOT_PASSWORD` empty for this option; only Docker reads it. If you already have `.env`, edit that existing file and preserve its `APP_KEY` and other settings instead of copying over it. After changing database configuration, clear any cached configuration with `php artisan config:clear` if dependencies are installed.

Prepare the shop, then create a separate test configuration (skip the copy if `.env.testing` already exists):

```powershell
php scripts/setup.php
Copy-Item .env.testing.example .env.testing
notepad .env.testing
```

In `.env.testing`, set the same MySQL host, port, username, and password, but keep `DB_DATABASE=ecommerce_shop_test` and `APP_ENV=testing`. Generate its application key:

```powershell
php artisan key:generate --env=testing
```

### Finish application setup

`php scripts/setup.php` installs the locked dependencies, creates an application key if missing, applies forward migrations to MySQL, inserts default settings and delivery-area names, links image storage, and compiles views. It preserves existing catalog data, orders, accounts, and the application key. Your database server and database must exist first. It does not reset tables or seed any default admin password.

For a demonstration catalog and some enabled delivery areas:

```powershell
php artisan db:seed --class=DemoCatalogSeeder
```

Demo seeding is for local testing. It creates twelve demonstration products; it does not create any admin password. To start with your own catalog, skip it and create categories, products, and delivery pricing in admin instead.

Create your own owner account:

```powershell
php artisan shop:admin you@example.com --name="Store owner"
```

Replace the email with yours. Enter and confirm a password when prompted. Input is hidden. Use 12+ characters including uppercase/lowercase letters, a number, and a symbol. The cloud workspace's `.local-admin.json` is private and is deliberately absent from GitHub, so create your own account after downloading.

## 4. Start the website

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Leave that terminal open. Open these addresses in your browser:

- Storefront: `http://127.0.0.1:8000`
- Admin login: `http://127.0.0.1:8000/manage-store/login`

Sign in with the account you just created. To stop the server, press Ctrl+C. If port 8000 is busy, use `--port=8001` and change the browser address accordingly.

## 5. Run the automated tests

Open a second PowerShell window in the project folder:

```powershell
php artisan test
```

The suite uses the MySQL test database configured in `.env.testing`; it resets that database, so keep all real shop data in `ecommerce_shop`. A guard runs before migrations and refuses cached configuration, non-MySQL drivers, and database names without a `_test` suffix. Never point tests at your shop database or put real data in a `_test` database. A passing run prints the current test count and no failed tests. It covers stock rules, concurrent-safe checkout behavior, multiple purchases in one session, browser-form cart removal, order privacy, roles, delivery prices, image processing, and phone-only WhatsApp handoff.

For the separate test of ten checkout processes competing for three units, install Python 3 if it is not already installed, then run:

```powershell
py -3 scripts/test-stock-race.py
```

If your Python installation uses `python` instead of the Windows launcher, use `python scripts/test-stock-race.py`. The expected result is three saved orders, seven sold-out responses, and zero remaining stock in `ecommerce_shop_race_test`. Its test tables are removed afterward. It does not touch `ecommerce_shop`. Do not run two copies of the suite/race simultaneously against the same test database.

## 6. Test the features in the browser

1. **Catalog and colors:** open Studio wireless headphones, select Graphite or Sage, and verify the main image changes. Add an item, open the bag, click Remove, and verify the bag becomes empty.
2. **Two purchases:** place an order, add a product again in the same browser, and place a second order. Check that admin shows two different order numbers and stock is reduced for both purchases.
3. **Stock:** in admin Products, adjust a tracked item to its low-stock threshold. Verify Low stock; set its quantity to zero and verify buying is disabled. An open-quantity item uses the Available / Low / Out of stock selector. Stock display refreshes every 15 seconds, with a five-second cache; refresh or allow up to roughly 20 seconds for the display to catch up. Checkout always checks current stock.
4. **Visibility and order:** hide a product or category, confirm it disappears from the shop, then enable it again. Test the up/down reorder controls.
5. **Delivery:** choose several towns in Delivery areas, apply a price and availability, and check that the selected area's fee appears in both cart and checkout. Turn delivery pricing off in Store settings and verify no fee is added.
6. **WhatsApp:** enter your receiving number with country code in Store settings and enable Orders to WhatsApp. Checkout should save the order in admin and open WhatsApp with the items, quantities, colors, address, delivery fee, and total. The customer must tap Send. No API token is needed. Returning to the confirmation or using Open WhatsApp must not create a duplicate order.
7. **Order management:** assign a pending order and move it through its statuses. Cancel an unshipped order and confirm tracked stock is restored once.
8. **Team permissions:** create an Order agent in Team & access. Sign in as that account in a private browser window. It should access orders, claim an unassigned order, and be blocked from catalog/settings/team pages.
9. **Images:** create or edit a product, upload a JPEG/PNG image, save, then assign an image to a color. Confirm the storefront serves the `.webp` image and switches it for that color. SVG uploads are refused.

Use demonstration orders for these checks. The development server is for local testing; review [deployment requirements](DEPLOYMENT.md) before putting the shop online.

## Troubleshooting

| Problem | What to do |
|---|---|
| `php`, `composer`, or `git` is not recognized | Reopen PowerShell after installation and ensure that tool is on PATH. |
| Missing extension / `could not find driver` | Use `php --ini`, enable the extension (especially `pdo_mysql`, `gd`, `mbstring`, or `fileinfo`), then rerun setup. |
| MySQL connection refused | Start MySQL (`docker compose up -d --wait` for option A); check `DB_HOST` / `DB_PORT` and port conflicts. |
| Access denied / unknown database | Check the account/password and database names in `.env` and `.env.testing`. For an existing Docker volume, keep its original password or change the account in MySQL too. |
| Tests refuse the database | Configure `.env.testing` with `DB_DATABASE=ecommerce_shop_test`, then run `php artisan config:clear`. Never rename your real database to satisfy the guard. |
| `imagewebp` unavailable | Enable a GD build with WebP support. The setup script checks this. |
| `storage:link` fails on Windows | Verify the path is not already occupied by an ordinary directory. Enable Windows Developer Mode or retry that command in an elevated terminal if permissions require it. |
| 500 error | Read the latest message in `storage/logs/laravel.log`. For local diagnosis, set `APP_DEBUG=true` in `.env` and run `php artisan optimize:clear`. Keep debug off on public deployments. |
| 419 / session error | Keep the same hostname (use `127.0.0.1` consistently), reload the form, and ensure `SESSION_SECURE_COOKIE=false` for local HTTP. |
| Port in use | Use `php artisan serve --host=127.0.0.1 --port=8001`. |
| No products | Run the demo seeder, or create your own categories and products in admin. |
| Checkout has no available delivery area | Enable areas and fees in admin Delivery areas, or turn delivery pricing off in Store settings. |
| Tests refer to a missing `tests/Unit` folder | Pull the latest `main`; the stale empty-suite reference was removed. |

The PHP setup, MySQL 8.4 services, phpMyAdmin login, test suite, and stock race were verified on Linux. These commands are written for native Windows/PowerShell; a Windows machine was not used for the verification.
