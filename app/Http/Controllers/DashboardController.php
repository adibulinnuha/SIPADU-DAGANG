/*
|--------------------------------------------------------------------------
| Data Grafik 7 Hari Terakhir
|--------------------------------------------------------------------------
*/

$dailyRevenue = Retribution::query()
    ->selectRaw('DATE(retribution_date) as date')
    ->selectRaw('SUM(amount) as total')
    ->whereDate('retribution_date', '>=', now()->subDays(6))
    ->groupBy('date')
    ->orderBy('date')
    ->get();

$chartLabels = [];
$chartValues = [];

for ($i = 6; $i >= 0; $i--) {

    $date = now()->subDays($i)->toDateString();

    $chartLabels[] = now()->subDays($i)->translatedFormat('d M');

    $chartValues[] = optional(
        $dailyRevenue->firstWhere('date', $date)
    )->total ?? 0;
}