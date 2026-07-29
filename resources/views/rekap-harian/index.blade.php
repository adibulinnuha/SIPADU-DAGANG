<x-layouts.app title="Rekap Harian">

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Rekap Harian
            </h1>

            <p class="text-sm text-gray-500">
                Rekap transaksi retribusi per pasar.
            </p>
        </div>

    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">

        <form method="GET" class="flex flex-wrap items-end gap-4">

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                    Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal"
                    value="{{ $tanggal }}"
                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>

            <div class="flex gap-3">

                <button
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700 active:scale-95">

                    Tampilkan

                </button>

                <a href="{{ route('rekap-harian.export', ['tanggal'=>$tanggal]) }}"
                   class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-emerald-700 active:scale-95">

                    Export Excel

                </a>

            </div>

        </form>

    </div>

    {{-- KPI CARDS --}}
    <div class="grid gap-6 md:grid-cols-2">

        <div class="kpi-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Total Transaksi
                </p>
                <div class="rounded-xl bg-blue-100 p-2.5 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            <h2 class="stat-value mt-3 text-3xl font-bold tracking-tight text-slate-800 dark:text-white">
                {{ number_format($grandTransaksi) }}
            </h2>
            <div class="mt-2 flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Transaksi</span>
            </div>
        </div>

        <div class="kpi-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Total Pendapatan
                </p>
                <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V5m0 15v-3"/>
                    </svg>
                </div>
            <h2 class="stat-value mt-3 text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($grandTotal,0,',','.') }}
            </h2>
            <div class="mt-2 flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Penerimaan</span>
            </div>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">

        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">

            <thead class="bg-slate-100 dark:bg-slate-700/50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        No
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Pasar
                    </th>

                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Total
                    </th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">

                @forelse($rekap as $item)

                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30">

                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                            {{ $loop->iteration }}
                        </td>

                        <td class="px-6 py-4 text-sm font-medium text-slate-800 dark:text-white">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ strtoupper(substr($item['market'], 0, 1)) }}
                                </span>
                                {{ $item['market'] }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-800 dark:text-white">
                            Rp {{ number_format($item['total'],0,',','.') }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3"
                            class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">

                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Belum ada data retribusi pada tanggal ini.</span>
                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

            <tfoot class="bg-slate-50 dark:bg-slate-700/30 font-bold">

                <tr>

                    <td colspan="2" class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
                        GRAND TOTAL
                    </td>

                    <td class="px-6 py-4 text-right text-sm text-slate-800 dark:text-white">
                        {{ number_format($grandTransaksi) }} transaksi &mdash; Rp {{ number_format($grandTotal,0,',','.') }}
                    </td>

                </tr>

            </tfoot>

        </table>

    </div>

{{-- Styles - reuse dashboard kpi-card --}}
<style>
    .kpi-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
    .stat-value { font-variant-numeric: tabular-nums; }
</style>

</x-layouts.app>
