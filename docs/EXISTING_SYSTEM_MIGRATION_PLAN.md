# TravelSync Existing-System Migration Plan

**Status:** Technical delivery baseline  
**Version:** 1.0  
**Date:** 25 August 2026  
**Related:** [Target Data Model](TARGET_DATA_MODEL_SPECIFICATION.md) · [Workflow Engine](WORKFLOW_ENGINE_SPECIFICATION.md) · [Information Architecture](INFORMATION_ARCHITECTURE_SPECIFICATION.md) · [Implementation Backlog](IMPLEMENTATION_BACKLOG.md)

## 1. Purpose

This plan describes how to migrate the current TravelSync application to the approved target data model, workflow engine, and information architecture without interrupting daily sales, operations, WhatsApp, finance, call-centre, or HR work.

The plan uses an **expand → backfill → verify → dual-run → cut over → contract** strategy. Destructive schema changes are deliberately postponed until the new system has operated successfully through an agreed rollback window.

## 2. Current-system summary

### 2.1 Current sources of truth

| Domain | Current source |
|---|---|
| Lead lifecycle | `leads.status` using `LeadStatus` |
| Sales ownership | `leads.assigned_to` |
| Operations ownership | `leads.assigned_operator` |
| Lead type | `is_group_lead`, `is_cruise_lead`, `is_other_lead` |
| Operational services | Four fixed lead columns |
| Quotes | One mutable `quotes` row per lead plus line items |
| Customer finance | Invoices and customer payments |
| Supplier finance | Vendor bills/payments and supplier payments |
| Lead audit | `lead_action_logs` plus model observers |
| Tasks | Workflow-specific records such as call-centre calls; no generic task model |
| Handoff | Implied by status and Operations assignment |
| Documents/files | Lead attachments plus domain-specific media/PDFs |
| Communications | WhatsApp contacts, conversations, messages, statuses |

### 2.2 Current workflow behavior locations

- Filament Resources and Pages
- `LeadObserver`
- `SendsLeadNotifications`
- Finance observers
- Service classes
- Eloquent query scopes
- Notification helpers
- Console backfill/migration commands
- Database constraints such as one quote per lead

### 2.3 Primary migration risks

1. Current statuses combine lifecycle, ownership, operations, and document state.
2. Multiple Filament resources write the same lead differently.
3. Observer side effects may double-send notifications during dual writes.
4. Quote versioning conflicts with the current unique `lead_id` constraint.
5. Existing status-to-stage mapping is ambiguous for some records.
6. Service history cannot be reconstructed fully from current snapshot columns.
7. Legacy action logs are incomplete as an event stream and should not be presented as fabricated full history.
8. Existing migration commands were designed as one-off utilities rather than resumable production backfills.
9. Queue connections currently default to `after_commit = false`; new workflow effects require explicit after-commit/outbox behavior.
10. Finance totals and PDFs must remain identical through quote migration.

## 3. Migration principles

1. **Add before replacing.** New tables and nullable columns are deployed first.
2. **Never infer more history than exists.** Record a migration event and current best state.
3. **Backfills are repeatable.** Every command supports dry-run, chunking, checkpointing, and idempotent upsert.
4. **Verification is separate from mutation.** Backfill commands do not declare themselves correct.
5. **No side effects during backfill.** No customer messages, notifications, or operational tasks unless explicitly creating current work.
6. **Financial reconciliation has zero tolerance.** Quote/invoice/payment totals must match before cutover.
7. **Canonical writes change only after shadow comparison.** New fields are not trusted merely because they exist.
8. **Rollback is application-first.** Old readers remain available throughout the rollout window.
9. **Legacy fields are not dropped during initial launch.** Contract migrations occur in a later release.
10. **Every cutover is observable.** Feature flags, metrics, logs, and reconciliation reports provide evidence.

## 4. Environments and rehearsal

### Required environments

- Local development
- Automated test/CI database
- Staging with production-like database engine and queue
- Production

### Rehearsal requirements

Before production:

1. Restore a recent sanitized production snapshot into staging.
2. Measure database size, row counts, index-build time, and backfill duration.
3. Run every migration and command in intended order.
4. Simulate interruption and resume at least twice.
5. Run reconciliation and manual spot checks.
6. Test application rollback while expanded schema remains.
7. Rehearse UI feature-flag cutover by role.
8. Verify WhatsApp intake, queue processing, PDFs, and finance flows.

