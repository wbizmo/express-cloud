# Double-Entry Accounting

Sprint 19 adds the ledger layer on top of the operational records introduced
through Sprint 18.

Every posted journal must balance. Posted entries are never edited or deleted;
corrections use a reversal. Source records are idempotently projected with a
unique source type, source identifier, and source event.

The default chart includes cash, bank, card clearing, receivables, inventory,
fixed assets, accumulated depreciation, payables, output tax, customer
deposits, equity, sales, sales returns, COGS, purchase returns, depreciation,
and general operating expense.

Accounting periods reject new postings after closure or locking.
