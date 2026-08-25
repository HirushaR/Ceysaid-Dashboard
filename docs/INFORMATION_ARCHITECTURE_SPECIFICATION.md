# TravelSync Information Architecture Specification

**Status:** Product design baseline  
**Version:** 1.0  
**Date:** 25 August 2026  
**Related:** [Role Workflows](ROLE_WORKFLOW_SPECIFICATION.md) · [Full Lead Workflow](FULL_LEAD_WORKFLOW_SPECIFICATION.md) · [Target Data Model](TARGET_DATA_MODEL_SPECIFICATION.md)

## 1. Purpose

This document defines how the redesigned TravelSync product is organized, named, navigated, searched, and presented to each role.

The architecture replaces the current collection of overlapping Filament resources with:

- A small set of stable business destinations
- Role-specific queues and saved views
- One canonical workspace for each business object
- Consistent terminology and URLs
- Progressive disclosure based on role and task
- Server-enforced permissions independent of navigation visibility

## 2. Architecture principles

1. **Organize around work, not database tables.** Navigation should answer “What do I need to do?”
2. **One object, one canonical workspace.** A lead opened from Sales, Operations, Finance, or Call Centre is the same record and URL.
3. **Views are not resources.** Group leads, cruise leads, visa leads, archived leads, and personal leads are filters or saved views.
4. **Roles change defaults, not truth.** Different roles see different priorities and actions without creating separate copies of the record.
5. **Keep global navigation stable.** Permissions may remove inaccessible destinations, but labels and hierarchy remain consistent.
6. **Show exceptions before reports.** Operational dashboards prioritize work and risk over decorative metrics.
7. **Use progressive disclosure.** Present summary first; reveal detailed commercial, operational, or financial data when relevant.
8. **Preserve context.** Cross-functional handoffs link to the exact workspace section and required action.
9. **Use customer language in the UI.** Internal model and legacy status names must not leak into labels.
10. **Design for search and deep links.** Every primary record, tab, saved view, task, and notification has a stable URL.

## 3. Canonical business objects

### 3.1 Primary objects

| Object | Meaning | Canonical label | Canonical workspace |
|---|---|---|---|
| Inquiry / Booking | Customer travel opportunity across its full lifecycle | Lead before confirmation; Booking after confirmation | Lead/Booking Workspace |
| Customer | Person or organization receiving services | Customer | Customer Workspace |
| Conversation | WhatsApp or future channel thread | Conversation | Conversation Workspace or embedded lead panel |
| Task | Owned, due action | Task | Task detail drawer/workspace link |
| Quote | Versioned customer proposal | Quote | Quote Workspace |
| Invoice | Customer receivable document | Invoice | Invoice Workspace |
| Customer payment | Receipt against invoice | Customer Payment | Payment detail |
| Service item | Operational deliverable | Service | Embedded Operations panel |
| Document requirement | Required/received/verified document | Document | Embedded Documents panel |
| Supplier | Supplier master record | Supplier | Supplier Workspace |
| Vendor bill | Supplier liability | Vendor Bill | Vendor Bill Workspace |
| Supplier payment | Payment to supplier | Supplier Payment | Supplier Payment detail |
| Tour | Group departure master record | Tour | Tour Workspace |
| Call task | Pre-departure or post-arrival call | Customer Call | Call Workspace |
| Leave request | Employee leave workflow | Leave Request | Leave Request Workspace |
| User | Employee identity and access | Team Member | Team Member Workspace |

### 3.2 Naming behavior for lead/booking

- Before confirmation, the primary label is **Lead**.
- After confirmation, the primary label is **Booking**.
- The canonical record and URL do not change at confirmation.
- Search recognizes lead reference, booking reference, legacy ID, customer, contact, quote, invoice, and WhatsApp identity.
- Generic system text may use **Lead / Booking** where both lifecycle states are possible.

### 3.3 Concepts that are not top-level objects

The following are saved views, filters, classifications, or embedded sections—not separate navigation resources:

- My Sales
- My Operations Leads
- All Leads
- Confirm Leads
- Visa Leads
- Group Leads
- Cruise Leads
- Other Leads, unless product policy retains a simplified dedicated intake shortcut
- Document Complete Leads
- Archive Leads
- Recent Arrivals
- Upcoming Departures
- Internal Notes

