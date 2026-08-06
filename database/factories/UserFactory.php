<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Petugas,
            'remember_token' => Str::random(10),
            'nip' => fake()->numerify('##########'),
            'rank' => fake()->randomElement(['Penata', 'Pranata', 'Pembina']),
            'jabatan' => fake()->randomElement(['Juru Pungut', 'Pengadministrasi Perkantoran']),
            'phone' => null,
            'notes' => null,
            'is_active' => true,
            'is_juru_pungut' => false,
            'market_id' => function () {
                return Market::query()->inRandomOrder()->first()?->id ?? null;
            },
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign the petugas to a specific market (and, for ERET Korwil records,
     * mark them as an active Juru Pungut by default).
     */
    public function forMarket(Market $market): static
    {
        return $this->state(fn () => [
            'market_id' => $market->id,
        ]);
    }

    /**
     * Mark the petugas as a Juru Pungut (appears in the ERET dropdown).
     */
    public function juruPungut(): static
    {
        return $this->state(fn () => [
            'is_juru_pungut' => true,
        ]);
    }

    /**
     * Mark the petugas as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}

