# Activity, Sessions, and Error Hardening

Sprint 14 completes the web-application polish around operational
traceability and production safety.

## Product activity

Product identity changes and stock movements are displayed in separate
streams. Product edits do not rewrite or delete historical movement data.

## System activity

The admin-only activity view uses the existing append-only audit table and
supports actor, entity-type, and date filtering with cursor pagination.

## Live sessions

Administrators can inspect active and recent terminal sessions and terminate
a session immediately. Every forced termination is audited.

## Structured errors

JSON validation errors return `VALIDATION_FAILED`. Business-rule failures
return `BUSINESS_RULE_FAILED`. Unexpected production failures return a
generic `UNEXPECTED_ERROR` message without exposing exception internals.

## Regression protection

Tests explicitly guard the known failure modes from the standalone master
specification, including external QR dependencies, missing branch addresses
on documents, and product edits deleting history.
