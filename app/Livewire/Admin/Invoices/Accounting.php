<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\AccountingPilot;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Accounting extends Component
{
    public Invoice $invoice;

    public string $policyEvidence = '';

    public string $event = 'receipt';

    public string $postingDate = '';

    public ?int $receiptId = null;

    public ?int $supplierId = null;

    public string $amount = '';

    public string $evidence = '';

    public string $rejectionReason = '';

    public function mount(): void
    {
        $this->authorizePage();
        $this->postingDate = AccountingPilot::today();
    }

    public function enroll(AccountingPilot $pilot): void
    {
        $pilot->enroll(auth()->user(), $this->invoice->id, $this->policyEvidence);
        $this->reset('policyEvidence');
    }

    public function submit(AccountingPilot $pilot): void
    {
        $pilot->submit(auth()->user(), $this->invoice->id, [
            'event' => $this->event,
            'posting_date' => $this->event === 'receipt' ? null : $this->postingDate,
            'customer_payment_id' => $this->event === 'receipt' ? $this->receiptId : null,
            'supplier_id' => $this->event === 'accrual' ? $this->supplierId : null,
            'amount' => $this->event === 'accrual' ? $this->amount : null,
            'evidence' => $this->evidence,
        ]);
        $this->reset('receiptId', 'supplierId', 'amount', 'evidence');
    }

    public function approve(int $journalId, AccountingPilot $pilot): void
    {
        $pilot->approve(auth()->user(), $this->invoice->id, $journalId);
    }

    public function reject(int $journalId, AccountingPilot $pilot): void
    {
        $pilot->reject(auth()->user(), $this->invoice->id, $journalId, $this->rejectionReason);
        $this->reset('rejectionReason');
    }

    public function render()
    {
        $this->authorizePage();

        $pilot = app(AccountingPilot::class);
        $booking = $pilot->booking($this->invoice->id);
        $journals = DB::table('accounting_journals')->where('invoice_id', $this->invoice->id)
            ->join('users as submitter', 'submitter.id', '=', 'submitted_by')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'reviewed_by')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'accounting_journals.supplier_id')
            ->orderByDesc('posting_date')->orderByDesc('accounting_journals.id')
            ->get(['accounting_journals.*', 'submitter.name as preparer', 'reviewer.name as reviewer', 'suppliers.name as supplier']);
        $lines = DB::table('accounting_journal_lines')->whereIn('journal_id', $journals->pluck('id'))
            ->orderBy('id')->get()->groupBy('journal_id');
        $receipts = $this->invoice->customerPayments()
            ->whereNotIn('id', $journals->whereNull('rejected_at')->pluck('customer_payment_id')->filter())
            ->orderBy('payment_date')->orderBy('id')->get();

        return view('livewire.admin.invoices.accounting', [
            'booking' => $booking,
            'journals' => $journals,
            'lines' => $lines,
            'receipts' => $receipts,
            'suppliers' => $booking && $this->event === 'accrual' ? Supplier::orderBy('name')->pluck('name', 'id') : collect(),
        ])->layout('components.layouts.admin', ['title' => 'Booking Accounting']);
    }

    private function authorizePage(): void
    {
        abort_unless(AccountingPilot::enabled(), 404);
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
    }
}
