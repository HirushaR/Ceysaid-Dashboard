<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use App\Models\Customer;
use App\Enums\LeadStatus;
use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'reference_id' => 'REF' . $this->faker->unique()->numberBetween(1000, 9999),
            'customer_name' => $this->faker->name(),
            'customer_id' => Customer::factory(),
            'platform' => $this->faker->randomElement(Platform::cases())->value,
            'tour' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
            'assigned_operator' => User::factory(),
            'status' => $this->faker->randomElement(LeadStatus::cases())->value,
            'contact_method' => $this->faker->randomElement(['phone', 'email', 'whatsapp']),
            'contact_value' => $this->faker->phoneNumber(),
            'subject' => $this->faker->sentence(),
            'country' => $this->faker->country(),
            'destination' => $this->faker->city(),
            'number_of_adults' => $this->faker->numberBetween(1, 4),
            'number_of_children' => $this->faker->numberBetween(0, 2),
            'number_of_infants' => $this->faker->numberBetween(0, 1),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'arrival_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'depature_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'number_of_days' => $this->faker->numberBetween(3, 14),
            'tour_details' => $this->faker->paragraphs(3, true),
            'air_ticket_status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
            'hotel_status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
            'visa_status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
            'land_package_status' => $this->faker->randomElement(['pending', 'in_progress', 'done']),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::CONFIRMED->value,
        ]);
    }

    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::NEW->value,
        ]);
    }

    public function assignedToSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::ASSIGNED_TO_SALES->value,
        ]);
    }
}
