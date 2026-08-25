# TravelSync Role-by-Role Workflow Specification

**Status:** Product design baseline  
**Version:** 1.0  
**Date:** 14 August 2026  
**Scope:** Redesigned TravelSync workflow and role-based user experience

**Related specifications:** [Full Lead Workflow](FULL_LEAD_WORKFLOW_SPECIFICATION.md) · [Information Architecture](INFORMATION_ARCHITECTURE_SPECIFICATION.md) · [Target Data Model](TARGET_DATA_MODEL_SPECIFICATION.md)

## 1. Purpose

This specification defines how each TravelSync role should work in the redesigned product. It describes ownership, queues, actions, handoffs, visibility, exceptions, notifications, and success measures.

The specification intentionally separates:

- **Role:** what a user is accountable for.
- **Permission:** a specific capability granted to that user.
- **Assignment:** which records the user is responsible for.
- **Workflow stage:** where a customer inquiry or booking is in its lifecycle.
- **Task:** the next concrete action required from a person or team.

The redesign must not use navigation visibility as the only form of authorization. Every page, record, action, download, and API operation must enforce the same server-side rules.

## 2. Roles in scope

TravelSync currently implements these base roles:

1. Marketing
2. Sales
3. Operations
4. Accounts
5. Call Centre
6. HR
7. Admin

Manager is a capability layered onto Sales, Operations, Accounts, Call Centre, and HR. It is specified separately in section 12.

All authenticated employees also receive common personal functions such as notifications and leave self-service.

## 3. Shared workflow language

### 3.1 Customer lifecycle

The redesigned lifecycle should use the following business stages:

1. **New inquiry** — received but not yet owned.
2. **Assigned** — a sales owner has accepted responsibility.
3. **Qualification** — customer, travel intent, dates, passengers, budget, and contactability are being established.
4. **Ready for pricing** — minimum required information is complete.
5. **Pricing** — itinerary and commercial proposal are being prepared.
6. **Quote sent** — a versioned proposal has been delivered to the customer.
7. **Negotiation / amendment** — changes or commercial decisions are pending.
8. **Confirmed** — the customer has accepted and the booking satisfies the confirmation policy.
9. **Operations handover** — sales context has been accepted by operations.
10. **In fulfilment** — services, documents, and supplier arrangements are in progress.
11. **Ready to travel** — mandatory operational work is complete.
12. **Travel completed** — the return/arrival date has passed and follow-up is due or completed.
13. **Closed** — lost, cancelled, duplicate, invalid, or fully completed.

Legacy lead statuses may remain during migration, but the UI should present this simplified language and use controlled transitions.

### 3.2 Required closure reasons

A workflow must not be closed without one of:

- Booked and completed
- Customer declined
- No response
- Price not accepted
- Dates unavailable
- Service unavailable
- Duplicate inquiry
- Invalid/spam
- Cancelled before confirmation
- Cancelled after confirmation
- Other, with required explanation

### 3.3 Shared record hierarchy

The customer-facing work should be represented as:

```text
Customer
└── Inquiry / Booking workspace
    ├── Conversation and contact history
    ├── Travel requirements
    ├── Quote versions
    ├── Invoice and customer receipts
    ├── Operational services and documents
    ├── Supplier bills and payments
    ├── Calls, tasks, notes, and attachments
    └── Complete audit timeline
```

### 3.4 Shared assignment rules

- Every active inquiry has one accountable sales owner after assignment.
- Every confirmed booking has one accountable operations owner after handover.
- A record may have collaborators, but collaborators do not replace the accountable owner.
- Reassignment requires a reason and creates an audit event.
- The previous owner is notified when responsibility moves.
- Unassigned work remains visible in a shared team queue.
- Tasks always have an owner, due time, priority, and completion state.

## 4. Common employee workflow

### Objective

Give every employee one reliable place to understand personal responsibilities.

### Default landing page: My Work

