# Application Stabilization and Lisa AI

This stabilization release connects the modules already implemented across the project into a coherent, permission-aware application.

## Authorization

Protected routes use named permissions and return 404 to authenticated users who lack access. The desktop and mobile navigation are generated from the same permission rules, so hidden links and direct URL protection remain aligned. Staff dashboards show the authenticated account's operational data; company-wide dashboards remain permission-gated.

## Access keys

Access keys are eight alphabetic characters displayed as `XXXX-XXXX`. Ambiguous letters I, L and O are excluded. The encrypted value and blind index are derived from the same normalized eight-letter value. Plaintext keys are displayed only when generated or rotated.

## Lisa AI v2

Lisa is an internal server-side business insight module, not an unrestricted chatbot. It operates on summarized, already-authorized operational data and stores explainable insights containing a category, severity, summary, recommendation and evidence. It never generates arbitrary production SQL and never receives login keys, application keys or encrypted credentials.

Current detectors cover discount pressure, low-stock exposure, receivables and a safe no-exception executive summary. The command `php artisan lisa:generate` can be scheduled; authorized users can also refresh insights from the Lisa interface.

## Database consistency

The application no longer queries the removed `accounts.account_type` column. Staff performance uses independent per-sale and per-line aggregates to avoid multiplying revenue when a sale contains multiple items. The new `business_insights` table is defined only through a migration; SQL packaging is intentionally deferred.
