# Storefront and admin design QA

final result: passed

## Current implementation

The store keeps the approved four-column desktop/two-column phone catalog, horizontal home carousel, responsive gallery, and mobile side drawer. Its identity uses Space Grotesk, one owner-selected accent, black and white, light black-alpha product borders, and no decorative shadows. Genuine product colors and photos retain their colors. Header search/cart icons have transparent backgrounds and use the saved accent.

Page content stays visible throughout ordinary navigation. There are no page opacity transitions or delayed page-link interception. Native history and scrolling remain available, with smooth scrolling and reduced-motion support.

Top picks advances by one card every five seconds and loops at the end. Manual swiping and desktop arrows remain available, alongside an unframed accent pause/resume control. Automatic movement pauses on hover/focus/touch, with open dialogs, in hidden tabs, and outside the viewport. Reduced-motion preferences disable autoplay. The search dialog's submit and close icons use the accent without filled backgrounds, including hover.

## Cart and checkout

The rounded sheet covers 90% of the viewport with a softly blurred backdrop. It first shows cart items, icon quantity/removal controls, subtotal, and **Proceed to checkout**. The second phase shows customer/delivery fields and an order review, with **Place order** as the final action. **Back to cart** preserves entered details and updates the review after quantity changes. Product colors and prices come from server-rendered snapshots; order prices and stock are validated on the server.

Fields have white surfaces, black text, and a single underline. The phone field fixes +961 beside the formatted Lebanese number. Email is absent; optional notes are labelled at the end of the label row. Checkout and confirmation contain no website footer. Confirmation uses the selected accent for its check and status badge.

## Search and catalog

The centered search dialog has one underline, debounced live suggestions, cancellation of older requests, and keyboard selection. Each result links to its product detail page. Typing, submitting, or closing search does not filter or navigate the underlying shop page. Candidate lookup is bounded and briefly cached; visibility and prices are checked fresh without opening a customer session. Product names render as text, with escaped literal SQL wildcard searches.

Product cards have slight borders, selectable colors above pricing, and a simple plus action beside the price. Reduced image height, title spacing, and color/price spacing shorten the cards by roughly 15–20% at phone/desktop reference sizes without shrinking product-name type. Prices align across each row, and plus controls remain alongside prices at 320px. Color selection changes the linked image and cart color, including products with a single color. Favorites stay unframed and fill with the selected accent when saved. Badges and five-second notifications also use the selected accent. Cart/favorite mutations do not refresh the page or reset scroll.

All remains fixed beside a separate scrolling category track. The selected category centers inside that track, icons retain their size, long names truncate, and the scrollbar stays hidden. Active storefront links use bold accent text on a transparent background. Close controls have a transparent background and accent icons, retaining 44px dialog/drawer targets.

## Footer, legal pages, and cookies

Benefits and the footer share the saved accent background with automatic black/white foreground contrast. Privacy policy and Terms & conditions have their own responsive pages and links from help, the footer, and checkout review. No footer is inserted into the cart/checkout sheet.

The nonblocking cookie notice explains the existing essential shopping/security cookies. There are no advertising/analytics cookies or invented optional-tracking switches. Acknowledgement lasts 180 days, survives navigation/reload, and can be reopened from the footer. The encrypted cookie is HttpOnly and SameSite=Lax, and Secure on HTTPS. The endpoint retains CSRF/session protection and serializes with cart mutations. The HTML form works without JavaScript; the footer then links to the cookie explanation.

## Evidence

| Surface | Captures |
|---|---|
| Catalog | [Desktop](docs/screenshots/catalog-desktop.png), [phone](docs/screenshots/catalog-mobile.png) |
| Top picks autoplay | [Desktop](docs/screenshots/top-picks-desktop.png), [phone](docs/screenshots/top-picks-mobile.png) |
| Cart phase | [Phone](docs/screenshots/cart-modal-mobile.png), [desktop](docs/screenshots/cart-modal-desktop.png) |
| Checkout and order review | [Phone fields](docs/screenshots/checkout-modal-mobile.png), [phone review](docs/screenshots/checkout-review-mobile.png), [desktop](docs/screenshots/checkout-modal-desktop.png) |
| Live search | [Phone](docs/screenshots/search-suggestions-mobile.png) |
| Pinned category navigation | [Phone](docs/screenshots/category-centered-mobile.png) |
| Drawer and close icons | [Phone](docs/screenshots/drawer-mobile.png) |
| Footer contrast | [Dark accent](docs/screenshots/footer-mobile.png), [light accent](docs/screenshots/footer-light-accent-mobile.png) |
| Privacy, terms, and cookie notice | [Privacy](docs/screenshots/privacy-mobile.png), [terms](docs/screenshots/terms-mobile.png), [notice](docs/screenshots/cookie-notice-mobile.png) |
| Gallery | [Desktop](docs/screenshots/product-desktop.png), [phone](docs/screenshots/product-mobile.png) |
| Admin | [Desktop](docs/screenshots/admin-desktop.png), [phone](docs/screenshots/admin-mobile.png) |

## Verification

- MySQL: **67 tests / 554 assertions**, including policy links, cookie version validation, encrypted cookie attributes/lifetime, cart/session preservation, HTML fallback, live-search bounds, literal wildcard handling, validated input, session-free responses, cached-candidate visibility, role authorization, stock safety, idempotent orders, and Lebanese phone normalization.
- The preceding shopping revision passed **111 public/admin page-and-viewport checks** at 320, 360, 390, 768, 1024, 1440, and 1920px. This revision adds **30 public page-and-viewport checks** at 320, 390, 768, 1440, and 1920px, including home and both new legal pages, with no horizontal document overflow, expanded mobile layout viewport, or JavaScript errors. Home/cookie reachability also passed at 360 and 1024px. The card action now contains its absolutely positioned screen-reader label so off-screen carousel labels cannot expand the mobile viewport.
- Real Chromium verified notice dismissal/reload/reopening, rejection of a missing CSRF token, the no-JavaScript HTML fallback, transparent close/active states, and dark-magenta/light-yellow theme contrast for the footer, badges, and filled hearts. Theme previews used the real theme CSS endpoint without changing saved store settings.
- Top picks passed real-time five-second advancement, card stepping, end-to-start looping, pause/resume icon state, desktop hover/keyboard-focus pauses, reduced-motion suppression, and pausing while a dialog is open. Search submit/close controls retained transparent backgrounds on hover and 44px+ targets.
- Separate cart/checkout phases at 320, 390, 768, and 1440px: no modal overflow; order review and totals update correctly; Back retains details. A real phone-only order completed and its temporary record was deleted, with demo stock unchanged.
- Search suggestion clicks and keyboard selection opened product detail pages. Closing and submitting search preserved the original shop URL and products. The focused input has one underline and no extra oval outline.
- Cards retained asynchronous add-to-cart behavior, a simple footer plus, selectable linked images, and colors above prices. All remained visible while the active category centered in its own track.
- Blade/route compilation, JavaScript/PHP syntax, and whitespace checks passed. Final captures were visually inspected.

Native Windows, iOS Safari, and Android devices are not available in this environment; responsive checks use real Chromium at the stated sizes. No WhatsApp message was sent during verification. Existing MySQL concurrency and infrastructure checks are recorded in [validation notes](docs/VALIDATION.md).