My Work contains:

- Due today
- Overdue
- Recently assigned
- Waiting on another team
- Approvals requested from the user
- Unread notifications and mentions
- Personal leave balance and upcoming leave

### Common actions

- Open assigned work in its full business context.
- Complete, reschedule, or delegate a task where authorized.
- Add an internal note or mention a colleague.
- Mark notifications read.
- Request leave and view request status.
- Update personal profile and password.

### Common notification rules

Employees are notified when:

- Work is assigned or reassigned to them.
- A task becomes due or overdue.
- They are mentioned in a note.
- A handoff is accepted or rejected.
- A record they own changes materially.
- A leave request is approved, rejected, or requires changes.

## 5. Marketing workflow

### Mission

Capture attributable, usable inquiries and maintain reliable lead-source data without owning sales conversion.

### Primary workspace

**Marketing Intake**

### Queues

- New manually captured inquiries
- Invalid or incomplete source records
- Unattributed WhatsApp inquiries
- Suspected duplicates
- Campaign-source exceptions
- Inquiries awaiting sales assignment

### Standard workflow

1. Create or review the incoming inquiry.
2. Verify basic contact information and consent/source context.
3. Check for an existing customer or duplicate open inquiry.
4. Record source platform, campaign, ad ID, click ID, and original message where available.
5. Classify the broad product interest: individual, group, cruise, visa, or other.
6. Submit the inquiry to the sales intake queue.
7. Monitor assignment and source-data quality; do not manage the sales lifecycle.

### Required data before submission

- Customer/display name or a clear anonymous-contact label
- At least one usable contact channel
- Source platform
- Original inquiry/message
- Product-interest classification when determinable
- Campaign attribution when supplied by the source

### Allowed actions

- Create inquiries.
- Edit source and intake information before sales acceptance.
- Merge or flag suspected duplicates.
- Attach campaign evidence.
- Route records to the sales queue.
- View aggregate source performance.

### Restricted actions

- Cannot confirm bookings.
- Cannot edit pricing, invoices, receipts, vendor bills, or supplier payments.
- Cannot change operational service completion.
- Cannot delete an accepted inquiry; it must be closed or merged with audit history.

### Handoff to Sales

The handoff payload contains:

- Contact identity and channel
- Original message
- Source and campaign attribution
- Product-interest classification
- Duplicate-check result
- Relevant attachments

Sales can **accept**, **return for correction**, or **mark invalid**. A return requires a reason.

### Success measures

- Median inquiry-to-assignment time
- Percentage of inquiries with usable contact details
- Campaign attribution completeness
- Duplicate rate
- Invalid/spam rate by source
- Sales return-for-correction rate

## 6. Sales workflow

### Mission

Convert qualified inquiries into commercially valid confirmed bookings while maintaining clear customer communication and a complete handover.

### Primary workspace

**Sales Pipeline** with a personal **My Work** queue.

### Queues

- Unassigned inquiries available to claim
- Newly assigned
- First contact due
- Qualification incomplete
- Ready for pricing
- Quote draft incomplete
- Quote ready to send
- Customer follow-up due
- Amendment requested
- Confirmation checks required
- Operations handover rejected
- Stale or overdue inquiries

### Standard workflow

1. Claim or accept an inquiry.
2. Review the complete conversation and source context.
3. Make first contact and record the outcome.
4. Qualify the request.
5. Complete required travel information.
6. Request or prepare pricing.
7. Create a versioned quote.
8. Validate price, terms, margin visibility, and validity period.
9. Send the quote through an approved channel.
10. Create a dated follow-up task.
11. Record customer feedback and create amendments as new quote versions.
12. Confirm only when confirmation requirements are satisfied.
13. Complete the operations handover checklist.
14. Remain the commercial/customer owner while operations fulfils the booking.
15. Close lost inquiries with a structured reason.

### Qualification requirements

