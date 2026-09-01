# 1Ecomm Headless Commerce PHP SDK

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

`php tests/live.php` runs the complete maintained fixture journey with no environment setup: catalog, isolated cart, checkout choices and one pending bank-transfer test order. Set `HEADLESS_STORE_ID` only for another provisioned sandbox. The test does not charge money.

The package supports catalog, anonymous cart, guest checkout preparation, capability-gated non-hosted order placement, and guest order-status lookup. Keep a cart token in the shopper's secure session. Reuse the same order intent key after an uncertain result; do not blindly replay ordinary cart changes. Order number and checkout email are sent in a POST body and the lookup is not automatically retried.

This preview does not collect card/wallet payments, capture/refund money, merge customer carts, or deliver webhooks. See [architecture](docs/architecture.md) and [security](docs/security.md).