Do not extrapolate production duration only from seed/factory data.

## 5. Feature flags

Use server-side flags, not frontend-only hiding.

| Flag | Default | Purpose |
|---|---:|---|
| `workflow.schema_ready` | false | New schema exists and is compatible |
| `workflow.shadow_events` | false | Build proposed actions/events without canonical mutation |
| `workflow.dual_write` | false | Workflow service writes canonical and legacy fields |
| `workflow.shadow_compare` | false | Compare legacy and canonical interpretations |
| `workflow.canonical_read` | false | Read canonical lifecycle/owners |
| `workflow.enforce_engine_writes` | false | Reject direct lifecycle/owner writes |
| `workflow.outbox_processing` | false | Process new side effects |
| `ui.new_shell` | false | New navigation shell |
| `ui.lead_workspace` | false | Unified Lead Workspace |
| `ui.sales_pipeline` | false | New Sales Pipeline |
| `ui.operations` | false | New handoff/Operations screens |
| `ui.inbox` | false | Unified Inbox |
| `ui.finance_context` | false | New embedded Finance context |

Flags must support targeting by:

- Environment
- User ID
- Base role
- Team/pilot group
- Percentage only for read-only UI when safe

Workflow write paths must not use random percentage rollout per request; a user/lead must remain on a consistent behavior path.

## 6. Migration support tables

### 6.1 `data_migration_runs`

Track every backfill/reconciliation execution:

| Column | Purpose |
|---|---|
| `id` | Identity |
| `migration_key` | Stable command/backfill name |
| `run_uuid` unique | Run identity |
| `mode` | Dry-run, execute, reconcile |
| `status` | Running, completed, failed, cancelled |
| `started_at`, `completed_at` | Timing |
| `started_by` nullable | User/operator where available |
| `source_min_id`, `source_max_id` | Scope |
| `last_processed_id` | Resume checkpoint |
| `processed_count` | Progress |
| `created_count`, `updated_count`, `skipped_count`, `error_count` | Results |
| `options` json | Chunk size/scope/version |
| `summary` json | Bounded results |
| `last_error` text nullable | Failure |
| timestamps | Standard timestamps |

### 6.2 `data_migration_issues`

Persist record-level issues without flooding console/log output:

| Column | Purpose |
|---|---|
| `id` | Identity |
| `run_id` | Migration run |
| `issue_code` | Stable issue type |
| `severity` | Info, warning, error, critical |
| `source_type`, `source_id` | Record |
| `message` | Human summary |
| `details` json | Bounded evidence |
| `resolution_status` | Open, accepted, corrected, ignored |
| `resolved_by`, `resolved_at`, `resolution_notes` | Review |
| timestamps | Standard timestamps |

### 6.3 Migration context

A `MigrationExecutionContext` suppresses:

- Customer/staff notifications
- External messages
- Normal SLA escalations
- Observer-created duplicate action logs
- Automatic current-work tasks, except commands explicitly designed to create them

It does not suppress:

- Database constraints
- Structured migration events
- Error reporting
- Reconciliation
- Authorization of production command execution

## 7. Deployment units

Each deployment must be independently reversible at the application level.

### Deployment 0 — Baseline and safeguards

**Application changes**

- Add feature-flag service/configuration.
- Add migration-run/issue infrastructure.
- Add structured migration logging/correlation.
- Add direct-write inventory instrumentation.
- Add production health checks for queues and failed jobs.
- Ensure database backup/restore procedure is tested.

**No business behavior changes.**

**Exit criteria**

- Every current lifecycle/owner writer is identified in logs/tests.
- Backup restore rehearsal succeeds.
- Production row-count baseline captured.

### Deployment 1 — Additive workflow foundation schema

Create:

- Canonical lead columns, nullable where necessary
- Tasks and task dependencies
- Workflow events
- Workflow requests/idempotency
- Workflow outbox
- Closure reasons and lead closures
- Exceptions and decisions
- Collaborators
- Optional workflow summary projection

Add indexes using database-appropriate online/nonblocking strategy.

Do not add strict stage-dependent `NOT NULL` constraints yet.

**Application behavior**

- Legacy writes remain authoritative.
- New schema is unused except migration infrastructure.
- Outbox processing disabled.

**Rollback**

