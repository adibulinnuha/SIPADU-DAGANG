<x-layouts.app title="Master Pasar">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Master Pasar
            </h1>

            <p class="mt-1 text-slate-500">
                Kelola data pasar yang terdaftar di SIPADU-DAGANG.
            </p>
        </div>

        <a href="{{ route('markets.create') }}"
           class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white shadow hover:bg-blue-700">
            + Tambah Pasar
        </a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">

        <table class="min-w-full divide-y divide-slate-200">

            <thead class="bg-slate-100">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">No</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Kode</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Nama Pasar</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Alamat</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Telepon</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-slate-700">Status</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-slate-700">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">

                @forelse($markets as $market)

                <tr class="hover:bg-slate-50">

                    <td class="px-6 py-4">{{ $loop->iteration }}</td>

                    <td class="px-6 py-4 font-medium">
                        {{ $market->code }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $market->name }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $market->address }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $market->phone }}
                    </td>

                    <td class="px-6 py-4 text-center">

                        @if($market->is_active)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Aktif
                            </span>
                        @else
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                Nonaktif
                            </span>
                        @endif

                    </td>

                    <td class="px-6 py-4">

                        <div class="flex justify-center gap-2">

                            <a href="{{ route('markets.edit', $market->id) }}"
                               class="rounded bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600">
                                Edit
                            </a>

                            <form action="{{ route('markets.destroy', $market->id) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm('Hapus data pasar ini?')"
                                    class="rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                                    Hapus
                                </button>
                            </form>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                        Belum ada data pasar.
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</x-layouts.app>