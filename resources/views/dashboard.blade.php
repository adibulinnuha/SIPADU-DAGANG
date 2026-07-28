<x-layouts.app title="Dashboard">

<!-- Chart.js CDN -->
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .kpi-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
    .quick-action { transition: all 0.2s ease; }
    .quick-action:hover { transform: translateY(-2px); box-shadow: 0 8px 16px -4px rgba(0,0,0,0.12); }
    .workflow-step { transition: all 0.3s ease; }
    .workflow-step:hover { transform: scale(1.05); }
    .progress-bar { transition: width 1s ease-in-out; }
    .stat-value { font-variant-numeric: tabular-nums; }
    @keyframes pulse-soft { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .pulse-soft { animation: pulse-soft 2s ease-in-out infinite; }
</style>
@endpush

<div class="space-y-6">

    {{-- ================================================================ --}}
    {{-- 1. ENTERPRISE HEADER --}}
    {{-- ================================================================ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 p-6 text-white shadow-xl dark:from-blue-900 dark:via-blue-800 dark:to-indigo-900">
        <!-- Decorative pattern -->
        <div class="pointer-events-none absolute -right-6 -top-6 h-48 w-48 rounded-full bg-white/5 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-8 -left-8 h-36 w-36 rounded-full bg-white/5 blur-xl"></div>

        <div class="relative flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <div class="flex items-center gap-4">
                    <div class="rounded-xl bg-white/20 p-3 shadow-inner">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">SIPADU-DAGANG</h1>
                        <p class="text-sm text-blue-200">Sistem Informasi Pendataan Retribusi Pasar</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-3 py-1 text-xs font-medium text-emerald-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 pulse-soft"></span>
                        Dashboard Monitoring Dinas Perdagangan
                    </span>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 md:items-end">
                <div class="rounded-xl bg-white/15 px-4 py-2 text-sm font-medium shadow-inner backdrop-blur-sm">
                    <svg class="-mt-0.5 mr-1.5 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>

                @auth
                <div class="flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-sm backdrop-blur-sm">
                    <span class="hidden sm:inline">Selamat datang,</span>
                    <span class="font-semibold">{{ auth()->user()->name }}</span>
                    <span class="h-4 w-px bg-white/30"></span>
                    <span class="text-blue-200">{{ auth()->user()->role?->label() ?? auth()->user()->role }}</span>
                </div>
                @endauth

                <a href="{{ route('retributions.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 font-semibold text-blue-700 shadow-lg transition-all hover:bg-blue-50 hover:shadow-xl active:scale-95 dark:bg-slate-800 dark:text-blue-300 dark:hover:bg-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Input Retribusi
                </a>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 2. KPI CARDS (8 Cards) --}}
    {{-- ================================================================ --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        @php
            $kpiCards = [
                ['label' => 'Total Pasar', 'value' => $marketCount, 'icon' => 'M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9', 'color' => 'blue', 'trend' => 'Pasar Aktif', 'trendUp' => true],
                ['label' => 'Retribusi Hari Ini', 'value' => number_format($todayRetributionCount), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z', 'color' => 'indigo', 'trend' => 'Input Masuk', 'trendUp' => true],
                ['label' => 'Nominal Hari Ini', 'value' => 'Rp ' . number_format($todayRetributionTotal,0,',','.'), 'icon' => 'M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V5m0 15v-3', 'color' => 'emerald', 'trend' => 'Penerimaan', 'trendUp' => true],
                ['label' => 'Verifikasi Pending', 'value' => number_format($pendingVerificationCount), 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber', 'trend' => 'Menunggu', 'trendUp' => $pendingVerificationCount > 0],
                ['label' => 'Verifikasi Disetujui', 'value' => number_format($approvedVerificationCount), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green', 'trend' => 'Terverifikasi', 'trendUp' => true],
                ['label' => 'Total Bendel', 'value' => number_format($totalBendelCount), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z', 'color' => 'purple', 'trend' => 'Dokumen', 'trendUp' => true],
                ['label' => 'Total Petugas', 'value' => number_format($petugasCount), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'cyan', 'trend' => 'Terdaftar', 'trendUp' => true],
                ['label' => 'Progress Hari Ini', 'value' => $progressToday . '%', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'color' => 'rose', 'trend' => $progressToday . '% Complete', 'trendUp' => $progressToday > 50],
            ];

            $colorMap = [
                'blue' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'icon' => 'bg-blue-100 dark:bg-blue-800/50', 'iconText' => 'text-blue-600 dark:text-blue-300', 'badge' => 'bg-blue-100 dark:bg-blue-800/50 text-blue-700 dark:text-blue-300', 'border' => 'border-blue-100 dark:border-blue-800/30'],
                'indigo' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'icon' => 'bg-indigo-100 dark:bg-indigo-800/50', 'iconText' => 'text-indigo-600 dark:text-indigo-300', 'badge' => 'bg-indigo-100 dark:bg-indigo-800/50 text-indigo-700 dark:text-indigo-300', 'border' => 'border-indigo-100 dark:border-indigo-800/30'],
                'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'icon' => 'bg-emerald-100 dark:bg-emerald-800/50', 'iconText' => 'text-emerald-600 dark:text-emerald-300', 'badge' => 'bg-emerald-100 dark:bg-emerald-800/50 text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-100 dark:border-emerald-800/30'],
                'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'icon' => 'bg-amber-100 dark:bg-amber-800/50', 'iconText' => 'text-amber-600 dark:text-amber-300', 'badge' => 'bg-amber-100 dark:bg-amber-800/50 text-amber-700 dark:text-amber-300', 'border' => 'border-amber-100 dark:border-amber-800/30'],
                'green' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'icon' => 'bg-green-100 dark:bg-green-800/50', 'iconText' => 'text-green-600 dark:text-green-300', 'badge' => 'bg-green-100 dark:bg-green-800/50 text-green-700 dark:text-green-300', 'border' => 'border-green-100 dark:border-green-800/30'],
                'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'icon' => 'bg-purple-100 dark:bg-purple-800/50', 'iconText' => 'text-purple-600 dark:text-purple-300', 'badge' => 'bg-purple-100 dark:bg-purple-800/50 text-purple-700 dark:text-purple-300', 'border' => 'border-purple-100 dark:border-purple-800/30'],
                'cyan' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'icon' => 'bg-cyan-100 dark:bg-cyan-800/50', 'iconText' => 'text-cyan-600 dark:text-cyan-300', 'badge' => 'bg-cyan-100 dark:bg-cyan-800/50 text-cyan-700 dark:text-cyan-300', 'border' => 'border-cyan-100 dark:border-cyan-800/30'],
                'rose' => ['bg' => 'bg-rose-50 dark:bg-rose-900/30', 'icon' => 'bg-rose-100 dark:bg-rose-800/50', 'iconText' => 'text-rose-600 dark:text-rose-300', 'badge' => 'bg-rose-100 dark:bg-rose-800/50 text-rose-700 dark:text-rose-300', 'border' => 'border-rose-100 dark:border-rose-800/30'],
            ];
        @endphp

        @foreach($kpiCards as $card)
        @php $c = $colorMap[$card['color']]; @endphp
        <div class="kpi-card rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <div class="rounded-xl {{ $c['icon'] }} p-2.5 {{ $c['iconText'] }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <h2 class="stat-value text-2xl font-bold tracking-tight text-slate-800 dark:text-white">{{ $card['value'] }}</h2>
            </div>
            <div class="mt-2 flex items-center gap-1.5">
                @if($card['trendUp'])
                <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                @else
                <svg class="h-3.5 w-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                @endif
                <span class="text-xs font-medium {{ $card['trendUp'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $card['trend'] }}
                </span>
            </div>
        </div>
        @endforeach

    </div>

    {{-- ================================================================ --}}
    {{-- 3. CHARTS ROW (3 Charts) --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-3">

        <!-- Daily Revenue Line Chart -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Pendapatan 7 Hari</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Grafik penerimaan harian</p>
                </div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">Mingguan</span>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Market Bar Chart -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Revenue per Pasar</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Bulan {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">Bulanan</span>
            </div>
            <div class="h-64">
                <canvas id="marketBarChart"></canvas>
            </div>
        </div>

        <!-- Distribution Donut Chart -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Distribusi Retribusi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Per jenis retribusi</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Komposisi</span>
            </div>
            <div class="h-64">
                <canvas id="donutChart"></canvas>
            </div>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- 4. QUICK ACTIONS --}}
    {{-- ================================================================ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Aksi Cepat</h3>
        <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Pintasan menu operasional SIPADU-DAGANG</p>

        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">

            @php
                $actions = [
                    ['route' => 'retributions.create', 'label' => 'Input Retribusi', 'desc' => 'Tambah data retribusi baru', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'color' => 'blue'],
                    ['route' => 'verifications.index', 'label' => 'Verifikasi', 'desc' => 'Verifikasi data retribusi', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
                    ['route' => 'bendel.index', 'label' => 'Generate ERET', 'desc' => 'Buat dokumen ERET', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z', 'color' => 'purple'],
                    ['route' => 'bendel.index', 'label' => 'Generate Bendel', 'desc' => 'Bundel dokumen retribusi', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'amber'],
                    ['route' => 'rekap-harian.index', 'label' => 'Rekap Harian', 'desc' => 'Lihat rekap harian', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z', 'color' => 'rose'],
                    ['route' => 'retributions.export', 'label' => 'Export Excel', 'desc' => 'Download data ke Excel', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4', 'color' => 'cyan'],
                    ['route' => '#', 'label' => 'Backup DB', 'desc' => 'Cadangan database', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4', 'color' => 'slate'],
                ];

                $actionColors = [
                    'blue' => 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 border-blue-200 dark:border-blue-800/30 text-blue-700 dark:text-blue-300',
                    'emerald' => 'bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 border-emerald-200 dark:border-emerald-800/30 text-emerald-700 dark:text-emerald-300',
                    'purple' => 'bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/20 dark:hover:bg-purple-900/40 border-purple-200 dark:border-purple-800/30 text-purple-700 dark:text-purple-300',
                    'amber' => 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 border-amber-200 dark:border-amber-800/30 text-amber-700 dark:text-amber-300',
                    'rose' => 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 border-rose-200 dark:border-rose-800/30 text-rose-700 dark:text-rose-300',
                    'cyan' => 'bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-900/20 dark:hover:bg-cyan-900/40 border-cyan-200 dark:border-cyan-800/30 text-cyan-700 dark:text-cyan-300',
                    'slate' => 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-700/30 dark:hover:bg-slate-700/50 border-slate-200 dark:border-slate-700/30 text-slate-700 dark:text-slate-300',
                ];
            @endphp

            @foreach($actions as $act)
            <a href="{{ $act['route'] === '#' ? '#' : route($act['route']) }}"
               class="quick-action flex flex-col items-center rounded-xl border p-4 text-center {{ $actionColors[$act['color']] }} shadow-sm">
                <svg class="mb-2 h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $act['icon'] }}"/>
                </svg>
                <span class="text-sm font-semibold">{{ $act['label'] }}</span>
                <span class="mt-0.5 text-[10px] opacity-75">{{ $act['desc'] }}</span>
            </a>
            @endforeach

        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 5. RECENT ACTIVITIES + WORKFLOW PROGRESS --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-3">

        <!-- Recent Activities -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktivitas Terbaru</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">10 transaksi terakhir</p>
                </div>
                <a href="{{ route('retributions.index') }}"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">
                    Lihat Semua
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-xs uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <th class="py-3 pr-2 font-medium">Tanggal</th>
                            <th class="py-3 pr-2 font-medium">Pasar</th>
                            <th class="py-3 pr-2 font-medium">Petugas</th>
                            <th class="py-3 pr-2 font-medium text-right">Nominal</th>
                            <th class="py-3 font-medium text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $trx)
                        <tr class="border-b transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50">
                            <td class="py-3 pr-2 text-slate-600 dark:text-slate-300">
                                {{ $trx->retribution_date->format('d/m/Y') }}
                            </td>
                            <td class="py-3 pr-2 font-medium text-slate-700 dark:text-slate-200">
                                {{ $trx->market->name ?? '-' }}
                            </td>
                            <td class="py-3 pr-2 text-slate-600 dark:text-slate-300">
                                {{ $trx->recorder->name ?? '-' }}
                            </td>
                            <td class="py-3 pr-2 text-right font-semibold text-slate-800 dark:text-white">
                                Rp {{ number_format($trx->amount ?? $trx->items->sum('amount'),0,',','.') }}
                            </td>
                            <td class="py-3 text-center">
                                @php
                                    $statusBadges = [
                                        'draft' => ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                                        'submitted' => ['bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
                                        'verified' => ['bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
                                        'approved' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'],
                                        'locked' => ['bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
                                    ];
                                    $badgeClass = $statusBadges[$trx->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase {{ $badgeClass[0] }}">
                                    {{ $trx->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Belum ada transaksi</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Workflow Progress -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Workflow Retribusi</h3>
            <p class="mb-5 text-xs text-slate-500 dark:text-slate-400">Progres alur kerja retribusi hari ini</p>

            <div class="space-y-6">
                @php
                    $wfSteps = [
                        ['status' => 'draft', 'label' => 'Input', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'color' => 'slate'],
                        ['status' => 'submitted', 'label' => 'Verifikasi', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'blue'],
                        ['status' => 'verified', 'label' => 'Approve', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'emerald'],
                        ['status' => 'locked', 'label' => 'Lock', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'color' => 'purple'],
                    ];

                    $wfColors = [
                        'slate' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-300', 'active' => 'bg-slate-600 dark:bg-slate-500'],
                        'blue' => ['bg' => 'bg-blue-100 dark:bg-blue-900/50', 'text' => 'text-blue-600 dark:text-blue-300', 'active' => 'bg-blue-600 dark:bg-blue-500'],
                        'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/50', 'text' => 'text-emerald-600 dark:text-emerald-300', 'active' => 'bg-emerald-600 dark:bg-emerald-500'],
                        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/50', 'text' => 'text-purple-600 dark:text-purple-300', 'active' => 'bg-purple-600 dark:bg-purple-500'],
                    ];
                @endphp

                <div class="relative flex flex-col items-center">
                    @foreach($wfSteps as $i => $step)
                    @php
                        $count = $workflowStats[$step['status']] ?? 0;
                        $c = $wfColors[$step['color']];
                        $isActive = $count > 0;
                    @endphp
                    <div class="workflow-step flex w-full items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl {{ $isActive ? $c['bg'] . ' ' . $c['text'] . ' ring-2 ring-offset-2 ring-offset-white dark:ring-offset-slate-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500' }} {{ $isActive ? 'ring-' . $step['color'] . '-400 dark:ring-' . $step['color'] . '-600' : '' }}">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $step['label'] }}</span>
                                <span class="text-lg font-bold {{ $isActive ? $c['text'] : 'text-slate-400 dark:text-slate-500' }}">{{ number_format($count) }}</span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                @php
                                    $maxVal = max($workflowStats) ?: 1;
                                    $pct = ($count / $maxVal) * 100;
                                @endphp
                                <div class="progress-bar h-full rounded-full {{ $isActive ? 'bg-' . $step['color'] . '-500 dark:bg-' . $step['color'] . '-400' : 'bg-slate-200 dark:bg-slate-600' }}"
                                     style="width: {{ max($pct, $count > 0 ? 8 : 0) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @if (!$loop->last)
                    <div class="flex h-6 w-12 items-center justify-center">
                        <svg class="h-5 w-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- 6. MONITORING: Top Markets + Not Submitted --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-2">

        <!-- Top Pasar -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Top 5 Pasar</h3>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Berdasarkan total retribusi tertinggi</p>

            @forelse($topMarkets as $rank => $item)
            <div class="flex items-center gap-3 border-b py-3 last:border-0 dark:border-slate-700">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                    {{ $rank + 1 }}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $item['market'] ?? '-' }}</p>
                </div>
                <p class="text-sm font-bold text-slate-800 dark:text-white">
                    Rp {{ number_format($item['total'],0,',','.') }}
                </p>
            </div>
            @empty
            <div class="flex flex-col items-center gap-2 py-8 text-slate-500 dark:text-slate-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/>
                </svg>
                <span>Belum ada data.</span>
            </div>
            @endforelse
        </div>

        <!-- Belum Input -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Pasar Belum Input</h3>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Pasar yang belum melakukan input hari ini</p>

            @forelse($notSubmittedMarkets as $market)
            <div class="flex items-center justify-between border-b py-3 dark:border-slate-700">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $market->name }}</span>
                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                    Belum Input
                </span>
            </div>
            @empty
            <div class="flex flex-col items-center gap-2 py-8">
                <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold text-emerald-600 dark:text-emerald-400">Semua pasar sudah input hari ini.</p>
            </div>
            @endforelse
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- 7. MARKET SUMMARY with Progress Bars --}}
    {{-- ================================================================ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Ringkasan Pasar</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Target vs Realisasi Bulan {{ now()->translatedFormat('F Y') }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-xs uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="py-3 pr-4 font-medium">Pasar</th>
                        <th class="py-3 pr-4 font-medium text-right">Target</th>
                        <th class="py-3 pr-4 font-medium text-right">Realisasi</th>
                        <th class="py-3 font-medium text-right">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketSummaries as $summary)
                    <tr class="border-b transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50">
                        <td class="py-3.5 pr-4 font-medium text-slate-700 dark:text-slate-200">{{ $summary['market'] }}</td>
                        <td class="py-3.5 pr-4 text-right text-slate-600 dark:text-slate-300">
                            Rp {{ number_format($summary['target'],0,',','.') }}
                        </td>
                        <td class="py-3.5 pr-4 text-right font-semibold text-slate-800 dark:text-white">
                            Rp {{ number_format($summary['realization'],0,',','.') }}
                        </td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                    <div class="progress-bar h-full rounded-full {{ $summary['percentage'] >= 80 ? 'bg-emerald-500' : ($summary['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                         style="width: {{ $summary['percentage'] }}%"></div>
                                </div>
                                <span class="min-w-[3rem] text-xs font-bold {{ $summary['percentage'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($summary['percentage'] >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $summary['percentage'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Belum ada data pasar.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 8. SYSTEM INFORMATION --}}
    {{-- ================================================================ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-4 text-lg font-bold text-slate-800 dark:text-white">Informasi Sistem</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">

            @php
                $sysCards = [
                    ['label' => 'Laravel Version', 'value' => $systemInfo['laravel_version'], 'icon' => 'M3 3v18h18M7 14l4-4 3 3 5-6', 'color' => 'red'],
                    ['label' => 'PHP Version', 'value' => $systemInfo['php_version'], 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'color' => 'indigo'],
                    ['label' => 'Database', 'value' => ucfirst($systemInfo['database']), 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4', 'color' => 'emerald'],
                    ['label' => 'Last Backup', 'value' => $systemInfo['last_backup'], 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'color' => 'amber'],
                    ['label' => 'Environment', 'value' => ucfirst($systemInfo['environment']), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'purple'],
                ];

                $sysColors = [
                    'red' => 'border-red-100 dark:border-red-900/30 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300',
                    'indigo' => 'border-indigo-100 dark:border-indigo-900/30 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-300',
                    'emerald' => 'border-emerald-100 dark:border-emerald-900/30 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-300',
                    'amber' => 'border-amber-100 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-300',
                    'purple' => 'border-purple-100 dark:border-purple-900/30 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-300',
                ];
            @endphp

            @foreach($sysCards as $sys)
            <div class="flex items-center gap-3 rounded-xl border p-4 {{ $sysColors[$sys['color']] }}">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sys['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider opacity-75">{{ $sys['label'] }}</p>
                    <p class="text-sm font-bold">{{ $sys['value'] }}</p>
                </div>
            </div>
            @endforeach

        </div>
    </div>

</div>

{{-- ================================================================ --}}
{{-- CHARTS JAVASCRIPT --}}
{{-- ================================================================ --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    // Set default Chart.js font
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

    // ─── Line Chart: Daily Revenue ─────────────────────────
    const lineCtx = document.getElementById('revenueChart');
    if (lineCtx) {
        const lineData = @json($chartValues);
        const hasLineData = lineData.some(v => v > 0);

        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Pendapatan',
                    data: hasLineData ? lineData : [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#3b82f6',
                    backgroundColor: isDark
                        ? 'rgba(59,130,246,0.1)'
                        : 'rgba(59,130,246,0.08)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return 'Rp ' + Number(ctx.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // ─── Bar Chart: Revenue by Market ──────────────────────
    const barCtx = document.getElementById('marketBarChart');
    if (barCtx) {
        const barLabels = @json($barChartLabels);
        const barValues = @json($barChartValues);
        const hasBarData = barValues.some(v => v > 0);

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: hasBarData ? barLabels : ['Belum Ada Data'],
                datasets: [{
                    label: 'Pendapatan',
                    data: hasBarData ? barValues : [0],
                    backgroundColor: hasBarData
                        ? ['#6366f1', '#8b5cf6', '#a855f7', '#c084fc', '#d8b4fe', '#818cf8', '#6366f1', '#4f46e5']
                        : ['#e2e8f0'],
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return 'Rp ' + Number(ctx.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            font: { size: 9 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // ─── Donut Chart: Distribution ─────────────────────────
    const donutCtx = document.getElementById('donutChart');
    if (donutCtx) {
        const donutLabels = @json($donutLabels);
        const donutValues = @json($donutValues);
        const hasDonutData = donutValues.some(v => v > 0);

        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899'];

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: hasDonutData ? donutLabels : ['Belum Ada Data'],
                datasets: [{
                    data: hasDonutData ? donutValues : [1],
                    backgroundColor: hasDonutData ? colors.slice(0, donutLabels.length) : ['#e2e8f0'],
                    borderColor: isDark ? '#1e293b' : '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true,
                            font: { size: 10 },
                            boxWidth: 8,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': Rp ' + Number(ctx.raw).toLocaleString('id-ID') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

</x-layouts.app>
