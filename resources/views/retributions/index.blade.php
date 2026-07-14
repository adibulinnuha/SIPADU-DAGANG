<x-layouts.app title="Retribusi Harian">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Retribusi Harian
            </h1>
            <p class="text-slate-500">
                Daftar transaksi retribusi harian.
            </p>
        </div>

        <a href="{{ route('retributions.create') }}"
            class="rounded-lg bg-blue-600 px-5 py-3 font-bold text-white shadow hover:bg-blue-700">
            + Tambah Retribusi
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-green-300 bg-green-100 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif
    <div class="mb-6 grid gap-4 md:grid-cols-3">

    <div class="rounded-xl bg-blue-600 p-5 text-white shadow">
        <p class="text-sm opacity-80">Total Transaksi</p>
        <h2 class="mt-2 text-3xl font-bold">
            {{ number_format($totalTransactions) }}
        </h2>
    </div>

    <div class="rounded-xl bg-emerald-600 p-5 text-white shadow">
        <p class="text-sm opacity-80">Total Nominal</p>
        <h2 class="mt-2 text-3xl font-bold">
            Rp {{ number_format($totalAmount,0,',','.') }}
        </h2>
    </div>

    <div class="rounded-xl bg-amber-500 p-5 text-white shadow">
        <p class="text-sm opacity-80">Pasar Menyetor</p>
        <h2 class="mt-2 text-3xl font-bold">
            {{ $totalMarkets }}
        </h2>
    </div>

</div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">

        <form method="GET" action="{{ route('retributions.index') }}">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div>
                    <label class="mb-2 block font-semibold">Pasar</label>

                    <select name="market_id" class="w-full rounded-lg border p-3">

                        <option value="">Semua Pasar</option>

                        @foreach($markets as $market)
                            <option value="{{ $market->id }}"
                                {{ request('market_id') == $market->id ? 'selected' : '' }}>
                                {{ $market->name }}
                            </option>
                        @endforeach

                    </select>

                </div>

                <div>
                    <label class="mb-2 block font-semibold">Tanggal Mulai</label>

                    <input
                        type="date"
                        name="date_start"
                        value="{{ request('date_start') }}"
                        class="w-full rounded-lg border p-3">
                </div>

                <div>
                    <label class="mb-2 block font-semibold">Tanggal Akhir</label>

                    <input
                        type="date"
                        name="date_end"
                        value="{{ request('date_end') }}"
                        class="w-full rounded-lg border p-3">
                </div>

            </div>

            <div class="mt-5 flex justify-end gap-3">

                <button
                    type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-3 font-bold text-white hover:bg-blue-700">
                    Filter
                </button>

                <a
                    href="{{ route('retributions.index') }}"
                    class="rounded-lg bg-gray-300 px-5 py-3 font-bold hover:bg-gray-400">
                    Reset
                </a>

            </div>

        </form>

    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow">

        <table class="min-w-full">

            <thead class="bg-slate-900 text-white">

                <tr>

                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Pasar</th>
                    <th class="px-4 py-3 text-left">Jenis Retribusi</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 text-left">Metode</th>
                    <th class="px-4 py-3 text-center">Aksi</th>

                </tr>

            </thead>

            <tbody>

                @forelse($retributions as $retribution)

                    <tr class="border-b hover:bg-slate-50">

                        <td class="px-4 py-3">
                            {{ $retributions->firstItem() + $loop->index }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $retribution->retribution_date->format('d/m/Y') }}
                        </td>

                        <td class="px-4 py-3 font-semibold">
                            {{ $retribution->market?->name }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $retribution->jenis_retribusi }}
                        </td>

                        <td class="px-4 py-3 text-right font-bold text-green-700">
                            Rp {{ number_format($retribution->amount,0,',','.') }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $retribution->payment_method }}
                        </td>

                        <td class="px-4 py-3">

                            <div class="flex justify-center gap-2">

                                <a
                                    href="{{ route('retributions.edit', $retribution) }}"
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-600">
                                    Edit
                                </a>

                                <form
                                    action="{{ route('retributions.destroy', $retribution) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus data ini?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                        Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7" class="py-10 text-center text-gray-500">
                            Belum ada data retribusi.
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