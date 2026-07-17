# Express Cloud v1.0.1

Final production package containing the application, Composer dependencies,
compiled assets, production environment, SQL installer, first-login
credentials, documentation, and Sprint 19 accounting implementation.

## Installation

1. Extract the ZIP.
2. Create an empty MySQL database.
3. Import `express-cloud-install.sql`.
4. Update database credentials and `APP_URL` in `.env`.
5. Point the domain document root to `public/`.
6. Ensure `storage/` and `bootstrap/cache/` are writable.
7. Log in using `FIRST_LOGIN.txt`.
8. Rotate the administrator access key immediately.
9. Delete `FIRST_LOGIN.txt` from the server.
