# Architecture Overview

Express Cloud is a modular Laravel monolith.

Controllers coordinate HTTP requests. Form requests validate input. Policies
and middleware authorize access. Application actions coordinate use cases.
Query objects calculate read models and analytics. Domain services enforce
business invariants. Models map persistence. Views and Livewire components
present already-authorized data.

The production database target is MySQL. During implementation, migrations
define the intended schema but no database service is run locally under the
locked workflow.

Scale begins with bounded queries, correct indexes, server-side pagination,
transactional writes, append-only ledgers, and streamed exports. Caching is
introduced only where measured and operationally safe.
