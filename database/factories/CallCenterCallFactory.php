<?php

namespace Database\Factories;

use App\Models\CallCenterCall;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallCenterCallFactory extends Factory
{
    protected $model = CallCenterCall::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'assigned_call_center_user' => User::factory(),
            'call_type' => $this->faker->randomElement([
                CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
                CallCenterCall::CALL_TYPE_POST_ARRIVAL,
            ]),
            'status' => $this->faker->randomElement([
                CallCenterCall::STATUS_PENDING,
                CallCenterCall::STATUS_ASSIGNED,
                CallCenterCall::STATUS_CALLED,
                CallCenterCall::STATUS_NOT_ANSWERED,
                CallCenterCall::STATUS_COMPLETED,
            ]),
            'call_notes' => $this->faker->paragraph(),
            'call_attempts' => $this->faker->numberBetween(0, 5),
            'last_call_attempt' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'call_checklist_completed' => $this->faker->optional()->randomElements([
                'confirmed_departure_details',
                'confirmed_passenger_count',
                'confirmed_contact_info',
                'reminded_documents',
                'reminded_visa',
                'reminded_insurance',
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallCenterCall::STATUS_PENDING,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallCenterCall::STATUS_ASSIGNED,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallCenterCall::STATUS_COMPLETED,
            'call_attempts' => $this->faker->numberBetween(1, 3),
            'last_call_attempt' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CallCenterCall::STATUS_PENDING,
            'created_at' => $this->faker->dateTimeBetween('-1 week', '-3 days'),
        ]);
    }

    public function preDeparture(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_type' => CallCenterCall::CALL_TYPE_PRE_DEPARTURE,
        ]);
    }

    public function postArrival(): static
    {
        return $this->state(fn (array $attributes) => [
            'call_type' => CallCenterCall::CALL_TYPE_POST_ARRIVAL,
        ]);
    }
}
