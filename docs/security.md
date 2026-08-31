# Security

Only publishable `pk_` keys are accepted. Do not embed confidential server credentials in distributable code. Safe GET requests may retry transient failures; future mutations must require idempotency keys before release.
