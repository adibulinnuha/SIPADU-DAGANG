<x-layouts.app title="Tambah Retribusi">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">
            Tambah Retribusi Harian
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Input setoran retribusi berdasarkan pasar.
        </p>
    </div>


    <div class="rounded-xl bg-white p-6 shadow-md">

        <form action="{{ route('retributions.store') }}"
              method="POST"
              class="space-y-6">

            @csrf


            <div class="grid gap-6 md:grid-cols-2">

                {{-- Pasar --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Pasar
                    </label>

                    <select name="market_id"
                            required
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">
                            -- Pilih Pasar --
                        </option>

                        @foreach($markets as $market)

                            <option value="{{ $market->id }}"
                                {{ old('market_id') == $market->id ? 'selected' : '' }}>
                                {{ $market->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('market_id')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Jenis Retribusi --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Jenis Retribusi
                    </label>

                    <select name="jenis_retribusi"
                            required
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">
                            -- Pilih Jenis --
                        </option>

                        @php
                            $jenis = [
                                'Kios',
                                'Los',
                                'Dasaran Terbuka',
                                'MCK',
                                'Kebersihan',
                                'Listrik'
                            ];
                        @endphp


                        @foreach($jenis as $item)

                            <option value="{{ $item }}"
                                {{ old('jenis_retribusi') == $item ? 'selected' : '' }}>
                                {{ $item }}
                            </option>

                        @endforeach

                    </select>

                    @error('jenis_retribusi')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>



            <div class="grid gap-6 md:grid-cols-3">


                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Tanggal Retribusi
                    </label>

                    <input type="date"
                           name="retribution_date"
                           required
                           value="{{ old('retribution_date', date('Y-m-d')) }}"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                </div>



                {{-- Jumlah --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Jumlah (Rp)
                    </label>

                    <input type="number"
                           name="amount"
                           required
                           min="0"
                           value="{{ old('amount') }}"
                           placeholder="Contoh: 10000"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                    @error('amount')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>



                {{-- Pembayaran --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700">
                        Metode Pembayaran
                    </label>

                    <select name="payment_method"
                            required
                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">

                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="Cash"
                            {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>
                            Cash
                        </option>

                        <option value="Transfer"
                            {{ old('payment_method') == 'Transfer' ? 'selected' : '' }}>
                            Transfer
                        </option>

                    </select>

                </div>

            </div>



            {{-- Catatan --}}
            <div>

                <label class="block text-sm font-bold text-slate-700">
                    Catatan
                </label>

                <textarea name="notes"
                          rows="4"
                          placeholder="Catatan tambahan..."
                          class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-3">{{ old('notes') }}</textarea>

            </div>



            <div class="flex justify-end gap-3 pt-4">

                <a href="{{ route('retributions.index') }}"
                   class="rounded-lg border-2 border-slate-300 px-5 py-3 font-bold text-slate-700 hover:bg-slate-100">
                    ← Batal
                </a>


                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-7 py-3 font-bold text-white shadow-md hover:bg-emerald-700">
                    💾 SIMPAN RETRIBUSI
                </button>

            </div>


        </form>

    </div>

</x-layouts.app>