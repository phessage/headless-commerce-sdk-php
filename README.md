# 1Ecomm PHP SDK

Framework-neutral PHP 8.2+ client for the versioned 1Ecomm headless commerce API.

Run `php tests/run.php`. The preview supports published catalog reads, opaque cursors, typed problem details, safe-read retries, and injectable transport for framework/testing integration.

Use `createCart()` to obtain a capability token, retain it in the shopper session, and pass it to cart reads and mutations. Mutations are never automatically retried because an add request is not replay-safe.

The preview also supports checkout preparation: update guest contact/addresses, read and select server-authoritative shipping/payment choices, and inspect missing prerequisites. Order finalization and payment capture are deliberately excluded.

See [architecture](docs/architecture.md) and [security](docs/security.md).

Laravel package discovery and configuration are documented in [Laravel integration](docs/laravel.md).
