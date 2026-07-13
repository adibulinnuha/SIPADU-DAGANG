<x-layouts.app title="Master Petugas">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Master Petugas</h1>
            <p class="text-sm text-slate-500">Kelola akun petugas aplikasi.</p>
        </div>

        <a href="{{ route('petugas.create') }}" class="rounded bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">
            Tambah Petugas
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">No</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Nama</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Email</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Peran</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($petugas as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $item->email }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $item->role->label() }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium">
                            <a href="{{ route('petugas.edit', $item) }}" class="mr-2 text-slate-700 hover:text-slate-900">Edit</a>

                            <form action="{{ route('petugas.destroy', $item) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada data petugas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
