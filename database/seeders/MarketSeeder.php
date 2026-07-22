<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            ['name' => 'REJOMULYO', 'code' => 'RJM01'],
            ['name' => 'TAMBAK LOROK', 'code' => 'TBL01'],
            ['name' => 'WARU INDAH', 'code' => 'WI01'],
            ['name' => 'REJOMULYO IB', 'code' => 'RJMIB01'],
            ['name' => 'DARGO', 'code' => 'DRG01'],
            ['name' => 'BUBAKAN', 'code' => 'BBK01'],
            ['name' => 'KARIMATA 1', 'code' => 'KRM01'],
            ['name' => 'KARIMATA 2', 'code' => 'KRM02'],
            ['name' => 'DARGO 2', 'code' => 'DRG02'],
            ['name' => 'LANGGAR', 'code' => 'LGR01'],
            ['name' => 'WARU INDAH 1', 'code' => 'WI02'],
            ['name' => 'WARU INDAH 2', 'code' => 'WI03'],
        ];

        foreach ($markets as $market) {
            Market::create([
                'name' => $market['name'],
                'code' => $market['code'],
                'is_active' => true,
            ]);
        }
    }
}
