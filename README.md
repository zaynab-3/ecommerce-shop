# MESH — Laravel Blade commerce

A lightweight, server-rendered storefront and multi-admin workspace for a general-purpose Lebanese shop. Blade, plain CSS, and small JavaScript modules; no React, SPA runtime, frontend CDN, or Node build is needed.

Screenshots: [desktop storefront](docs/screenshots/storefront-desktop.png) · [mobile storefront](docs/screenshots/storefront-mobile.png) · [admin workspace](docs/screenshots/admin-desktop.png)

## Start here

The database is **MySQL 8.4**, with **phpMyAdmin** for database management. Laravel connects directly to MySQL. On Windows, follow the [PowerShell installation and manual test guide](docs/WINDOWS-SETUP.md), including the existing-server option if you already have MySQL/phpMyAdmin. The bundled Docker services require Docker Desktop/Engine with Compose.

```bash
cd /workspace/ecommerce-shop
bash scripts/cloud-database.sh  # starts MySQL + phpMyAdmin; preserves private configuration
bash scripts/setup.sh
source scripts/cloud-php.sh   # activates the local PHP/Composer runtime when needed
php artisan db:seed --class=DemoCatalogSeeder  # optional demonstration products
bash scripts/dev.sh
```

The development server listens on port 8000 (`PORT` overrides it). The cloud database script uses `compose.cloud.yaml` to retain database files under `/workspace/.runtime/mysql-data`; it must run again after a task restart. On your own machine use `php scripts/local-database.php`, `docker compose up -d --wait`, then `php scripts/setup.php`. phpMyAdmin is on local port 8081; sign in with `DB_USERNAME` and `DB_PASSWORD` from your private `.env`. Both database services bind only to loopback. `scripts/setup.sh` is repeatable: it preserves `.env`, the application key, catalog edits, order data, and existing accounts. It installs locked Composer dependencies, runs forward migrations, adds missing Lebanon delivery areas, links image storage, and compiles Blade views. It never seeds a default password. The Debian 13 cloud fallback installs a user-local PHP 8.4 runtime under `/workspace/.runtime` with verified APT metadata. On other machines, install PHP 8.2+ and the extensions below normally.

Create an owner securely:

```bash
source scripts/cloud-php.sh
php artisan shop:admin you@example.com --name="Your name"
```

Password input is hidden. Use at least 12 characters with upper/lowercase letters, a number, and a symbol. In this prepared cloud workspace, a local owner already exists; its generated development credentials are in the ignored, permission-600 `.local-admin.json` file. That file is not a production credential source and must not be published. Replace the account/password before deployment.

The admin login is `/manage-store/login`. Change `ADMIN_PATH` in `.env` to choose another unlisted route, then clear/rebuild Laravel config and route caches. There are no `/login` or `/register` routes and no storefront admin links. Route obscurity supplements authentication and permission checks.

WhatsApp checkout only needs the recipient phone number and its switch in **Store settings**. Checkout saves the order, then opens the customer's WhatsApp with the cart message prepared. The customer must tap **Send**. No API credentials, approved templates, or queue worker are needed. See [WhatsApp setup](docs/WHATSAPP.md).

The web server must restart when a cloud task or server restarts. See [deployment notes](docs/DEPLOYMENT.md).

After setup, `php scripts/start-local.php --demo` starts a local checkout, adds demo products only to an empty catalog, and prompts for an owner if none exists. It supports an existing XAMPP database without Docker. Generated product WebP images have a Laravel fallback route when Windows cannot create the storage link; images still use direct static serving when the link works.

## What works

