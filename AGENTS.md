# AI engineering guide

Read `README.md`, `docs/architecture.md`, `docs/security.md`, `docs/laravel.md`, `src/*` and both test harnesses before editing.

## Boundary and contract

This is a PHP 8.2+ framework-neutral SDK with an optional Laravel provider. Canonical API truth is `phessage/ecommerce-service/contracts/headless-commerce-v1.openapi.yaml`. Keep Laravel integration as configuration/wiring; the core client must work without Laravel.

`storeId` bootstraps publishable runtime configuration. Preserve key-derived tenancy, cart bearer-token secrecy, non-retry of mutations/lookup, exact-key retry for order placement and neutral guest proof. Guest orders contain `items[]`; do not consume `itemCount`.

Signed outbound webhooks are deployed. Receiver code verifies the exact raw body, timestamp, HMAC and delivery-ID binding before parsing or side effects, then atomically claims the delivery ID in durable storage.

## PHP/Laravel practices

- Use strict types, typed properties/returns, immutable value flow and explicit exceptions. Do not suppress warnings or accept arbitrary mixed shapes without validation.
- Escape only at presentation boundaries; the SDK returns data, not HTML.
- Configure TLS verification, connect/read timeout, redirect policy and bounded responses. Never log keys, cart tokens, email proof or address payloads.
- Follow PSR-4. Do not introduce Laravel facades into the core client.
- The service provider may merge/publish config and bind the client, but must not create routes or hidden network calls at boot.
- Dependency changes update `composer.lock` at the PHP 8.2 platform floor and must also pass current supported PHP/Laravel matrices.

## License boundary

`LICENSE.md` allows authorized 1Ecomm customer projects and deployed or compiled shopper applications, but prohibits redistribution of this reusable SDK/plugin or its derivatives. Preserve the notice in clones, packages, generated projects and documentation. Do not describe this repository as open source or grant broader rights in examples.

## Verification

Run `composer install`, `composer check`, and the maintained sandbox `php tests/live.php` when authorized. Use a real Laravel boot test for provider changes. Never use production customer data or call a skipped live test complete.

## Releases

`.github/workflows/release.yml` is the only release path. It requires the protected `package-release` environment and a new exact semantic-version tag, runs the PHP/Laravel suite, installs the built archive without development dependencies, and attaches the immutable archive, dependency manifest and checksums to a GitHub Release. Never publish this private proprietary source to public Packagist, move a tag or replace an existing release asset without an explicit licensing/distribution decision.
