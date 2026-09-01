# 1Ecomm PHP SDK

Framework-neutral PHP 8.2+ client for the versioned 1Ecomm headless commerce API.

Use `Client::forStore('your-site-uuid')` for one-field setup. It resolves the public runtime document and returns the same tenant-bound client used by the explicit URL/key constructor.

Run `php tests/run.php`. The preview supports published catalog reads, opaque cursors, typed problem details, safe-read retries, and injectable transport for framework/testing integration.

Run `HEADLESS_API_URL=https://... HEADLESS_PUBLISHABLE_KEY=pk_... php tests/live.php` against a dedicated fixture environment for the real catalog, cart and checkout-preparation gate. The command fails closed without both values and never prints the key.

Use `createCart()` to obtain a capability token, retain it in the shopper session, and pass it to cart reads and mutations. Mutations are never automatically retried because an add request is not replay-safe.

The preview also supports checkout preparation and capability-gated non-hosted placement. Call `placeOrder($cartToken, $intentKey)` only after selecting a method that explicitly supports non-hosted orders; retain and reuse the same intent key after uncertainty. Hosted payment and payment capture remain excluded.

See [architecture](docs/architecture.md) and [security](docs/security.md).

Laravel package discovery and configuration are documented in [Laravel integration](docs/laravel.md).
