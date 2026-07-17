# Express Cloud API

The production API is versioned under `/api/v1`.

## Authentication

API requests use independently issued bearer tokens:

    Authorization: Bearer ec_live_...

Tokens are hashed with SHA-256 before storage. Plaintext is displayed only
once during creation. Tokens support abilities, expiry, last-used tracking,
and immediate revocation.

Web login keys are never reused as API credentials.

## OpenAPI

The current machine-readable document is available at:

    GET /api/openapi.json

## Pagination

List endpoints use cursor pagination. Responses use:

    {
      "data": [...],
      "meta": {
        "next_cursor": "..."
      }
    }

## Quote conversion

`POST /api/v1/quotes/{quote}/convert` converts a quote to an invoice or POS
sale without re-entering its items. Conversion is idempotent per quote and
target type. Tracked stock moves only when conversion occurs.
