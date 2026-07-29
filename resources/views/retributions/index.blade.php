<x-layouts.app title="Data Retribusi">

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Data Retribusi
            </h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">
                Pengelolaan transaksi retribusi SIPADU-DAGANG
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('retributions.export-template') }}"
               class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-700">
                Download ERET JULI
            </a>

            <a href="{{ route('retributions.create') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700">
                + Tambah Retribusi
            </a>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="grid gap-6 md:grid-cols-2">
        <div class="kpi-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Retribusi</p>
            <h2 class="stat-value mt-3 text-3xl font-bold tracking-tight text-slate-800 dark:text-white">
                {{ $retributions->total() }}
            </h2>
        </div>

        <div class="kpi-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Nominal</p>
            <h2 class="stat-value mt-3 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($totalAmount ?? 0,0,',','.') }}
            </h2>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <form method="GET" action="{{ route('retributions.index') }}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Pasar</label>
                    <select name="market_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <option value="">Semua Pasar</option>
                        @foreach($markets as $market)
                        <option value="{{ $market->id }}" @selected(request('market_id')==$market->id)>
                            {{ $market->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                </div>

                <div class="flex items-end gap-2">
                    <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('retributions.index') }}"
                       class="rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-600 dark:text-slate-200 dark:hover:bg-slate-500">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-100 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pasar</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Jenis</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nominal</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($retributions as $item)
                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">
                            {{ $item->retribution_date ? $item->retribution_date->format('d/m/Y') : $item->retribution_date }}
                        </td>
                        <td class="px-5 py-4 text-sm font-medium text-slate-800 dark:text-white">
                            {{ $item->market->name ?? '-' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">
                            {{ $item->jenis_retribusi }}
                        </td>
                        <td class="px-5 py-4 text-right text-sm font-semibold text-slate-800 dark:text-white">
                            Rp {{ number_format($item->amount,0,',','.') }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    'submitted' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    'verified' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                    'locked' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                ];
                                $statusClass = $statusClasses[$item->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                            @endphp
                            <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase {{ $statusClass }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('retributions.edit', $item) }}"
                                   class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-600">
                                    Edit
                                </a>
                                <form action="{{ route('retributions.destroy', $item) }}" method="POST"
                                      onsubmit="return confirm('Hapus retribusi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Belum ada data retribusi</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
            {{ $retributions->links() }}
        </div>
    </div>

</div>

<style>
    .kpi-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
    .stat-value { font-variant-numeric: tabular-nums; }
</style>

</x-layouts.app>

