# Laravel integration

Laravel package discovery registers `HeadlessCommerceServiceProvider`. Publish the configuration with the `headless-commerce-config` tag, set `HEADLESS_COMMERCE_URL` and a public `HEADLESS_COMMERCE_PUBLISHABLE_KEY`, then type-hint `Phessage\HeadlessCommerce\Client`. The package boots and resolves its singleton inside a real Laravel runtime through `composer test:laravel`.

Keep the shopper's `hc_…` cart capability in Laravel's encrypted session and never log it. The provider is a server-side integration convenience. Do not expose confidential credentials through Laravel Mix/Vite variables. Order finalization, payment capture and public webhooks are not available; queue no webhook processing until raw-body signature and duplicate-event support ship.
