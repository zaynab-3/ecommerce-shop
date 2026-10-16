# Phone-number-only WhatsApp checkout

1. Open **Store settings** in the admin panel.
2. Enter the WhatsApp number that should receive orders, including its country code (for example `+961 70 123 456`). Formatted numbers are normalized to digits for the link.
3. Turn **Orders to WhatsApp** on and save.

No WhatsApp token, Meta application, approved template, phone-number ID, or queue worker is needed. The old `WHATSAPP_*` environment entries can be skipped and removed from environment settings; the application no longer reads them. The phone number is saved in store settings, not in `.env`.

## Checkout behavior

Checkout validates stock and prices, saves the order and item snapshots in the admin panel, deducts tracked stock, and clears the cart. The confirmation page then navigates to a `wa.me` link addressed to the configured number with the order message prefilled. It includes:

- Order number; every item, SKU, selected color, quantity, unit price, and line total.
- Customer name, phone, optional email, delivery area, full address, and notes.
- Subtotal, delivery fee, total in USD, and cash-on-delivery payment method.

The customer must review the message and tap **Send** in WhatsApp. A phone-number link cannot silently send a message. The store does not claim to track sending, receipt, or reading. On desktop, the customer may need WhatsApp Web/Desktop; on mobile, WhatsApp must be available to handle the link.

The order remains saved even if the customer closes WhatsApp or never taps Send. Returning to or refreshing the private confirmation page does not launch WhatsApp repeatedly. A visible **Open WhatsApp** button lets the customer retry opening the message without creating another order. Disabling the switch keeps the normal confirmation flow and removes the WhatsApp handoff.

The message uses saved order snapshots, so later product price or name changes do not alter it. Text is URL-encoded and no cart items are deliberately truncated. Very large carts can exceed a browser or WhatsApp URL/message limit; the complete order remains visible on the confirmation page and in the admin panel.

The browser makes the WhatsApp navigation; the server does not call a WhatsApp API or require outbound access to Meta. Automated tests verify encoded content, phone normalization, privacy, and the switch. Browser tests intercept `wa.me` before any external request or message is sent.
