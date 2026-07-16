# Release Build

Sprint 19 creates the customer package only after all tests and audits pass.

Required release-host tools:

- PHP 8.3 or later with required extensions;
- Composer 2;
- Node.js and npm;
- MySQL client and server access;
- `mysqldump`;
- `zip`.

The build uses a temporary database, runs all migrations and production
seeders, synchronizes accounting, dumps SQL, writes one-time credentials,
copies the application with `vendor/` and built assets, and creates the final
ZIP.
