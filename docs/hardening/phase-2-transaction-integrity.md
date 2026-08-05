# Express Cloud Phase 2 Transaction Integrity Kernel

Phase 2 introduces a durable idempotent command boundary for retryable financial and inventory writes.

## Delivered

- Durable operation requests keyed by operation scope and idempotency key.
- Canonical request fingerprints that reject key reuse with changed payloads.
- Deterministic operation status for the initiating account and system owners.
- Ordered row locking and bounded deadlock/serialization retry handling.
- Append-only operation completion outbox events.
- Unique operation linkage for sales, payments, sale returns, stock movements and journals.
- Overpayment rejection for initial and subsequent sale payments.
- Idempotent sale creation, payment, return, stock intake/transfer/adjustment and batch journal paths.
- A multi-process SQLite probe that proves duplicate retries execute the business callback once.

## Deferred

Phase 3 will consume the same command transaction and outbox contract to post source accounting journals atomically with each operational write.