## 4. Global application shell

The visual direction follows the approved SmartHR-inspired concept: compact left navigation, global search in the top bar, restrained cards, clear hierarchy, and a warm orange primary accent.

### 4.1 Persistent left navigation

The maximum top-level destinations are:

1. My Work
2. Inbox
3. Sales
4. Operations
5. Call Centre
6. Finance
7. Customers
8. Insights
9. People
10. Administration

Only destinations the user may access appear. The order never changes between roles.

### 4.2 Top bar

The top bar contains:

- Sidebar collapse/expand
- Global search
- Quick-create menu
- Notifications
- Help/documentation
- User menu
- Optional current-role/team context for users with multiple approved work contexts

### 4.3 Quick-create menu

Show only permitted actions:

- New inquiry
- New customer
- New quote
- New invoice
- Record customer payment
- New vendor bill
- Record supplier payment
- New tour
- New leave request
- New team member

Creation initiated from a workspace should inherit context. For example, creating a quote from a lead preselects the lead and customer.

### 4.4 Breadcrumbs

Use breadcrumbs only on deep record/configuration pages:

```text
Sales / Pipeline / LD-2026-0187
Finance / Receivables / INV-2026-0041
Administration / Access / Permission Groups
```

Do not repeat the sidebar hierarchy mechanically on landing pages.

## 5. Global navigation hierarchy

```text
My Work
├── Today
├── Overdue
├── Assigned to Me
├── Waiting
└── Completed

Inbox
├── Unassigned
├── My Conversations
├── Team Conversations
└── Folders

Sales
├── Pipeline
├── All Leads
├── Quotes
├── Follow-ups
└── Closed Leads

Operations
├── Handover Queue
├── Operations Board
├── Departing Soon
├── Documents
├── Service Exceptions
└── Tours

Call Centre
├── Departure Queue
├── Arrival Queue
├── My Calls
└── Escalations

Finance
├── Finance Control
├── Receivables
├── Customer Payments
├── Payables
├── Supplier Payments
├── Suppliers
├── Tour Finance
└── Payment Register

Customers
├── All Customers
└── Customer Segments

Insights
├── Executive Overview
├── Sales
├── Operations
├── Finance
├── Marketing Sources
└── Team Performance

People
├── My Leave
├── Leave Management
├── Team Members
└── Office Calendar

Administration
├── Users & Access
├── Workflow Configuration
├── Master Data
├── Integrations
├── Notifications
├── Audit Log
└── System Health
```

## 6. My Work

### Purpose

My Work is the default landing page for every employee. It is an action queue, not a general analytics dashboard.

### Page structure

1. Greeting and date context
2. Critical alert strip when blocking work exists
3. Compact counts: overdue, due today, recently assigned, waiting
4. Prioritized work list
5. Upcoming work
6. Role-specific small summary

### Tabs

- Today
- Overdue
- Assigned to Me
- Waiting
- Completed

### Work-list columns

- Priority/health
- Task
- Related customer or booking
- Workflow stage
- Due time
- Waiting/dependency indicator
- Primary action

### Prioritization

Order by:

1. Travel-critical blocked work
2. Overdue mandatory tasks
3. Customer waiting
4. Due today
5. Newly assigned
6. Upcoming work

Users may filter but cannot permanently hide critical work.

## 7. Inbox

### Purpose

Provide one communication workspace for intake, ownership, conversation, and lead creation without losing context.

### Desktop structure

```text
Conversation list | Active conversation | Customer/lead context
```

### Conversation list

- Contact/customer
- Last message preview
- Last activity
- Unread count
- Assignment
- Campaign/ad attribution
- Linked lead indicator
- Folder

### Active conversation

- Message history
- Attachments/media
- Reply composer
- Delivery state
- Session-window warning
- Template message option when implemented

### Context panel

- Customer match
- Linked lead/booking
- Source/ad attribution
- Current owner and stage
- Qualification summary
- Next task
- Actions: create lead, link lead, assign, open workspace

### Saved views

