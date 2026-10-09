<?php

namespace App\Livewire\Admin\Payments;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Services\PaymentRegisterService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $direction = '';

    #[Url]
    public string $transaction_type = '';

    #[Url]
    public string $payment_method = '';

    #[Url]
    public string $account = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(PaymentRegisterService $service)
    {
        abort_unless(auth()->user()->canViewPayments(), 403);
        $filters = collect(['search', 'direction', 'transaction_type', 'payment_method', 'account', 'date_from', 'date_to'])->mapWithKeys(fn ($key) => [$key => $this->{$key} ?: null])->all();

        return view('livewire.admin.payments.index', ['payments' => $service->paginate($filters, 30), 'summary' => $service->summary($filters), 'accounts' => DepositAccount::options(), 'modes' => PaymentMode::options()])->layout('components.layouts.admin', ['title' => 'Payment register']);
    }
}
