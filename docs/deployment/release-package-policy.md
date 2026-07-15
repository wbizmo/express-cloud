# Release Package Policy

The final customer deliverable contains the application, built assets,
Composer dependencies for no-shell deployment, an importable MySQL SQL file,
and complete installation and operating documentation.

The release excludes:

- sprint shell scripts;
- temporary sprint logs;
- development `.env`;
- local test artifacts;
- editor state;
- `public/hot`;
- development source maps not required in production.

No production `.env` file is shipped.