- Unassigned
- Unread
- My Conversations
- Team Conversations
- No Linked Lead
- Waiting on Customer
- Personal folders

The current separate WhatsApp Inbox and My WhatsApp Chats become views of this destination.

## 8. Sales

### 8.1 Sales landing page

The default route is the Pipeline, filtered for the current user unless they are a manager with team scope.

### 8.2 Pipeline presentation

Support two presentations with shared filters:

- Board by lifecycle stage
- Table for dense operational work

### Board columns

- New / Assigned
- Qualification
- Ready for Pricing
- Pricing
- Quote Sent
- Negotiation
- Confirmed / Handover Due

Closed and operational stages are not normal board columns.

### Lead card

- Customer
- Destination/product
- Travel dates
- Quote value when available
- Sales owner
- Health
- Next action and due time
- Unread customer message indicator
- Product-type indicator only when relevant

### Saved views

- Unassigned Intake
- My Active Leads
- First Contact Due
- Qualification Incomplete
- Ready for Pricing
- Quotes Awaiting Customer
- Follow-ups Due
- Amendments
- Confirmed Awaiting Handover
- Handover Returned
- Stale Leads
- Group Bookings
- Cruise Leads
- Visa-Focused
- Closed Leads

### 8.3 Quotes

Quotes is a commercial document list, not a substitute for the lead pipeline.

Views:

- Draft
- Awaiting Approval
- Ready to Send
- Sent
- Expiring
- Accepted / Converted
- Declined / Expired

## 9. Unified Lead / Booking Workspace

### 9.1 Stable URL

```text
/work/leads/{reference}
```

The URL remains stable after confirmation. An optional human-readable canonical alias may use `/work/bookings/{reference}` but must redirect to one canonical form.

### 9.2 Sticky header

- Reference
- Customer
- Lead/Booking label
- Lifecycle stage
- Health
- Sales owner
- Operations owner
- Travel dates
- Next action
- Contextual primary action
- More-actions menu

### 9.3 Primary tab order

1. Overview
2. Conversation
3. Requirements
4. Quotes
5. Operations
6. Finance
7. Tasks
8. Files
9. Timeline

### 9.4 Tab visibility

- Tabs remain in a consistent order.
- A user without access either does not see the tab or sees a safe summary when cross-functional context is required.
- Do not reorder tabs per role.
- The initial tab can vary by entry context: a notification may deep-link directly to Operations, Finance, or Tasks.

### 9.5 Overview

Contains:

- Next-best-action panel
- Customer and contact summary
- Travel summary
- Commercial summary
- Operations readiness summary
- Payment summary
- Active exceptions
- Owners/collaborators
- Recent activity

### 9.6 Conversation

- Embedded linked conversation
- Contact history and attempts
- Internal/customer communication distinction
- Compose action according to channel permission

### 9.7 Requirements

- Qualification checklist
- Passenger/traveller data
- Travel dates and itinerary requirements
- Service requirements
- Visa/document implications
- Special requests
- Completion state and missing-field guidance

### 9.8 Quotes

- Current quote summary
- Version history
- Draft editor entry point
- Approval state
- Send history
- Acceptance evidence
- PDF actions

### 9.9 Operations

- Handover status and checklist
- Service-item board
- Documents/readiness
- Supplier references
- Operational exceptions
- Ready-to-travel review

### 9.10 Finance

- Customer invoice and receipt summary
- Outstanding customer balance
- Supplier commitments and payments
- Gross margin and cash gap according to permission
- Finance holds/exceptions
- Links to full finance documents

### 9.11 Tasks

- Open tasks first
- Completed tasks collapsed below
- Owner, due date, dependency, priority, outcome
- Create task, complete, reassign, reschedule actions according to permission

### 9.12 Files

- Group by Customer, Quote, Finance, Operations, and Other
- Show document type, verification state, owner, uploaded date
- Avoid exposing sensitive files to roles without permission

### 9.13 Timeline

- Chronological immutable audit history
- Filter by communication, sales, operations, finance, task, system
- Expand structured before/after values
- Deep-link related records

## 10. Customers

### Customer Workspace URL

```text
/customers/{customer}
```

