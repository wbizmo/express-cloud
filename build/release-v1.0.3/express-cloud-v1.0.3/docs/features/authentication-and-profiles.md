# Authentication and Profiles

## Shared sign-in

Administrators and staff use one login page at `/`.

The interface contains:

- a searchable first-name and last-name combobox;
- an eight-character access key field;
- no email field;
- no password field;
- no role selector.

Search begins after two characters and returns at most twenty active accounts.
Results are sorted by last name and first name.

## Access keys

Keys use the format:

    K7M4-P9XR

The application stores:

- encrypted ciphertext for authorised display;
- an HMAC blind index for exact login matching;
- encryption version metadata.

Ordinary login compares blind indexes and does not decrypt the key.

## Throttling

The login endpoint uses short-lived request throttling.

Express Cloud does not automatically ban IP addresses and does not
permanently lock accounts after failed attempts.

## Sessions

Successful login:

- regenerates the Laravel session identifier;
- records an account-session row;
- records last activity;
- records a security event.

The default inactivity period is thirty minutes.

Logout:

- records a security event;
- revokes the current account-session row;
- invalidates the Laravel session;
- regenerates the CSRF token.

## Profiles

Users can view:

- name;
- status;
- assigned access key;
- profile picture.

Users may change only the profile picture.

They cannot change:

- name;
- email;
- access key;
- role;
- permissions;
- branch assignments;
- account status.

## Security events

The security-events table is append-only and indexed for event type, actor,
subject, IP address, and event time.

Plaintext access keys and encrypted credential payloads are never written to
security-event context.