- Customer name and preferred contact channel
- Destination or requested product
- Intended travel dates or acceptable range
- Adults, children, and infants
- Origin/departure location where applicable
- Budget or commercial expectation where available
- Passport/nationality implications where relevant
- Visa requirement
- Hotel, air, land, cruise, or group requirements
- Special requests and accessibility needs
- Decision deadline

### Confirmation gate

Sales cannot mark a booking confirmed until:

- The customer accepted a current quote.
- Required deposit/payment evidence is present or an authorized exception exists.
- Customer identity and contact details are sufficient.
- Travel dates and passenger counts are confirmed.
- Group bookings are linked to a Tour Master record.
- Material customer commitments are recorded.
- Cancellation and payment terms have been provided.

### Operations handoff checklist

- Accepted quote/version
- Customer and passenger details
- Travel dates and itinerary
- Included and excluded services
- Payment/deposit state
- Supplier assumptions or holds
- Visa/document requirements
- Special requests
- Customer promises and deadlines
- Attachments and supporting correspondence

Operations can accept or reject the handoff. Rejection returns the record to a visible Sales correction queue without removing the confirmed state.

### Allowed actions

- Claim and manage assigned inquiries.
- Create and update customers linked to owned inquiries.
- Record requirements, notes, tasks, and attachments.
- Create and revise quotes.
- View finance records related to owned bookings.
- Send customer communications.
- Confirm, hand over, close, or request reopening according to transition rules.

### Restricted actions

- Cannot edit supplier payments or accounting-controlled records.
- Cannot mark operational services complete unless explicitly granted collaboration permission.
- Cannot view another salesperson's private pipeline unless acting as an authorized manager/collaborator.
- Cannot delete bookings with financial or operational history.

### Success measures

- First-response time
- Qualification completion time
- Quote turnaround time
- Follow-up adherence
- Inquiry-to-quote conversion
- Quote-to-confirmation conversion
- Gross margin and revenue
- Handoff first-pass acceptance rate
- Lost-reason distribution
- Customer response time awaiting sales

## 7. Operations workflow

### Mission

Deliver every confirmed booking accurately and on time, with visible service readiness and controlled exceptions.

### Primary workspace

**Operations Board**

### Queues

- Awaiting operations owner
- Handover awaiting acceptance
- Handover corrections pending
- Departing soon
- Service booking overdue
- Supplier confirmation pending
- Visa/document risk
- Customer information required
- Ready-for-travel review
- Post-travel completion

### Standard workflow

1. Accept assignment and review the sales handoff.
2. Accept or reject the handoff with structured reasons.
3. Build the service checklist from the accepted quote.
4. Assign due dates for air, hotel, visa, land package, cruise, and other applicable services.
5. Request missing customer information through the sales owner unless direct contact is authorized.
6. Book or confirm services with suppliers.
7. Store confirmations, references, deadlines, and attachments.
8. Update each service independently: pending, in progress, done, cancelled, or exception.
9. Raise commercial differences before committing unapproved additional cost.
10. Complete the document checklist.
11. Perform a ready-to-travel review.
12. Release the booking to the departure-call queue.
13. Resolve issues raised by the call centre.
14. Mark operational completion after required fulfilment evidence is present.

### Handoff rejection reasons

- Accepted quote missing or inconsistent
- Passenger information incomplete
- Dates/itinerary unclear
- Missing payment/deposit evidence
- Service inclusion unclear
- Missing passport/visa information
- Unsupported customer promise
- Supplier feasibility concern
- Group booking has no Tour Master link
- Other, with required explanation

### Ready-to-travel gate

- All mandatory services are done or covered by an approved exception.
- Supplier confirmations and references are stored.
- Required customer documents are present.
- Outstanding balance risk is acknowledged by Accounts.
- Emergency/customer contact details are available.
- Final itinerary and inclusions match the confirmed commercial record.
- Pre-departure call can be performed with complete information.

