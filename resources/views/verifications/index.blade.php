<x-layouts.app title="Verifikasi Billing">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Verifikasi Billing
            </h1>

            <p class="mt-1 text-slate-500">
                Daftar verifikasi billing retribusi SIPADU-DAGANG.
            </p>
        </div>

        <a href="{{ route('verifications.create') }}"
           class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white shadow hover:bg-blue-700">
            + Verifikasi Baru
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl bg-white shadow">

        <table class="min-w-full">

            <thead class="bg-slate-100">

                <tr>

                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-left">Pasar</th>
                    <th class="px-6 py-3 text-left">Nomor Setor</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Verifikator</th>
                    <th class="px-6 py-3 text-center">Aksi</th>

                </tr>

            </thead>

            <tbody>

            @forelse($verifications as $verification)

                <tr class="border-t">

                    <td class="px-6 py-4">
                        {{ optional($verification->tanggal_verifikasi)->format('d-m-Y') ?? '-' }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $verification->retribution?->market?->name }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $verification->nomor_setor ?? '-' }}
                    </td>

                    <td class="px-6 py-4">

                        @if($verification->status === 'Terverifikasi')
                            <span class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-700">
                                Terverifikasi
                            </span>
                        @else
                            <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm text-yellow-700">
                                Pending
                            </span>
                        @endif

                    </td>

                    <td class="px-6 py-4">
                        {{ $verification->verifier?->name ?? '-' }}
                    </td>

                    <td class="px-6 py-4 text-center">

                        <a href="{{ route('verifications.edit', $verification) }}"
                           class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                            Edit
                        </a>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                        Belum ada data verifikasi.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-5">
        {{ $verifications->links() }}
    </div>

</x-layouts.app>