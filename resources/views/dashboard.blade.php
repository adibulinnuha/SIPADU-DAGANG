<x-layouts.app title="Dashboard">

@php
    // Helper untuk membangun URL sorting pada Tabel ERET Harian.
    if (! function_exists('eretSortUrl')) {
        function eretSortUrl(string $col, string $currentSort, string $currentDir): string
        {
            $dir = ($col === $currentSort && $currentDir === 'asc') ? 'desc' : 'asc';
            $params = array_merge(
                request()->except(['sort', 'dir', 'page']),
                ['sort' => $col, 'dir' => $dir]
            );
            return url()->current().'?'.http_build_query($params);
        }
    }
@endphp

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

    /* ── Spreadsheet ERET ──────────────────────────────── */
    .eret-scroll {
        overflow: auto;
        max-height: 620px;
        position: relative;
        border-bottom: 1px solid rgb(226 232 240);
    }
    .dark .eret-scroll { border-bottom-color: rgb(51 65 85 / 0.5); }
    .eret-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 100%;
        background: #fff;
        font-size: 0.8125rem;
    }
    .dark .eret-table { background: #1e293b; }
    .eret-table thead th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.6875rem;
        letter-spacing: 0.05em;
        padding: 12px 14px;
        white-space: nowrap;
        border-bottom: 2px solid #cbd5e1;
        border-right: 1px solid #e2e8f0;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .dark .eret-table thead th {
        background: #334155;
        color: #cbd5e1;
        border-bottom-color: #475569;
        border-right-color: #475569;
    }
    .eret-table tbody td,
    .eret-table tfoot td {
        padding: 6px 8px;
        white-space: nowrap;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
    }
    .dark .eret-table tbody td,
    .dark .eret-table tfoot td { border-bottom-color: #334155; border-right-color: #334155; }
    .eret-table tbody tr:nth-child(even) { background: #f8fafc; }
    .dark .eret-table tbody tr:nth-child(even) { background: #1e293b; }
    .eret-table tbody tr:hover { background: #eff6ff; }
    .dark .eret-table tbody tr:hover { background: #172554; }
    .eret-num { text-align: right !important; font-variant-numeric: tabular-nums; }
    .eret-text { color: #334155; }
    .dark .eret-text { color: #e2e8f0; }
    .eret-date { color: #64748b; }
    .dark .eret-date { color: #94a3b8; }
    .eret-total { background: #fefce8 !important; font-weight: 700; }
    .dark .eret-total { background: #3f3f46 !important; }
    .eret-footer td {
        background: #e2e8f0 !important;
        color: #0f172a;
        font-weight: 700;
        border-top: 2px solid #94a3b8;
        position: sticky;
        bottom: 0;
        z-index: 5;
    }
    .dark .eret-footer td { background: #475569 !important; color: #f1f5f9; border-top-color: #64748b; }
    .eret-sticky-col {
        position: sticky;
        left: 0;
        z-index: 8;
        background: #f8fafc;
        font-weight: 600;
        color: #64748b;
        min-width: 48px;
        text-align: center !important;
    }
    .dark .eret-sticky-col { background: #1e293b; color: #94a3b8; }
    .eret-table thead .eret-sticky-col { z-index: 20; background: #f1f5f9; }
    .dark .eret-table thead .eret-sticky-col { background: #334155; }
    .eret-table tfoot .eret-sticky-col { z-index: 20; background: #e2e8f0; }
    .dark .eret-table tfoot .eret-sticky-col { background: #475569; }
    .eret-sortable a { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .eret-sortable a:hover { color: #2563eb; }
    .eret-sortable a::after {
        content: ''; width: 0; height: 0;
        border-left: 4px solid transparent; border-right: 4px solid transparent;
        border-top: 5px solid #94a3b8; opacity: 0.5;
    }
    .eret-sorted a { color: #2563eb; }
    .eret-sorted a::after { border-top-color: #2563eb; opacity: 1; }
    .eret-sorted[data-dir='asc'] a::after { transform: rotate(180deg); }
    .eret-empty { color: #64748b; }
    .eret-selected { outline: 2px solid #2563eb; outline-offset: -2px; background-color: #dbeafe !important; }
    .dark .eret-selected { background-color: #1e3a8a !important; }
    .eret-cell-input {
        width: 100%;
        min-width: 90px;
        padding: 6px 8px;
        border: 1px solid transparent;
        border-radius: 4px;
        background: transparent;
        font-size: 0.8125rem;
        font-variant-numeric: tabular-nums;
        text-align: right;
        color: #334155;
        transition: all 0.15s ease;
    }
    .dark .eret-cell-input { color: #e2e8f0; }
    .eret-cell-input:focus {
        outline: 2px solid #2563eb;
        outline-offset: -1px;
        background: #fff;
        border-color: #2563eb;
    }
    .dark .eret-cell-input:focus { background: #1e293b; }
    .eret-cell-input.is-invalid { outline: 2px solid #ef4444; background: #fef2f2; }
    .dark .eret-cell-input.is-invalid { background: #450a0a; }
    .eret-select-input {
        width: 100%;
        min-width: 110px;
        padding: 6px 8px;
        border: 1px solid transparent;
        border-radius: 4px;
        background: transparent;
        font-size: 0.8125rem;
        color: #334155;
        cursor: pointer;
    }
    .dark .eret-select-input { color: #e2e8f0; background: transparent; }
    .eret-select-input:focus { outline: 2px solid #2563eb; background: #fff; }
    .dark .eret-select-input:focus { background: #1e293b; }
    .eret-cell-input.is-invalid,
    .eret-select-input.is-invalid { outline: 2px solid #ef4444; background: #fef2f2; }
    .dark .eret-cell-input.is-invalid,
    .dark .eret-select-input.is-invalid { background: #450a0a; }
    .row-error {
        color: #dc2626;
        font-size: 0.7rem;
        margin-top: 2px;
        display: none;
    }
    .row-error.visible { display: block; }
    .eret-row-header { font-weight: 600; color: #475569; }
    .dark .eret-row-header { color: #cbd5e1; }
    .eret-actions { display: flex; gap: 4px; align-items: center; }
    .eret-btn {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        transition: all 0.15s;
    }
.eret-btn-add { background: #dbeafe; color: #1d4ed8; }
    .eret-btn-add:hover { background: #bfdbfe; }
    .eret-btn-del { background: #fee2e2; color: #b91c1c; }
    .eret-btn-del:hover { background: #fecaca; }
    .eret-row-drag { cursor: grab; user-select: none; }
    .eret-row-drag:active { cursor: grabbing; }
    .eret-drag-handle { opacity: 0.5; margin-right: 2px; }
    .eret-drop-target td { background: #dbeafe !important; }
    .dark .eret-drop-target td { background: #1e3a8a !important; }
    .eret-scroll { overflow-anchor: none; }
</style>
@endpush

<div class="space-y-6">

    {{-- ================================================================ --}}
    {{-- 1. ENTERPRISE HEADER --}}
    {{-- ================================================================ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 p-6 text-white shadow-xl dark:from-blue-900 dark:via-blue-800 dark:to-indigo-900">
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
                'blue' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'icon' => 'bg-blue-100 dark:bg-blue-800/50', 'iconText' => 'text-blue-600 dark:text-blue-300'],
                'indigo' => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'icon' => 'bg-indigo-100 dark:bg-indigo-800/50', 'iconText' => 'text-indigo-600 dark:text-indigo-300'],
                'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'icon' => 'bg-emerald-100 dark:bg-emerald-800/50', 'iconText' => 'text-emerald-600 dark:text-emerald-300'],
                'amber' => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'icon' => 'bg-amber-100 dark:bg-amber-800/50', 'iconText' => 'text-amber-600 dark:text-amber-300'],
                'green' => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'icon' => 'bg-green-100 dark:bg-green-800/50', 'iconText' => 'text-green-600 dark:text-green-300'],
                'purple' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'icon' => 'bg-purple-100 dark:bg-purple-800/50', 'iconText' => 'text-purple-600 dark:text-purple-300'],
                'cyan' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'icon' => 'bg-cyan-100 dark:bg-cyan-800/50', 'iconText' => 'text-cyan-600 dark:text-cyan-300'],
                'rose' => ['bg' => 'bg-rose-50 dark:bg-rose-900/30', 'icon' => 'bg-rose-100 dark:bg-rose-800/50', 'iconText' => 'text-rose-600 dark:text-rose-300'],
            ];
        @endphp

        @foreach($kpiCards as $card)
        @php $c = $colorMap[$card['color']]; @endphp
        <div class="kpi-card rounded-2xl border {{ $c['bg'] }} p-5 shadow-sm">
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
    {{-- 3. FILTER DASHBOARD ERET --}}
    {{-- ================================================================ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $filters['tanggal'] }}"
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">Pasar</label>
                <select name="market_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Semua Pasar</option>
                    @foreach($markets as $market)
                    <option value="{{ $market->id }}" @selected($filters['market_id'] == $market->id)>{{ $market->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[220px] flex-1">
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">Pencarian</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Pasar, Nomor Setor, atau Petugas..."
                       class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="flex gap-3">
                <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700 active:scale-95">Terapkan</button>
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-600 dark:text-slate-200 dark:hover:bg-slate-500">Reset</a>
            </div>
        </form>
    </div>

{{-- ================================================================ --}}
    {{-- 4. KERJA HARIAN — SPREADSHEET ERET (TABEL A + TABEL B) --}}
    {{-- ================================================================ --}}

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">ERET — Pekerjaan Harian</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Input retribusi harian per pasar (spreadsheet)</p>
        </div>
    </div>

    {{-- PRS (Partial Reusable Sheet) — rendered twice below, once per table. --}}
    @php
        $sheetConfigs = [
            [
                'id' => 'manual',
                'title' => 'Tabel A — Retribusi Manual',
                'subtitle' => 'Pengumpulan setoran retribusi manual per pasar',
                'accent' => 'blue',
                'rows' => $manualSpreadsheetRows,
                'totalLabel' => 'Subtotal Manual',
            ],
            [
                'id' => 'eret',
                'title' => 'Tabel B — E-Retribusi',
                'subtitle' => 'Setoran retribusi elektronik (E-Retribusi) per pasar',
                'accent' => 'emerald',
                'rows' => $eretSpreadsheetRows,
                'totalLabel' => 'Subtotal E-Retribusi',
            ],
        ];
    @endphp

    @foreach($sheetConfigs as $sheet)
    <div
        x-data='createEretSpreadsheet({
            gridId: "eret-grid-{{ $sheet['id'] }}",
            colKeys: @json($colKeys),
            markets: @json($markets->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values()),
            petugas: @json($petugas->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values()),
petugasApiUrl: @json(route('markets.active-petugas', ['market' => '__MARKET__'])),
            tanggal: @json($filters['tanggal']),
            csrfToken: @json(csrf_token()),
            apiUrl: @json(route('dashboard.eret.save')),
            entryType: @json($sheet['id']),
            initialRows: @json($sheet['rows'])
        })'
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
        x-init="init()">

        {{-- Spreadsheet Toolbar / Header Info --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ $sheet['title'] }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $sheet['subtitle'] }} — {{ \Carbon\Carbon::parse($filters['tanggal'])->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
{{-- Status input indicator --}}
                <span class="rounded-full {{ $sheet['accent'] === 'emerald' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' }} px-3 py-1 text-xs font-medium">
                    <span x-text="rowCount"></span> baris di spreadsheet
                </span>

                {{-- Draft indicator (localStorage autosave) --}}
                <span x-show="draftState === 'draft'" x-cloak
                      class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 pulse-soft"></span>Draft
                </span>

                {{-- Saving... indicator --}}
                <span x-show="draftState === 'saving'" x-cloak
                      class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500 pulse-soft"></span>Menyimpan...
                </span>

                {{-- Saved indicator --}}
                <span x-show="draftState === 'saved'" x-cloak
                      class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Tersimpan
                </span>

                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    <span x-text="saveState"></span>
                </span>

                {{-- Discard draft button --}}
                <button type="button" @click="discardDraft()" x-show="hasDraft" x-cloak
                        title="Buang draft yang belum disimpan"
                        class="inline-flex items-center rounded-lg border border-orange-300 px-3 py-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-50 dark:border-orange-800 dark:text-orange-300 dark:hover:bg-orange-900/40">
                    Buang Draft
                </button>

                {{-- Clipboard actions --}}
                <button type="button" @click="onCopy()" title="Salin (Ctrl+C)"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Salin
                </button>
                <button type="button" @click="onCut()" title="Potong (Ctrl+X)"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Potong
                </button>
                <button type="button" @click="pasteClipboard()" title="Tempel (Ctrl+V)"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Tempel
                </button>

                {{-- Row selection actions --}}
                <button type="button" @click="duplicateSelected()" title="Duplikat baris terpilih"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Duplikat
                </button>
                <button type="button" @click="removeSelected()" title="Hapus baris terpilih"
                        class="inline-flex items-center rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/40">
                    Hapus Terpilih
                </button>

                <button type="button" @click="addBlankRow()"
                        class="inline-flex items-center gap-1.5 rounded-lg {{ $sheet['accent'] === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }} px-4 py-2 text-sm font-semibold text-white shadow transition active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Tambah Baris
                </button>

                <button type="button" @click="saveRows()" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-700 active:scale-95 disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Semua'"></span>
                </button>
            </div>
        </div>

        {{-- Save result feedback --}}
        <div x-show="saveMessage" x-cloak
             class="mx-6 mt-4 rounded-lg border px-4 py-3 text-sm"
             :class="saveSuccess ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'">
            <span x-text="saveMessage"></span>
        </div>

        {{-- SPREADSHEET INPUT --}}
        <div class="eret-scroll" id="eret-grid-{{ $sheet['id'] }}" x-ref="scroll">
            <table class="eret-table w-full text-sm">
                <thead>
                    <tr>
                        <th class="eret-sticky-col">No</th>
                        <th class="min-w-[140px]">Pasar</th>
                        <th class="min-w-[140px]">Petugas</th>
                        <th class="min-w-[120px]">Nomor Setor</th>
                        @foreach($colKeys as $colKey)
                        <th class="eret-num min-w-[110px]">{{ $dashboardColumns[$colKey]['label'] }}</th>
                        @endforeach
                        <th class="eret-num eret-total min-w-[110px]">Total</th>
                        <th class="min-w-[80px]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="height: 0;" :style="{ height: topSpacerHeight + 'px' }">
                        <td colspan="{{ count($colKeys) + 6 }}"></td>
                    </tr>
                    <template x-for="(row, rowIndex) in gridRows" :key="row.id ?? rowIndex">
                        <tr
                            :class="{
                                'eret-selected': isSelected(rowIndex),
                                'eret-drop-target': isDropTarget(rowIndex)
                            }"
                            @click="toggleSelect(rowIndex, $event)"
                            @dblclick="startEdit(rowIndex, 0)"
                        >
                            <td class="eret-sticky-col eret-row-header eret-row-drag"
                                draggable="true"
                                @dragstart="onDragStart(rowIndex)"
                                @dragover.prevent="onDragOver(rowIndex)"
                                @drop.prevent="onDrop(rowIndex)"
                                @dragend="onDragEnd()"
                                title="Klik untuk pilih, seret untuk urutkan ulang">
                                <span class="eret-drag-handle cursor-grab">⠿</span>
                                <span x-text="rowIndex + 1"></span>
                            </td>
                            <td @click.stop>
<select :value="row.market_id"
                                        @change="onMarketChange(rowIndex, $event)"
                                        @dblclick="startEdit(rowIndex, 'market_id')"
                                        :title="cellError(rowIndex, 'market_id') || undefined"
                                        class="eret-select-input" :class="{'is-invalid': cellError(rowIndex, 'market_id')}">
                                    <option value="">-- Pilih Pasar --</option>
                                    <template x-for="m in markets" :key="m.id">
                                        <option :value="m.id" x-text="m.name"></option>
                                    </template>
                                </select>
                                <div class="row-error" :class="{'visible': cellError(rowIndex, 'market_id')}" x-text="cellError(rowIndex, 'market_id')"></div>
                            </td>
                            <td @click.stop>
                                <select :value="row.petugas_id"
                                        @change="onCellInput(rowIndex, 'petugas_id', $event)"
                                        @dblclick="startEdit(rowIndex, 'petugas_id')"
                                        class="eret-select-input">
                                    <option value="">-- Petugas --</option>
                                    <template x-for="p in petugasForRow(rowIndex)" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                                <div class="row-error" :class="{'visible': cellError(rowIndex, 'petugas_id')}" x-text="cellError(rowIndex, 'petugas_id')"></div>
                            </td>
                            <td :data-row="rowIndex" data-col="nomor_setor"
                                :class="{'eret-selected': isCellInRange(rowIndex, 0)}"
                                @mousedown="onCellMouseDown(rowIndex, 'nomor_setor', $event)"
                                @click.stop>
                                <div class="eret-cell-outer">
                                    <input type="text" :value="row.nomor_setor"
                                       @input="onCellInput(rowIndex, 'nomor_setor', $event)"
                                       @keydown="onCellKeydown(rowIndex, 'nomor_setor', $event)"
                                       @dblclick="startEdit(rowIndex, 'nomor_setor')"
                                       :title="cellError(rowIndex, 'nomor_setor') || undefined"
                                       placeholder="Nomor setor"
                                       class="eret-cell-input" :class="{'is-invalid': cellError(rowIndex, 'nomor_setor')}" style="text-align:left">
                                    <div class="row-error" :class="{'visible': cellError(rowIndex, 'nomor_setor')}" x-text="cellError(rowIndex, 'nomor_setor')"></div>
                                    <div class="eret-fill-handle" x-show="isActive(rowIndex, 0)"
                                        @mousedown.stop.prevent="startFillDrag(rowIndex, 0)"></div>
                                </div>
                            </td>
                            @foreach($colKeys as $colKey)
                            <td :data-row="rowIndex" data-col="{{ $colKey }}"
                                :class="{'eret-selected': isCellInRange(rowIndex, {{ $loop->index + 1 }})}"
                                @mousedown="onCellMouseDown(rowIndex, '{{ $colKey }}', $event)"
                                @click.stop>
                                <div class="eret-cell-outer">
                                    <input type="text" inputmode="decimal" :value="row.{{ $colKey }}"
                                       @input="onCellInput(rowIndex, '{{ $colKey }}', $event)"
                                       @keydown="onCellKeydown(rowIndex, '{{ $colKey }}', $event)"
                                       @dblclick="startEdit(rowIndex, '{{ $colKey }}')"
                                       :title="cellError(rowIndex, '{{ $colKey }}') || undefined"
                                       class="eret-cell-input" :class="{'is-invalid': cellError(rowIndex, '{{ $colKey }}')}">
                                <div class="row-error" :class="{'visible': cellError(rowIndex, '{{ $colKey }}')}" x-text="cellError(rowIndex, '{{ $colKey }}')"></div>
                                <div class="eret-fill-handle" x-show="isActive(rowIndex, {{ $loop->index + 1 }})"
                                    @mousedown.stop.prevent="startFillDrag(rowIndex, {{ $loop->index + 1 }})"></div>
                                </div>
                            </td>
                            @endforeach
                            <td class="eret-num eret-total font-bold" x-text="fmtCell(row.total)"></td>
                            <td>
                                <div class="eret-actions">
                                    <button type="button" @click="insertRowAt(rowIndex)" title="Sisipkan baris di atas"
                                            class="eret-btn eret-btn-add">+</button>
                                    <button type="button" @click="duplicateRow(rowIndex)" title="Duplikat baris"
                                            class="eret-btn eret-btn-add">⧉</button>
                                    <button type="button" @click="removeRow(rowIndex, $event)" title="Hapus baris"
                                            class="eret-btn eret-btn-del">🗑</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr style="height: 0;" :style="{ height: bottomSpacerHeight + 'px' }">
                        <td colspan="{{ count($colKeys) + 6 }}"></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="eret-footer">
                        <td class="eret-sticky-col" colspan="1">&nbsp;</td>
                        <td colspan="3">{{ $sheet['totalLabel'] }}</td>
                        @foreach($colKeys as $colKey)
                        <td class="eret-cell eret-num" x-text="fmtCell(colTotal('{{ $colKey }}'))"></td>
                        @endforeach
                        <td class="eret-cell eret-num eret-total font-bold" x-text="fmtCell(grandTotal)"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    💡 Enter = simpan & pindah ke bawah · Escape = batal · Tab = ke kanan · Shift+Tab = ke kiri · Arrow = navigasi · Ctrl+Enter = isi sel terpilih · Copy/Paste dari Excel didukung
                </p>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                    Grand Total: <span class="text-emerald-600 dark:text-emerald-400" x-text="fmtCell(grandTotal)"></span>
                </span>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ================================================================ --}}
    {{-- 4b. GRAND TOTAL SELURUH PASAR (Manual + E-Retribusi) --}}
    {{-- ================================================================ --}}
    <div class="overflow-hidden rounded-2xl border-2 border-slate-300 bg-slate-50 shadow-sm dark:border-slate-600 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
<div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">TOTAL SELURUH PASAR</h3>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Grand Total Seluruh Pasar — Gabungan Tabel A (Manual) + Tabel B (E-Retribusi) — sesuai template ERET</p>
            </div>
        </div>
        <div class="overflow-x-auto p-1">
            <table class="min-w-full divide-y divide-slate-300 dark:divide-slate-700">
                <thead class="bg-slate-200 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Sum</th>
                        @foreach($colKeys as $colKey)
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ $dashboardColumns[$colKey]['label'] }}</th>
                        @endforeach
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr class="bg-blue-50/60 dark:bg-blue-900/20">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Tabel A — Manual</td>
                        @foreach($colKeys as $colKey)
                        <td class="px-5 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($manualGrandTotals[$colKey] ?? 0,0,',','.') }}</td>
                        @endforeach
                        <td class="px-5 py-3 text-right text-sm font-bold text-blue-700 dark:text-blue-300">Rp {{ number_format($manualGrandTotal,0,',','.') }}</td>
                    </tr>
                    <tr class="bg-emerald-50/60 dark:bg-emerald-900/20">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Tabel B — E-Retribusi</td>
                        @foreach($colKeys as $colKey)
                        <td class="px-5 py-3 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($eretGrandTotals[$colKey] ?? 0,0,',','.') }}</td>
                        @endforeach
                        <td class="px-5 py-3 text-right text-sm font-bold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($eretGrandTotal,0,',','.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-300 dark:bg-slate-600">
                        <td class="px-5 py-3 text-sm font-bold text-slate-800 dark:text-white">GRAND TOTAL</td>
                        @foreach($colKeys as $colKey)
                        <td class="px-5 py-3 text-right text-sm font-bold text-slate-800 dark:text-white">Rp {{ number_format($grandTotals[$colKey] ?? 0,0,',','.') }}</td>
                        @endforeach
                        <td class="px-5 py-3 text-right text-sm font-extrabold text-slate-900 dark:text-white">Rp {{ number_format($grandTotal,0,',','.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 5. TABEL REKAP (TABEL B) — REALTIME --}}
    {{-- ================================================================ --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Rekap Per Pasar</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ringkasan transaksi & status workflow per pasar (realtime)</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-100 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pasar</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Jumlah Transaksi</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Retribusi</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status Workflow</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($rekapRows as $rekap)
                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-4 text-sm font-medium text-slate-800 dark:text-white">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ strtoupper(substr($rekap['market'], 0, 1)) }}
                                </span>
                                {{ $rekap['market'] }}
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800 dark:text-white">{{ number_format($rekap['total_transaksi']) }}</td>
                        <td class="px-5 py-4 text-right text-sm font-bold text-slate-800 dark:text-white">Rp {{ number_format($rekap['total_retribusi'],0,',','.') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-center gap-1.5">
                                @php
                                    $wfBadges = [
                                        'draft' => ['Draft', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                                        'submitted' => ['Submitted', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
                                        'verified' => ['Verified', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
                                        'approved' => ['Approved', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'],
                                        'locked' => ['Locked', 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
                                    ];
                                @endphp
                                @foreach($rekap['status'] as $statusKey => $count)
                                    @if($count > 0)
                                        @php $badge = $wfBadges[$statusKey] ?? [$statusKey, 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300']; @endphp
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase {{ $badge[1] }}">{{ $badge[0] }} · {{ $count }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                                <span>Belum ada data rekap.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 6. CHARTS ROW (3 Charts) --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Pendapatan 7 Hari</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Grafik penerimaan harian</p>
                </div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">Mingguan</span>
            </div>
            <div class="h-64"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Revenue per Pasar</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Bulan {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">Bulanan</span>
            </div>
            <div class="h-64"><canvas id="marketBarChart"></canvas></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-1">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Distribusi Retribusi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Per jenis retribusi</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Komposisi</span>
            </div>
            <div class="h-64"><canvas id="donutChart"></canvas></div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 7. QUICK ACTIONS --}}
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
                <svg class="mb-2 h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $act['icon'] }}"/></svg>
                <span class="text-sm font-semibold">{{ $act['label'] }}</span>
                <span class="mt-0.5 text-[10px] opacity-75">{{ $act['desc'] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 8. RECENT ACTIVITIES + WORKFLOW PROGRESS --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktivitas Terbaru</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">10 transaksi terakhir</p>
                </div>
                <a href="{{ route('retributions.index') }}"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">Lihat Semua</a>
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
                            <td class="py-3 pr-2 text-slate-600 dark:text-slate-300">{{ $trx->retribution_date->format('d/m/Y') }}</td>
                            <td class="py-3 pr-2 font-medium text-slate-700 dark:text-slate-200">{{ $trx->market->name ?? '-' }}</td>
                            <td class="py-3 pr-2 text-slate-600 dark:text-slate-300">{{ $trx->recorder->name ?? '-' }}</td>
                            <td class="py-3 pr-2 text-right font-semibold text-slate-800 dark:text-white">Rp {{ number_format($trx->amount ?? $trx->items->sum('amount'),0,',','.') }}</td>
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
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase {{ $badgeClass[0] }}">{{ $trx->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                                    <span>Belum ada transaksi</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

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
                    @foreach($wfSteps as $step)
                    @php
                        $count = $workflowStats[$step['status']] ?? 0;
                        $c = $wfColors[$step['color']];
                        $isActive = $count > 0;
                    @endphp
                    <div class="workflow-step flex w-full items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl {{ $isActive ? $c['bg'] . ' ' . $c['text'] . ' ring-2 ring-offset-2 ring-offset-white dark:ring-offset-slate-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500' }} {{ $isActive ? 'ring-' . $step['color'] . '-400 dark:ring-' . $step['color'] . '-600' : '' }}">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/></svg>
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
                                <div class="progress-bar h-full rounded-full {{ $isActive ? 'bg-' . $step['color'] . '-500 dark:bg-' . $step['color'] . '-400' : 'bg-slate-200 dark:bg-slate-600' }}" style="width: {{ max($pct, $count > 0 ? 8 : 0) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @if (!$loop->last)
                    <div class="flex h-6 w-12 items-center justify-center">
                        <svg class="h-5 w-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 9. DASHBOARD SUMMARY WIDGETS (moved to bottom) --}}
    {{-- ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Top 5 Pasar</h3>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Berdasarkan total retribusi tertinggi</p>
            @forelse($topMarkets as $rank => $item)
            <div class="flex items-center gap-3 border-b py-3 last:border-0 dark:border-slate-700">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">{{ $rank + 1 }}</div>
                <div class="flex-1"><p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $item['market'] ?? '-' }}</p></div>
                <p class="text-sm font-bold text-slate-800 dark:text-white">Rp {{ number_format($item['total'],0,',','.') }}</p>
            </div>
            @empty
            <div class="flex flex-col items-center gap-2 py-8 text-slate-500 dark:text-slate-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/></svg>
                <span>Belum ada data.</span>
            </div>
            @endforelse
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">Pasar Belum Input</h3>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Pasar yang belum melakukan input hari ini</p>
            @forelse($notSubmittedMarkets as $market)
            <div class="flex items-center justify-between border-b py-3 dark:border-slate-700">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $market->name }}</span>
                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">Belum Input</span>
            </div>
            @empty
            <div class="flex flex-col items-center gap-2 py-8">
                <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-semibold text-emerald-600 dark:text-emerald-400">Semua pasar sudah input hari ini.</p>
            </div>
            @endforelse
        </div>
    </div>

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
                        <td class="py-3.5 pr-4 text-right text-slate-600 dark:text-slate-300">Rp {{ number_format($summary['target'],0,',','.') }}</td>
                        <td class="py-3.5 pr-4 text-right font-semibold text-slate-800 dark:text-white">Rp {{ number_format($summary['realization'],0,',','.') }}</td>
                        <td class="py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                    <div class="progress-bar h-full rounded-full {{ $summary['percentage'] >= 80 ? 'bg-emerald-500' : ($summary['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $summary['percentage'] }}%"></div>
                                </div>
                                <span class="min-w-[3rem] text-xs font-bold {{ $summary['percentage'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($summary['percentage'] >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">{{ $summary['percentage'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
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
    {{-- 10. SYSTEM INFORMATION --}}
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
                <div class="flex-shrink-0"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sys['icon'] }}"/></svg></div>
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider opacity-75">{{ $sys['label'] }}</p>
                    <p class="text-sm font-bold">{{ $sys['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

@push('scripts')
<script>
// ─── Chart.js setup (DOM-dependent) ─────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

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
                    data: hasLineData ? lineData : [0,0,0,0,0,0,0],
                    borderColor: '#3b82f6',
                    backgroundColor: isDark ? 'rgba(59,130,246,0.1)' : 'rgba(59,130,246,0.08)',
                    borderWidth: 3, tension: 0.4, fill: true,
                    pointBackgroundColor: '#3b82f6', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Rp ' + Number(ctx.raw).toLocaleString('id-ID') } } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { font: { size: 10 }, callback: v => v >= 1000000 ? 'Rp ' + (v/1000000).toFixed(1) + 'jt' : v >= 1000 ? 'Rp ' + (v/1000).toFixed(0) + 'rb' : 'Rp ' + v } }
                }
            }
        });
    }

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
                    backgroundColor: hasBarData ? ['#6366f1','#8b5cf6','#a855f7','#c084fc','#d8b4fe','#818cf8','#6366f1','#4f46e5'] : ['#e2e8f0'],
                    borderRadius: 6, borderSkipped: false,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'Rp ' + Number(ctx.raw).toLocaleString('id-ID') } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: { grid: { color: gridColor }, ticks: { font: { size: 9 }, callback: v => v >= 1000000 ? 'Rp ' + (v/1000000).toFixed(1) + 'jt' : v >= 1000 ? 'Rp ' + (v/1000).toFixed(0) + 'rb' : 'Rp ' + v } }
                }
            }
        });
    }

    const donutCtx = document.getElementById('donutChart');
    if (donutCtx) {
        const donutLabels = @json($donutLabels);
        const donutValues = @json($donutValues);
        const hasDonutData = donutValues.some(v => v > 0);
        const colors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'];
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: hasDonutData ? donutLabels : ['Belum Ada Data'],
                datasets: [{
                    data: hasDonutData ? donutValues : [1],
                    backgroundColor: hasDonutData ? colors.slice(0, donutLabels.length) : ['#e2e8f0'],
                    borderColor: isDark ? '#1e293b' : '#ffffff', borderWidth: 2, hoverOffset: 8,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, font: { size: 10 }, boxWidth: 8 } },
                    tooltip: { callbacks: { label: ctx => { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); const pct = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0; return ctx.label + ': Rp ' + Number(ctx.raw).toLocaleString('id-ID') + ' (' + pct + '%)'; } } }
                }
            }
        });
    }
});
</script>
@endpush

</x-layouts.app>
