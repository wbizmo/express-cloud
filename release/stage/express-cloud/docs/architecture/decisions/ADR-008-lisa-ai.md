# ADR-008: Lisa AI Architecture

## Status

Accepted.

## Decision

Lisa AI by Zivora is a first-class navigation module.

Its core intelligence uses deterministic, permission-aware query handlers and
a versioned product knowledge base. An optional language-model provider may
improve wording and intent classification later.

## Security constraints

- Lisa receives only already-authorized data.
- Lisa never generates arbitrary production SQL.
- Lisa respects company, branch, role, and user scope.
- Login keys and sensitive credentials never enter Lisa context.
