# ADR-004: Selective Field Encryption

## Status

Accepted.

## Decision

Passwords use one-way hashing.

Viewable login keys and selected sensitive employee fields use versioned
encryption. Exact login-key lookup uses a separate HMAC blind index.

## Consequences

Encryption keys remain outside the database and are separated by purpose.
Sensitive plaintext never enters logs, exports, analytics, email, or Lisa AI
context.
