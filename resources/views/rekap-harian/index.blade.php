<x-layouts.app title="Rekap Harian">

    <div class="mb-6 flex items-center justify-between">

        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Rekap Harian
            </h1>

            <p class="mt-1 text-slate-500">
                Rekap transaksi retribusi per pasar.
            </p>
        </div>

    </div>


    <div class="mb-6 rounded-xl bg-white p-6 shadow">

        <form method="GET" class="flex flex-wrap items-end gap-4">

            <div>
                <label class="mb-2 block text-sm font-semibold">
                    Tanggal
                </label>

                <input
                    type="date"
                    name="tanggal"
                    value="{{ $tanggal }}"
                    class="rounded-lg border-slate-300">
            </div>


            <div class="flex gap-3">

                <button
                    class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700">

                    Tampilkan

                </button>


                <a href="{{ route('rekap-harian.export', ['tanggal'=>$tanggal]) }}"
                   class="rounded-lg bg-emerald-600 px-5 py-2 text-white hover:bg-emerald-700">

                    Export Excel

                </a>

            </div>

        </form>

    </div>



    <div class="mb-6 grid gap-6 md:grid-cols-2">

        <div class="rounded-xl bg-white p-6 shadow">

            <p class="text-sm text-slate-500">
                Total Transaksi
            </p>

            <h2 class="mt-2 text-3xl font-bold text-blue-600">
                {{ $grandTransaksi }}
            </h2>

        </div>


        <div class="rounded-xl bg-white p-6 shadow">

            <p class="text-sm text-slate-500">
                Total Pendapatan
            </p>

            <h2 class="mt-2 text-3xl font-bold text-emerald-600">

                Rp {{ number_format($grandTotal,0,',','.') }}

            </h2>

        </div>

    </div>



    <div class="overflow-hidden rounded-xl bg-white shadow">

        <table class="min-w-full divide-y divide-slate-200">

            <thead class="bg-slate-100">

                <tr>

                    <th class="px-6 py-3 text-left">
                        No
                    </th>

                    <th class="px-6 py-3 text-left">
                        Pasar
                    </th>

                    <th class="px-6 py-3 text-right">
                        Total
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-slate-100">

                @forelse($rekap as $item)

                    <tr>

                        <td class="px-6 py-4">
                            {{ $loop->iteration }}
                        </td>


                        <td class="px-6 py-4 font-medium">
                            {{ $item['market'] }}
                        </td>


                        <td class="px-6 py-4 text-right font-semibold">

                            Rp {{ number_format($item['total'],0,',','.') }}

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="4"
                            class="px-6 py-8 text-center text-slate-500">

                            Belum ada data retribusi pada tanggal ini.

                        </td>

                    </tr>

                @endforelse

            </tbody>


            <tfoot class="bg-slate-100 font-bold">

                <tr>

                    <td colspan="1" class="px-6 py-4">
                        GRAND TOTAL
                    </td>


                    <td class="px-6 py-4 text-right">
                        {{ $grandTransaksi }} transaksi
                    </td>

                </tr>

            </tfoot>


        </table>

    </div>


</x-layouts.app>