### Allowed actions

- Accept and manage assigned bookings.
- Update operational services and document readiness.
- Add suppliers, references, notes, tasks, and attachments where authorized.
- View the accepted quote, invoice state, and customer payment summary.
- Request sales or accounts action.
- Flag risk and request manager approval.

### Restricted actions

- Cannot silently alter customer price or accepted quote terms.
- Cannot record or reverse customer receipts.
- Cannot approve supplier payments unless also granted Accounts authority.
- Cannot close unresolved operational exceptions without approval.

### Success measures

- Handover acceptance time
- Service completion before deadline
- Bookings ready before departure threshold
- Supplier confirmation turnaround
- Number and age of operational exceptions
- Rework caused by incomplete handoffs
- Customer-impacting incident rate
- On-time operational completion

## 8. Accounts workflow

### Mission

Maintain accurate customer receivables, supplier liabilities, payment records, margin visibility, and cash control.

### Primary workspace

**Finance Control**

### Queues

- Confirmed bookings awaiting invoice
- Customer deposits awaiting matching
- Customer balances due or overdue
- Vendor bills awaiting review
- Supplier payments due
- Payment discrepancies
- Negative or low-margin bookings
- Tour cash-gap warnings
- Records awaiting reconciliation

### Standard customer-receivable workflow

1. Review the confirmed commercial record.
2. Create or verify the invoice.
3. Issue the invoice PDF.
4. Record each customer payment with date, mode, account, amount, reference, and evidence.
5. Issue the receipt.
6. Update balance and payment state automatically.
7. Investigate overpayment, short payment, duplicate reference, or unmatched payment.
8. Confirm financial clearance or record an approved exception before travel.

### Standard supplier-payable workflow

1. Receive or create a vendor bill against the correct invoice/booking and supplier.
2. Validate services, amount, due date, and supporting document.
3. Confirm the group booking has a linked Tour Master where required.
4. Approve, dispute, or return the bill for correction.
5. Schedule payment according to due date and cash position.
6. Record partial or full supplier payment.
7. Update the supplier bank book and payable balance.
8. Reconcile the booking and supplier account.

### Financial controls

- Posted payments are never edited invisibly; corrections use reversal/adjustment entries.
- Payment date, mode, account, amount, reference, and evidence are mandatory.
- Duplicate external references produce a warning or hard validation rule.
- Invoice totals and accepted quote totals require an explicit variance explanation.
- Vendor commitments above the approved sale economics require authorization.
- Every financial mutation appears in the booking audit timeline.

### Allowed actions

- View all finance records.
- Create and edit invoices and vendor bills.
- Record customer and supplier payments.
- Manage suppliers and financial master data.
- Generate PDFs and finance reports.
- View booking context needed to validate transactions.
- Place and clear finance holds.

### Restricted actions

- Cannot change customer requirements or operational completion merely to reconcile finance.
- Cannot erase posted transactions with dependent history.
- Cannot approve their own exceptional adjustment above the configured threshold.

### Handoffs

- To Sales: invoice/query, customer balance, pricing variance.
- To Operations: supplier-payment status, finance hold, cost variance.
- To Manager/Admin: write-off, refund, exceptional payment, negative margin, or policy override.

### Success measures

- Time from confirmation to invoice
- Receivables ageing
- Payment matching accuracy
- Overdue customer balance
- Payables ageing
- On-time supplier payment rate
- Unreconciled transaction count
- Booking margin accuracy
- Cash-gap exposure

## 9. Call Centre workflow

### Mission

Complete consistent pre-departure and post-arrival customer contact, capture outcomes, and escalate actionable issues.

### Primary workspace

**Customer Call Queues**

### Queues

- Upcoming departures eligible for calls
- Recent arrivals eligible for calls
- My assigned calls
- Due today
- Retry required
- Not answered
- Customer issue raised
- Escalation awaiting resolution

### Pre-departure workflow

