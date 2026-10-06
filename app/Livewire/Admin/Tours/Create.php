<?php

namespace App\Livewire\Admin\Tours;

use App\Enums\TourStatus;
use App\Models\Tour;
use App\Services\TourCodeGenerator;
use Carbon\Carbon;
use Livewire\Component;

class Create extends Component
{
    public string $tour_code = '';

    public string $name = '';

    public string $tour_type = 'group_tour';

    public string $departure_date = '';

    public string $return_date = '';

    public $package_price = 0;

    public string $currency = 'LKR';

    public $seat_capacity = null;

    public string $status = 'open';

    public $estimated_vendor_cost = null;

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isAccount(), 403);
    }

    public function save(TourCodeGenerator $codes)
    {
        $data = $this->validate($this->rules());
        if (blank($data['tour_code'])) {
            $data['tour_code'] = $codes->generate(null, Carbon::parse($data['departure_date']), $data['name']);
        }
        $tour = Tour::create($data);
        session()->flash('success', 'Tour created.');

        return $this->redirectRoute('admin.tours.show', $tour, navigate: true);
    }

    private function rules(): array
    {
        return [
            'tour_code' => ['nullable', 'string', 'max:50', 'unique:tours,tour_code'],
            'name' => ['required', 'string', 'max:255'],
            'tour_type' => ['required', 'in:group_tour,fixed_departure'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'package_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:LKR,USD,SGD,CNY'],
            'seat_capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', array_keys(TourStatus::options()))],
            'estimated_vendor_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.tours.form', ['heading' => 'New tour', 'submitLabel' => 'Create tour', 'statuses' => TourStatus::options()])
            ->layout('components.layouts.admin', ['title' => 'New tour']);
    }
}
