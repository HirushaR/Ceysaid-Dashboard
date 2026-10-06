<?php

namespace App\Livewire\Admin\Tours;

use App\Enums\TourStatus;
use App\Models\Tour;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Tour $tour;

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
        foreach (['tour_code', 'name', 'tour_type', 'package_price', 'currency', 'seat_capacity', 'estimated_vendor_cost'] as $field) {
            $this->{$field} = $this->tour->{$field};
        }
        $this->notes = $this->tour->notes ?? '';
        $this->status = $this->tour->status->value;
        $this->departure_date = $this->tour->departure_date?->toDateString() ?? '';
        $this->return_date = $this->tour->return_date?->toDateString() ?? '';
    }

    public function save()
    {
        $data = $this->validate([
            'tour_code' => ['required', 'string', 'max:50', Rule::unique('tours')->ignore($this->tour)],
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
        ]);
        foreach (['return_date', 'seat_capacity', 'estimated_vendor_cost'] as $field) {
            if (blank($data[$field] ?? null)) {
                $data[$field] = null;
            }
        }
        $this->tour->update($data);
        session()->flash('success', 'Tour updated.');

        return $this->redirectRoute('admin.tours.show', $this->tour, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.tours.form', ['heading' => 'Edit tour', 'submitLabel' => 'Save changes', 'statuses' => TourStatus::options()])
            ->layout('components.layouts.admin', ['title' => 'Edit '.$this->tour->tour_code]);
    }
}
