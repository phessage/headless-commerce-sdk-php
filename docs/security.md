# Security

Return creation is never automatically retried. Persist one stable 1–120 character intent key and reuse it only for the identical business intent; a changed payload conflicts.

Only publishable `pk_` keys are accepted. Do not embed confidential server credentials in distributable code. Safe GET requests may retry transient failures; mutations follow the documented idempotency policy.

Handle failures by `ProblemException::$problemCode`, not by matching exception text. `errors` and `fields` contain only public validation diagnostics declared by the contract. Send `requestId` to support; never attach keys, cart/customer/refresh tokens, address data or fixture credentials.

Pass the exact raw webhook body to `WebhookVerifier::verify`. It validates timestamp tolerance, constant-time HMAC comparison and delivery-ID/body binding. Use a database unique constraint in the replay-claim callable; process-local memory is not sufficient.
# Customer sessions

Send access tokens only as `x-customer-token`. Store rotating refresh capabilities in encrypted server-side session storage, replace them after each refresh, and clear both credentials on logout. Replaying a consumed member revokes its live refresh-token family; treat an unexpected refresh 401 as sign-in-required. An access token already issued can remain valid for at most 15 minutes.
