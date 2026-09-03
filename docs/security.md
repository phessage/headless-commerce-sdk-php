# Security

Only publishable `pk_` keys are accepted. Do not embed confidential server credentials in distributable code. Safe GET requests may retry transient failures; mutations follow the documented idempotency policy.

Pass the exact raw webhook body to `WebhookVerifier::verify`. It validates timestamp tolerance, constant-time HMAC comparison and delivery-ID/body binding. Use a database unique constraint in the replay-claim callable; process-local memory is not sufficient.
