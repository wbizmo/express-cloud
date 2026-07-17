# ADR-005: Modular Monolith

## Status

Accepted.

## Decision

Build Express Cloud as a modular Laravel monolith.

## Rationale

A modular monolith avoids network latency, distributed transactions,
duplicated authorization logic, and unnecessary deployment complexity.

## Boundaries

Controllers coordinate requests. Actions implement use cases. Queries produce
read models. Policies authorize access. Services enforce reusable domain
rules. Views present already-authorized data.
