<?php

namespace App\Models;

use App\Enums\TourStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    /** @use HasFactory<\Database\Factories\TourFactory> */
    use HasFactory;

    protected $fillable = [
        'tour_code',
        'name',
        'departure_date',
        'return_date',
        'package_price',
        'currency',
        'seat_capacity',
        'status',
        'estimated_vendor_cost',
        'notes',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'package_price' => 'decimal:2',
        'estimated_vendor_cost' => 'decimal:2',
        'status' => TourStatus::class,
        'seat_capacity' => 'integer',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getBookedSeatsAttribute(): int
    {
        if ($this->relationLoaded('leads')) {
            return (int) $this->leads->sum(fn (Lead $lead) => $lead->booked_pax);
        }

        return (int) $this->leads()
            ->sum(\Illuminate\Support\Facades\DB::raw(
                'COALESCE(number_of_adults, 0) + COALESCE(number_of_children, 0) + COALESCE(number_of_infants, 0)'
            ));
    }

    public function getAvailableSeatsAttribute(): ?int
    {
        if ($this->seat_capacity === null) {
            return null;
        }

        return max(0, $this->seat_capacity - $this->booked_seats);
    }

    public function getDepartureMonthAttribute(): string
    {
        return $this->departure_date?->format('M Y') ?? '';
    }

    public function getExpectedSalesAttribute(): float
    {
        return round((float) $this->package_price * $this->booked_seats, 2);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TourStatus::Open);
    }

    public function scopeDeparted(Builder $query): Builder
    {
        return $query->where('status', TourStatus::Departed);
    }
}
