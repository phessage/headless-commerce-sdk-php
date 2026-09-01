# Laravel integration

Laravel package discovery registers `HeadlessCommerceServiceProvider`. Publish the configuration with the `headless-commerce-config` tag, set only `HEADLESS_COMMERCE_STORE_ID`, then type-hint `Phessage\HeadlessCommerce\Client`. The provider resolves public runtime configuration when the singleton is first used. Explicit URL/key settings remain a compatibility path for isolated tests.

Keep the shopper's `hc_…` cart capability in Laravel's encrypted session and never log it. The provider is a server-side integration convenience. Do not expose confidential credentials through Laravel Mix/Vite variables. Order finalization, payment capture and public webhooks are not available; queue no webhook processing until raw-body signature and duplicate-event support ship.
