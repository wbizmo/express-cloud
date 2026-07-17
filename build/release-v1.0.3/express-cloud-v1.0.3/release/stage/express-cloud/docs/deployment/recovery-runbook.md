# Recovery Runbook

1. Declare the incident and stop write traffic.
2. Put the application into maintenance mode.
3. Identify the newest completed backup with a successful checksum.
4. Copy the archive to an isolated recovery host.
5. Verify the archive SHA-256 value.
6. Extract the archive and inspect `manifest.json`.
7. Restore into a new empty database, never directly over the damaged one.
8. Run schema and record-count validation.
9. Point a staging application instance at the recovered database.
10. Test login, products, stock, customers, sales, payments, returns, and
    audit history.
11. Switch production only after approval.
12. Keep the damaged database immutable until the incident review closes.
13. Record the recovery action in the operational incident log.
