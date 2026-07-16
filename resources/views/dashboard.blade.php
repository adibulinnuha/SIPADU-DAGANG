<x-layouts.app title="Dashboard">

    <div class="grid gap-6 md:grid-cols-4">

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">Total Pasar</p>
            <h2 class="mt-2 text-3xl font-bold">{{ $marketCount }}</h2>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">Transaksi Hari Ini</p>
            <h2 class="mt-2 text-3xl font-bold text-blue-600">
                {{ $todayRetributionCount }}
            </h2>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">Pendapatan Hari Ini</p>
            <h2 class="mt-2 text-3xl font-bold text-emerald-600">
                Rp {{ number_format($todayRetributionTotal,0,',','.') }}
            </h2>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">Pendapatan Bulan Ini</p>
            <h2 class="mt-2 text-3xl font-bold text-orange-600">
                Rp {{ number_format($monthRetributionTotal,0,',','.') }}
            </h2>
        </div>

    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-bold">
                Top 5 Pasar
            </h3>

            @forelse($topMarkets as $item)
                <div class="flex justify-between border-b py-2">
                    <span>{{ $item->market->name ?? '-' }}</span>
                    <span class="font-semibold">
                        Rp {{ number_format($item->total,0,',','.') }}
                    </span>
                </div>
            @empty
                <p class="text-slate-500">
                    Belum ada data.
                </p>
            @endforelse
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-bold">
                Pasar Belum Input Hari Ini
            </h3>

            @forelse($notSubmittedMarkets as $market)
                <div class="border-b py-2">
                    {{ $market->name }}
                </div>
            @empty
                <p class="text-emerald-600 font-semibold">
                    Semua pasar sudah input hari ini.
                </p>
            @endforelse
        </div>

    </div>

    <div class="mt-6 rounded-xl bg-white p-6 shadow">

        <div class="mb-4 flex items-center justify-between">

            <h3 class="text-lg font-bold">
                Transaksi Terbaru
            </h3>

            <a href="{{ route('retributions.index') }}"
               class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Lihat Semua
            </a>

        </div>

        <table class="min-w-full">

            <thead>
                <tr class="border-b">
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
                        <td colspan="4" class="py-8 text-center text-slate-500">
                            Belum ada transaksi.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-6 rounded-xl bg-white p-6 shadow">

        <h3 class="mb-4 text-lg font-bold">
            Shortcut Admin Korwil
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

</x-layouts.app>