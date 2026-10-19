# Validation of this implementation

Validated in the prepared cloud instance with Laravel 12, PHP 8.4, MySQL Community Server 8.4.11, phpMyAdmin 5.2.3, and a four-worker PHP development server.

- Feature suite: 54 tests and 334 assertions against MySQL, all passing in the prepared instance after the storefront/theme revision. The GitHub matrix also checks PHP 8.2, 8.3, and 8.4 on clean MySQL databases.
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

The readable-storefront revision removes decorative introductions, increases desktop/phone typography, adds category/subcategory navigation on small screens, and allows short-screen admin navigation to scroll. Expired login forms redirect to a fresh login form with an error, preserve only the email, and continue to reject the stale CSRF submission. CSS assets use modification-version query strings. Space Grotesk (22,288 bytes) and Teko (15,420 bytes) are hosted locally under SIL OFL 1.1; licenses are in `public/fonts/`. See `design-qa.md` for current visual evidence and browser outcomes.


Final responsive pass: 111 public/admin page-and-viewport combinations at 320, 360, 390, 768, 1024, 1440, and 1920 CSS pixels had no horizontal document overflow or JavaScript errors. Desktop categories stayed at their sticky offset; mobile filters preserved search/sort/stock values; short-screen admin navigation and stock controls remained reachable. Data tables scroll inside their containers on phones.

Additional real-browser interactions passed: mobile search, carousel arrows, color-linked images, Buy it now with the selected color, stock updates affecting both occurrences of a product, and an authenticated owner changing the accent color through settings. The temporary color change was restored. The theme uses a same-origin, immutable, color-keyed CSS response without session cookies; the existing CSP continues to prohibit inline styles/scripts. Feature tests reject CSS-like color input and verify contrast ratios for light, dark, and saturated colors. Product enquiries use the configured WhatsApp phone and switch; no external messages were sent during testing.
