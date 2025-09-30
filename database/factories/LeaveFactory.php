<?php

namespace Database\Factories;

use App\Models\Leave;
use App\Models\User;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 months');

        return [
            'user_id' => User::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $this->faker->randomElement(LeaveType::cases())->value,
            'status' => $this->faker->randomElement(LeaveStatus::cases())->value,
            'reason' => $this->faker->paragraph(),
            'approved_by' => User::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::APPROVED->value,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::PENDING->value,
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeaveType::ANNUAL->value,
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeaveType::SICK->value,
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LeaveType::PERSONAL->value,
        ]);
    }
}
