# Production Readiness Checklist

Before release:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and the public `APP_URL`.
2. Configure HTTPS and trusted proxy headers.
3. Configure the production database with least-privilege credentials.
4. Configure `BACKUP_DISK` to off-server object storage.
5. Configure SMTP and verify end-of-day digest delivery.
6. Set the operations cron secret and schedule the digest request.
7. Schedule `backup:create` and `backup:prune`.
8. Run `php artisan optimize`.
9. Run migrations during a documented maintenance window.
10. Verify `/api/health`.
11. Create and verify the first production backup.
12. Confirm queue workers and cron processes restart automatically.
13. Confirm storage links and upload permissions.
14. Confirm all default administrator access keys have been rotated.
15. Run the full automated test suite and dependency audits.
