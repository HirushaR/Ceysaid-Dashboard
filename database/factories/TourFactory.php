<?php

namespace Database\Factories;

use App\Enums\TourStatus;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'tour_code' => strtoupper(fake()->lexify('???')).'-'.strtoupper($departure->format('dM')).'-'.$departure->format('Y'),
            'name' => fake()->words(3, true).' Tour',
            'departure_date' => $departure,
            'return_date' => (clone $departure)->modify('+'.fake()->numberBetween(5, 12).' days'),
            'package_price' => fake()->numberBetween(150000, 1500000),
            'currency' => 'LKR',
            'seat_capacity' => fake()->numberBetween(20, 45),
            'status' => TourStatus::Open,
            'estimated_vendor_cost' => fake()->numberBetween(100000, 1200000),
        ];
    }

    public function departed(): static
    {
        return $this->state(fn () => [
            'status' => TourStatus::Departed,
            'departure_date' => fake()->dateTimeBetween('-3 months', '-1 week'),
        ]);
    }
}
