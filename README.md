# 1Ecomm Headless Commerce PHP SDK

Customer return creation requires a caller-owned 1–120 character intent key. Reuse it with the identical request after an uncertain response to recover the original RMA; changed details with that key return HTTP 409.

Free for authorized 1Ecomm customers and their developers to build and operate 1Ecomm-connected commerce experiences. You may deploy finished sites, but may not redistribute, resell, sublicense, mirror, or republish this SDK or a reusable derivative. See [LICENSE.md](LICENSE.md).

Use this PHP 8.2+ package for a PHP or Laravel website that sells products from a 1Ecomm store.

## Start

Install the package dependencies, run its self-check, then create a client with the one value shown by 1Ecomm:

```bash
composer install
composer check
```

```php
$client = Phessage\HeadlessCommerce\Client::forStore('your-store-id');
$products = $client->listProducts();
$order = $client->lookupOrder('ORD123', 'buyer@example.com');
```

The client discovers the correct public API settings. A store ID is an identifier, not a password. Laravel package discovery and configuration are explained in [Laravel integration](docs/laravel.md).

Preview releases are distributed from immutable GitHub Release tags because this repository and package are proprietary. Composer consumers authorize GitHub and reference the repository as a VCS source; each release includes an install-smoked archive, locked dependency manifest and SHA-256 checksums. Packagist publication would require a publicly fetchable source distribution and remains disabled unless the owner explicitly changes the licensing/distribution policy.

Eligible reads and idempotent order placement use bounded exponential backoff and honor `Retry-After` up to 30 seconds. Ordinary mutations are never replayed. Typed errors prefer the authoritative `X-Request-Id` response header for support correlation.

Native HTTP requests default to a 10-second timeout; pass `timeoutSeconds` as the final constructor or `forStore` argument to select a value up to 120 seconds. Custom transports must enforce their own deadline. `ProblemException::$rateLimit` provides normalized limit, remaining, reset, and retry-after diagnostics.

`php tests/live.php` runs the complete maintained fixture journey with no environment setup: catalog, isolated cart, checkout choices and one pending bank-transfer test order. Set `HEADLESS_STORE_ID` only for another provisioned sandbox. The test does not charge money.

CI allocates a short-lived, repository-specific fixture and injects its publishable key and product ID into this journey, then revokes the key in an `always()` cleanup step. A missing allocator secret fails CI; it never silently skips deployed verification.

The package supports catalog, anonymous cart, guest checkout preparation, capability-gated non-hosted order placement, and guest order-status lookup. Keep a cart token in the shopper's secure session. Reuse the same order intent key after an uncertain result; do not blindly replay ordinary cart changes. Order number and checkout email are sent in a POST body and the lookup is not automatically retried.

The client also covers customer auth configuration, password/OTP/native social sign-in, refresh/logout, profile, cart merge, addresses, customer orders/cancellation and returns. Access tokens last 15 minutes and refresh capabilities rotate on every use; keep them in encrypted server-side session storage. Replaying a consumed refresh capability revokes its live family replacement, and logout revokes the family. This preview does not directly capture or initiate refunds. Hosted checkout and signed commerce events are platform capabilities. Pass the exact request body and headers to `WebhookVerifier::verify`; keep the `whsec_` value server-side and provide an atomic replay-claim callable backed by a unique delivery-ID constraint before side effects. See [architecture](docs/architecture.md) and [security](docs/security.md).

The repository carries a reviewed SHA-256-pinned copy of the production OpenAPI 3.1 contract. `composer contract:check` fails if that snapshot changes unexpectedly or loses the customer/problem schemas the SDK consumes. `ProblemException::$problemCode` preserves the stable problem `code`, with validation `errors` and structured `fields`, alongside the request ID and rate-limit diagnostics. CI's deployed journey uses the allocator's synthetic customer—never production shopper data—to prove login projection redaction, profile, cart merge, address CRUD, eligible return creation/listing/cancellation, refresh rotation/replay rejection and logout in addition to guest checkout.
