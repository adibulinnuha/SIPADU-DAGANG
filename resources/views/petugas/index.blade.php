<x-layouts.app title="Master Petugas">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Master Petugas</h1>
            <p class="text-slate-500">
                Kelola akun petugas SIPADU-DAGANG.
            </p>
        </div>

        <a href="{{ route('petugas.create') }}"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            + Tambah Petugas
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">No</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Nama</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Role</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($petugas as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">
                            {{ $petugas->firstItem() + $loop->index }}
                        </td>

                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $item->name }}
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            {{ $item->email }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ $item->role->label() }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('petugas.edit', $item) }}"
                                    class="rounded-lg bg-amber-500 px-3 py-2 text-sm text-white hover:bg-amber-600">
                                    Edit
                                </a>

                                <form action="{{ route('petugas.destroy', $item) }}" method="POST"
                                    onsubmit="return confirm('Hapus petugas ini?')">
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
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                            Belum ada data petugas.
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