<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetributionFactory extends Factory
{
    protected $model = Retribution::class;

    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'jenis_retribusi' => 'Retribusi Harian',
            'recorded_by' => User::factory(),
            'retribution_date' => fake()->date(),
            'amount' => fake()->numberBetween(10000, 500000),
            'payment_method' => 'cash',
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