- Roll back application freely.
- Prefer leaving additive tables in place if rollback migration could lock large tables.

### Deployment 2 — Canonical lead backfill and shadow comparison

Deploy:

- Legacy-to-canonical mapping service
- Lead backfill command
- Reconciliation command/report
- Shadow compare instrumentation
- Compatibility accessors, read-only

Execute backfill in bounded chunks.

Enable `workflow.shadow_compare` for Admin/test users, then production-wide read comparison without changing visible UI.

**Exit criteria**

- Every non-deleted lead has canonical type, stage, and owner mapping.
- Critical mapping issues resolved or explicitly queued for review.
- No material query/report divergence outside documented mapping differences.

### Deployment 3 — Workflow engine shadow mode

Deploy:

- Workflow engine contracts, registry, gates, handlers
- Event/outbox writers
- Available-action query service
- Direct-write detector

Enable `workflow.shadow_events`:

- Legacy actions continue to execute.
- Adapter evaluates corresponding canonical action and records comparison telemetry.
- No canonical workflow mutation beyond safe comparison records.
- No outbox processing.

Use differences to refine gates and mappings.

### Deployment 4 — Dual-write lifecycle and ownership

Enable workflow engine for a small internal pilot.

For pilot actions:

- UI calls engine.
- Engine writes canonical fields and mapped legacy fields in one transaction.
- Workflow events become canonical for pilot actions.
- Legacy action-log compatibility may continue with correlation deduplication.
- Notifications use outbox only for pilot actions.

Non-pilot legacy screens remain functional.

Expand by role/team after evidence.

**Exit criteria**

- Zero unexplained legacy/canonical divergence.
- No duplicate notifications.
- Idempotency and concurrency metrics healthy.
- Rollback to legacy UI tested while dual writes enabled.

### Deployment 5 — Quote-version schema and backfill

Create:

- Quote series
- Quote versions
- Version items
- Deliveries
- Responses

Modify invoice relation additively with nullable `quote_version_id`; retain `quote_id`.

Backfill all quotes and verify:

- One series per current quote
- Version 1 per quote
- Line-item count and order
- Total parity
- Invoice relation parity
- PDF rendering parity

Enable dual writes for quote drafts only after backfill. Sent quote versions become immutable in the new path.

### Deployment 6 — Handoffs, services, documents

Create:

- Handoffs and checklist items
- Service items
- Document requirements and submissions
- Generic attachment columns/relations

Backfill:

- Current fixed service statuses into service items
- Current attachments into generalized ownership metadata
- Current confirmed/operations records into a conservative handoff state
- Only current actionable document requirements, not invented historical verification events

Enable Operations pilot after reconciliation.

### Deployment 7 — Generic tasks and My Work

Backfill only current required tasks based on canonical stage/state.

Do not fabricate historical completed tasks.

Enable:

- My Work for pilot users
- Engine task automation
- SLA reminders
- Waiting-state handling
- Task projections

Keep current call-centre call records and link generated tasks rather than replacing their business history.

### Deployment 8 — New UI pilot

Release approved wireframes in this order:

1. New shell and My Work
2. Lead/Booking Workspace read-only sections
3. Sales action forms and Pipeline
4. Inbox context/linking
5. Handover Queue
6. Operations Board
7. Finance context
8. Call Centre task integration

Pilot order:

1. Admin/test accounts
2. One Sales manager and small Sales team
3. One Operations manager/team
4. Accounts
5. Call Centre
6. Remaining users

### Deployment 9 — Canonical read and write cutover

Enable:

- `workflow.canonical_read`
- `workflow.enforce_engine_writes`
- New role navigation
- Canonical reports/projections

Legacy fields continue receiving mapped dual writes during rollback window.

Legacy resources become:

- Hidden from navigation
- Read-only where still needed
- Redirected to canonical workspace for normal records
- Available only to migration support/Admin if necessary

### Deployment 10 — Compatibility retirement

Only after the rollback window and explicit approval:

- Stop dual writes.
- Stop legacy action-log writes.
- Remove observer workflow orchestration.
- Remove legacy query scopes and duplicate resources.
- Drop obsolete unique constraints, fields, and tables in small contract migrations.
- Retain historical data according to policy.

Do not combine cutover and destructive retirement in one deployment.

## 8. Proposed migration files

Use separate, focused migrations. Exact timestamps are assigned when implementation begins.