### Workspace sections

- Overview
- Contact details
- Leads and bookings
- Conversations
- Financial summary, permission-controlled
- Documents, permission-controlled
- Notes
- Timeline

### Customer-list saved views

- All Customers
- Active Travellers
- Repeat Customers
- Customers with Active Leads
- Customers with Upcoming Travel
- Customers with Outstanding Balance, Accounts only
- Duplicate Candidates

Customer creation should occur naturally from intake/lead context. A separate customer form remains available for authorized data-management work.

## 11. Operations

### 11.1 Handover Queue

Views:

- Awaiting Assignment
- Awaiting Review
- Returned to Sales
- Accepted Today
- Overdue Review

Each row/card shows handover completeness, customer, dates, sales owner, proposed operations owner, risks, and review deadline.

### 11.2 Operations Board

Board groups work by operational state, not lead lifecycle:

- Accepted / Planning
- Booking Services
- Awaiting Customer
- Awaiting Supplier
- Exceptions
- Readiness Review
- Ready to Travel

### 11.3 Departing Soon

Calendar/table views with:

- Departure countdown
- Service completeness
- Document readiness
- Finance clearance
- Pre-departure call state
- Blocking exceptions

### 11.4 Documents

Saved views:

- Required
- Requested
- Received / Awaiting Verification
- Replacement Required
- Overdue
- Complete
- Departing Soon with Missing Documents

### 11.5 Service Exceptions

Central exception queue with owner, booking, affected service, severity, customer impact, due time, and resolution state.

## 12. Call Centre

### 12.1 Departure Queue

- Eligible Today
- Due Soon
- Assigned
- Retry Required
- Issue Raised
- Completed

### 12.2 Arrival Queue

- Eligible Today
- Assigned
- Retry Required
- Complaint / Follow-up
- Completed

### 12.3 Call Workspace

- Customer and booking summary
- Contact actions
- Required script/checklist
- Attempt history
- Structured outcome
- Notes
- Escalation action
- Linked issue resolution state

Call Centre receives only the customer, travel, service, and exception context necessary for the call.

## 13. Finance

### 13.1 Finance Control

Action-oriented landing page:

- Invoices to issue
- Unmatched receipts
- Customer balances due/overdue
- Vendor bills awaiting review
- Supplier payments due
- Negative/low-margin bookings
- Cash-gap warnings
- Reconciliation exceptions

### 13.2 Receivables

Views:

- Draft Invoices
- Issued / Unpaid
- Partially Paid
- Due Soon
- Overdue
- Paid
- Payment Exceptions

### 13.3 Payables

Views:

- Bills Awaiting Review
- Approved / Unpaid
- Due Soon
- Overdue
- Partially Paid
- Paid
- Disputed

### 13.4 Finance document workspaces

Use canonical URLs:

```text
/finance/quotes/{quote}
/finance/invoices/{invoice}
/finance/customer-payments/{payment}
/finance/vendor-bills/{bill}
/finance/supplier-payments/{payment}
/finance/suppliers/{supplier}
```

Each finance record includes its business document, payment/activity timeline, PDF actions, and link back to the lead/booking.

### 13.5 Tour Finance

Tour Finance groups:

- Tour-level receivables
- Supplier commitments
- Paid and outstanding balances
- Revenue, cost, profit, margin
- Cash gap by date
- Linked group bookings and passenger count

## 14. Insights

Insights is separated from operational work. Reports never replace queues.

### Executive Overview

- Funnel and conversion
- Revenue and margin
- Receivables/payables exposure
- Booking readiness risk
- Team workload and SLA trend

### Sales

- Source funnel
- Stage conversion
- Stage ageing
- Quote conversion
- Lost reasons
- Revenue and margin by owner/product/source

### Operations

- Handover acceptance
- Service completion
- Readiness
- Exceptions
- Supplier turnaround
- Departure risk

### Finance

- Revenue
- Collections
- Receivables ageing
- Payables ageing
- Margin
- Cash gap

### Marketing Sources

- Inquiry volume
- Attribution completeness
- Qualified and confirmed conversion
- Revenue/margin by source/campaign

### Team Performance

