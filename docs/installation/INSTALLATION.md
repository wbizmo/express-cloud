# Express Cloud Installation

## Package contents

- application files;
- `vendor/`;
- compiled `public/build/` assets;
- `express-cloud-install.sql`;
- `.env.example`;
- `FIRST_LOGIN.txt`;
- documentation.

## Shared-hosting installation

1. Create an empty MySQL 8 or compatible MariaDB database.
2. Import `express-cloud-install.sql`.
3. Upload and extract the application package.
4. Point the domain document root to the package's `public/` directory.
5. Copy `.env.example` to `.env`.
6. Fill in `APP_URL`, database credentials, SMTP settings, and all encryption keys.
7. Ensure `storage/` and `bootstrap/cache/` are writable by PHP.
8. Create the storage link if the host supports shell access. Without shell
   access, map or copy `public/storage` according to the hosting panel.
9. Open the application and sign in using `FIRST_LOGIN.txt`.
10. Immediately update company details, verify document branding, create a
    backup, and rotate the installation access key.

## Required PHP extensions

BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO MySQL, Tokenizer,
XML, GD, ZIP, Phar, and Intl.

## Security

Do not upload `.env` from development. Do not leave `FIRST_LOGIN.txt` inside
the public web root. Delete it after the first successful login and secure
handover.