```text
create_data_migration_runs_and_issues
add_canonical_workflow_columns_to_leads
create_tasks_and_task_dependencies
create_workflow_events
create_workflow_requests
create_workflow_outbox
create_closure_reasons_and_lead_closures
create_lead_exceptions_and_decisions
create_lead_collaborators
create_lead_workflow_summaries

create_quote_series
create_quote_versions
create_quote_version_items
create_quote_deliveries_and_responses
add_quote_version_id_to_invoices

create_lead_handoffs_and_items
create_service_items
create_document_requirements_and_submissions
generalize_attachments

add_enforced_workflow_constraints
remove_legacy_quote_unique_lead_constraint

retire_legacy_lead_columns                 # later release only
retire_legacy_quote_tables_or_columns      # later release only
retire_lead_action_logs                    # policy decision required
```

### Migration rules

- Schema migrations do not perform large data backfills.
- Avoid `Schema::hasColumn` branching as a substitute for controlled versioning in new migrations.
- Large indexes use online/concurrent features supported by the production engine.
- Foreign keys are added after dirty-data audit if existing data could violate them.
- Destructive down migrations are not relied upon as production rollback for data-bearing changes.

## 9. Backfill command suite

### 9.1 Common options

Every command supports:

```text
--dry-run
--chunk=500
--after-id=
--until-id=
--run-id=
--resume
--limit=
--fail-on=critical
--output=
```

Commands:

```text
workflow:audit-legacy
workflow:backfill-leads
workflow:backfill-quotes
workflow:backfill-services
workflow:backfill-documents
workflow:backfill-handoffs
workflow:backfill-current-tasks
workflow:backfill-events
workflow:reconcile
workflow:cutover-check
workflow:projection-rebuild
```

### 9.2 `workflow:audit-legacy`

Read-only audit for:

- Null/duplicate lead references
- Unknown statuses, roles, service states, platforms
- Contradictory lead-type flags
- Confirmed group leads without tours
- Assigned users whose roles do not match ownership
- Quotes missing leads/line items/totals
- Multiple quote anomalies despite current uniqueness expectations
- Invoices with missing lead/quote relations
- Invoice/payment total mismatch
- Vendor bill/payment mismatch
- Attachments missing files or ownership
- Conversations pointing to missing leads
- Call records missing leads/users
- Archived records with active status/tasks

Run before schema enforcement and after every backfill phase.

### 9.3 `workflow:backfill-leads`

For each lead:

1. Lock/check row for concurrent changes using updated timestamp/version strategy.
2. Map lead type.
3. Map Sales and Operations owners.
4. Infer canonical lifecycle stage using evidence priority.
5. Populate stage entry/activity timestamps conservatively.
6. Link current closure where the legacy state is closed and evidence supports it.
7. Write one `migration.lead_backfilled` workflow event.
8. Record ambiguity as migration issue and optional review exception.

Use `updateOrCreate`/deterministic keys for related records; reruns must not duplicate closures/events.

### 9.4 Lifecycle evidence priority

Use strongest evidence first:

1. Explicit valid closure/archive data
2. Accepted/converted quote, invoices, confirmation state
3. Operations assignment, service states, documents
4. Travel dates and call history
5. Legacy status
6. Safe fallback with migration review issue

Example decisions:

- `sent_to_customer` with no quote delivery evidence maps to Quote sent but raises `missing_quote_delivery_evidence`.
- `confirmed` without a quote maps Confirmed with `missing_acceptance_evidence` review issue; do not invent acceptance.
- `document_upload_complete` with future travel maps Ready to travel only if service evidence supports it; otherwise In fulfilment with issue.
- `operation_complete` with past return date may map Travel completed; otherwise Ready to travel/In fulfilment based on evidence.
- Archived lead retains best lifecycle and archive state; archive does not force Closed.

### 9.5 `workflow:backfill-quotes`

For each current quote:

- Create deterministic series linked to legacy quote ID.
- Preserve quote number.
- Create version 1.
- Copy line items in order.
- Recalculate totals using target decimal rules.
- Store legacy/calculated discrepancy.
- Map status conservatively.
- Link invoices via new nullable version relation.
- Attach or reference existing PDF when available.
- Emit one migration event.

Never change current invoice totals to match recalculated quote totals automatically.

