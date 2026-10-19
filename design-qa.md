# Storefront and admin design QA

final result: passed

## Current target

The user approved the page layouts from the Jaber Games references, then requested a distinct MESH identity: **one configurable accent, black, and white**, no decorative borders or pastel surfaces, and no condensed uppercase typography. Product photography and genuine product-color swatches retain their colors. The home carousel, four-column desktop/two-column phone catalog, and responsive product gallery remain.

MESH now uses locally hosted Space Grotesk throughout, sentence-case headings, black prices, rounded primary/secondary actions, and spacing rather than framed cards. Owner settings still control the single accent; light accents receive black button labels without introducing another shade. Versioned theme URLs invalidate the previous cached tinted palette.

## Evidence

Real Chromium captures use one image pixel per CSS pixel, excluding browser chrome. Catalog/product captures are 1440 × 1000 and 390 × 844. The mobile navigation before/after captures are both 393 × 851. Source/reference merchandise and brand assets differ intentionally; comparisons demonstrate layout structure, not a pixel-clone objective.

| Surface | Evidence |
|---|---|
| Home, desktop / phone | [Desktop](docs/screenshots/storefront-desktop.png), [phone](docs/screenshots/storefront-mobile.png) |
| Catalog, desktop / phone | [Desktop](docs/screenshots/catalog-desktop.png), [phone](docs/screenshots/catalog-mobile.png) |
| Product, desktop / phone | [Desktop](docs/screenshots/product-desktop.png), [phone](docs/screenshots/product-mobile.png) |
| Mobile side drawer | [Before/after comparison](docs/screenshots/navigation-comparison-mobile.png) |
| Admin product list | [Desktop](docs/screenshots/admin-desktop.png), [phone](docs/screenshots/admin-mobile.png) |
| Open-stock editor | [Phone](docs/screenshots/admin-open-stock-mobile.png) |
| Reference layout comparison | [Home](docs/screenshots/home-comparison-desktop.png), [catalog desktop](docs/screenshots/catalog-comparison-desktop.png), [catalog phone](docs/screenshots/catalog-comparison-mobile.png), [product desktop](docs/screenshots/product-comparison-desktop.png), [product phone](docs/screenshots/product-comparison-mobile.png) |

## Findings and fixes

- P1 resolved: The previous identity resembled the reference too closely. Replaced condensed uppercase display text, accent prices, framed cards, gradients, shadows, and pastel surfaces with MESH's borderless, three-color identity. Kept the approved layouts and merchandise functionality.
- P1 resolved: Mobile menu expanded inline and crowded navigation links. Replaced it with a native modal side drawer, separate close control, backdrop dismissal, Escape dismissal, background scroll locking, inert background controls, focus restoration, and desktop-resize cleanup. Links are stacked with comfortable touch spacing. Shared Blade links keep mobile and desktop destinations consistent.
- P1 resolved: Open quantity showed irrelevant tracked-stock fields. Quantity and threshold are hidden and disabled in open mode; the manual status is visible. Tracked mode shows numeric fields and hides manual status. Values survive switching modes, and the required manual value remains submitted for backend compatibility. Existing open-stock products render correctly before JS initialization.
- P1 resolved: Old colors leaked into quick stock and visibility controls. Reworked the controls with the same accent/black/white palette and explicit text labels. Theme tokens cannot introduce lighter/darker accent shades. A real saved purple accent was checked across the shop and admin product list, then restored.
- P1 resolved during visual QA: Some admin inputs inherited black text on their new black surface. Added explicit white input/select/textarea text and verified the selected mode and entered values are visible.
- P2 resolved: The role explainer and decorative admin introductions added clutter. Removed them, shortened empty states and shortcuts, and retained actual order metadata, essential constraints, and validation errors. Role permissions are unchanged.
- P2 resolved: Tinting selected gallery thumbnails changed the appearance of product colors. Selection now uses a small accent marker under a white thumbnail; product colors remain intact.
- Prior fixes retained: readable typography, aligned card actions, mobile category/subcategory filtering, sticky desktop categories, duplicate stock-action updates, short-screen admin navigation, and safe expired-login recovery with CSRF still enforced.

## Shopping interaction revision

- Full-screen cart sheet animates upward on open and downward on close, with focus management, scroll locking, and background scroll restoration. Cart content loads only when needed; quantities, removal, delivery selection, prices, and totals use server-validated JSON/HTML snapshots.
- Search opens in a centered dialog. The borderless hamburger uses a staggered two-line icon and retains the side drawer. The sticky store header slides away when scrolling down and returns after a small upward movement.
- Add-to-cart and favorite actions update without navigation or resetting page scroll. Every card color, including a single color, has a selectable control below the price. Choosing a color updates its linked image and submitted color ID. Card prices use an aligned row, and hearts remain unframed.
- All notifications use top-right toasts with a five-second lifetime. Main product/settings save actions sit in the sticky admin header and submit the associated form.
- The phone/tablet category selector is replaced with a scrollable All/category/subcategory navigation bar. Existing filters are preserved. Icons can come from the local library or a sanitized SVG upload for any category/subcategory.
- SVG files have size/complexity limits, a maintained sanitizer with a narrow shape/attribute allowlist, no linked resources/document entities, private storage, and a public UUID route with sandbox CSP. Product-photo SVG uploads remain prohibited.

Additional captures: [cart modal](docs/screenshots/cart-modal-mobile.png), [search modal](docs/screenshots/search-modal-mobile.png), [sticky save action](docs/screenshots/admin-save-top-mobile.png).

## Verification

- 111 public/admin page-and-viewport checks at widths 320, 360, 390, 768, 1024, 1440, and 1920: no horizontal document overflow or JavaScript errors.
- Drawer checks at 320, 393, and 600px widths and a 320px-high window: no page layout shift; keyboard focus cannot enter background page controls; close button, Escape, backdrop, link navigation, search interaction, and desktop resize all passed. Native browser chrome remains accessible with the keyboard.
- Tracked/open stock checks on create and existing-product forms: visibility, required state, disabled state, FormData omission, manual-status submission, and readable selected values passed.
- Actual authenticated owner color save: the visible shop/admin product-list CSS palette contained only accent, black, white, and transparent. Original settings were restored.
- MySQL feature suite: **60 tests / 441 assertions**, including palette constraints, contrast, color validation, role authorization, stock behavior, and stale-login recovery.
- The final interaction flow passed in real mobile Chromium: selected-color cart data, asynchronous cart/favorites, quantity/delivery updates, preserved scroll, toast expiry, centered search, header motion, category navigation, top save actions, and actual SVG upload/render/cleanup. Composer audit reports no advisories.
- Blade and route compilation, PHP/JavaScript syntax, and whitespace checks passed. Final rendered comparisons were visually inspected together. No unresolved P0, P1, or P2 findings remain within this scope.

Native Windows, iOS Safari, and Android devices are not available in the cloud environment; responsive checks use Chromium at the stated sizes. Data-heavy admin tables retain horizontal scrolling within their own region. No WhatsApp message was sent during verification. See [validation notes](docs/VALIDATION.md) for infrastructure limits and earlier MySQL checkout-race results.
