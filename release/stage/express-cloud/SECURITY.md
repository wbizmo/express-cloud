# Security Policy

Never commit production credentials, encryption keys, database dumps,
plaintext access keys, customer data, or production `.env` files.

Passwords must use one-way password hashing.

Viewable access keys use encrypted ciphertext plus a separate blind index.

Sensitive values must not appear in logs, exports, analytics, email, error
responses, or Lisa AI context.
