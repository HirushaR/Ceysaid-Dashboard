<?php

namespace App\Livewire\Admin\Expenses;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $expense_date = '';

    public string $category_id = '';

    /** Backward-compatible free-text input used by existing integrations. */
    public string $category = '';

    public string $new_category_name = '';

    public string $new_category_code = '';

    public string $new_category_description = '';

    public string $description = '';

    public $amount = null;

    public string $payment_mode = 'bank_transfer';

    public string $paid_through = 'cash';

    public string $reference_number = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->expense_date = now()->toDateString();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $data = $this->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category_id' => ['nullable', 'exists:expense_categories,id', 'required_without:category'],
            'category' => ['nullable', 'string', 'max:100', 'required_without:category_id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['required', 'in:'.implode(',', array_keys(PaymentMode::options()))],
            'paid_through' => ['required', 'in:'.implode(',', array_keys(DepositAccount::options()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $category = $data['category_id']
            ? ExpenseCategory::query()->whereKey($data['category_id'])->where('is_active', true)->firstOrFail()
            : ExpenseCategory::query()->firstOrCreate(['name' => trim($data['category'])], ['created_by' => auth()->id()]);
        $data['category_id'] = $category->id;
        $data['category'] = $category->name;
        $data['created_by'] = auth()->id();
        Expense::create($data);
        $this->reset(['category', 'description', 'amount', 'reference_number', 'notes']);
        $this->resetPage();
        session()->flash('success', 'Expense added to the finance register.');
    }

    public function createCategory(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $data = $this->validate([
            'new_category_name' => ['required', 'string', 'max:100'],
            'new_category_code' => ['nullable', 'string', 'max:30', Rule::unique('expense_categories', 'code')],
            'new_category_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = ExpenseCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['new_category_name']))])->first();
        if ($existing) {
            if ($existing->is_active) {
                $this->addError('new_category_name', 'An active category with this name already exists.');

                return;
            }
            $existing->update(['is_active' => true]);
            $this->category_id = (string) $existing->id;
            $this->reset(['new_category_name', 'new_category_code', 'new_category_description']);
            session()->flash('success', 'Expense category reactivated.');

            return;
        }

        $category = ExpenseCategory::create([
            'name' => trim($data['new_category_name']),
            'code' => $data['new_category_code'] !== '' ? strtoupper(trim($data['new_category_code'])) : null,
            'description' => $data['new_category_description'] ?: null,
            'created_by' => auth()->id(),
        ]);
        $this->category_id = (string) $category->id;
        $this->reset(['new_category_name', 'new_category_code', 'new_category_description']);
        session()->flash('success', 'Expense category created.');
    }

    public function toggleCategory(int $categoryId): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $category = ExpenseCategory::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function render()
    {
        return view('livewire.admin.expenses.index', [
            'expenses' => Expense::with(['creator', 'expenseCategory'])->latest('expense_date')->latest('id')->paginate(25),
            'categories' => ExpenseCategory::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'modes' => PaymentMode::options(),
            'accounts' => DepositAccount::options(),
        ])->layout('components.layouts.admin', ['title' => 'Expenses']);
    }
}
