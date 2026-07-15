# Alerts, Admin Dashboard, and End-of-Day Digest

Sprint 12 implements Phase 4 of the standalone master specification.

Express Cloud remains explicitly **not an accounting system**. This sprint
contains no chart of accounts, journal, general ledger, trial balance,
double-entry, profit-and-loss, balance-sheet, or external Atlas coupling.

## Admin dashboard

The admin dashboard answers “what needs attention?” first.

It provides:

- total sales and transaction count for a selected date range;
- open low-stock alert count;
- active session count;
- sales by branch;
- daily sales trend;
- payment-method breakdown;
- open operational notifications;
- staff performance ranking.

Charts follow the master specification:

- categorical comparison data is rendered as bars;
- trend data is ordered by date;
- payment composition is presented as stacked/bar comparison;
- headline health indicators use single-number cards;
- no pie charts are introduced.

## Low-stock notifications

The existing low-stock service now synchronizes an admin notification whenever
a branch-level stock alert opens, updates, or resolves.

The notification remains in-app. Low-stock events are not sent as individual
emails, avoiding alert fatigue. They are included in the next end-of-day
digest.

## Alert recipients

Administrators can add any number of recipient email addresses, label them,
and activate or deactivate them. No email address is hardcoded in source.

## Business settings

The single business-settings record stores non-secret operational values:

- business name;
- logo path;
- head-office address;
- end-of-day digest time;
- session inactivity minutes.

SMTP credentials and cron secrets remain environment configuration.

## End-of-day digest

The digest contains:

- total daily sales;
- branch sales breakdown;
- payment-method totals;
- top five selling items;
- per-staff sales, revenue, units, and customers served;
- every open low-stock item grouped by branch.

One digest record exists per business date. Repeated cron calls return the
already-sent record instead of sending duplicate email.

## Cron setup

Set a strong value in the environment:

    OPERATIONS_CRON_SECRET=YOUR_LONG_RANDOM_SECRET

Configure cPanel Cron Jobs or an external scheduler to request:

    https://your-domain.example/cron/YOUR_LONG_RANDOM_SECRET/end-of-day-digest

Run the request once per minute. The endpoint checks the configured business
digest time and responds without sending when the digest is not due.

The secret is part of the route path, not a query parameter. Invalid secrets
return HTTP 404.

For controlled testing only, append `?force=1` while using the correct secret.

## Email format

The digest email uses restrained text and tables only. It does not depend on
icon fonts or external image services.
