# Authentication and Security Events

## Shared login

Guests visiting `/` see the shared administrator and staff login page.

The form contains:

1. searchable staff-name selector;
2. access key.

The selector does not reveal staff email addresses. It requires typed search,
uses a fixed-height scrollable result list, supports keyboard navigation, and
shows branch or department when duplicate names require disambiguation.

## Access keys

Access keys use eight non-ambiguous uppercase alphanumeric characters,
displayed as:

    K7M4-P9XR

Approved alphabet:

    ABCDEFGHJKMNPQRSTUVWXYZ23456789

The plaintext key is not stored. The application stores encrypted ciphertext
for authorised display and a keyed blind index for exact login lookup.

## No bans or permanent lockouts

Express Cloud does not automatically ban IP addresses and does not
permanently lock accounts after failed login attempts.

The endpoint may apply short-lived throttling that expires automatically.

## Security-event scale

Security histories must support millions of records through:

- indexed filters;
- bounded date ranges;
- server-side sorting;
- cursor pagination;
- immutable identifiers;
- retention and archival;
- streamed exports.

Sensitive plaintext values are never stored inside event payloads.
