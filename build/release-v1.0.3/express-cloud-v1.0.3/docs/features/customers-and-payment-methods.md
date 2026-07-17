# Customers and Payment Methods

Sprint 9 adds the customer and payment-method foundations used by invoice,
quote, and POS workflows.

## Customers

Minimum quick-add fields are name and phone.

Optional fields include encrypted email, address, credit limit, and wholesale
status. Customer codes are generated independently of database row order.

The search endpoint:

- requires at least two search characters;
- returns at most twenty active matches;
- searches name, phone, and customer code;
- supports inline customer creation without leaving a sale.

## Payment methods

Cash and Bank Transfer are protected system defaults in release seeding.

Custom methods support:

- name;
- encrypted account number;
- bank name;
- description;
- active state.

Exactly one active method may be selected as the default POS method through
an explicit service transaction. Selecting a new default does not alter any
other payment-method configuration.
