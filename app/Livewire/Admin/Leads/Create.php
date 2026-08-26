<?php

namespace App\Livewire\Admin\Leads;

use App\Services\LeadWorkflowService;
use Livewire\Component;

class Create extends Component
{
    public string $customer_name = '';
    public string $contact_method = 'phone';
    public string $contact_value = '';
    public string $platform = 'other';
    public string $destination = '';
    public string $message = '';
    public string $priority = 'medium';
    public ?string $arrival_date = null;
    public ?string $depature_date = null;
    public int $number_of_adults = 1;
    public int $number_of_children = 0;
    public int $number_of_infants = 0;
    public bool $is_group_lead = false;
    public bool $is_cruise_lead = false;

    public function save(LeadWorkflowService $workflow)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isMarketing() || auth()->user()->isSales() || auth()->user()->isCallCenter(), 403);
        $data = $this->validate([
            'customer_name' => ['required', 'string', 'max:255'], 'contact_method' => ['required', 'string', 'max:32'],
            'contact_value' => ['required', 'string', 'max:255'], 'platform' => ['required', 'string', 'max:32'],
            'destination' => ['nullable', 'string', 'max:255'], 'message' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high'], 'arrival_date' => ['nullable', 'date'],
            'depature_date' => ['nullable', 'date', 'after_or_equal:arrival_date'], 'number_of_adults' => ['required', 'integer', 'min:1'],
            'number_of_children' => ['required', 'integer', 'min:0'], 'number_of_infants' => ['required', 'integer', 'min:0'],
            'is_group_lead' => ['boolean'], 'is_cruise_lead' => ['boolean'],
        ]);
        $lead = $workflow->create($data, auth()->user());
        session()->flash('success', 'Lead created successfully.');

        return $this->redirectRoute('admin.leads.show', $lead, navigate: true);
    }

    public function render() { return view('livewire.admin.leads.create')->layout('components.layouts.admin', ['title' => 'New lead']); }
}
