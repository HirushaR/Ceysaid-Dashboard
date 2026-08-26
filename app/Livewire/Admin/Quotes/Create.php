<?php

namespace App\Livewire\Admin\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\Quote;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public ?int $lead_id = null; public string $subject = ''; public ?string $valid_until = null; public string $terms = 'Due on Receipt'; public string $notes = '';
    public array $lines = [['description' => '', 'quantity' => 1, 'rate' => 0]];
    public function mount(): void { $this->lead_id = request()->integer('lead') ?: null; $this->valid_until = now()->addDays(14)->toDateString(); }
    public function addLine(): void { $this->lines[] = ['description' => '', 'quantity' => 1, 'rate' => 0]; }
    public function removeLine(int $index): void { if (count($this->lines) > 1) { unset($this->lines[$index]); $this->lines = array_values($this->lines); } }
    public function save(DocumentNumberService $numbers)
    {
        $data = $this->validate(['lead_id'=>['required','exists:leads,id'],'subject'=>['required','string','max:255'],'valid_until'=>['required','date','after_or_equal:today'],'terms'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:5000'],'lines'=>['required','array','min:1'],'lines.*.description'=>['required','string','max:2000'],'lines.*.quantity'=>['required','numeric','gt:0'],'lines.*.rate'=>['required','numeric','min:0']]);
        $quote = DB::transaction(function () use ($data, $numbers) {
            $quote = Quote::create(['lead_id'=>$data['lead_id'],'family_id'=>(string)Str::uuid(),'revision'=>1,'quote_number'=>$numbers->nextQuoteNumberForLead($data['lead_id']),'status'=>QuoteStatus::Draft,'quote_date'=>now(),'valid_until'=>$data['valid_until'],'terms'=>$data['terms'],'subject'=>$data['subject'],'notes'=>$data['notes'],'created_by'=>auth()->id()]);
            foreach ($data['lines'] as $index=>$line) $quote->lineItems()->create($line + ['sort_order'=>$index]);
            $quote->actionLogs()->create(['user_id'=>auth()->id(),'action'=>'created','after'=>['status'=>'draft']]);
            return $quote;
        });
        session()->flash('success','Quote created.'); return $this->redirectRoute('admin.quotes.show',$quote,navigate:true);
    }
    public function render() { return view('livewire.admin.quotes.create',['leads'=>Lead::query()->forFinanceLeadSelection()->where('is_other_lead',false)->latest()->limit(200)->get()])->layout('components.layouts.admin',['title'=>'New quote']); }
}
