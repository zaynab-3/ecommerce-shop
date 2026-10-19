# Validation of this implementation

Validated in the prepared cloud instance with Laravel 12, PHP 8.4, MySQL Community Server 8.4.11, phpMyAdmin 5.2.3, and a four-worker PHP development server.

- Feature suite: 62 tests and 492 assertions against MySQL, all passing in the prepared instance after the storefront/theme revision. The GitHub matrix also checks PHP 8.2, 8.3, and 8.4 on clean MySQL databases.
- Independent MySQL stock race: ten checkout processes released together at a start barrier competed for three units and produced three orders, seven sold-out responses, and zero remaining stock. The race uses a separate `_race_test` database and cleans its tables afterward.
- Database isolation: a deliberate attempt to run the suite with `DB_DATABASE=ecommerce_shop` was refused before migrations. The shop retained twelve products, its owner account, and its existing order data.
- Setup: initial schema creation, normal seeding, repeat setup, and MySQL restart with retained database files passed. The previous local SQLite file/configuration were preserved while the existing catalog/account were copied into MySQL.
- phpMyAdmin: real Chromium login using the application database account succeeded, and the shop table structure was visible. Database/phpMyAdmin ports are bound to loopback.
- Browser checks: real Chromium at widths 360, 390, 768, 1024, and 1440. No homepage horizontal overflow. Admin products, categories, delivery, team, and settings were checked for mobile overflow.
- Browser flow: color-linked image switching, add to cart, selected color in cart, delivery fee and total, guest checkout, private confirmation, admin login, catalog image settings, and cancellation/restocking. No JavaScript errors or failed resources in the tested flow. The review adds regression coverage for successive purchases in one session, HTML-form removal, stale color options, and literal SQL search characters.
- All twelve demo product images loaded after scrolling through lazy-loaded content.
- Blade compilation, route caching, Composer manifest/lock validation, and repeat setup passed. Composer audit completed with no security vulnerability advisories for the locked dependencies during the review.
- WhatsApp checkout tests verify the phone-only setup, encoded complete order messages, switch behavior, one-time automatic handoff, and manual retry link. Browser navigation to `wa.me` is intercepted so no external request or real message is sent. Customers must tap Send in WhatsApp.

Read-only catalog burst results against MySQL from this cloud machine:

| Concurrent clients / requests | Failed | Median response | 95th percentile | Burst duration |
|---|---|---|---|---|
| 50 | 0 | 598 ms | 1,004 ms | 1.04 s |
| 100 | 0 | 869 ms | 1,654 ms | 1.80 s |
| 150 | 0 | 1,386 ms | 2,491 ms | 2.79 s |

Each client made one catalog GET, without an existing browser session. These measurements are a short development-server smoke check with a twelve-item demo catalog. They do not validate sustained traffic, a large production catalog, or a production SLA. The query-count test separately verifies that list-page query count does not scale per product.

Windows storage-link compatibility: generated product WebP images and thumbnails now have a Laravel fallback route, restricted to UUID filenames inside public product storage. Tests verify image bytes, content type, public caching without session cookies, and refusal of other/missing paths. The private disk has no public serving route. The local launcher was exercised on Linux with MySQL, including demo seeding, hidden-password owner creation, repeat startup preserving the existing owner/catalog, and an HTTP catalog request. Native Windows execution and non-NTFS media are not available in this environment and remain user-machine validation.

The readable-storefront revision removes decorative introductions, increases desktop/phone typography, adds category/subcategory navigation on small screens, and allows short-screen admin navigation to scroll. Expired login forms redirect to a fresh login form with an error, preserve only the email, and continue to reject the stale CSRF submission. CSS assets use modification-version query strings. Space Grotesk (22,288 bytes) is hosted locally under SIL OFL 1.1. The current interface uses Space Grotesk for both headings and body text. See `design-qa.md` for current visual evidence and browser outcomes.


Final responsive pass: 111 public/admin page-and-viewport combinations at 320, 360, 390, 768, 1024, 1440, and 1920 CSS pixels had no horizontal document overflow or JavaScript errors. Desktop categories stayed at their sticky offset; mobile filters preserved search/sort/stock values; short-screen admin navigation and stock controls remained reachable. Data tables scroll inside their containers on phones.

Additional real-browser interactions passed: mobile search, carousel arrows, color-linked images, Buy it now with the selected color, stock updates affecting both occurrences of a product, and an authenticated owner changing the accent color through settings. The temporary color change was restored. The theme uses a same-origin, immutable, color-keyed CSS response without session cookies; the existing CSP continues to prohibit inline styles/scripts. Feature tests reject CSS-like color input and verify contrast ratios for light, dark, and saturated colors, as well as the strict three-color token palette. Product enquiries use the configured WhatsApp phone and switch; no external messages were sent during testing.


The latest identity uses the saved accent, black, and white; decorative frames, shadows, pastel surfaces, and condensed uppercase headings were removed. Mobile storefront navigation is a native side drawer, with backdrop/close/Escape dismissal, focus restoration, background scroll locking, and cleanup on desktop resize. Tracked/open stock form fields now show only relevant inputs; numeric fields are disabled and omitted from open-stock submissions. Static admin introductions, the role explainer, and redundant promotional copy were removed without changing role permissions. Theme stylesheet URLs include a version so previously cached tinted styles are not reused.


The latest shopping-interaction revision adds a rounded 90%-height cart/checkout sheet, centered search dialog, scroll-aware header, asynchronous cart/favorite mutations, timed top-right toasts, and selectable color-linked card images. Cart JSON uses server-side prices, stock validation, delivery fees, and counts; regular HTML submissions remain available. Cart/favorite session mutations are locked, and public stock/location reads do not open sessions. Category navigation exposes All and visible categories/subcategories. Custom SVG icons use enshrined/svg-sanitize 1.1.0 with a restrictive shape/attribute allowlist, bounded size/complexity, no linked resources/entities, private storage, and a sandboxed public asset route. Composer audit reports no advisories for the lock. The local launcher detects missing locked dependencies after a pull and installs them before starting.


Real-browser checks for the final shopping revision passed: no document navigation or scroll jump on add/favorite, correct selected-color cart lines, 90%-height cart opening/closing with scroll restoration, quantity/delivery total updates, centered search focus, scroll-direction header behavior, five-second toast expiry, Jewelry/All filtering with preserved stock/sort, sticky top save actions, and a real SVG upload rendered through its sandboxed route. The temporary category/file were deleted. The 111 page/viewport pass was repeated with the new navigation and dialogs, without horizontal document overflow or JavaScript errors.


The combined checkout revision passed 62 MySQL tests / 492 assertions. New coverage checks normalized Lebanese mobile/landline numbers, invalid input and input types, ignored email, server-side prices, private confirmation access, and repeated JSON checkout without duplicate orders or stock deductions. The ten-process MySQL stock race still produced exactly three orders for three units.

Real Chromium verified the cart icon at the bottom of the product image, hidden phone/category scrollbars, centered selected categories (including first/last categories), soft blurred drawer backdrop, 90%-height rounded checkout sheet, white form fields/black text, fixed +961 prefix and national-number formatting, preserved entered details across quantity updates, inline validation/focus, and a real phone-only order rendered in the sheet. The confirmation check and Pending badge use the saved accent. The temporary QA order was deleted; demo stock was unchanged. Modal overflow/phone input checks passed at 320, 390, 768, 1440, and 1920px. The earlier 111-page responsive pass was repeated successfully with this implementation. Native-device limitations still apply.
