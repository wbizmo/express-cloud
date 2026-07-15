# ADR-006: MySQL Production Target

## Status

Accepted.

## Decision

The production and release database target is MySQL.

Development sprints write migrations but do not run database services under
the locked project-files-only workflow.

## Release consequence

The final package includes one MySQL installation SQL file matching the
application schema and required system records.
