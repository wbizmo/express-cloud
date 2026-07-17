# Testing Strategy

Under the locked workflow, Codespaces testing is limited to checks that do
not require a database service:

- unit tests;
- PHP syntax and static analysis;
- formatting;
- Composer validation and advisories;
- frontend build;
- npm advisories.

Database integration, SQL import, foreign keys, migrations, transactions,
authentication persistence, inventory concurrency, and live reports are
validated after deployment with the packaged MySQL SQL file.
