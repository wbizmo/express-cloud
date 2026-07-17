# ADR-007: Blade and Livewire

## Status

Accepted.

## Decision

Use Blade and Livewire for reactive application screens, with small isolated
browser-side JavaScript only where the browser must perform the work.

## Consequences

The application retains one authorization and deployment boundary without
requiring a separately deployed React frontend.