### 9.6 `workflow:backfill-services`

Create at most one initial service item for each applicable fixed field:

| Legacy field | Service type |
|---|---|
| `air_ticket_status` | Air ticket |
| `hotel_status` | Hotel |
| `visa_status` | Visa |
| `land_package_status` | Land package |

Map status values and preserve original in metadata. Required state is derived from recorded requirement and legacy behavior; unknown applicability becomes a migration issue rather than silently `not_required`.

### 9.7 `workflow:backfill-handoffs`

- Leads before confirmation: no handoff.
- Confirmed without Operations assignment: create Draft handoff only when UI/current workflow needs it.
- Assigned to Operations: create conservative Accepted legacy handoff snapshot marked as migrated.
- Never claim checklist verification that cannot be evidenced.
- Create incomplete/migrated checklist items with safe status and review issues.

### 9.8 `workflow:backfill-current-tasks`

Create current work only:

- Unassigned lead → assignment task
- Assigned/Qualification → contact/qualification task
- Ready/Pricing → pricing task
- Quote sent/Negotiation → follow-up/amendment task
- Confirmed → handoff task
- Handover submitted → review task
- In fulfilment → relevant service/readiness tasks
- Ready to travel → call scheduling where eligible
- Travel completed → post-arrival/reconciliation task

Use deterministic `automation_key`. Do not send notifications during initial creation.

### 9.9 `workflow:backfill-events`

Options:

- Preserve current `lead_action_logs` as legacy timeline source, or
- Convert each row into `legacy.*` workflow event with `event_version = 1` and original timestamps.

Rules:

- Do not reinterpret vague descriptions as precise modern events.
- Mark source `migration` and include legacy action-log ID.
- Write a separate current-state backfill event.
- Ensure deterministic event UUID from legacy table/ID.

## 10. Compatibility layer

### 10.1 Field mapping

| Canonical | Legacy mirror |
|---|---|
| `lifecycle_stage` | `status` mapped through explicit adapter |
| `sales_owner_id` | `assigned_to` |
| `operations_owner_id` | `assigned_operator` |
| `lead_type` | Three boolean flags |
| Service items | Four status columns for supported types |
| Quote accepted version | Current quote status/relations where representable |

Some canonical states cannot map one-to-one. The adapter chooses the nearest legacy-compatible value and logs mapping version; canonical truth remains precise.

### 10.2 Dual-write direction

During cutover:

- New workflow actions write canonical first, then legacy mirror in the same transaction.
- Legacy screens remain legacy-authoritative only for non-pilot users before enforcement.
- Avoid bidirectional asynchronous synchronization; it creates loops and race conditions.
- Direct legacy writes are detected and immediately mapped into canonical through a compatibility command/service only during the permitted phase.

### 10.3 Direct-write detector

Instrument mutations to:

- `status`
- `assigned_to`
- `assigned_operator`
- lead-type flags
- fixed service statuses
- current quote mutable fields

Log source stack/request route/command with sensitive data removed. Build a dashboard/report of violations.

Enforcement stages:

1. Observe only
2. Warn and metric
3. Reject in tests/staging
4. Reject in production except explicit migration context

## 11. Reconciliation

### 11.1 Required report categories

#### Leads

- Total active/deleted/archived count parity
- Reference uniqueness
- Legacy status vs canonical mapping
- Owner parity
- Lead-type parity
- Group tour integrity
- Customer/conversation/invoice relation parity

#### Quotes

- Series/version count
- Line-item count/order
- Total amount exact parity
- Quote-number parity
- Status mapping
- Invoice relation
- PDF generation comparison

#### Operations/documents

- Fixed service to service-item mapping coverage
- Unknown/contradictory states
- Required service/document readiness differences
- Handoff assignment parity
- Attachment metadata/file existence

#### Tasks/events

- Exactly one current automation task per key
- No open tasks on irrelevantly closed work
- Event UUID uniqueness
- Migration source linkage
- Lead next-action projection parity

#### Finance

- Invoice totals
- Customer payment sums and balances
- Vendor bill sums
- Supplier payment sums and balances
- Tour financial totals
- Profit and margin parity

### 11.2 Acceptance thresholds

