# Finance Enhancement Scope

## Objective

Extend the existing finance workspace into an auditable account ledger covering receipts, supplier payments, expenses, internal transfers, searchable registers, bank statements, and Excel downloads.

## Roles and access

- **Account users:** full create, view, edit, search, statement, and export access.
- **Administrators:** full access and audit visibility.
- **Other roles:** no access unless a dedicated read-only permission is added later.
- Every create, edit, transfer, and export action must be recorded in the audit log.

## 1. Excel report downloads

### Included

- Add `.xlsx` downloads for:
  - Payment Register
  - Bank Statement
  - Expenses
  - Vendor Payments
  - Invoices
- Export the currently selected date range and filters.
- Include report title, selected filters, generated date/time, column headings, totals, and currency formatting.
- Protect text values beginning with spreadsheet formula characters.
- Use descriptive filenames such as `payment-register-2026-10-01-to-2026-10-31.xlsx`.

### Acceptance criteria

- Account and admin users can download the filtered report.
- The Excel totals match the totals shown on screen.
- Unauthorized users receive HTTP 403.
- Large exports are streamed and do not exhaust application memory.

## 2. User-created expense categories

### Included

- Replace the current free-text category field with an Expense Category master list.
- Category fields:
  - Name
  - Optional code
  - Description
  - Active/inactive status
  - Created by
- Account and admin users can create categories from the Expenses workspace.
- Expenses use a category dropdown with an inline “Create category” action.
- Existing free-text expense categories are migrated into unique category records.
- Categories already used by expenses cannot be deleted; they can be made inactive.

### Acceptance criteria

- Duplicate active category names are rejected case-insensitively.
- Inactive categories remain visible on historical expenses but cannot be selected for new expenses.
- Category creation and status changes are audited.

## 3. Vendor payment editing

### Included

- Add an Edit action to supplier/vendor payment details.
- Editable fields:
  - Payment date
  - Payment method
  - Paid-through account
  - External reference
  - Notes
  - Vendor bill allocations
- Editing occurs inside a database transaction.
- Recalculate every affected vendor bill and invoice payment status after saving.
- Keep the payment number and creator immutable.
- Require an edit reason for the audit trail.

### Validation rules

- Payment total must equal the allocation total when bill allocations exist.
- An allocation cannot exceed the bill’s available outstanding amount after accounting for the payment’s existing allocation.
- Bills must belong to the selected supplier.
- A payment recorded without a vendor bill may remain unallocated.
- Changing the supplier is outside this scope; incorrect-supplier payments should use a controlled reversal workflow later.

### Acceptance criteria

- Old and new bill balances are recalculated correctly.
- Payment register and bank statement update immediately.
- Audit history shows before/after values and the edit reason.

## 4. Invoice numbers on vendor bills

### Included

- Show the linked customer invoice number on:
  - Vendor bill list
  - Vendor bill detail page
  - Vendor bill PDF
  - Supplier payment allocations
- Invoice numbers link to the invoice detail page when the user has access.
- Common tour bills show the tour code instead.
- Standalone bills display “Standalone”.

### Acceptance criteria

- Users can identify the related invoice without opening each vendor bill.
- Invoice visibility rules are still enforced.

## 5. Search by any name

### Included

- Add one search field to finance lists and the Payment Register.
- Match partial, case-insensitive values across:
  - Customer name
  - Supplier/vendor name
  - Salesperson name
  - Lead reference
  - Invoice number
  - Vendor bill number
  - Receipt/payment reference
  - Expense description and category
- Preserve search and filters in the URL.
- Apply a short debounce to avoid a query on every keystroke.

### Acceptance criteria

- Searching any supported name returns all authorized related records.
- Results never bypass role or record-level visibility.
- Search can be combined with date, account, direction, and payment-method filters.

## 6. Payment Register enhancement

### Current state

The system already combines customer receipts, supplier payments, legacy vendor payments, and expenses, with basic direction and date filters.

### Included

- Add filters for:
  - Search/name/reference
  - Date range
  - Direction
  - Transaction type
  - Payment method
  - Account
  - Supplier/customer
- Include these transaction types:
  - Customer receipt
  - Supplier payment
  - Expense
  - Internal transfer
- Add columns for transaction type, account, linked invoice/vendor bill, and recorded by.
- Make each source transaction clickable.
- Show received, paid, transfer, and net movement totals.
- Add Excel download using the active filters.

### Acceptance criteria

- Each financial transaction appears exactly once as a business transaction.
- Transfers do not change the overall company net movement.
- Screen and exported totals match.

## 7. Internal payment transfer

### Included

- Add “New internal transfer” under Finance.
- Transfer fields:
  - Transfer date
  - From account
  - To account
  - Amount
  - Reference number
  - Notes
  - Created by
- Supported accounts initially use the existing list:
  - Cash
  - NTB Current
  - NTB Saving
  - Seylan Current
  - Seylan Saving
  - HNB Current
  - HNB Saving
- Store one transfer record and expose two ledger legs:
  - Debit from the source account
  - Credit to the destination account
- Both legs share one transfer reference and audit record.

### Validation rules

- Source and destination accounts must differ.
- Amount must be greater than zero.
- Transfer date cannot be in a locked accounting period when period locking is introduced.
- Editing a transfer requires an audit reason and updates both ledger legs atomically.

### Acceptance criteria

- The source account balance decreases and destination account balance increases by the same amount.
- Overall company cash is unchanged.
- The transfer appears in both account statements and in the Payment Register as an internal transfer.

## 8. Bank Statement

### Phase 1: internal account statement

- Add Finance → Bank Statements.
- Select one account and date range.
- Display:
  - Opening balance
  - Date
  - Transaction/reference
  - Description/party
  - Debit
  - Credit
  - Running balance
  - Source type
  - Closing balance
- Statement sources:
  - Customer receipts deposited to the selected account
  - Supplier payments paid through the selected account
  - Expenses paid through the selected account
  - Both sides of internal transfers
- Add Excel and PDF downloads.
- Allow an opening balance and opening-balance date for each account.

### Phase 2: bank-file import and reconciliation (separate scope)

- Upload official bank CSV/XLSX files.
- Define a parser per bank/file format.
- Match imported lines against CRM transactions.
- Mark matched, partially matched, unmatched, and ignored entries.
- This phase requires sample statement files from NTB, Seylan, and HNB before implementation.

### Acceptance criteria for Phase 1

- Running balance equals opening balance plus credits minus debits.
- Transfers appear on both affected account statements.
- Statement totals match the Payment Register for the same account and date range.

## Data model additions

- `expense_categories`
- `internal_transfers`
- Account opening-balance fields, either in a new `financial_accounts` table or a dedicated account-balance table.
- `expenses.category_id` foreign key while retaining the legacy category text during migration.
- Optional audit-reason fields are captured in audit logs rather than duplicated on every table.

## Recommended implementation order

1. Financial account master data and expense categories.
2. Internal transfer model and ledger service.
3. Vendor payment edit workflow and recalculation safeguards.
4. Unified Payment Register filters and name search.
5. Internal bank statement with running balances.
6. Excel/PDF exports from the finalized filtered queries.
7. Optional bank-file import and reconciliation after receiving sample files.

## Explicitly out of scope for the first release

- Direct integration with bank APIs.
- Automatic bank reconciliation without imported statement formats.
- Deleting posted financial transactions.
- Multi-currency conversion or exchange-gain calculations.
- Accounting-period locks and formal journal entries, unless separately requested.
