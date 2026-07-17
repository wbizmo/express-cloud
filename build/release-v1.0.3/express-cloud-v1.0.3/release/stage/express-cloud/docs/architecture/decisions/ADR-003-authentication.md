# ADR-003: Shared Staff-Selector Authentication

## Status

Accepted.

## Decision

Administrators and staff use one login screen at `/`.

The form uses a searchable staff-name combobox and an eight-character access
key formatted like `K7M4-P9XR`.

## Security constraints

- No email addresses appear in the selector.
- No automatic IP banning.
- No permanent account lockout.
- Short-lived endpoint throttling is permitted.
- Invalid authentication responses remain generic.
