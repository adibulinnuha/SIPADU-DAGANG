<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\Trader;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'marketCount' => Market::query()->count(),
            'traderCount' => Trader::query()->count(),
            'todayRetributionCount' => Retribution::query()
                ->whereDate('retribution_date', today())
                ->count(),
            'todayRetributionTotal' => Retribution::query()
                ->whereDate('retribution_date', today())
                ->sum('amount'),
        ]);
    }
}
