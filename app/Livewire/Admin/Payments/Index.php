<?php

namespace App\Livewire\Admin\Payments;

use App\Services\PaymentRegisterService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination; #[Url] public string $direction=''; #[Url] public string $date_from=''; #[Url] public string $date_to='';
    public function render(PaymentRegisterService $service){ abort_unless(auth()->user()->canViewPayments(),403); $filters=['direction'=>$this->direction?:null,'date_from'=>$this->date_from?:null,'date_to'=>$this->date_to?:null]; return view('livewire.admin.payments.index',['payments'=>$service->paginate($filters,30),'summary'=>$service->summary($filters)])->layout('components.layouts.admin',['title'=>'Payment register']); }
}
