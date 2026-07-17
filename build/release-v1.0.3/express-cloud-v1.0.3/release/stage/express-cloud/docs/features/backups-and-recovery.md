# Backups and Recovery

Sprint 17 introduces explicit, verifiable backup operations.

## Backup format

Every backup contains:

- a native database dump;
- a JSON manifest;
- per-file SHA-256 checksums;
- a compressed tar archive;
- a database record containing the archive checksum, size, path, status,
  timestamps, and failure details.

## Storage

`BACKUP_DISK` may point to local storage or any configured Laravel filesystem
disk, including S3-compatible object storage. Production deployments should
use off-server object storage.

## Commands

    php artisan backup:create
    php artisan backup:verify <backup-run-ulid>
    php artisan backup:prune

## Recovery policy

The application deliberately does not expose one-click production restore in
the browser. Restore is an operational procedure requiring maintenance mode,
a verified archive, infrastructure access, and a post-restore validation
checklist. This avoids overwriting live data through an accidental web action.

## Scheduling

Recommended production cron:

    15 2 * * * php /path/to/artisan backup:create
    45 2 * * * php /path/to/artisan backup:prune

A separate scheduled verification should verify the newest completed backup
at least weekly.

## Health endpoint

`GET /api/health` checks the database, configured backup storage, and
application-key presence. It returns HTTP 503 when a critical check is
degraded.
