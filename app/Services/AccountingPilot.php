<?php

namespace App\Services;

use App\Enums\PaymentMode;
use App\Enums\TourStatus;
use App\Models\CustomerPayment;
use App\Models\FinancialAccount;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountingPilot
{
    public const EVENTS = [
        'receipt' => 'Verified customer receipt',
        'billing' => 'Unconditional advance billing',
        'delivery' => 'Recognise delivered tour',
        'accrual' => 'Accrue supplier cost',
    ];

    public const ACCOUNTS = [
        '1140' => 'Trade receivables',
        '2120' => 'Customer advances / contract liabilities',
        '2140' => 'Accrued tour costs',
        '4100' => 'Group tour revenue',
        '5170' => 'Direct tour costs',
    ];

    public static function enabled(): bool
    {
        return (bool) config('accounting.pilot_enabled') && filled(config('accounting.pilot_start_date'));
    }

    public static function today(): string
    {
        return now('Asia/Colombo')->toDateString();
    }

    public function booking(int $invoiceId): ?object
    {
        return DB::table('accounting_bookings')->where('invoice_id', $invoiceId)->first();
    }

    public function enroll(User $actor, int $invoiceId, string $evidence): void
    {
        $this->authorize($actor);
        Validator::validate(['evidence' => $evidence], ['evidence' => 'required|string|max:2000']);

        DB::transaction(function () use ($actor, $invoiceId, $evidence): void {
            $state = $this->lockState();
            $invoice = Invoice::query()->with(['tour', 'lead'])->lockForUpdate()->findOrFail($invoiceId);
            if ($this->booking($invoiceId)) {
                return;
            }

            $tour = $invoice->tour;
            $departure = $tour?->departure_date?->toDateString();
            $return = $tour?->return_date?->toDateString();
            $date = $invoice->invoice_date?->toDateString();
            $this->require($invoice->lead?->is_group_lead && $tour?->currency === 'LKR'
                && $departure && $return && $departure <= $return && substr($departure, 0, 7) === substr($return, 0, 7)
                && $tour->status !== TourStatus::Cancelled,
                'Only LKR group tours departing and returning in the same month can enter this pilot.');
            $this->require($date && $date <= $departure, 'Use a booking request dated on or before departure.');
            $this->validateDate($state, $date);
            $this->require(! $invoice->customerPayments()->exists()
                && in_array($invoice->payment_amount, [null, '0.00'], true),
                'Enroll the booking before recording any payments.');
            $this->require(! DB::table('accounting_bookings')->where('lead_id', $invoice->lead_id)->exists(),
                'This lead already has an enrolled booking request.');

            DB::table('accounting_bookings')->insert([
                'invoice_id' => $invoiceId,
                'lead_id' => $invoice->lead_id,
                'tour_id' => $tour->id,
                'booking_date' => $date,
                'departure_date' => $departure,
                'return_date' => $return,
                'contract_minor' => $this->minor($invoice->total_amount),
                'enrolled_by' => $actor->id,
                'policy_evidence' => trim($evidence),
                'created_at' => now(),
            ]);
        });
    }

    public function submit(User $actor, int $invoiceId, array $input): int
    {
        $this->authorize($actor);
        $input = Validator::validate($input, [
            'event' => ['required', Rule::in(array_keys(self::EVENTS))],
            'posting_date' => 'required_unless:event,receipt|nullable|date_format:Y-m-d',
            'customer_payment_id' => 'required_if:event,receipt|nullable|integer',
            'supplier_id' => 'required_if:event,accrual|nullable|integer|exists:suppliers,id',
            'amount' => ['required_if:event,accrual', 'nullable', 'regex:/^\d{1,13}(\.\d{1,2})?$/D'],
            'evidence' => 'required|string|max:2000',
        ]);

        return DB::transaction(function () use ($actor, $invoiceId, $input): int {
            $state = $this->lockState();
            $booking = $this->booking($invoiceId);
            $this->require($booking !== null, 'Enroll this booking first.');
            $invoice = Invoice::query()->with('tour')->lockForUpdate()->findOrFail($invoiceId);
            $event = $input['event'];
            $date = $input['posting_date'] ?? null;
            $amount = null;
            $payment = null;
            $supplier = $event === 'accrual' ? (int) $input['supplier_id'] : null;
            $sourceKey = $invoiceId.':'.$event;

            if ($event === 'receipt') {
                $payment = $invoice->customerPayments()->lockForUpdate()->findOrFail($input['customer_payment_id']);
                $this->require($payment->recorded_by && $payment->receipt_number, 'Use a numbered receipt recorded through the pilot booking.');
                $date = $payment->payment_date->toDateString();
                $amount = $this->minor($payment->amount);
                $sourceKey = 'receipt:'.$payment->id;
            } elseif ($event === 'accrual') {
                $amount = $this->minor($input['amount']);
                $sourceKey .= ':'.$supplier;
            }

            $existing = DB::table('accounting_journals')->where('source_key', $sourceKey)->first();
            if ($existing) {
                $this->require($date === $existing->posting_date
                    && ($amount === null || $amount === (int) $existing->amount_minor)
                    && trim($input['evidence']) === $existing->evidence,
                    'This source already has a journal. Review the existing journal before submitting changes.');

                return $existing->id;
            }

            $this->validateDate($state, $date);
            $this->require($date >= $booking->booking_date, 'The posting date precedes the booking.');
            $this->require(! $this->pending()->where('invoice_id', $invoiceId)->exists(),
                'Review the pending journal for this booking first.');

            if ($event !== 'accrual') {
                $lastDate = $this->posted()->where('invoice_id', $invoiceId)->where('event', '<>', 'accrual')->max('posting_date');
                $this->require(! $lastDate || $date >= $lastDate, 'Submit customer events in posting date order.');
                $this->require(! $this->unpostedReceipts($invoiceId)->where('customer_payments.payment_date', '<', $date)->exists(),
                    'Post earlier receipts before submitting this event.');
            }

            $snapshot = $payment ? $this->receiptSnapshot($payment, $invoice) : null;
            [$amount, $entries] = $this->posting($booking, $invoice, $event, $date, $amount, $snapshot);
            $this->require(array_sum($entries) === 0 && count($entries) >= 2, 'This journal does not balance.');

            $journalId = DB::table('accounting_journals')->insertGetId([
                'invoice_id' => $invoiceId,
                'event' => $event,
                'source_key' => $sourceKey,
                'customer_payment_id' => $payment?->id,
                'supplier_id' => $supplier,
                'posting_date' => $date,
                'amount_minor' => $amount,
                'source_snapshot' => $snapshot ? json_encode($snapshot, JSON_THROW_ON_ERROR) : null,
                'evidence' => trim($input['evidence']),
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);
            foreach ($entries as $code => $value) {
                DB::table('accounting_journal_lines')->insert([
                    'journal_id' => $journalId,
                    'account_code' => (string) $code,
                    'debit_minor' => max($value, 0),
                    'credit_minor' => max(-$value, 0),
                ]);
            }

            return $journalId;
        });
    }

    public function approve(User $actor, int $invoiceId, int $journalId): void
    {
        $this->authorize($actor, approver: true);

        DB::transaction(function () use ($actor, $invoiceId, $journalId): void {
            $state = $this->lockState();
            $journal = $this->reviewable($actor, $invoiceId, $journalId);
            if ($journal->posted_at) {
                return;
            }

            $this->validateDate($state, $journal->posting_date);
            $booking = $this->booking($invoiceId);
            $invoice = Invoice::query()->with('tour')->lockForUpdate()->findOrFail($invoiceId);
            $snapshot = json_decode($journal->source_snapshot ?? 'null', true, flags: JSON_THROW_ON_ERROR);
            if ($snapshot) {
                $payment = $invoice->customerPayments()->lockForUpdate()->findOrFail($journal->customer_payment_id);
                abort_if((int) $payment->recorded_by === $actor->id, 403);
                $current = $this->receiptSnapshot($payment, $invoice);
                ksort($snapshot);
                ksort($current);
                $this->require($snapshot === $current,
                    'The receipt changed after submission. Reject and resubmit it.');
            }

            if ($journal->event !== 'accrual') {
                $this->require(! $this->unpostedReceipts($invoiceId)->where('customer_payments.payment_date', '<', $journal->posting_date)->exists(),
                    'An earlier receipt must be posted first. Reject this journal and submit it again after that receipt.');
            }

            [$amount, $entries] = $this->posting($booking, $invoice, $journal->event, $journal->posting_date, (int) $journal->amount_minor, $snapshot);
            $preview = DB::table('accounting_journal_lines')->where('journal_id', $journalId)->orderBy('id')->get()
                ->mapWithKeys(fn (object $line) => [$line->account_code => (int) $line->debit_minor - (int) $line->credit_minor])->all();
            $this->require($amount === (int) $journal->amount_minor && $preview === $entries,
                'The journal preview has changed. Reject and resubmit this journal.');

            DB::table('accounting_journals')->where('id', $journalId)->update([
                'reviewed_by' => $actor->id,
                'posted_at' => now(),
            ]);
        });
    }

    public function reject(User $actor, int $invoiceId, int $journalId, string $reason): void
    {
        $this->authorize($actor, approver: true);
        Validator::validate(['reason' => $reason], ['reason' => 'required|string|max:2000']);

        DB::transaction(function () use ($actor, $invoiceId, $journalId, $reason): void {
            $this->lockState();
            $journal = $this->reviewable($actor, $invoiceId, $journalId);
            $this->require(! $journal->posted_at, 'This journal has already posted.');
            DB::table('accounting_journals')->where('id', $journalId)->update([
                'source_key' => null,
                'reviewed_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => trim($reason),
            ]);
        });
    }

    public function close(User $actor, string $through, string $reason): void
    {
        $this->authorize($actor, approver: true);
        Validator::validate(['through' => $through, 'reason' => $reason], [
            'through' => 'required|date_format:Y-m-d|before:'.self::today(),
            'reason' => 'required|string|max:2000',
        ]);
        $this->require($through === (new \DateTimeImmutable($through))->format('Y-m-t'),
            'Choose a month-end date.');

        DB::transaction(function () use ($actor, $through, $reason): void {
            $state = $this->lockState();
            if ($state->closed_through === $through) {
                return;
            }
            $this->validateDate($state, $through);
            $this->require(! $this->pending()->where('posting_date', '<=', $through)->exists()
                && ! $this->unpostedReceipts()->where('customer_payments.payment_date', '<=', $through)->exists(),
                'Review pending journals and post all recorded pilot receipts through this date before closing.');
            $this->require(! $this->unrecognisedBookings($through)->exists(),
                'Review enrolled tours due for recognition before closing this period.');

            $balance = $this->trialBalance($through);
            $this->require((int) $balance->sum('debit_minor') === (int) $balance->sum('credit_minor'), 'The pilot trial balance does not balance.');
            $cash = $balance->filter(fn (object $row) => str_starts_with($row->account_code, 'bank:'))
                ->sum(fn (object $row) => (int) $row->debit_minor - (int) $row->credit_minor);
            $recorded = CustomerPayment::query()->whereIn('invoice_id', DB::table('accounting_bookings')->select('invoice_id'))
                ->where('payment_date', '<=', $through)->select('amount')->cursor()
                ->sum(fn (CustomerPayment $payment) => $this->minor($payment->amount));
            $this->require($cash === $recorded, 'Pilot cash does not reconcile to recorded receipts through this date.');
            $accrued = $this->posted()->where('event', 'accrual')->where('posting_date', '<=', $through)->sum('amount_minor');
            $this->require(-$this->net($balance->keyBy('account_code'), '2140') === (int) $accrued,
                'Accrued tour costs do not reconcile to approved supplier accruals.');

            DB::table('accounting_pilot_state')->where('id', 1)->update(['closed_through' => $through]);
            app(AuditService::class)->record('AccountingPilot.period_closed', new: [
                'closed_through' => $through,
                'approved_by' => $actor->id,
                'reconciled_receipts_minor' => $recorded,
                'reconciled_supplier_accruals_minor' => (int) $accrued,
                'trial_balance' => $balance->map(fn (object $row) => (array) $row)->all(),
            ], reason: trim($reason));
        });
    }

    /**
     * Called inside the existing receipt transaction before locking the invoice.
     * Close, posting and receipt creation share the pilot state lock.
     */
    public function validateNewReceipt(int $invoiceId, string $date, string $method, string $account, string $amount): bool
    {
        if (! self::enabled()) {
            $this->require(! $this->booking($invoiceId), 'Re-enable the pilot to record a receipt for this booking.');

            return false;
        }

        $state = $this->lockState();
        if ($booking = $this->booking($invoiceId)) {
            $this->validateDate($state, $date);
            $this->receiptAccount($method, $account);
            $this->minor($amount);
            $this->require($date >= $booking->booking_date, 'The receipt date precedes the booking.');
            $lastDate = $this->posted()->where('invoice_id', $invoiceId)->where('event', '<>', 'accrual')->max('posting_date');
            $this->require(! $lastDate || $date >= $lastDate, 'The receipt predates a posted customer event for this booking.');

            return true;
        }

        return false;
    }

    public function state(): object
    {
        return DB::table('accounting_pilot_state')->where('id', 1)->first();
    }

    public function outstandingWork(string $through): Collection
    {
        return $this->unpostedReceipts()->where('customer_payments.payment_date', '<=', $through)
            ->join('invoices', 'invoices.id', '=', 'customer_payments.invoice_id')
            ->get(['invoices.id', 'invoice_number', 'customer_payments.payment_date as date'])
            ->each(fn (object $row) => $row->task = 'Unposted receipt')
            ->concat($this->unrecognisedBookings($through)->join('invoices', 'invoices.id', '=', 'accounting_bookings.invoice_id')
                ->get(['invoices.id', 'invoice_number', 'return_date as date'])
                ->each(fn (object $row) => $row->task = 'Recognition due'))
            ->sortBy('date');
    }

    public static function money(int $minor): string
    {
        $absolute = abs($minor);

        return ($minor < 0 ? '-' : '').number_format(intdiv($absolute, 100)).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    public function trialBalance(string $through, ?int $invoiceId = null): Collection
    {
        return $this->lines($through, $invoiceId)->groupBy('account_code')->orderBy('account_code')
            ->selectRaw('account_code, SUM(debit_minor) AS debit_minor, SUM(credit_minor) AS credit_minor')->get();
    }

    public function supplierAccruals(string $through): Collection
    {
        return $this->lines($through)->where('account_code', '2140')
            ->join('suppliers', 'suppliers.id', '=', 'accounting_journals.supplier_id')
            ->groupBy('suppliers.id', 'suppliers.name')->orderBy('suppliers.name')
            ->selectRaw('suppliers.id, suppliers.name, SUM(credit_minor) - SUM(debit_minor) AS balance_minor')->get();
    }

    public function customerBalances(string $through): Collection
    {
        return $this->lines($through)->whereIn('account_code', ['1140', '2120'])
            ->join('invoices', 'invoices.id', '=', 'accounting_journals.invoice_id')
            ->groupBy('invoices.id', 'invoices.invoice_number')->orderBy('invoices.invoice_number')
            ->selectRaw("invoices.id, invoices.invoice_number,
                SUM(CASE WHEN account_code = '1140' THEN debit_minor ELSE 0 END)
                    - SUM(CASE WHEN account_code = '1140' THEN credit_minor ELSE 0 END) AS receivable_minor,
                SUM(CASE WHEN account_code = '2120' THEN credit_minor ELSE 0 END)
                    - SUM(CASE WHEN account_code = '2120' THEN debit_minor ELSE 0 END) AS advance_minor")
            ->get();
    }

    private function lines(string $through, ?int $invoiceId = null): Builder
    {
        return $this->posted()->join('accounting_journal_lines', 'accounting_journals.id', '=', 'journal_id')
            ->where('posting_date', '<=', $through)
            ->when($invoiceId !== null, fn (Builder $query) => $query->where('accounting_journals.invoice_id', $invoiceId));
    }

    private function posted(): Builder
    {
        return DB::table('accounting_journals')->whereNotNull('posted_at');
    }

    private function pending(): Builder
    {
        return DB::table('accounting_journals')->whereNull('posted_at')->whereNull('rejected_at');
    }

    private function unpostedReceipts(?int $invoiceId = null): Builder
    {
        return DB::table('customer_payments')
            ->join('accounting_bookings', 'accounting_bookings.invoice_id', '=', 'customer_payments.invoice_id')
            ->when($invoiceId !== null, fn (Builder $query) => $query->where('customer_payments.invoice_id', $invoiceId))
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('accounting_journals')
                ->whereColumn('customer_payment_id', 'customer_payments.id')->whereNotNull('posted_at'));
    }

    private function unrecognisedBookings(string $through): Builder
    {
        return DB::table('accounting_bookings')->where('return_date', '<=', $through)
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('accounting_journals')
                ->whereColumn('accounting_journals.invoice_id', 'accounting_bookings.invoice_id')
                ->where('event', 'delivery')->whereNotNull('posted_at'));
    }

    private function lockState(): object
    {
        $state = DB::table('accounting_pilot_state')->where('id', 1)->lockForUpdate()->first();
        $start = config('accounting.pilot_start_date');
        Validator::validate(['start' => $start], ['start' => 'required|date_format:Y-m-d']);
        $this->require(! $state->start_date || $state->start_date === $start,
            'The configured pilot start date differs from the original cut-off.');
        if (! $state->start_date) {
            DB::table('accounting_pilot_state')->where('id', 1)->update(['start_date' => $start]);
            $state->start_date = $start;
        }

        return $state;
    }

    private function reviewable(User $actor, int $invoiceId, int $journalId): object
    {
        $journal = DB::table('accounting_journals')->where('invoice_id', $invoiceId)->where('id', $journalId)->first();
        abort_unless($journal, 404);
        abort_if((int) $journal->submitted_by === $actor->id, 403);
        $this->require(! $journal->rejected_at, 'This journal has already been rejected.');

        return $journal;
    }

    private function receiptSnapshot(CustomerPayment $payment, Invoice $invoice): array
    {
        return [
            'amount' => $payment->amount,
            'date' => $payment->payment_date->toDateString(),
            'number' => $payment->receipt_number,
            'invoice_number' => $invoice->invoice_number,
            'deposit_to' => $payment->deposit_to,
            'method' => $payment->payment_method,
            'recorded_by' => (int) $payment->recorded_by,
        ];
    }

    /** @return array{int, array<int|string, int>} Positive values are debits. */
    private function posting(object $booking, Invoice $invoice, string $event, string $date, ?int $amount, ?array $receipt): array
    {
        $this->require((int) $invoice->tour_id === (int) $booking->tour_id && $this->minor($invoice->total_amount) === (int) $booking->contract_minor,
            'The booking changed after enrollment.');
        $this->require($invoice->tour->status !== TourStatus::Cancelled, 'Cancelled tours require a separate review.');
        $lines = $this->trialBalance($date, $invoice->id)->keyBy('account_code');
        $receivable = $this->net($lines, '1140');
        $advances = -$this->net($lines, '2120');
        $earned = -$this->net($lines, '4100');

        if ($event === 'receipt') {
            $this->require($date <= $booking->return_date || $earned > 0,
                'Post delivery before a receipt dated after the enrolled return date.');
            $bank = $this->receiptAccount($receipt['method'], $receipt['deposit_to']);
            $applied = min($amount, $receivable);
            $this->require($advances + $earned + $amount - $applied <= (int) $booking->contract_minor,
                'The receipt exceeds the remaining pilot booking balance.');

            return [$amount, array_filter(['bank:'.$bank->code => $amount, '1140' => -$applied, '2120' => -($amount - $applied)])];
        }

        if ($event === 'billing') {
            $this->require($date <= $booking->return_date, 'Advance billing cannot be dated after the enrolled return date.');
            $remaining = (int) $booking->contract_minor - $advances;
            $this->require($earned === 0 && $remaining > 0, 'This booking has no amount available for advance billing.');

            return [$remaining, ['1140' => $remaining, '2120' => -$remaining]];
        }

        if ($event === 'delivery') {
            $this->require($date >= $booking->return_date && $invoice->tour->status === TourStatus::Departed
                && $invoice->tour->return_date?->toDateString() === $booking->return_date,
                'Verify delivery after the enrolled return date and mark the tour departed.');
            $this->require($date === $booking->return_date, 'Use the verified return date as the pilot recognition date.');
            $this->require($advances === (int) $booking->contract_minor && $earned === 0,
                'Post the full contract value through verified receipts or unconditional advance billing first.');

            return [$advances, ['2120' => $advances, '4100' => -$advances]];
        }

        $this->require($date >= $booking->departure_date && $amount > 0, 'Enter a positive cost dated on or after departure.');

        return [$amount, ['5170' => $amount, '2140' => -$amount]];
    }

    private function net(Collection $lines, string $code): int
    {
        $line = $lines->get($code);

        return $line ? (int) $line->debit_minor - (int) $line->credit_minor : 0;
    }

    private function receiptAccount(?string $method, ?string $account): FinancialAccount
    {
        $bank = FinancialAccount::query()->where('code', $account)->where('is_active', true)->first();
        $this->require($bank !== null && in_array($method, [PaymentMode::BankTransfer->value, PaymentMode::Cash->value], true),
            'The pilot accepts verified bank transfers and cash in an active financial account.');

        return $bank;
    }

    private function minor(string $amount): int
    {
        $this->require(preg_match('/^\d{1,13}(\.\d{1,2})?$/D', $amount) === 1, 'Enter a valid LKR amount with up to two decimal places.');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $minor = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        $this->require($minor > 0, 'The amount must be positive.');

        return $minor;
    }

    private function validateDate(object $state, string $date): void
    {
        $this->require($date >= $state->start_date && $date <= self::today(), 'Use a posting date between the pilot cut-off and today.');
        $this->require(! $state->closed_through || $date > $state->closed_through, 'The pilot accounting period is closed.');
    }

    private function authorize(User $actor, bool $approver = false): void
    {
        abort_unless(self::enabled(), 404);
        abort_unless($approver ? $actor->isAdmin() : $actor->canManageAccountingRecords(), 403);
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['accounting' => $message]);
        }
    }
}
