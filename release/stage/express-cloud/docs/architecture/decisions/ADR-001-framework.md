# ADR-001: Laravel Framework

## Status

Accepted.

## Decision

Use Laravel 13 with PHP 8.4 as the target runtime.

## Context

The product must support shared-hosting deployment, conventional PHP
maintenance, and a single application deployment unit.

## Alternatives

- React frontend plus API backend
- Node.js full-stack application
- Laravel modular monolith

## Consequences

Laravel provides routing, validation, authorization, mail, queues, exports,
and deployment portability inside one codebase.
