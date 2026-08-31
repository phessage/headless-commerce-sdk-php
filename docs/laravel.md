# Laravel integration

Laravel package discovery registers `HeadlessCommerceServiceProvider`. Publish the configuration with the `headless-commerce-config` tag, set `HEADLESS_COMMERCE_URL` and a public `HEADLESS_COMMERCE_PUBLISHABLE_KEY`, then type-hint `Phessage\HeadlessCommerce\Client`.

The provider is a server-side integration convenience. Do not expose confidential credentials through Laravel Mix/Vite variables. Queue future webhook processing only after raw-body signature and duplicate-event support ship.
