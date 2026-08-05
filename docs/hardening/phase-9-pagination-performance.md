# Phase 9 — Pagination, query efficiency, cache and dynamic loading

- Default list size remains 10; event and operational streams use cursor pagination.
- Versioned reference-data cache invalidates on branch, payment-method and category changes.
- Sales exports use `lazyById` and streamed CSV output.
- High-volume indexes cover customer debt, sales workflows, POS shifts, attendance, stock events, payments and audit logs.
- `performance:audit --fail-on-violation` inventories every Blade/controller list surface and enforces Phase 6-9 primary-list pagination.
- Runtime tests load 120 employees and enforce the configured query budget.
