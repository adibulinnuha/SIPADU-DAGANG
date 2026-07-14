<x-layouts.app title="Edit Retribusi">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">
            Edit Retribusi Harian
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui data transaksi retribusi harian.
        </p>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-md">

        <form action="{{ route('retributions.update', $retribution) }}" method="POST" class="space-y-6">

            @csrf
            @method('PUT')

            <div class="grid gap-6 md:grid-cols-2">

                {{-- Pasar --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Pasar
                    </label>

                    <select
                        name="market_id"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">-- Pilih Pasar --</option>

                        @foreach($markets as $market)
                            <option
                                value="{{ $market->id }}"
                                {{ old('market_id', $retribution->market_id) == $market->id ? 'selected' : '' }}>
                                {{ $market->name }}
                            </option>
                        @endforeach

                    </select>

                    @error('market_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Jenis Retribusi --}}
                <div>

                    <label class="block text-sm font-bold text-slate-700">
                        Jenis Retribusi
                    </label>

                    <select
                        name="jenis_retribusi"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">-- Pilih Jenis --</option>

                        @foreach([
                            'Kios',
                            'Los',
                            'Dasaran Terbuka',
                            'Pelataran',
                            'MCK',
                            'Kebersihan',
                            'Listrik'
                        ] as $jenis)

                            <option
                                value="{{ $jenis }}"
                                {{ old('jenis_retribusi', $retribution->jenis_retribusi) == $jenis ? 'selected' : '' }}>
                                {{ $jenis }}
                            </option>

                        @endforeach

                    </select>

                    @error('jenis_retribusi')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

            </div>

            <div class="grid gap-6 md:grid-cols-3">

                {{-- Tanggal --}}
                <div>

                    <label class="block text-sm font-bold text-slate-700">
                        Tanggal Retribusi
                    </label>

                    <input
                        type="date"
                        name="retribution_date"
                        required
                        value="{{ old('retribution_date', $retribution->retribution_date->format('Y-m-d')) }}"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                    @error('retribution_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Nominal --}}
                <div>

                    <label class="block text-sm font-bold text-slate-700">
                        Jumlah (Rp)
                    </label>

                    <input
                        type="number"
                        name="amount"
                        required
                        min="0"
                        value="{{ old('amount', $retribution->amount) }}"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                    @error('amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Metode Pembayaran --}}
                <div>

                    <label class="block text-sm font-bold text-slate-700">
                        Metode Pembayaran
                    </label>

                    <select
                        name="payment_method"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">-- Pilih Metode --</option>

                        @foreach(['Tunai', 'Transfer', 'QRIS'] as $metode)

                            <option
                                value="{{ $metode }}"
                                {{ old('payment_method', $retribution->payment_method) == $metode ? 'selected' : '' }}>
                                {{ $metode }}
                            </option>

                        @endforeach

                    </select>

                    @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

            </div>

            {{-- Catatan --}}
            <div>

                <label class="block text-sm font-bold text-slate-700">
                    Catatan
                </label>

                <textarea
                    name="notes"
                    rows="4"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">{{ old('notes', $retribution->notes) }}</textarea>

                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>

            <div class="flex justify-end gap-3 pt-4">

                <a
                    href="{{ route('retributions.index') }}"
                    class="rounded-lg border-2 border-slate-300 px-5 py-3 font-bold text-slate-700 hover:bg-slate-100">
                    ← Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-emerald-600 px-7 py-3 font-bold text-white shadow hover:bg-emerald-700">
                    💾 Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</x-layouts.app>