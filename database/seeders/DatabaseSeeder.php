<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\Trader;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@sipadu.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Petugas Pasar',
            'email' => 'petugas@sipadu.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Petugas,
        ]);

        $market = Market::query()->create([
            'name' => 'Pasar Induk Contoh',
            'code' => 'PSR-001',
            'address' => 'Jl. Pasar No. 1',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        Trader::query()->create([
            'market_id' => $market->id,
            'name' => 'Budi Santoso',
            'stall_number' => 'A-12',
            'phone' => '081298765432',
            'business_type' => 'Sayur',
            'is_active' => true,
        ]);
    }
}