| Category | Required threshold |
|---|---|
| Missing lead mappings | 0 |
| Unknown lifecycle values | 0 |
| Duplicate references/version keys | 0 |
| Quote/invoice/payment numeric discrepancy | 0 unresolved |
| Broken required foreign-key relation | 0 |
| Duplicate automated tasks/outbox messages | 0 |
| Critical migration issues | 0 open |
| Warning issues | Explicitly reviewed and signed off |
| Shadow action disagreement | 0 unexplained for pilot actions |
| Duplicate user/customer notification | 0 |

### 11.3 Reconciliation artifacts

Each production run stores:

- Run UUID
- Git commit/release version
- Database snapshot timestamp
- Row counts and hashes where suitable
- Issue list
- Summary JSON/CSV
- Operator and reviewer sign-off

Do not include full sensitive customer/document data in artifacts.

## 12. Data review queue

Ambiguous records need a controlled Admin/data steward queue.

### Review categories

- Lifecycle ambiguous
- Confirmation evidence missing
- Quote total discrepancy
- Owner role mismatch
- Group tour missing
- Service applicability unknown
- Attachment missing/unreadable
- Finance relation mismatch
- Duplicate candidate

### Review action

- Show legacy evidence and proposed mapping.
- Require structured decision and notes.
- Apply correction through migration/workflow service.
- Append corrective event.
- Mark migration issue resolved.

Review UI must not become a general unrestricted data editor.

## 13. UI and route cutover

### 13.1 Legacy-to-target mapping

| Legacy resource | Cutover behavior |
|---|---|
| LeadResource | Redirect/open canonical Lead Workspace |
| MySalesDashboard | Sales Pipeline saved view |
| AllLeadDashboard | All Leads view |
| ConfirmLead | Confirmed Awaiting Handover view |
| Group/Cruise/Visa resources | Saved filters/classifications |
| MyOperationLeadDashboard | Operations Board owner filter |
| ArchiveLead | Closed/Archived filter |
| WhatsApp Inbox/My Chats | Unified Inbox views |
| Arrival/Departure resources | Call Centre queues |
| Internal Notes | Record timeline/mentions |

### 13.2 Redirect requirements

- Preserve record identity.
- Apply authorization before redirecting.
- Redirect directly to relevant tab when context is known.
- Notifications use canonical routes after the recipient is migrated.
- Old bookmarked list URLs redirect to closest saved view.
- PDFs and webhook/media routes remain stable until independently migrated.

### 13.3 Mixed-mode navigation

During pilot:

- Pilot users see new navigation.
- Non-pilot users see existing Filament navigation.
- A lead changed by a pilot remains readable by legacy users because of dual writes.
- Do not allow a user to switch modes mid-action.

## 14. Queue and notification migration

### 14.1 Queue safety

Current queue connections have `after_commit = false`. The new engine uses transactional outbox, so workflow side effects must not rely on dispatch timing inside transactions.

Before enabling outbox:

- Ensure persistent queue worker supervision.
- Separate queues if needed: workflow, notifications, WhatsApp, media, default.
- Define retry/backoff/timeout.
- Monitor failed jobs and queue age.
- Verify rolling deploy workers restart with new code.

### 14.2 Notification deduplication

During dual-run:

- Engine action includes correlation ID.
- Legacy observer/trait checks compatibility context and suppresses equivalent notification.
- Outbox message UUID ensures delivery adapter idempotency.
- Notification record stores semantic topic/correlation where schema permits.

### 14.3 Canonical links

Notification templates resolve URL at delivery time using canonical route service. Remove imports of role-specific Filament Resources from domain/notification code.

## 15. Deployment procedure

### 15.1 Pre-deploy checklist

- Approved release and migration runbook
- Recent verified backup
- Database disk/connection headroom
- Queue workers healthy
- Failed-job backlog understood
- Staging rehearsal evidence
- Feature flags confirmed off/default
- Monitoring dashboards and alerts ready
- Named deploy operator and rollback decision owner
- User communication prepared when UI changes

### 15.2 Schema deployment

1. Put no broad maintenance mode on unless a measured migration requires it.
2. Deploy backward-compatible application first when needed.
3. Run additive migrations.
4. Verify schema/indexes/foreign keys.
5. Run application health/smoke checks.
6. Keep flags off.
7. Run audit/dry-run commands.
8. Execute bounded backfills separately from deploy transaction.

### 15.3 Backfill execution