- Categories and one level of subcategories; parent visibility applies to every descendant product.
- Scrollable category/subcategory navigation with All, bundled icons, and sanitized custom SVG icon uploads.
- Search, category filters, in-stock filtering, sorting, pagination, session-based saved items, and shopping cart.
- Products with optional colors and optional hex swatches. Images can belong to a color or remain unassigned. Choosing a color switches the main image; an unassigned image is the fallback.
- Up to ten uploaded JPEG/PNG/WebP images per item. Server-side decoding, EXIF orientation, 1,600 px WebP masters and 480 px WebP thumbnails. Uploads are limited by byte size and pixel count. Quality-84 WebP is visually optimized but is lossy: neither a universal 500 KB target nor zero quality loss is promised.
- Tracked stock and open quantity with manual Available / Low / Out of stock states.
- Editable low-stock thresholds. Defaults: initial stock ≤10 → 5, ≤30 → 10, ≤100 → 15, above 100 → ceil(15%). Thresholds remain fixed as items sell. A threshold may be overridden for any item.
- Stock polling every 15 seconds while a tab is visible, backed by a five-second shared cache. Checkout always checks authoritative stock. This is periodic synchronization, not WebSocket push.
- Owner-controlled website accent color, neutral body text, readable contrast for light colors, and locally hosted fonts.
- Atomic checkout, aggregate stock checks across colors, integer-cent totals, immutable order item snapshots, idempotency keys, and session locking. Stock is deducted when the order is placed. Cancellation before shipping restores it exactly once.
- Admin stock adjustments and visibility controls from the product list; category visibility controls and up/down reordering; color/image assignment and image ordering.
- Owner, manager, and order-agent roles. Agents may claim unassigned orders and update only their assigned orders; assignment is rechecked inside the order transaction. Owners manage team access and settings.
- Bulk delivery prices and availability: select all, a governorate, or individual towns. Add custom towns. Cart delivery estimates carry into checkout; fees are recalculated on the server. Delivery pricing has a store-wide switch. `/delivery-areas` supplies a read-only JSON list of enabled areas; no paid location provider is required.
- Guest checkout with cash on delivery. Orders appear in the admin order desk. Optional WhatsApp checkout opens a complete order message addressed to the configured store number. The customer taps Send; refreshing the confirmation does not open WhatsApp again.
- Escaped Blade output, CSRF protection, validated inputs, parameterized database queries, restricted CORS, a strict CSP, secure header defaults, password hashing, login/checkout throttles, private confirmation pages, and an audit log for admin mutations.

## Feature map

| Concern | Files |
|---|---|
| Public routes | `routes/web.php` |
| Catalog / product page | `app/Http/Controllers/StorefrontController.php`, `resources/views/store/` |
| Shared UI | `resources/views/components/`, `resources/views/layouts/` |
| Stock rules | `app/Models/Product.php` |
| Cart and checkout | `app/Services/CartService.php`, `app/Services/CheckoutService.php` |
| Database transactions and deadlock retries | `app/Services/TransactionService.php` |
| Order transitions and restocking | `app/Services/OrderStatusService.php` |
| Image conversion | `app/Services/ImageService.php` |
| Admin features | `app/Http/Controllers/Admin/`, `resources/views/admin/` |
| Access and security headers | `app/Http/Middleware/` |
| WhatsApp integration | `app/Services/WhatsAppOrderService.php`, `resources/views/components/whatsapp-order.blade.php`, `public/js/store/whatsapp.js` |
| Demo and delivery data | `database/seeders/` |
| Category icons | `app/Services/CategoryIconService.php`, `app/Http/Controllers/CategoryIconController.php`, `resources/views/components/category-icon.blade.php` |
| Website theme | `app/Services/StoreTheme.php`, `app/Http/Controllers/StoreThemeController.php`, admin Store settings |
| Styles and interactions | `public/css/`, `public/js/` |
| Schema | `database/migrations/` |
| Automated checks | `tests/Feature/ShopTest.php`, `scripts/test-stock-race.py` |

Eager loading and pagination bound product-list queries; local/test environments reject accidental lazy loading. A query-count regression test checks that catalog query count stays constant as products are added. Product edits use a stock revision so a stale editor cannot overwrite stock changed by checkout or another admin.

