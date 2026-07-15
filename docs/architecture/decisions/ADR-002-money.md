# ADR-002: NGN Integer Money Model

## Status

Accepted.

## Decision

Express Cloud v1 supports NGN only. Monetary values are represented in
integer kobo.

## Consequences

The application avoids floating-point arithmetic and does not expose a
currency selector in v1.
