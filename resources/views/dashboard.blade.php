<x-layouts.app title="Dashboard">

<div class="space-y-6">

    <!-- Header SIPADU -->
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-blue-500 p-6 text-white shadow-lg">

        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">

            <div>
                <div class="flex items-center gap-3">

                    <div class="rounded-xl bg-white/20 p-3">
                        <svg class="h-8 w-8"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold">
                            SIPADU-DAGANG
                        </h1>

                        <p class="text-sm text-blue-100">
                            Sistem Informasi Pendataan Retribusi Pasar
                        </p>
                    </div>

                </div>

                <p class="mt-4 text-sm text-blue-100">
                    Dashboard Monitoring Dinas Perdagangan
                </p>

            </div>


            <div class="flex flex-col items-start gap-3 md:items-end">

                <div class="rounded-lg bg-white/20 px-4 py-2 text-sm">

                    {{ now()->translatedFormat('l, d F Y') }}

                </div>


                <a href="{{ route('retributions.create') }}"
                   class="rounded-lg bg-white px-5 py-3 font-semibold text-blue-700 shadow hover:bg-blue-50">

                    + Input Retribusi

                </a>

            </div>

        </div>

    </div>



    <!-- Statistik Utama -->

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">


        <!-- Total Pasar -->

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <p class="text-sm font-medium text-slate-500">
                    Total Pasar
                </p>

                <div class="rounded-lg bg-blue-100 p-2 text-blue-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/>

                    </svg>

                </div>

            </div>


            <h2 class="mt-4 text-4xl font-bold text-slate-800">
                {{ $marketCount }}
            </h2>


            <span class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">

                Pasar Aktif

            </span>

        </div>



        <!-- Transaksi Hari Ini -->

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6 shadow-sm">


            <div class="flex items-center justify-between">

                <p class="text-sm font-medium text-indigo-700">
                    Transaksi Hari Ini
                </p>

                <div class="rounded-lg bg-indigo-100 p-2 text-indigo-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>

                    </svg>

                </div>

            </div>


            <h2 class="mt-4 text-4xl font-bold text-indigo-700">

                {{ $todayRetributionCount }}

            </h2>


            <span class="mt-2 inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">

                Input Hari Ini

            </span>


        </div>
                <!-- Pendapatan Hari Ini -->

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <p class="text-sm font-medium text-emerald-700">
                    Pendapatan Hari Ini
                </p>

                <div class="rounded-lg bg-emerald-100 p-2 text-emerald-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V5m0 15v-3"/>

                    </svg>

                </div>

            </div>


            <h2 class="mt-4 text-3xl font-bold text-emerald-700">

                Rp {{ number_format($todayRetributionTotal,0,',','.') }}

            </h2>


            <span class="mt-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">

                Penerimaan Hari Ini

            </span>

        </div>



        <!-- Pendapatan Bulanan -->

        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <p class="text-sm font-medium text-orange-700">
                    Pendapatan Bulan Ini
                </p>


                <div class="rounded-lg bg-orange-100 p-2 text-orange-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M3 3v18h18M7 14l4-4 3 3 5-6"/>

                    </svg>

                </div>

            </div>


            <h2 class="mt-4 text-3xl font-bold text-orange-700">

                Rp {{ number_format($monthRetributionTotal,0,',','.') }}

            </h2>


            <span class="mt-2 inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-700">

                Akumulasi Bulanan

            </span>

        </div>


    </div>
<!-- Grafik Pendapatan 7 Hari -->

<div class="rounded-2xl border bg-white p-6 shadow-sm">

    <div class="mb-5 flex items-center justify-between">

        <div>

            <h3 class="text-lg font-bold text-slate-800">
                Pendapatan 7 Hari Terakhir
            </h3>

            <p class="text-sm text-slate-500">
                Monitoring penerimaan retribusi harian
            </p>

        </div>


        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">

            Mingguan

        </span>

    </div>


    <div class="h-80">

        <canvas id="revenueChart"></canvas>

    </div>

</div>


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('revenueChart');

