<x-layouts.app title="Master Petugas">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Master Petugas</h1>
            <p class="text-slate-500">
                Kelola data petugas (Korwil / Juru Pungut) SIPADU-DAGANG.
            </p>
        </div>

        <a href="{{ route('petugas.create') }}"
            class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white shadow hover:bg-blue-700">
            + Tambah Petugas
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <form method="GET" action="{{ route('petugas.index') }}" class="flex gap-3">
            <input
                type="text"
                name="q"
                value="{{ $search ?? '' }}"
                placeholder="Cari nama, NIP, jabatan, atau pasar..."
                class="w-full max-w-md rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-slate-900 focus:outline-none"
            >
            <button
                type="submit"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Cari
            </button>
            @if($search)
                <a href="{{ route('petugas.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">No</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Nama</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">NIP</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Jabatan</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Pasar</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Juru Pungut</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($petugas as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $petugas->firstItem() + $loop->index }}
                        </td>

                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $item->name }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-600 font-mono">
                            {{ $item->nip ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $item->jabatan ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $item->market?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if($item->is_juru_pungut)
                                <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                    Ya
                                </span>
                            @else
                                <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                    Tidak
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if($item->is_active)
                                <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('petugas.edit', $item) }}"
                                    class="rounded-lg bg-amber-500 px-3 py-2 text-sm text-white hover:bg-amber-600">
                                    Edit
                                </a>

                                <form action="{{ route('petugas.destroy', $item) }}" method="POST"
                                    onsubmit="return confirm('Hapus petugas {{ $item->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            @if($search)
                                Tidak ditemukan petugas dengan kata kunci "{{ $search }}".
                            @else
                                Belum ada data petugas. <a href="{{ route('petugas.create') }}" class="text-blue-600 underline">Tambah sekarang</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-200 px-4 py-3">
            {{ $petugas->links() }}
        </div>
    </div>
</x-layouts.app>

