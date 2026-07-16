<x-layouts.app title="Retribusi Harian">

    <div class="mb-6 flex items-center justify-between">

        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Retribusi Harian
            </h1>

            <p class="mt-1 text-slate-500">
                Monitoring transaksi retribusi pasar SIPADU-DAGANG.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('retributions.export', request()->query()) }}"
               class="flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 font-semibold text-white shadow hover:bg-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </a>

            <a href="{{ route('retributions.create') }}"
               class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white shadow hover:bg-blue-700">
                + Tambah Retribusi
            </a>
        </div>

    </div>


    {{-- Statistik --}}

    <div class="mb-6 grid gap-6 md:grid-cols-3">

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">
                Total Transaksi
            </p>

            <h2 class="mt-2 text-3xl font-bold text-slate-900">
                {{ $totalTransactions }}
            </h2>
        </div>


        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">
                Total Pendapatan
            </p>

            <h2 class="mt-2 text-3xl font-bold text-emerald-600">
                Rp {{ number_format($totalAmount,0,',','.') }}
            </h2>
        </div>


        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm text-slate-500">
                Jumlah Pasar
            </p>

            <h2 class="mt-2 text-3xl font-bold text-blue-600">
                {{ $totalMarkets }}
            </h2>
        </div>

    </div>



    {{-- Filter --}}

    <div class="mb-6 rounded-xl bg-white p-6 shadow">

        <form method="GET" action="{{ route('retributions.index') }}"
              class="grid gap-4 md:grid-cols-4">


            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">
                    Pasar
                </label>

                <select name="market_id"
                    class="w-full rounded-lg border-slate-300">

                    <option value="">
                        Semua Pasar
                    </option>

                    @foreach($markets as $market)

                    <option value="{{ $market->id }}"
                        @selected(request('market_id') == $market->id)>
                        {{ $market->name }}
                    </option>

                    @endforeach

                </select>

            </div>


            <div>
                <label class="mb-1 block text-sm font-semibold">
                    Dari Tanggal
                </label>

                <input type="date"
                       name="date_start"
                       value="{{ request('date_start') }}"
                       class="w-full rounded-lg border-slate-300">
            </div>


            <div>
                <label class="mb-1 block text-sm font-semibold">
                    Sampai Tanggal
                </label>

                <input type="date"
                       name="date_end"
                       value="{{ request('date_end') }}"
                       class="w-full rounded-lg border-slate-300">
            </div>


            <div class="flex items-end gap-2">

                <button
                    class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">
                    Cari
                </button>

                <a href="{{ route('retributions.index') }}"
                   class="rounded-lg bg-slate-200 px-5 py-2 text-slate-700">
                    Reset
                </a>

                <a href="{{ route('retributions.export', request()->query()) }}"
                   class="flex items-center gap-1 rounded-lg bg-emerald-600 px-5 py-2 text-white hover:bg-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>

            </div>


        </form>

    </div>



    {{-- Tabel --}}

    <div class="overflow-hidden rounded-xl bg-white shadow">

        <table class="min-w-full divide-y divide-slate-200">


            <thead class="bg-slate-100">

                <tr>

                    <th class="px-6 py-3 text-left text-sm font-semibold">
                        No
                    </th>

                    <th class="px-6 py-3 text-left text-sm font-semibold">
                        Tanggal
                    </th>

                    <th class="px-6 py-3 text-left text-sm font-semibold">
                        Pasar
                    </th>

                    <th class="px-6 py-3 text-left text-sm font-semibold">
                        Jenis
                    </th>

                    <th class="px-6 py-3 text-left text-sm font-semibold">
                        Nominal
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-slate-100">


            @forelse($retributions as $item)

                <tr class="hover:bg-slate-50">

                    <td class="px-6 py-4">
                        {{ $loop->iteration }}
                    </td>


                    <td class="px-6 py-4">
                        {{ $item->retribution_date }}
                    </td>


                    <td class="px-6 py-4">
                        {{ $item->market->name ?? '-' }}
                    </td>


                    <td class="px-6 py-4">
                        {{ $item->jenis_retribusi }}
                    </td>


                    <td class="px-6 py-4 font-semibold">
                        Rp {{ number_format($item->amount,0,',','.') }}
                    </td>


                </tr>


            @empty

                <tr>

                    <td colspan="5"
                        class="px-6 py-10 text-center text-slate-500">

                        Belum ada transaksi retribusi.

                    </td>

                </tr>


            @endforelse


            </tbody>


        </table>


    </div>


    <div class="mt-5">
        {{ $retributions->links() }}
    </div>


</x-layouts.app>