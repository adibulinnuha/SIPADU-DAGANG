<x-layouts.app title="Verifikasi Baru">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">
            Verifikasi Baru
        </h1>

        <p class="mt-1 text-slate-500">
            Verifikasi billing retribusi dengan nomor setor.
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

        <form action="{{ route('verifications.store') }}" method="POST">

            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                <div>
                    <label for="retribution_id" class="mb-2 block font-medium">
                        Retribusi <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="retribution_id"
                        name="retribution_id"
                        class="w-full rounded-lg border px-4 py-2"
                        required>

                        <option value="">-- Pilih Retribusi --</option>

                        @foreach($retributions as $retribution)
                            <option value="{{ $retribution->id }}"
                                @selected(old('retribution_id') == $retribution->id)>
                                {{ $retribution->market->name ?? 'Tanpa Pasar' }} -
                                {{ optional($retribution->retribution_date)->format('d-m-Y') }} -
                                Rp {{ number_format($retribution->amount ?? 0,0,',','.') }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div>
                    <label for="nomor_setor" class="mb-2 block font-medium">
                        Nomor Setor <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="nomor_setor"
                        type="text"
                        name="nomor_setor"
                        value="{{ old('nomor_setor') }}"
                        placeholder="Contoh: 001/SETOR/VI/2026"
                        class="w-full rounded-lg border px-4 py-2"
                        required>
                </div>

                <div>
                    <label for="tanggal_verifikasi" class="mb-2 block font-medium">
                        Tanggal Verifikasi <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="tanggal_verifikasi"
                        type="date"
                        name="tanggal_verifikasi"
                        value="{{ old('tanggal_verifikasi', date('Y-m-d')) }}"
                        class="w-full rounded-lg border px-4 py-2"
                        required>
                </div>

            </div>

            <div class="mt-6">
                <label for="catatan" class="mb-2 block font-medium">
                    Catatan
                </label>

                <textarea
                    id="catatan"
                    name="catatan"
                    rows="4"
                    class="w-full rounded-lg border px-4 py-2"
                    placeholder="Opsional">{{ old('catatan') }}</textarea>
            </div>

            <div class="mt-8 flex gap-3">

                <a href="{{ route('verifications.index') }}"
                   class="rounded-lg bg-slate-200 px-5 py-3">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                    Simpan Verifikasi
                </button>

            </div>

        </form>

    </div>

</x-layouts.app>

