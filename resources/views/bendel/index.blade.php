<x-layouts.app title="Bendel">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Bendel
            </h1>

            <p class="mt-1 text-slate-500">
                Generate dan kelola Bendel Retribusi.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-emerald-100 p-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 rounded-xl bg-white p-6 shadow">

        <form method="POST" action="{{ route('bendels.generate') }}">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Tanggal Pendapatan
                    </label>

                    <input
                        type="date"
                        name="tanggal_pendapatan"
                        class="w-full rounded-lg border p-3"
                        required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Tanggal Setor
                    </label>

                    <input
                        type="date"
                        name="tanggal_setor"
                        class="w-full rounded-lg border p-3"
                        required>
                </div>

                <div class="flex items-end">
                    <button
                        class="w-full rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                        Generate Bendel
                    </button>
                </div>

            </div>

        </form>

    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">

        <table class="min-w-full">

            <thead class="bg-slate-100">
                <tr>
                    <th class="px-6 py-3 text-left">Tanggal Pendapatan</th>
                    <th class="px-6 py-3 text-left">Tanggal Setor</th>
                    <th class="px-6 py-3 text-left">Status</th>
                </tr>
            </thead>

            <tbody>

            @forelse($bendels as $bendel)

                <tr class="border-t">

                    <td class="px-6 py-4">
                        {{ $bendel->tanggal_pendapatan }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $bendel->tanggal_setor }}
                    </td>

                    <td class="px-6 py-4">
                        {{ ucfirst($bendel->status) }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                        Belum ada Bendel.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

        <div class="p-4">
            {{ $bendels->links() }}
        </div>

    </div>

</x-layouts.app>