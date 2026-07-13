<x-layouts.app title="Retribusi Harian">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Retribusi Harian</h1>
            <p class="text-sm text-slate-500">Daftar retribusi transaksi pedagang.</p>
        </div>

        <a href="{{ route('retributions.create') }}" class="inline-flex items-center rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            Tambah Retribusi
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('retributions.index') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Cari Pedagang</label>
                <input type="search" name="search" value="{{ request('search') }}" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="Nama atau nomor kios">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Pasar</label>
                <select name="market_id" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="">Semua Pasar</option>
                    @foreach($markets as $market)
                        <option value="{{ $market->id }}" {{ request('market_id') == $market->id ? 'selected' : '' }}>{{ $market->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Mulai</label>
                <input type="date" name="date_start" value="{{ request('date_start') }}" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal Akhir</label>
                <input type="date" name="date_end" value="{{ request('date_end') }}" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('retributions.index') }}" class="ml-3 rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50 text-left text-sm uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pasar</th>
                    <th class="px-4 py-3">Pedagang</th>
                    <th class="px-4 py-3">Jumlah</th>
                    <th class="px-4 py-3">Metode</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
                @forelse($retributions as $retribution)
                    <tr>
                        <td class="px-4 py-3">{{ $loop->iteration + ($retributions->currentPage() - 1) * $retributions->perPage() }}</td>
                        <td class="px-4 py-3">{{ $retribution->retribution_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $retribution->market->name }}</td>
                        <td class="px-4 py-3">{{ $retribution->trader->name }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($retribution->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $retribution->payment_method }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('retributions.edit', $retribution) }}" class="rounded bg-slate-100 px-3 py-1 text-sm text-slate-700 hover:bg-slate-200">Edit</a>
                            <form action="{{ route('retributions.destroy', $retribution) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Hapus retribusi ini?')" class="ml-2 rounded bg-red-600 px-3 py-1 text-sm font-semibold text-white hover:bg-red-700">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada data retribusi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $retributions->links() }}
    </div>
</x-layouts.app>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-lg font-semibold">
            Modul Retribusi Harian
        </p>

        <p class="text-gray-500 mt-2">
            Dalam pengembangan...
        </p>
    </div>

</x-layouts.app>