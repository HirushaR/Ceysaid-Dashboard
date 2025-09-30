<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'invoice_number' => 'INV' . $this->faker->unique()->numberBetween(1000, 9999),
            'total_amount' => $this->faker->randomFloat(2, 500, 5000),
            'payment_amount' => null,
            'balance_amount' => null,
            'payment_date' => null,
            'receipt_number' => null,
            'description' => $this->faker->sentence(),
            'customer_payment_status' => 'pending',
            'vendor_payment_status' => 'pending',
            'notes' => $this->faker->paragraph(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'customer_payment_status' => 'paid',
            'payment_amount' => $attributes['total_amount'],
            'balance_amount' => 0,
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'receipt_number' => 'RC' . $this->faker->unique()->numberBetween(1000, 9999),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'customer_payment_status' => 'pending',
            'balance_amount' => $attributes['total_amount'],
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
            'customer_payment_status' => 'partial',
            'payment_amount' => $attributes['total_amount'] * 0.5,
            'balance_amount' => $attributes['total_amount'] * 0.5,
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
