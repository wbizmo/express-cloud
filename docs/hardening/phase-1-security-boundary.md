# Express Cloud Phase 1 Security Boundary

Phase 1 centralizes authorization and branch isolation without enabling the dormant API surface.

## Implemented boundaries

- Database-backed Laravel gates for every permission catalog slug, including wildcard grants.
- A universal authenticated route branch boundary for bound resources and submitted branch identifiers.
- Branch-aware policies for financial, inventory, sales, procurement, reporting and Lisa resources.
- Sale visibility that combines permission scope, branch scope and ownership.
- One navigation visibility service used by desktop and mobile interfaces.
- Security headers for every web response, with no-store caching for authenticated HTML.
- MIME/size-verified uploads with opaque server-generated filenames.
- Dynamic operation-document authorization before rendering.

## Deliberately deferred

- API v1 activation remains disabled until its full ability and object-policy matrix is implemented.
- Transaction idempotency and row-locking belong to Phase 2.
- Full accounting source posting belongs to Phase 3.