new Chart(ctx, {

    type: 'line',

    data: {

        labels: @json($chartLabels),

        datasets: [{

            label: 'Pendapatan',

            data: @json($chartValues),

            borderWidth: 3,

            tension: 0.4,

            fill: true

        }]

    },


    options: {

        responsive: true,

        maintainAspectRatio: false,


        plugins: {

            legend: {

                display: false

            }

        },


        scales: {

            y: {

                ticks: {

                    callback: function(value){

                        return 'Rp ' + value.toLocaleString('id-ID');

                    }

                }

            }

        }

    }

});

</script>

@endpush


    <!-- Area Monitoring -->

    <div class="grid gap-6 lg:grid-cols-2">


        <!-- Top Pasar -->

        <div class="rounded-2xl border bg-white p-6 shadow-sm">

            <h3 class="mb-4 text-lg font-bold text-slate-800">
                Top 5 Pasar
            </h3>


            @forelse($topMarkets as $item)

                <div class="flex items-center justify-between border-b py-3">

                    <span class="font-medium text-slate-700">
                        {{ $item->market->name ?? '-' }}
                    </span>


                    <span class="font-semibold text-blue-700">

                        Rp {{ number_format($item->total,0,',','.') }}

                    </span>

                </div>

            @empty

                <p class="text-slate-500">
                    Belum ada data.
                </p>

            @endforelse

        </div>



        <!-- Belum Input -->

        <div class="rounded-2xl border bg-white p-6 shadow-sm">

            <h3 class="mb-4 text-lg font-bold text-slate-800">

                Pasar Belum Input Hari Ini

            </h3>


            @forelse($notSubmittedMarkets as $market)

                <div class="flex items-center justify-between border-b py-3">

                    <span>
                        {{ $market->name }}
                    </span>


                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs text-red-700">

                        Belum Input

                    </span>

                </div>


            @empty

                <p class="font-semibold text-emerald-600">

                    Semua pasar sudah input hari ini.

                </p>

            @endforelse


        </div>


    </div>



    <!-- Transaksi Terbaru -->

    <div class="rounded-2xl border bg-white p-6 shadow-sm">

        <div class="mb-4 flex items-center justify-between">

            <h3 class="text-lg font-bold">

                Transaksi Terbaru

            </h3>


            <a href="{{ route('retributions.index') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">

                Lihat Semua

            </a>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead>

                    <tr class="border-b text-sm text-slate-500">

                        <th class="py-3 text-left">Tanggal</th>
                        <th class="py-3 text-left">Pasar</th>
                        <th class="py-3 text-left">Jenis</th>
                        <th class="py-3 text-right">Nominal</th>

                    </tr>

                </thead>


                <tbody>

                @forelse($recentTransactions as $trx)

                    <tr class="border-b">

                        <td class="py-3">
                            {{ $trx->retribution_date->format('d-m-Y') }}
                        </td>

                        <td>
                            {{ $trx->market->name ?? '-' }}
                        </td>

                        <td>
                            {{ $trx->jenis_retribusi }}
                        </td>

                        <td class="text-right font-semibold">

                            Rp {{ number_format($trx->amount,0,',','.') }}

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4"
                            class="py-8 text-center text-slate-500">

                            Belum ada transaksi.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>



    <!-- Shortcut -->

    <div class="rounded-2xl border bg-white p-6 shadow-sm">

        <h3 class="mb-4 text-lg font-bold">
            Menu Cepat SIPADU-DAGANG
        </h3>


        <div class="flex flex-wrap gap-3">


            <a href="{{ route('retributions.create') }}"
               class="rounded-lg bg-blue-600 px-5 py-3 text-white">

                + Retribusi

            </a>


            <a href="{{ route('rekap-harian.index') }}"
               class="rounded-lg bg-emerald-600 px-5 py-3 text-white">

                Rekap Harian

            </a>


            <a href="{{ route('verifications.index') }}"
               class="rounded-lg bg-amber-500 px-5 py-3 text-white">

                Verifikasi

            </a>


            <a href="{{ route('bendel.index') }}"
               class="rounded-lg bg-slate-700 px-5 py-3 text-white">

                Bendel

            </a>


        </div>

    </div>


</div>

</x-layouts.app>