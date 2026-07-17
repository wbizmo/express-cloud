# Encryption and Key Management

Passwords are one-way hashed and never reversibly encrypted.

Viewable login keys and selected employee information use versioned encrypted
payloads. Exact login lookup uses a separate HMAC blind index.

Keys are separated by purpose:

    APP_KEY
    DATA_ENCRYPTION_KEY
    BLIND_INDEX_KEY
    BACKUP_ENCRYPTION_KEY
    CRON_PATH_SECRET

Sensitive values must not appear in logs, exports, analytics, exception
messages, email, or Lisa AI context.

Database encryption primarily protects against database-only disclosure. It
does not protect data when an attacker also obtains application keys or a
valid privileged session.
