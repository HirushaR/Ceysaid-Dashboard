<?php

namespace App\Livewire\Admin\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Edit extends Component
{
    public Quote $quote;
    public string $subject = '';
    public ?string $valid_until = null;
    public string $terms = '';
    public string $notes = '';
    public array $lines = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canViewQuote($this->quote) && $this->quote->isEditable(), 403);
        $this->quote->load('lineItems');
        $this->subject = $this->quote->subject;
        $this->valid_until = $this->quote->valid_until?->toDateString();
        $this->terms = $this->quote->terms ?? '';
        $this->notes = $this->quote->notes ?? '';
        $this->lines = $this->quote->lineItems->map(fn ($line) => ['description' => $line->description, 'quantity' => $line->quantity, 'rate' => $line->rate])->values()->all();
        if ($this->lines === []) {
            $this->lines = [['description' => '', 'quantity' => 1, 'rate' => 0]];
        }
    }

    public function addLine(): void { $this->lines[] = ['description' => '', 'quantity' => 1, 'rate' => 0]; }
    public function removeLine(int $index): void { if (count($this->lines) > 1) { unset($this->lines[$index]); $this->lines = array_values($this->lines); } }

    public function save()
    {
        $data = $this->validate(['subject' => ['required','string','max:255'], 'valid_until' => ['required','date'], 'terms' => ['nullable','string','max:255'], 'notes' => ['nullable','string','max:5000'], 'lines' => ['required','array','min:1'], 'lines.*.description' => ['required','string','max:2000'], 'lines.*.quantity' => ['required','numeric','gt:0'], 'lines.*.rate' => ['required','numeric','min:0']]);
        DB::transaction(function () use ($data): void {
            $quote = Quote::query()->lockForUpdate()->findOrFail($this->quote->id);
            abort_unless($quote->isEditable(), 403);
            $before = $quote->only(['subject','valid_until','terms','notes']);
            $quote->update(collect($data)->only(['subject','valid_until','terms','notes'])->all());
            $quote->lineItems()->delete();
            foreach ($data['lines'] as $index => $line) $quote->lineItems()->create($line + ['sort_order' => $index]);
            $quote->actionLogs()->create(['user_id' => auth()->id(), 'action' => 'updated', 'before' => $before, 'after' => $quote->fresh()->only(['subject','valid_until','terms','notes'])]);
        });
        session()->flash('success', 'Quote draft updated.');
        return $this->redirectRoute('admin.quotes.show', $this->quote, navigate: true);
    }

    public function render() { return view('livewire.admin.quotes.edit')->layout('components.layouts.admin', ['title' => 'Edit '.$this->quote->quote_number]); }
}