1. Capture source maximum ID at run start.
2. Run small initial chunk.
3. Inspect performance/locks/issues.
4. Increase chunk only within safe latency limits.
5. Pause on replication lag, lock contention, error threshold, or application degradation.
6. Resume from checkpoint.
7. Process rows created after source max in delta pass.
8. Run reconciliation.

### 15.4 Feature cutover

1. Enable for named internal pilot.
2. Complete scripted smoke workflows.
3. Observe at least the agreed business interval.
4. Expand one role/team at a time.
5. Reconcile after each expansion.
6. Enable canonical reads globally.
7. Enable write enforcement.
8. Hide/redirect legacy resources.

## 16. Smoke-test matrix

### Sales

- Create/claim WhatsApp lead
- Assign/reassign
- Complete qualification
- Draft/send/amend quote
- Confirm booking
- Submit handoff
- Close/reopen pre-confirmation lead

### Operations

- Review/return/resubmit/accept handoff
- Create/update service
- Request/verify/reject document
- Open/resolve exception
- Mark/revoke readiness

### Finance

- Create invoice from accepted quote version
- Record customer payment and verify balance
- Create vendor bill
- Record supplier payment
- Verify tour finance totals/PDFs

### Call Centre

- Create/assign/attempt/complete departure call
- Raise issue and link resolution
- Post-arrival call

### Cross-cutting

- Notifications link to correct canonical record/tab
- Permission-denied paths reveal no data
- Concurrent claim/action conflict
- Idempotent resubmit
- Audit timeline correlation
- Archive/restore
- Search by references/contact/invoice

## 17. Monitoring and stop conditions

### Monitor

- Database CPU, latency, locks, deadlocks
- Queue depth, oldest job, failed jobs
- Outbox backlog and failure rate
- Workflow action success/failure/gate codes
- Legacy/canonical divergence
- Duplicate notifications/tasks/events
- UI errors and permission denials
- WhatsApp receive/send success
- PDF generation
- Finance reconciliation

### Automatic/manual stop conditions

Pause rollout/backfill when:

- Any financial discrepancy appears
- Critical foreign-key or mapping issue appears
- Duplicate external/customer notification occurs
- Queue/outbox exceeds agreed age
- Database latency/locks affect normal work
- Workflow action error rate exceeds agreed threshold
- WhatsApp intake or send degrades
- Canonical/legacy divergence is unexplained
- Permission regression exposes or blocks material data

Pausing a rollout means turn off affected feature flag; it does not run destructive rollback migrations.

## 18. Rollback strategy

### 18.1 Before canonical cutover

- Disable new UI/engine/outbox flags.
- Legacy application continues using legacy fields.
- Leave additive schema and backfilled data in place.
- Correct issue and rerun idempotent backfill.

### 18.2 During dual write

- Disable new action/UI flags.
- Legacy fields are current because they were mirrored transactionally.
- Stop outbox processing only after determining whether pending committed effects must still be delivered.
- Do not delete canonical records.

### 18.3 After canonical read cutover, before legacy retirement

- Re-enable legacy reads/UI.
- Continue or temporarily reverse mapping canonical changes into legacy fields.
- Run reconciliation for actions performed since cutover.
- Restore workflow only after issue corrected.

### 18.4 After destructive retirement

Rollback becomes data restoration/forward recovery and is materially riskier. Therefore:

- Do not retire legacy fields for at least the agreed number of stable releases/business cycles.
- Take explicit pre-contract backup.
- Store compatibility export/snapshot.
- Rehearse restore.
- Prefer forward corrective migration over destructive down migration.

### 18.5 External side effects

Rollback cannot unsend WhatsApp/email, unissue documents, or reverse payments. These require explicit compensating business actions. Track them by correlation ID.

## 19. Destructive contract plan

Contract work is separate and individually approved.

### Preconditions

- New UI/workflow globally stable
- Direct legacy writes zero for agreed period
- All reports use canonical data
- All commands/tests updated
- Legacy URLs redirected
- Finance reconciled
- Data-retention decision approved
- Backup and restore tested

### Suggested order

1. Remove legacy navigation/resources from code.
2. Remove legacy observer workflow side effects.
3. Stop legacy dual writes.
4. Mark old model accessors deprecated and monitor.
5. Remove old quote unique constraint once new versioning is active.
6. Archive or retain `lead_action_logs` based on audit decision.
7. Drop fixed service fields.
8. Drop type flags.
9. Drop legacy status/owner columns last, or retain aliases if reporting integrations still depend on them.