## Validation

```bash
source scripts/cloud-php.sh
php artisan test
python3 scripts/test-stock-race.py
php artisan view:cache
composer validate --strict
composer audit
```

The feature suite exercises stock thresholds, oversell rejection, color ownership, duplicate checkout, cancellation, private order access, admin role restrictions, image processing, escaping, delivery pricing, polling, and WhatsApp message generation and handoff behavior. Tests verify the recipient, complete encoded order text, the enabled/disabled switch, and one-time automatic opening. Browser checks intercept the external WhatsApp navigation; no message is sent during testing.

The suite uses the separate MySQL database in `.env.testing` (`ecommerce_shop_test` by default); the race check uses `ecommerce_shop_race_test`. The local Docker initialization creates both and grants the application user access. Tests refuse non-MySQL databases, cached configuration, or names without a `_test` suffix before resetting tables. The race releases ten PHP processes together for three units: exactly three orders should succeed and seven should report sold out. It drops its test tables afterward and leaves the shop database untouched. Do not run two test suites/races against the same test database concurrently.

Read-only local load smoke checks:

```bash
python3 scripts/load-smoke.py --clients 50
python3 scripts/load-smoke.py --clients 100
python3 scripts/load-smoke.py --clients 150
```

These are short development-server request bursts, not a sustained production load test. See [validation results](docs/VALIDATION.md).

## Before selling