1. Receive or claim an eligible departure call.
2. Review booking, itinerary, services, documents, and known exceptions.
3. Contact the customer using the approved script.
4. Confirm the customer received essential documents and understands key arrangements.
5. Record outcome, attempt number, time, notes, and requested follow-up.
6. Escalate operational, financial, or sales issues to the correct owner.
7. Schedule a retry when the customer is not reached.
8. Complete the call only when the completion criteria are met.

### Post-arrival workflow

1. Receive or claim an eligible arrival call.
2. Contact the customer after the configured arrival interval.
3. Record service feedback, incidents, complaints, and future interest.
4. Route service issues to Operations and commercial opportunities to Sales.
5. Create a follow-up task where required.
6. Complete the call with a structured outcome.

### Required call outcomes

- Completed — no issue
- Completed — follow-up required
- No answer — retry scheduled
- Wrong/unusable number
- Customer requested another time
- Complaint or incident raised
- Cancelled/not travelled

### Allowed actions

- View assigned call and necessary booking context.
- Claim work from permitted shared queues.
- Record attempts, outcomes, notes, and follow-ups.
- Escalate issues and mention owners.
- View service readiness without editing operational source-of-truth fields.

### Restricted actions

- Cannot change quote, invoice, payment, or supplier records.
- Cannot mark operational services complete.
- Cannot view unrelated customer records.
- Cannot delete call history.

### Success measures

- Calls completed within target window
- Contact rate
- Average attempts per completed call
- Retry adherence
- Escalation acknowledgement and resolution time
- Percentage of calls with structured outcomes
- Customer issue and satisfaction trends

## 10. HR workflow

### Mission

Administer people, leave, office closures, and role-policy requests consistently and audibly.

### Primary workspace

**People & Leave**

### Queues

- Leave requests awaiting review
- Requests needing correction
- Overlapping/coverage-risk leave
- Upcoming office closures
- User onboarding requests
- Role/access change requests
- Offboarding tasks

### Leave workflow

1. Receive employee request.
2. Validate entitlement, dates, type, attachments, and office closures.
3. Identify conflicts or coverage concerns.
4. Request correction, approve, or reject with reason.
5. Update leave balance automatically.
6. Notify the employee and relevant manager.
7. Display approved leave in shared planning views according to privacy rules.

### User lifecycle workflow

1. Receive authorized onboarding/change/offboarding request.
2. Create or update user identity and base role.
3. Apply the approved permission group.
4. Set manager capability only for eligible roles.
5. Verify access using a role-permission preview.
6. Record approval source and effective date.
7. For offboarding, revoke access while preserving record ownership and audit history.

### Allowed actions

- Manage users where authorized.
- Review and manage leave.
- Configure office closures and holidays.
- View leave balances and team coverage.
- Propose or apply approved role/permission changes.

### Restricted actions

- Cannot grant themselves Admin authority.
- Cannot alter business records outside HR scope solely because they manage the user.
- Cannot expose private leave documentation beyond authorized reviewers.
- Cannot delete users who own audit history.

### Success measures

- Leave decision turnaround
- Balance accuracy
- Coverage conflicts caught before approval
- Onboarding completion time
- Access-change completion time
- Offboarding access-revocation time
- Unauthorized-access findings

## 11. Admin workflow

### Mission

Govern the platform, resolve cross-functional exceptions, maintain configuration, and audit system health.

### Primary workspace

**Administration & Control**

### Queues

- Cross-team approvals and exceptions
- Permission/access requests
- Integration and queue failures
- Data-quality exceptions
- Duplicate or orphaned records
- Audit alerts
- Configuration changes awaiting review

### Standard workflow

1. Review the request, incident, or exception with full audit context.
2. Identify the owning business role.
3. Return normal business work to that role rather than completing it as Admin.
4. Use privileged action only when necessary.
5. Require a reason for overrides, reassignment, reopening, reversal, merge, or archival.
6. Notify affected owners.
7. Verify the resulting state and retain the audit record.