- Workload
- SLA adherence
- Conversion or completion metrics appropriate to role
- Rework and exception rates

Access to individual performance is restricted to authorized managers, HR, and Admin according to policy.

## 15. People

### 15.1 My Leave

- Balance summary
- New request
- My requests
- Upcoming approved leave
- Office calendar

### 15.2 Leave Management

- Awaiting Review
- Coverage Conflict
- Approved
- Rejected
- Upcoming Leave

### 15.3 Team Members

- Role/team directory
- Workload summary for managers
- Leave/availability context
- Profile and access summary according to permission

### 15.4 Office Calendar

- Approved leave
- Holidays
- Office closures
- Coverage indicators

Sensitive leave details must not appear in general team views.

## 16. Administration

### 16.1 Users & Access

- Users
- Roles
- Permission Groups
- Individual Exceptions
- Access Requests
- Access Audit

### 16.2 Workflow Configuration

- Lifecycle stages
- Transition rules
- Required-field gates
- SLA policies
- Closure reasons
- Waiting reasons
- Exception types
- Approval thresholds

Configuration should be versioned and restricted. Initial implementation may keep policy in code while presenting read-only documentation.

### 16.3 Master Data

- Product/service types
- Lead sources
- Suppliers
- Tours
- Payment modes/accounts
- Document types
- Destinations/countries where centrally managed

### 16.4 Integrations

- WhatsApp status
- Webhook health
- Queue health
- Storage/media health
- Future email/payment integrations

Never display secrets after storage.

### 16.5 Notifications

- Templates
- Routing policies
- Digest rules
- Delivery health

### 16.6 Audit Log

Global privileged search across business audit events, access changes, overrides, merges, archives, and reversals.

### 16.7 System Health

- Failed jobs
- Queue backlog
- Webhook failures
- Integration errors
- Storage issues
- Scheduled-task status

## 17. Role-specific navigation

The following table defines default visible destinations. Individual permissions may narrow or extend access.

| Destination | Marketing | Sales | Operations | Accounts | Call Centre | HR | Admin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| My Work | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Inbox | ✓ | ✓ | Context | — | Context | — | ✓ |
| Sales | Intake | ✓ | Context | Context | — | — | ✓ |
| Operations | — | Context | ✓ | Context | Context | — | ✓ |
| Call Centre | — | Context | Context | — | ✓ | — | ✓ |
| Finance | — | Context | Context | ✓ | — | — | ✓ |
| Customers | Limited | Assigned | Assigned | Finance context | Call context | — | ✓ |
| Insights | Sources | Personal/team | Operational | Financial | Call metrics | People | ✓ |
| People | Self | Self | Self | Self | Self | ✓ | ✓ |
| Administration | — | — | — | — | — | Limited | ✓ |

**Context** means a safe embedded summary or deep-link related to an assigned record, not unrestricted browsing.

## 18. Manager navigation overlay

Managers retain their base role navigation and receive:

- My Work → Team Work
- Team workload filters
- Unassigned team queue
- Team overdue queue
- Handoff/exception approvals delegated to the role
- Team performance within authorization

Manager capability does not reveal Administration, HR-sensitive data, or unrestricted Finance.

## 19. Search architecture

### 19.1 Global search targets

- Lead/booking reference
- Legacy lead ID
- Customer name
- Phone/WhatsApp number
- Email
- Destination
- Tour code/name
- Quote number
- Invoice number
- Receipt number
- Vendor bill number
- Supplier

### 19.2 Result groups

Group results by:

- Leads / Bookings
- Customers
- Conversations
- Tours
- Finance Documents
- Suppliers
- People, if authorized

### 19.3 Result behavior

- Show only authorized results.
- Include object type, reference, key context, and current state.
- Prefer exact reference/contact matches.
- Recent records may rank higher after exact-match rules.
- Search does not expose restricted values through snippets.
- Keyboard shortcut follows the approved top-bar pattern.

## 20. Filters, saved views, and personal preferences

### Global filter language

Reuse consistent fields:

- Owner
- Team
- Stage/state
- Health
- Priority
- Source
- Product type
- Destination
- Travel date
- Created date
- Updated date
- Due date
- Waiting reason
- Closure reason

