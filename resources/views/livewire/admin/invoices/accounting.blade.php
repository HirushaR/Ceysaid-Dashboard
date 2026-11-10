@php use App\Services\AccountingPilot; @endphp
<div class="space-y-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Finance / Accounting Pilot</p>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>Principal group tour · LKR · Tax-exclusive pilot</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn-secondary" href="{{ route('admin.invoices.show', $invoice) }}">Booking details</a>
            <a class="btn-secondary" href="{{ route('admin.accounting-pilot.index') }}">Pilot trial balance</a>
        </div>
    </div>

    @if ($errors->any())
        <ul class="panel space-y-1 p-4 text-sm text-red-700" role="alert">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif

    @if (! $booking)
        <form wire:submit="enroll" class="panel space-y-4 p-5">
            <h2 class="font-bold">Enroll a new booking request</h2>
            <p class="text-sm">Use an unpaid LKR group tour booking departing and returning in the same month. Enrolling locks the booking request and creates no journal.</p>
            <label class="block">
                <span class="block text-sm font-semibold">Approved policy reference</span>
                <textarea wire:model="policyEvidence" class="form-input w-full" required maxlength="2000"
                    placeholder="Record the basis for principal treatment, recognition at delivery and tax-exclusive handling."></textarea>
            </label>
            <button class="btn-primary" type="submit">Enroll booking</button>
        </form>
    @else
        <section class="panel space-y-2 p-5">
            <h2 class="font-bold">Booking request · LKR {{ AccountingPilot::money($booking->contract_minor) }}</h2>
            <p class="text-sm">{{ $booking->departure_date }} to {{ $booking->return_date }}</p>
            <p class="text-sm whitespace-pre-line">{{ $booking->policy_evidence }}</p>
        </section>

        @if (! $journals->contains(fn ($journal) => ! $journal->posted_at && ! $journal->rejected_at))
            <form wire:submit="submit" class="panel space-y-4 p-5">
                <h2 class="font-bold">Submit for approval</h2>
                <label class="block">
                    <span class="block text-sm font-semibold">Event</span>
                    <select wire:model.live="event" class="form-input">
                        @foreach (AccountingPilot::EVENTS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($event === 'receipt')
                    <label class="block">
                        <span class="block text-sm font-semibold">Recorded receipt</span>
                        <select wire:model="receiptId" class="form-input" required>
                            <option value="">Select receipt</option>
                            @foreach ($receipts as $receipt)
                                <option value="{{ $receipt->id }}">{{ $receipt->receipt_number }} · {{ $receipt->payment_date->toDateString() }} · LKR {{ $receipt->amount }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="text-sm">Verify the bank or cash evidence before submitting. The journal uses the payment date.</p>
                @else
                    <label class="block">
                        <span class="block text-sm font-semibold">Posting date</span>
                        <input type="date" wire:model="postingDate" class="form-input" required>
                    </label>
                @endif
                @if ($event === 'billing')
                    <p class="text-sm">Use this when the remaining booking value has become unconditionally due before delivery.</p>
                @elseif ($event === 'delivery')
                    <p class="text-sm">Use the verified return date after the full booking value has been received or billed.</p>
                @elseif ($event === 'accrual')
                    <label class="block">
                        <span class="block text-sm font-semibold">Supplier</span>
                        <select wire:model="supplierId" class="form-input" required>
                            <option value="">Select supplier</option>
                            @foreach ($suppliers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="block text-sm font-semibold">Cost incurred (LKR)</span>
                        <input type="text" inputmode="decimal" wire:model="amount" class="form-input" required>
                    </label>
                    <p class="text-sm">Submit the consolidated unbilled cost for this supplier on this booking.</p>
                @endif
                <label class="block">
                    <span class="block text-sm font-semibold">Evidence reference</span>
                    <textarea wire:model="evidence" class="form-input w-full" required maxlength="2000"></textarea>
                </label>
                <button class="btn-primary" type="submit">Submit journal</button>
            </form>
        @endif

        @foreach ($journals as $journal)
            <section class="panel space-y-3 p-5" wire:key="journal-{{ $journal->id }}">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-bold">#{{ $journal->id }} · {{ AccountingPilot::EVENTS[$journal->event] }}</h2>
                    <span class="status-badge">{{ $journal->posted_at ? 'Posted' : ($journal->rejected_at ? 'Rejected' : 'Awaiting approval') }}</span>
                    <span class="text-sm">{{ $journal->posting_date }}</span>
                </div>
                <p class="text-sm">Prepared by {{ $journal->preparer }}@if ($journal->reviewer) · Reviewed by {{ $journal->reviewer }}@endif</p>
                @if ($journal->supplier)
                    <p class="text-sm">Supplier: {{ $journal->supplier }}</p>
                @endif
                <p class="text-sm whitespace-pre-line">{{ $journal->evidence }}</p>
                @if ($journal->customer_payment_id)
                    <a class="text-sm font-semibold text-blue-600" href="{{ route('finance.customer-payments.pdf', $journal->customer_payment_id) }}">Download source receipt</a>
                @endif
                @if ($journal->rejected_at)
                    <p class="text-sm">Rejection reason: {{ $journal->rejection_reason }}</p>
                @endif
                <table class="data-table">
                    <thead><tr><th>Account</th><th>Debit (LKR)</th><th>Credit (LKR)</th></tr></thead>
                    <tbody>
                        @foreach ($lines->get($journal->id, []) as $line)
                            <tr>
                                <td>{{ AccountingPilot::ACCOUNTS[$line->account_code] ?? $line->account_code }}</td>
                                <td>{{ AccountingPilot::money($line->debit_minor) }}</td>
                                <td>{{ AccountingPilot::money($line->credit_minor) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (! $journal->posted_at && ! $journal->rejected_at && auth()->user()->isAdmin() && (int) $journal->submitted_by !== auth()->id())
                    <div class="flex flex-wrap items-end gap-3">
                        <button class="btn-primary" type="button" wire:click="approve({{ $journal->id }})" wire:confirm="Approve and post this journal?" wire:loading.attr="disabled">Approve and post</button>
                        <form wire:submit="reject({{ $journal->id }})" class="flex flex-wrap items-end gap-2">
                            <label>
                                <span class="block text-sm font-semibold">Rejection reason</span>
                                <input wire:model="rejectionReason" class="form-input" required maxlength="2000">
                            </label>
                            <button class="btn-secondary" type="submit">Reject</button>
                        </form>
                    </div>
                @endif
            </section>
        @endforeach
    @endif
</div>
