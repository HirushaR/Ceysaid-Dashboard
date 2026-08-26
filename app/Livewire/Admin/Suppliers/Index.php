<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination; #[Url] public string $search='';
    public function render(){ abort_unless(auth()->user()->isAdmin()||auth()->user()->isAccount()||auth()->user()->hasPermission('suppliers.view'),403); $q=Supplier::query()->withCount('vendorBills')->when($this->search,fn($q)=>$q->where('name','like',"%{$this->search}%")); return view('livewire.admin.suppliers.index',['suppliers'=>$q->orderBy('name')->paginate(20)])->layout('components.layouts.admin',['title'=>'Suppliers']); }
}