### Saved views

- System views are maintained by the product and cannot be deleted.
- Team views may be published by authorized managers/admins.
- Personal views belong to the current user.
- Saved views store filters, columns, sort, and presentation mode.
- Saved views never bypass authorization.

### URL behavior

Filters important for collaboration must be represented in the URL. Personal UI preferences may remain session/account settings.

## 21. Badge and count policy

Navigation badges are reserved for actionable counts:

- Unread/unassigned conversations
- Overdue My Work tasks
- Handover reviews awaiting the user/team
- Call tasks due
- Finance exceptions requiring action
- Leave approvals awaiting HR/manager

Do not badge total records, completed work, or informational analytics.

Counts must use cached/optimized queries and should not make every navigation render expensive.

## 22. Empty, loading, and error states

### Empty queue

- Confirm the user is up to date.
- Offer the most relevant next action.
- Do not show a generic “No records found” when a business explanation is possible.

### Empty first-use state

- Explain the purpose in one sentence.
- Provide one primary creation or setup action.

### Permission-denied state

- Do not reveal record details.
- Explain that access is unavailable.
- Offer a safe route back; access-request workflow may be added later.

### Integration/queue failure

- Preserve user input.
- Show whether the action is queued, failed, or safe to retry.
- Provide a support/reference ID for operational investigation.

## 23. Responsive behavior

### Desktop

- Persistent/collapsible sidebar
- Three-column Inbox when space allows
- Lead workspace may use a compact right context/action rail
- Dense tables are permitted with configurable columns

### Tablet

- Collapsed sidebar
- Two-column layouts become stacked or master-detail
- Context rail becomes drawer

### Mobile

- Mobile navigation drawer
- My Work, Inbox, task completion, notes, calls, and essential record summaries are prioritized
- Dense finance configuration and complex quote editing may be desktop-preferred
- Sticky contextual primary action is allowed when it does not obscure content

## 24. URL and route conventions

Proposed route families:

```text
/work
/work/overdue
/inbox
/inbox/conversations/{conversation}
/sales/pipeline
/sales/leads
/work/leads/{lead}
/customers/{customer}
/operations/handovers
/operations/board
/call-centre/departures
/call-centre/arrivals
/call-centre/calls/{call}
/finance/control
/finance/invoices/{invoice}
/finance/vendor-bills/{bill}
/finance/tours/{tour}
/insights/{area}
/people/leave
/people/team
/admin/{area}
```

Rules:

- Use lowercase plural nouns and kebab-case.
- Prefer stable identifiers over database IDs in user-facing URLs where practical.
- Tabs use URL segments or query values so deep links survive refresh.
- Record actions do not create unique routes unless they are multi-step workflows.
- Legacy Filament URLs should redirect during migration where record authorization permits.

## 25. Page templates

### Queue page

- Title and concise purpose
- Saved-view tabs
- Search/filter toolbar
- Table/board/calendar presentation
- Bulk actions only where safe
- Row/card primary action

### Record workspace

- Sticky record header
- Status/health/ownership
- Stable tabs
- Contextual primary action
- Related tasks and exceptions visible early
- Audit timeline

### Dashboard / insight page

- Date/team filters
- Small number of decision-oriented KPIs
- Trends and breakdowns
- Drill-through to underlying authorized records

### Configuration page

- Clear scope and risk
- Version/change history
- Confirmation for high-impact changes
- Preview of affected workflow where possible

## 26. Current-to-target navigation migration

