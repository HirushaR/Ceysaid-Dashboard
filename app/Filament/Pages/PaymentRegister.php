<?php

namespace App\Filament\Pages;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Services\PaymentRegisterService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class PaymentRegister extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Payment Register';

    protected static ?string $title = 'Payment Register';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.payment-register';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $direction = null;

    public ?string $paymentMethod = null;

    public ?string $account = null;

    public function mount(): void
    {
        $today = now()->toDateString();
        $this->form->fill([
            'dateFrom' => $today,
            'dateTo' => $today,
            'direction' => null,
            'paymentMethod' => null,
            'account' => null,
        ]);
        $this->syncFilterPropertiesFromForm();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label('Payment date from')
                    ->required()
                    ->native(false),
                DatePicker::make('dateTo')
                    ->label('Payment date to')
                    ->required()
                    ->native(false)
                    ->rules(['after_or_equal:dateFrom']),
                Select::make('direction')
                    ->label('Direction')
                    ->options([
                        'in' => 'Customer receipts (in)',
                        'out' => 'Vendor payments (out)',
                    ])
                    ->placeholder('All payments'),
                Select::make('paymentMethod')
                    ->label('Payment method')
                    ->options(PaymentMode::options())
                    ->placeholder('All methods'),
                Select::make('account')
                    ->label('Account')
                    ->options(DepositAccount::options())
                    ->placeholder('All accounts'),
            ])
            ->columns(5);
    }

    public function applyFilters(): void
    {
        $this->syncFilterPropertiesFromForm();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $today = now()->toDateString();
        $this->form->fill([
            'dateFrom' => $today,
            'dateTo' => $today,
            'direction' => null,
            'paymentMethod' => null,
            'account' => null,
        ]);
        $this->syncFilterPropertiesFromForm();
        $this->resetPage();
    }

    public function getPayments(): LengthAwarePaginator
    {
        return app(PaymentRegisterService::class)->paginate($this->filters());
    }

    /**
     * @return array{received: float, paid: float, net: float, count: int}
     */
    public function getSummary(): array
    {
        return app(PaymentRegisterService::class)->summary($this->filters());
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isAccount());
    }

    /**
     * @return array<string, string|null>
     */
    private function filters(): array
    {
        return [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'direction' => $this->direction,
            'payment_method' => $this->paymentMethod,
            'account' => $this->account,
        ];
    }

    private function syncFilterPropertiesFromForm(): void
    {
        $state = $this->form->getState();

        $this->dateFrom = $state['dateFrom'] ?? null;
        $this->dateTo = $state['dateTo'] ?? null;
        $this->direction = $state['direction'] ?? null;
        $this->paymentMethod = $state['paymentMethod'] ?? null;
        $this->account = $state['account'] ?? null;
    }
}
