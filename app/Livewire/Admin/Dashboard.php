<?php

namespace App\Livewire\Admin;

use App\Enums\LeadStatus;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\VendorBill;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $leads = Lead::query()->notArchived()->excludingOtherLeads();
        if ($user->isSales() && ! $user->isManager()) {
            $leads->where('assigned_to', $user->id);
        } elseif ($user->isOperation() && ! $user->isManager()) {
            $leads->where(fn ($q) => $q->where('assigned_operator', $user->id)->orWhere('status', LeadStatus::INFO_GATHER_COMPLETE->value));
        }

        $canSeeFinance = $user->canManageAccountingRecords();
        $metrics = [
                ['label' => 'Active leads', 'value' => (clone $leads)->whereNotIn('status', [LeadStatus::MARK_CLOSED->value, LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value])->count(), 'tone' => 'blue'],
                ['label' => 'Operations queue', 'value' => (clone $leads)->where('status', LeadStatus::INFO_GATHER_COMPLETE->value)->count(), 'tone' => 'amber'],
                ['label' => 'Confirmed', 'value' => (clone $leads)->where('status', LeadStatus::CONFIRMED->value)->count(), 'tone' => 'emerald'],
        ];
        if ($canSeeFinance) {
            $metrics[] = ['label' => 'Outstanding invoices', 'value' => 'LKR '.number_format((float) Invoice::query()->sum('balance_amount'), 2), 'tone' => 'rose'];
        }

        return view('livewire.admin.dashboard', [
            'metrics' => $metrics,
            'recentLeads' => $leads->with(['assignedUser', 'assignedOperator'])->latest()->limit(8)->get(),
            'canSeeFinance' => $canSeeFinance,
            'receipts' => $canSeeFinance ? CustomerPayment::query()->whereDate('payment_date', '>=', now()->startOfMonth())->sum('amount') : 0,
            'payables' => $canSeeFinance ? VendorBill::query()->whereIn('payment_status', ['pending', 'partial'])->sum('bill_amount') : 0,
        ])->layout('components.layouts.admin', ['title' => 'Dashboard']);
    }
}