| Current destination/resource | Target location |
|---|---|
| Home | My Work |
| Lead | Sales → All Leads / canonical workspace |
| My Sales | Sales → Pipeline saved view: My Active Leads |
| Other Leads | Sales → saved view/product-type shortcut |
| Group Lead | Sales → saved view: Group Bookings |
| Cruise Lead | Sales → saved view: Cruise Leads |
| Confirm Lead | Sales → saved view: Confirmed Awaiting Handover |
| Visa Leads | Operations → Documents saved view: Visa-Focused |
| Internal Notes | My Work mentions/notes filter and record Timeline |
| All Lead Dashboard | Sales → All Leads |
| My Operation Lead | Operations → Board filtered to current owner |
| My Leads for Call Centre | Call Centre → My Calls |
| Archive Leads | Sales → Closed Leads with Archived filter |
| WhatsApp Inbox | Inbox → Unassigned |
| My WhatsApp Chats | Inbox → My Conversations |
| Upcoming Departures | Call Centre → Departure Queue |
| Recent Arrivals | Call Centre → Arrival Queue |
| My Assigned Calls | Call Centre → My Calls |
| Tour Master | Operations → Tours; Finance context retained |
| Quotes | Sales → Quotes; Finance access through permissions |
| Invoices | Finance → Receivables |
| Vendor Bills | Finance → Payables |
| Supplier Payables | Finance → Payables saved view/summary |
| Supplier Payments | Finance → Supplier Payments |
| Payment Register | Finance → Payment Register |
| Tour Finance Control | Finance → Tour Finance |
| Customers | Customers → All Customers |
| Analytics | Insights → role-appropriate area |
| Sales Performance | Insights → Team Performance |
| My Leave Requests | People → My Leave |
| Leave Management | People → Leave Management |
| Office Closures | People → Office Calendar / Admin master data |
| User Management | Administration → Users & Access |
| Permissions | Administration → Users & Access → Permissions |
| Team Members | People → Team Members |

## 27. Permission architecture requirements

Navigation uses capabilities such as:

- `work.view`
- `inbox.view_unassigned`
- `inbox.view_team`
- `sales.pipeline.view`
- `sales.leads.view_all`
- `operations.handovers.review`
- `operations.board.view_team`
- `call_centre.queue.view`
- `finance.receivables.manage`
- `finance.payables.manage`
- `insights.sales.view`
- `people.leave.manage`
- `admin.access.manage`

Record-level policies additionally enforce assignment, team scope, lifecycle stage, and sensitive field access.

The architecture must support:

- Base role permission templates
- Manager overlays
- Individual exceptions
- Field/section-level restrictions for sensitive data
- Audit of permission changes
- A permission preview showing the effective user experience

## 28. Analytics event requirements

To make the architecture measurable, capture:

- Destination and saved-view opened
- Search performed, without storing sensitive free-text unnecessarily
- Record opened from queue/search/notification
- Primary workflow action started/completed/failed
- Filter/view usage
- Empty-result and permission-denied frequency
- Time from queue entry to action

Product telemetry must not replace the business audit log.

## 29. Accessibility requirements

- Full keyboard navigation for global search, sidebar, tabs, tables, dialogs, and actions
- Visible focus indicators
- Text labels or accessible names for every icon action
- Status conveyed with text, not color alone
- Sufficient color contrast in light and dark modes
- Table headers and responsive alternatives
- Error summary plus field-level errors
- Notifications announced appropriately without stealing focus
- Reduced-motion support

## 30. Acceptance criteria

The information architecture is ready for detailed UI design when:

1. Every current feature has a target destination or intentional retirement path.
2. Each canonical object has one record workspace and stable URL.
3. Role-specific navigation is defined without duplicating business records.
4. My Work exposes actionable personal responsibility for every role.
5. Sales and Operations use saved views of the same lead/booking records.
6. WhatsApp intake and linked lead context can be used without page-hopping.
7. Finance documents link back to the canonical booking workspace.
8. Search targets, result permissions, and route conventions are defined.
9. Manager visibility extends team scope without granting unrelated authority.
10. Mobile prioritization is defined for field-critical work.
11. Navigation badges represent actionable work only.
12. Legacy URLs/resources have a migration mapping.

## 31. Recommended wireframe sequence

Create detailed wireframes in this order:

1. Global shell and role-aware sidebar
2. My Work dashboard
3. Unified Lead / Booking Workspace
4. Inbox with lead context
5. Sales Pipeline board and table
6. Operations Handover Queue
7. Operations Board and Departing Soon
8. Finance Control and Receivables
9. Call Centre queue and Call Workspace
10. Customer Workspace
11. People / Leave
12. Administration / Users & Access

The first six screens validate most navigation, ownership, handoff, and cross-functional context decisions before implementation begins.