Each step has its own release and observation window where practical.

## 20. Data ownership and sign-off

| Area | Required reviewer |
|---|---|
| Lifecycle/lead mapping | Sales and Operations owners |
| Quote/version totals | Sales and Accounts |
| Invoice/payment/vendor totals | Accounts |
| Handoff/service readiness | Operations |
| Call migration | Call Centre |
| Permissions/roles | Admin and HR/security owner |
| Audit/history | Business owner and technical owner |
| WhatsApp attribution | Marketing/Sales owner |
| Final cutover | Product/business owner + technical owner |

Technical completion alone is not data sign-off.

## 21. User communication and training

### Before pilot

- Explain which team is piloting.
- Demonstrate My Work, unified workspace, and controlled actions.
- Explain that stages cannot be changed arbitrarily.
- Explain handoff acceptance/return.
- Provide issue-reporting channel and reference capture.

### Before global cutover

- Role-specific quick guides
- Legacy-to-new navigation mapping
- Training data/sandbox where possible
- Cutover date/time and expected behavior
- Support coverage
- Known intentional workflow differences

### After cutover

- Daily issue review during stabilization
- Publish resolved issues/changes
- Track adoption and bypass attempts
- Retire legacy guidance promptly

## 22. Migration backlog

### Epic M0 — Discovery and safeguards

- Inventory direct writers and readers
- Production baseline report
- Feature flags
- Migration run tracking
- Backup/restore rehearsal

### Epic M1 — Additive workflow foundation

- Canonical lead columns
- Tasks/events/outbox/idempotency
- Closure and exceptions
- Indexes

### Epic M2 — Lead backfill and reconciliation

- Audit command
- Mapping service
- Lead backfill
- Review queue
- Shadow comparison

### Epic M3 — Engine shadow and dual write

- Action adapters
- Direct-write detector
- Observer suppression
- Pilot engine actions
- Notification deduplication

### Epic M4 — Quote versioning

- Schema
- Backfill
- Total/PDF reconciliation
- Invoice relinking
- Immutable send path

### Epic M5 — Operations model

- Handoffs
- Services
- Documents/attachments
- Backfills and review

### Epic M6 — Task/UI cutover

- Current task backfill
- My Work
- Lead Workspace
- Sales Pipeline
- Inbox
- Operations screens

### Epic M7 — Global cutover

- Role pilots
- Canonical reads
- Write enforcement
- Legacy redirects
- Reporting switch

### Epic M8 — Contract/retirement

- Remove workflow observers
- Remove duplicate resources
- Stop dual writes
- Drop/deprecate legacy schema
- Final archival documentation

## 23. Cutover approval checklist

Do not enable global canonical read/write unless all are true:

- [ ] Production backup verified
- [ ] Staging full rehearsal completed
- [ ] Additive schema deployed and healthy
- [ ] Lead backfill 100% processed
- [ ] Quote version backfill reconciled
- [ ] Service/document/handoff backfill reconciled
- [ ] Current tasks created without duplicates
- [ ] Zero open critical migration issues
- [ ] Finance numeric reconciliation exact
- [ ] Shadow action disagreement zero/unexplained zero
- [ ] Pilot users completed scripted workflows
- [ ] Duplicate notification count zero
- [ ] Queue/outbox monitoring healthy
- [ ] Direct legacy writers known and controlled
- [ ] Permission regression tests passed
- [ ] Rollback drill passed
- [ ] Business reviewers signed off
- [ ] User communication/training delivered
- [ ] On-call/support coverage confirmed

## 24. Definition of done

Migration is complete only when:

1. Canonical workflow fields and related models are authoritative.
2. All lifecycle mutations use the workflow engine.
3. Legacy and canonical finance totals reconcile exactly.
4. Quote versions preserve accepted/sent commercial history.
5. Current work appears correctly in My Work and role queues.
6. Handoff, service, document, exception, and closure history is auditable.
7. Notifications and external side effects are idempotent and outbox-driven.
8. Legacy resources redirect or are intentionally retained read-only.
9. Direct legacy writes remain zero through the observation window.
10. Rollback window closes with business and technical approval.
11. Contract migrations are completed or consciously deferred with owners.
12. Operational runbooks, user guidance, and support ownership are current.
