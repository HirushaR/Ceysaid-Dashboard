<?php

namespace App\Livewire\Admin\Invoices;

use App\Models\Invoice;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    #[Url] public string $search='';
    public function render()
    {
        $user=auth()->user(); abort_unless($user->can('viewAny', Invoice::class), 403); $query=Invoice::query()->with(['lead','quote']);
        if(!$user->canViewAllInvoices()) $query->whereHas('lead',fn($q)=>$user->isSales()?$q->where('assigned_to',$user->id):$q->where('assigned_operator',$user->id));
        $query->when($this->search,fn($q)=>$q->where(fn($i)=>$i->where('invoice_number','like',"%{$this->search}%")->orWhereHas('lead',fn($l)=>$l->where('customer_name','like',"%{$this->search}%"))));
        return view('livewire.admin.invoices.index',['invoices'=>$query->latest()->paginate(20)])->layout('components.layouts.admin',['title'=>'Invoices']);
    }
}
