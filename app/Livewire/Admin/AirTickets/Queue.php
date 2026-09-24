<?php

namespace App\Livewire\Admin\AirTickets;

use App\Enums\AirTicketStatus;
use App\Enums\ServiceStatus;
use App\Models\AirTicketRequest;
use App\Models\VendorBill;
use App\Services\LeadActionLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Queue extends Component
{
    public VendorBill $vendorBill;
    public string $provider = '';
    public string $booking_reference = '';
    public string $airline = '';
    public string $time_limit = '';

    public const PROVIDERS = [
        'sabre' => 'Sabre',
        'ndc' => 'NDC',
        'air_asia_portal' => 'Air Asia Portal',
        'fly_dubai_portal' => 'Fly Dubai Portal',
        'other_airline_portal' => 'Other Airline Portal',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->canQueueAirTickets() && $user->can('view', $this->vendorBill), 403);

        $this->vendorBill->load(['supplier', 'invoice.lead', 'airTicketRequest']);
        $request = $this->vendorBill->airTicketRequest;
        abort_if($request && $request->status !== AirTicketStatus::RETURNED_FOR_CORRECTION, 409, 'This vendor bill is already in the ticket workflow.');

        if ($request) {
            $this->provider = $request->provider;
            $this->booking_reference = $request->booking_reference;
            $this->airline = $request->airline;
            $this->time_limit = $request->time_limit?->format('Y-m-d\TH:i') ?? '';
        }
    }

    public function save()
    {
        $user = auth()->user();
        abort_unless($user->canQueueAirTickets() && $user->can('view', $this->vendorBill), 403);

        $data = $this->validate([
            'provider' => ['required', 'in:'.implode(',', array_keys(self::PROVIDERS))],
            'booking_reference' => ['required', 'string', 'max:100'],
            'airline' => ['required', 'string', 'max:150'],
            'time_limit' => ['required', 'date', 'after:now'],
        ]);

        DB::transaction(function () use ($data, $user): void {
            $request = AirTicketRequest::updateOrCreate(
                ['vendor_bill_id' => $this->vendorBill->id],
                $data + [
                    'amount' => $this->vendorBill->bill_amount,
                    'status' => AirTicketStatus::PENDING_APPROVAL,
                    'queued_by' => $user->id,
                    'queued_at' => now(),
                    'approved_by' => null,
                    'approved_at' => null,
                    'decision_notes' => null,
                    'issued_by' => null,
                    'issued_at' => null,
                    'ticket_number' => null,
                    'issue_notes' => null,
                ],
            );

            $lead = $this->vendorBill->invoice?->lead;
            $lead?->update(['air_ticket_status' => ServiceStatus::IN_PROGRESS->value]);
            LeadActionLogger::log(
                $lead,
                'air_ticket_queued',
                "Vendor bill {$this->vendorBill->vendor_bill_number} queued for ticket issuing (PNR {$request->booking_reference}).",
                null,
                ['air_ticket_request_id' => $request->id, 'status' => $request->status->value],
            );
        });

        session()->flash('success', 'Ticket request queued for accounts approval.');

        return $this->redirectRoute('admin.air-tickets.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.air-tickets.queue', [
            'providers' => self::PROVIDERS,
            'request' => $this->vendorBill->airTicketRequest,
        ])->layout('components.layouts.admin', ['title' => 'Queue ticket issuing']);
    }
}