### Administrative domains

- Users, roles, permission groups, and individual permissions
- Workflow configuration and transition policy
- Reference/master data
- Tour and supplier governance
- Integration configuration and operational health
- Notification templates and routing
- Record merge, archive, restore, and exceptional correction
- Analytics and audit access

### Privileged-action controls

- Sensitive actions require confirmation and a reason.
- High-risk financial actions may require a second approver.
- Admin impersonation, if introduced, must be visible, temporary, and audited.
- Admin must not be able to conceal or delete audit events through the normal UI.
- Production secrets are never displayed after initial storage.

### Success measures

- Access review findings
- Exception resolution time
- Integration uptime and queue health
- Data-quality defect count
- Number of privileged overrides
- Audit completeness
- Permission drift from approved role templates

## 12. Manager capability overlay

### Applicability

Manager capability can be assigned to:

- Sales
- Operations
- Accounts
- Call Centre
- HR

Managers retain the complete workflow of their base role.

### Mission

Ensure team work is owned, balanced, timely, and completed to quality standards.

### Primary workspace

**Team Work**

### Manager queues

- Unassigned team work
- Overdue work
- Workload imbalance
- Handoff rejection
- Exceptions awaiting approval
- Records with no recent activity
- SLA or target breaches
- Leave/coverage conflicts where relevant

### Standard workflow

1. Review team capacity and priority work.
2. Assign or rebalance work.
3. Investigate stalled records using their timeline and dependencies.
4. Approve or reject permitted exceptions.
5. Return corrective action to the accountable owner.
6. Monitor resolution.
7. Coach using workflow evidence and outcome trends.

### Manager visibility

- Managers see records assigned to themselves and non-manager members of the same role/team.
- Cross-functional visibility is limited to context needed for a handoff or dependency.
- Managers do not automatically receive Admin, HR, or Accounts privileges.
- Sensitive financial and employee fields remain permission-controlled.

### Allowed actions

- View team queues and workload.
- Assign and reassign work within their team.
- View team performance and ageing.
- Approve explicitly delegated exceptions.
- Add notes, mention owners, and create corrective tasks.

### Restricted actions

- Cannot create or delete users solely through manager capability.
- Cannot grant permissions.
- Cannot modify another functional team's source-of-truth data.
- Cannot approve their own exceptional request when segregation of duties applies.

### Success measures

- Team SLA compliance
- Workload balance
- Overdue-work reduction
- Handoff acceptance rate
- Exception resolution time
- Reassignment frequency
- Quality/rework rate

## 13. Cross-role handoff contracts

### 13.1 Marketing → Sales

**Trigger:** inquiry validated and ready for ownership.  
**Receiver must:** accept, return, or invalidate.  
**SLA starts:** when inquiry enters the sales intake queue.

### 13.2 Sales → Operations

**Trigger:** booking confirmed and handoff checklist complete.  
**Receiver must:** accept or reject with structured reasons.  
**Sales remains:** customer/commercial owner.  
**Operations becomes:** fulfilment owner.

### 13.3 Sales → Accounts

**Trigger:** invoice/payment action required.  
**Payload:** booking, accepted quote, amount, due date, customer, and evidence.  
**Accounts must:** complete, query, or place a finance exception.

### 13.4 Operations → Accounts

**Trigger:** supplier commitment, vendor bill, or payment action required.  
**Payload:** supplier, booking, service, amount, due date, and supporting evidence.

### 13.5 Operations → Call Centre

**Trigger:** booking passes ready-to-travel criteria and enters the configured date window.  
**Payload:** current itinerary, contact details, readiness summary, and known exceptions.

### 13.6 Call Centre → Sales/Operations

**Trigger:** customer issue, change request, complaint, or new commercial opportunity.  
**Receiver must:** acknowledge, resolve, and return resolution status to the call task.