The catalog, product names, prices, and descriptions are demo content. Replace them with your own merchandise. Product photography is sourced from the public [Fake Store API demo](https://github.com/keikaavousi/fake-store-api); replace it or verify rights for commercial use. The headphones, lamp, placeholder, favicon, and storefront decoration are local SVG illustrations. All images are served locally.

MESH keeps the requested retail layouts while using its own visual identity with light product-card borders: locally hosted Space Grotesk, sentence-case product names, black prices, and rounded actions. The interface uses only **one owner-selected accent, black, and white**; product images and actual color swatches keep their colors. Choose the accent in **Store settings → Website appearance → Main color**. Button labels use black or white to remain readable. The storefront includes a touch-friendly product carousel, a four-column desktop/two-column phone catalog, and a responsive thumbnail gallery. The phone menu opens in a side drawer with a softly blurred backdrop and smooth sliding transitions. Product cards have a light border, selectable colors above the price, a simple plus action beside the price, and an unframed favorite icon at the top. Phone scrollbars and category-strip scrollbars are hidden; All stays pinned beside the scrolling category bar; selected categories scroll into view without artificial edge spacers or a blank gap after All. Discover shows only its home category grid on phones, without a duplicate category strip. Cart and checkout share a rounded bottom sheet that sizes to its content up to 90% of the viewport, with soft backdrop blur. The first phase shows cart items and Proceed to checkout, without the redundant Your items heading or a continue-shopping arrow. The next phase contains name, Lebanese phone (+961), address, delivery selection, order review, totals, and Place order, without an email field or footer. Back to cart retains entered details. Checkout uses compact 48px rounded, lightly filled controls, including both textareas; focus changes the label/fill without a rectangular border. Selecting a delivery area dismisses the phone picker while keeping checkout open and updating totals. Search opens in a centered dialog with debounced live suggestions linking to product details; closing or submitting that dialog does not filter the underlying shop page; the header hides while scrolling down and returns when scrolling up. Categories, filters, pagination, and public store links update server-rendered content within the existing page and use the History API for the address, title, and Back/Forward scroll restoration. Existing content stays visible until the response is ready; cancelled or failed requests cannot replace a newer page. Real links and GET forms remain available without JavaScript. Motion respects reduced-motion preferences. Header search/cart icons use the saved accent on a transparent background. Cart/favorite actions use JSON without a page reload; notifications appear at the top right for five seconds. Admin introductions and the role guide have been removed; stock fields adapt to tracked/open quantity. See [design QA](design-qa.md) for screenshots and responsive checks.

Active storefront navigation uses bold accent text without a filled background. Close icons are unframed. Badges, saved hearts, notifications, and the footer use the selected accent; foregrounds on the accent use black or white for contrast. The delivery/payment benefits stay white with black text and accent icons. Selected card colors use a circular accent ring instead of an underline. Current and crossed-out comparison prices sit beside one another in a single 44px purchase row, with the plus action alongside them even at 320px. Product-detail Add to cart and Buy it now buttons also sit beside one another.

Client navigation uses a short slide on arrival; the header stays still and content stays fully opaque. Back/Forward restore content and scroll through the same navigation path. Product galleries and information have a slight staggered entry movement. Gallery/quantity behavior reuses delegated events, while category/carousel observers and timers are cleaned up when their content is replaced. Navigation and cart/favorite mutations share a queue to preserve session state. Reduced-motion preferences suppress animations. There is no page fade, loading overlay, or browser document refresh during ordinary store navigation; explicit reload still works normally.

Card image frames explicitly fill the card width, with the contained photo centered horizontally and vertically. Favorite and footer-plus icons share one right-hand anchor and the same 20px icon size, retaining 44px controls. Their positioning is independent of product image dimensions and price width. The same card component is used in Top picks, the catalog, saved items, and related products.

Top picks advances by one card automatically after five seconds, looping back at the end. Manual swiping/arrows still work. A transparent accent pause/resume icon is available; motion pauses during hover, keyboard focus, touch interaction, open dialogs, hidden browser tabs, and when the carousel is outside the viewport. Reduced-motion preferences disable automatic scrolling. The centered search dialog's search and close actions are also transparent accent icons.

The desktop header has no secondary navigation row. **Delivery across Lebanon** links to Help from the announcement bar, beside Cash on delivery, including on phones. The phone drawer retains **Discover** and **Shop**. Home category links have transparent backgrounds, unframed accent icons, regular labels that become bold on hover, and no trailing arrows; selected category navigation remains bold. Cart and favorites have live counters at the upper right of their transparent header icons, including on phones; the header has no My cart label. The cash-on-delivery announcement has no arrow. Desktop catalog breadcrumbs, result count, and filters share one row, with the category strip and filters stacked on smaller screens. Card image frames are capped at 156px, empty color rows are omitted, and desktop related cards stay at most 220px wide. Product-detail image frames are 360px high on desktop, 300px on tablets, and 260px on phones, with contained images and purchase buttons beside each other.

Visible vertical desktop scrollbars use the saved website accent, rounded inset thumbs, and transparent tracks. Browsers with custom scrollbar parts use an 8px rail and a slightly wider thumb on hover; other browsers use their thin native scrollbar with the same accent. Horizontal scrollbar rails are hidden, including Top picks, categories, swatches, horizontal gallery thumbnails, and admin tables; their content still scrolls. Phone/touch scrollbar visibility retains the existing behavior. Uploaded category SVG icons also inherit the surrounding icon color while continuing to load from their sandboxed asset route.

Dedicated `/privacy-policy` and `/terms-and-conditions` pages are linked from the footer, help page, and checkout review. Their copy lives in `resources/views/store/privacy.blade.php` and `resources/views/store/terms.blade.php`; update business-specific contact, retention, delivery, and return details there as needed. The cookie notice explains the essential shopping/security cookies and the absence of advertising/analytics cookies. Dismissal is saved for 180 days in an encrypted, HttpOnly, SameSite=Lax cookie (Secure over HTTPS), with a CSRF-protected form and AJAX enhancement. **Cookie settings** in the footer reopens the notice; without JavaScript it links to the cookie explanation. Browsing is never blocked by a consent wall, and the notice stays behind open dialogs. Optional tracking is not installed or enabled by acknowledging the notice.

Online card payments, customer accounts, advanced shipping-provider integration, and per-color stock are not part of this implementation; stock is maintained at the product level and payment is cash on delivery.
