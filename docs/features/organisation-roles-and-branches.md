# Organisation, Roles, Branches, and Staff Administration

## Company and head office

Company records carry the legal identity, trading name, head-office address,
logo, timezone, and encrypted contact email.

The head-office address is the authoritative company address used in exported
PDF headers. Branch addresses remain operational location data.

## Branches

Branches are physical operating locations.

Inactive branches:

- are excluded from new operational selectors;
- remain visible in historical records;
- are never hard-deleted merely because operations stop.

Each branch has a unique uppercase code.

## Staff branch access

An account may either:

- access all branches; or
- access only explicitly assigned branches through the account-branch pivot.

Branch access is checked server-side. Hiding a branch in the interface is not
an authorization control.

## Roles and permissions

Express Cloud supports system roles and custom roles.

Permissions use stable dot-separated slugs. Roles are many-to-many with
permissions, and accounts are many-to-many with roles.

System roles cannot be casually deleted. Custom roles remain editable under
the appropriate permissions.

## Staff administration

Staff creation includes:

- first name;
- last name;
- optional encrypted email;
- system-generated encrypted access key;
- role assignment;
- all-branch or specific-branch access.

The generated key is shown immediately after creation and remains available
only through authorised reveal controls.

Suspending an account revokes active sessions immediately.

## Active sessions

The active-session screen uses bounded, cursor-paginated queries and supports
individual session revocation.

The screen must remain usable from zero records to large histories.

## Audit logs

Every sensitive change records:

- actor;
- actor role snapshot;
- action;
- entity type and identifier;
- branch where relevant;
- safe before and after values;
- request IP and user agent;
- timestamp.

Credentials and encryption keys are prohibited from audit payloads.
