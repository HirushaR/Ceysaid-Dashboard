<?php

namespace App\Livewire\Admin\Quotes;

use App\Models\Quote;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    #[Url] public string $search = '';
    #[Url] public ?int $lead = null;

    public function render()
    {
        $query = Quote::query()->visibleToUser(auth()->user())->with(['lead', 'lineItems'])->when($this->lead, fn ($q) => $q->where('lead_id', $this->lead))
            ->when($this->search, fn ($q) => $q->where(fn ($i) => $i->where('quote_number', 'like', "%{$this->search}%")->orWhereHas('lead', fn ($l) => $l->where('customer_name', 'like', "%{$this->search}%"))));
        return view('livewire.admin.quotes.index', ['quotes' => $query->latest()->paginate(20)])->layout('components.layouts.admin', ['title' => 'Quotes']);
    }
}