### 13.7 Any team → Admin/Manager

**Trigger:** policy exception, blocked ownership, data correction, or privileged action.  
**Payload:** requested outcome, reason, evidence, risk, and deadline.

## 14. Notification and escalation policy

### Immediate

- New assignment
- Handoff rejection
- Customer complaint or travel-critical issue
- Payment failure affecting departure
- Supplier cancellation or material service exception
- Privileged change affecting access or ownership

### Due-date based

- Upcoming task reminder
- Task overdue
- Customer follow-up overdue
- Service deadline approaching
- Customer balance due
- Supplier payment due
- Departure call window opened

### Digest only

- Non-critical team performance
- Low-priority data-quality warnings
- Completed workflow summaries
- General analytics updates

Notifications should link to the exact record and action. The product should avoid sending a notification when the same information is already visible as an active task unless urgency or ownership changed.

## 15. Global audit requirements

The audit timeline must record:

- Record creation and source
- Assignment and reassignment
- Stage transition
- Handoff submission, acceptance, and rejection
- Quote creation, versioning, sending, acceptance, and conversion
- Invoice and vendor-bill creation or material update
- Customer and supplier payment posting, reversal, or adjustment
- Service and document completion
- Call attempts and outcomes
- Notes, mentions, attachments, and significant communications
- Archive, restore, merge, close, reopen, and deletion where allowed
- Privileged access or override

Each event contains actor, timestamp, previous value, new value, reason where required, and related record links.

## 16. Proposed navigation by role

### Marketing

- My Work
- Marketing Intake
- Campaign Sources
- Customers
- Insights

### Sales

- My Work
- Inbox
- Sales Pipeline
- Customers
- Quotes
- My Performance

### Operations

- My Work
- Handover Queue
- Operations Board
- Documents
- Tours
- Suppliers (view where needed)

### Accounts

- My Work
- Finance Control
- Receivables
- Payables
- Payments
- Suppliers
- Tour Finance

### Call Centre

- My Work
- Departure Queue
- Arrival Queue
- My Calls
- Escalations

### HR

- My Work
- Leave Management
- People
- Office Calendar
- Access Requests

### Admin

- My Work
- Administration
- People & Access
- Workflow Configuration
- Master Data
- Integrations
- Audit & System Health
- All Insights

## 17. Acceptance criteria for the redesigned workflow

The redesign is acceptable when:

1. Each role lands on a queue of actionable work rather than a generic record list.
2. Every active record has an accountable owner and a visible next action.
3. Sales-to-Operations handoff is explicit, reviewable, and reversible for correction.
4. Workflow transitions are controlled actions, not an unrestricted status dropdown.
5. Conversation, commercial, operational, and financial context is accessible from one booking workspace.
6. Role permissions and record visibility are enforced consistently on the server.
7. Every privileged or financially meaningful action is audited.
8. Managers can manage team workload without receiving unrelated functional authority.
9. Closed work has a structured outcome/reason.
10. Dashboard metrics can be derived from workflow events and source-of-truth records without manual interpretation.

## 18. Implementation notes against the current codebase

- The seven role names match the current `users.role` options.
- Manager remains the existing `is_manager` capability rather than a new role.
- Existing permissions should be migrated into role templates plus explicit exceptions.
- Existing lead statuses require mapping to the lifecycle in section 3.1.
- Existing `assigned_to`, `assigned_operator`, and call-centre assignment relationships remain useful, but should feed a shared assignment/task model.
- Other Leads can retain their simplified Draft → Confirmed → Completed lifecycle, while using the same workspace, tasks, finance links, and audit conventions.
- Group and Cruise classifications should be product types, not separate navigation destinations.
- Confirm Lead, Visa Leads, Archive Leads, My Sales, My Operation Lead, and All Lead Dashboard should become saved views or queues rather than separate resources in the redesigned UI.
