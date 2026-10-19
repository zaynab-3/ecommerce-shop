# Storefront and admin design QA

final result: passed

## Target and evidence

Adaptation of https://jabergames.com/ and the user's desktop/phone references: condensed retail headings, prominent accent-colored prices/actions, pale image panels, bordered product cards, horizontal home collections, a four-column desktop/two-column phone catalog, and a thumbnail product gallery. MESH retains its own general merchandise, branding, color options, stock behavior, and Laravel checkout.

The user's explicit changes take priority over a pixel clone: remove decorative introductions such as “The whole collection”; increase readable text sizes; make the main color editable in admin settings; keep white backgrounds, dark primary text, and gray secondary text. Marketing banners, invented brands/warranties, and the reference's merchandise were not copied.

Real Chromium captures use 1 device pixel per CSS pixel, excluding browser chrome. Catalog/product captures are 1440 × 1000 and 390 × 844. The home comparison places the Top picks section near the top of each viewport. Content, product aspect ratios, cart counts, and accent hues differ intentionally.

| Compared surface | Combined source/implementation evidence |
|---|---|
| Home carousel, desktop | [Comparison](docs/screenshots/home-comparison-desktop.png) |
| Catalog, desktop | [Comparison](docs/screenshots/catalog-comparison-desktop.png) |
| Catalog, phone | [Comparison](docs/screenshots/catalog-comparison-mobile.png) |
| Product, desktop | [Comparison](docs/screenshots/product-comparison-desktop.png) |
| Product, phone | [Comparison](docs/screenshots/product-comparison-mobile.png) |

Separate implementation captures: [home desktop](docs/screenshots/storefront-desktop.png), [home phone](docs/screenshots/storefront-mobile.png), [admin desktop](docs/screenshots/admin-desktop.png), [admin phone](docs/screenshots/admin-mobile.png).

## Findings and fixes

- P1 resolved: Tiny storefront/admin text. Added locally hosted Space Grotesk for body text and Teko for retail headings/prices. Product names are 20px on phones and 24px on desktop; card prices are 27/30px; native form controls use 16px. Metadata is at least 12px. Admin keeps readable sans-serif typography.
- P1 resolved: Decorative introductions pushed products down. Removed those introductions and sidebar/footer promotional panels; retained functional section names, breadcrumbs, and product information.
- P1 resolved: Reference layouts were missing. Added a touch/keyboard-scrollable Top picks carousel with working arrows and Shop all; four-column desktop catalog and two-column phone grid; desktop vertical thumbnail rail switching to a horizontal row under the main image on phones; stacked purchase actions.
- P1 resolved: Mobile category access disappeared with the sidebar. Added category/subcategory selection preserving search, sort, and stock filters. Desktop category navigation remains sticky, with internal scrolling when needed.
- P1 resolved: Expired admin login showed a raw 419 page. Stale login submissions are rejected and redirected to a fresh form with an error. Only email is preserved. Fresh login succeeds; other state changes retain CSRF protection. This does not establish the original cause of a user-machine cookie problem.
- P1 resolved: Fixed blue did not meet the requested editable palette. Owner settings now control the main accent, with neutral primary/secondary text and light accent tints. Very light choices receive contrasting button labels and darker price/link text. Color-keyed CSS is same-origin and cacheable; CSP remains strict. Invalid CSS-like input is rejected.
- P2 resolved: Card footers moved with varying content. Flex card bodies align price/action areas. Direct Add to cart works for products with zero/one color; products with multiple colors lead to Choose options. Quantity, color ownership, and stock are validated on the server.
- P2 resolved: Duplicate carousel/catalog products needed all stock actions updated. Polling now updates every matching button, including Buy it now, and restores each button's own label when available.
- P2 resolved: Phone search and navigation consumed too much space. A compact header exposes search/menu controls, with Escape-to-close and accessible expanded states. Search remains visible on desktop.
- P2 resolved: Short admin screens clipped account navigation. The sidebar now scrolls; sign-out stays reachable. Tables scroll internally, with a visible phone hint and reachable stock controls.

## Verification

- 111 public/admin page-and-viewport checks at widths 320, 360, 390, 768, 1024, 1440, and 1920: no horizontal document overflow or JavaScript errors.
- Real-browser interactions passed: mobile search, carousel arrows, color-linked images, Buy it now with the selected color, duplicate stock updates, mobile category/filter preservation, sticky desktop categories, stale/fresh login, short-screen admin navigation, and access to stock controls.
- An owner changed the main color through the actual settings form; buttons and prices changed while the white background and dark text remained. The original color was restored.
- MySQL feature suite: 54 tests / 334 assertions passed, including color validation/contrast, quick add, Buy it now validation, WhatsApp product enquiries, and expired-login rejection/recovery.
- MySQL stock race: ten simultaneous checkouts for three units produced exactly three orders and seven sold-out responses, with zero stock remaining.
- Blade/route compilation, PHP/JavaScript syntax checks, Composer validation, and whitespace checks passed.
- Source/coded images were inspected together after the final storefront styling pass. No unresolved P0, P1, or P2 findings remain within this adaptation's scope.

Native Windows, iOS Safari, and Android devices are not available in the cloud environment; responsive checks use real Chromium at the listed viewport sizes. Data-heavy admin tables intentionally retain horizontal scrolling within their own region. No WhatsApp message was sent during verification. See [validation notes](docs/VALIDATION.md) for infrastructure and production limits.
