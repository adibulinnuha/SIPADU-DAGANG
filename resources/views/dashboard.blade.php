<x-layouts.app title="Dashboard">

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">
                Total Pasar
            </p>

            <h2 class="mt-3 text-4xl font-bold text-slate-800">
                {{ $marketCount }}
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Pasar aktif dalam sistem
            </p>
        </div>

        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
            <p class="text-sm text-blue-700">
                Transaksi Hari Ini
            </p>

            <h2 class="mt-3 text-4xl font-bold text-blue-700">
                {{ $todayRetributionCount }}
            </h2>

            <p class="mt-2 text-sm text-blue-600">
                Transaksi telah dicatat
            </p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-6 shadow-sm">
            <p class="text-sm text-emerald-700">
                Pendapatan Hari Ini
            </p>

            <h2 class="mt-3 text-3xl font-bold text-emerald-700">
                Rp {{ number_format($todayRetributionTotal,0,',','.') }}
            </h2>

            <p class="mt-2 text-sm text-emerald-600">
                Total penerimaan hari ini
            </p>
        </div>

        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-6 shadow-sm">
            <p class="text-sm text-orange-700">
                Pendapatan Bulan Ini
            </p>

            <h2 class="mt-3 text-3xl font-bold text-orange-700">
                Rp {{ number_format($monthRetributionTotal,0,',','.') }}
            </h2>

            <p class="mt-2 text-sm text-orange-600">
                Akumulasi bulan berjalan
            </p>
        </div>

    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-bold">
                Top 5 Pasar
            </h3>

            @forelse($topMarkets as $item)

                <div class="flex justify-between border-b py-2">

                    <span>
                        {{ $item->market->name ?? '-' }}
                    </span>

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

                <p class="font-semibold text-emerald-600">
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

                    <th class="py-3 text-left">
                        Tanggal
                    </th>

                    <th class="py-3 text-left">
                        Pasar
                    </th>

                    <th class="py-3 text-left">
                        Jenis
                    </th>

                    <th class="py-3 text-right">
                        Nominal
                    </th>

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

    <div class="mt-6 rounded-xl bg-white p-6 shadow">

        <h3 class="mb-4 text-lg font-bold">
            Shortcut Admin Korwil
        </h3>

        <div class="flex flex-wrap gap-3">

            <a href="{{ route('retributions.create') }}"
               class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700">

                + Retribusi

            </a>

            <a href="{{ route('rekap-harian.index') }}"
               class="rounded-lg bg-emerald-600 px-5 py-3 text-white hover:bg-emerald-700">

                Rekap Harian

            </a>

            <a href="{{ route('verifications.index') }}"
               class="rounded-lg bg-amber-500 px-5 py-3 text-white hover:bg-amber-600">

                Verifikasi

            </a>

            <a href="{{ route('bendel.index') }}"
               class="rounded-lg bg-slate-700 px-5 py-3 text-white hover:bg-slate-800">

                Bendel

            </a>

        </div>

    </div>

</x-layouts.app>