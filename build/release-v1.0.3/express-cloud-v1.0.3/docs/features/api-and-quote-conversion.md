# API and Quote Conversion

Sprint 16 completes the quote workflow and introduces the versioned
integration surface described in the master specification.

Quotes retain the shared sales engine but create no payments or stock
movements. Conversion copies immutable item snapshots into a confirmed
invoice or POS sale and creates stock movements for tracked products at that
point.

The API uses separate bearer tokens rather than staff login keys. Every token
is hashed, scoped, expirable, revocable, and auditable.
