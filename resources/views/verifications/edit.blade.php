<x-layouts.app title="Edit Verifikasi">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">
            Edit Verifikasi Billing
        </h1>

        <p class="mt-1 text-slate-500">
            Perbarui data verifikasi billing retribusi.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="list-disc pl-5 text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">

        <form action="{{ route('verifications.update', $verification) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                <div>
                    <label class="mb-2 block font-medium">
                        Retribusi
                    </label>

                    <input
                        type="text"
                        class="w-full rounded-lg border bg-slate-100 px-4 py-2"
                        value="{{ $verification->retribution?->market?->name }} - {{ optional($verification->retribution?->retribution_date)->format('d-m-Y') }}"
                        readonly>
                </div>

                <div>
                    <label class="mb-2 block font-medium">
                        Nomor Setor
                    </label>

                    <input
                        type="text"
                        name="nomor_setor"
                        value="{{ old('nomor_setor', $verification->nomor_setor) }}"
                        class="w-full rounded-lg border px-4 py-2">
                </div>

                <div>
                    <label class="mb-2 block font-medium">
                        Tanggal Verifikasi
                    </label>

                    <input
                        type="date"
                        name="tanggal_verifikasi"
                        value="{{ old('tanggal_verifikasi', optional($verification->tanggal_verifikasi)->format('Y-m-d')) }}"
                        class="w-full rounded-lg border px-4 py-2">
                </div>

                <div>
                    <label class="mb-2 block font-medium">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full rounded-lg border px-4 py-2">

                        <option value="Pending"
                            @selected(old('status', $verification->status) == 'Pending')>
                            Pending
                        </option>

                        <option value="Terverifikasi"
                            @selected(old('status', $verification->status) == 'Terverifikasi')>
                            Terverifikasi
                        </option>

                    </select>
                </div>

            </div>

            <div class="mt-6">
                <label class="mb-2 block font-medium">
                    Catatan
                </label>

                <textarea
                    name="catatan"
                    rows="4"
                    class="w-full rounded-lg border px-4 py-2">{{ old('catatan', $verification->catatan) }}</textarea>
            </div>

            <div class="mt-8 flex gap-3">

                <a href="{{ route('verifications.index') }}"
                   class="rounded-lg bg-slate-200 px-5 py-3">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">

                    Simpan Perubahan

                </button>

            </div>

        </form>

    </div>

</x-layouts.app>