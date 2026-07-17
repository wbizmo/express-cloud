# Coding Standards

## PHP

- Use strict types.
- Follow Laravel Pint formatting.
- Pass Larastan at the configured level.
- Keep controllers thin.
- Use Form Requests for external input validation.
- Use policies and middleware for authorization.
- Use actions for state-changing use cases.
- Use query objects for analytics and read models.
- Do not place business logic inside Blade templates.

## Database design

- Use explicit indexes for common filters and ordering.
- Use integer kobo for money.
- Use append-only ledgers for inventory and sensitive histories.
- Avoid unbounded list queries.
- Use transactions for financial and inventory writes.
- Document migration intent with each feature.

## Frontend

- Build reusable Blade and Livewire components.
- Use consistent loading, success, error, and disabled states.
- Preserve responsive desktop and mobile behaviour.
- Avoid visual clutter and unnecessary dashboard decoration.
