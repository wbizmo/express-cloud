# Phase 13 rollback runbook

1. Stop queue and cron execution.
2. Put the application in maintenance mode.
3. Restore the verified database backup and matching application archive.
4. Clear config, route, event and view caches.
5. Run read-only accounting, inventory and operation reconciliation.
6. Reopen traffic only after health, authentication and critical transaction smoke tests pass.
