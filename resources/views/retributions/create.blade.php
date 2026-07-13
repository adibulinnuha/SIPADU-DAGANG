<x-layouts.app title="Tambah Retribusi">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Tambah Retribusi</h1>
        <p class="text-sm text-slate-500">Catat transaksi retribusi baru untuk pedagang.</p>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <form action="{{ route('retributions.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Pasar</label>
                    <select name="market_id" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <option value="">Pilih pasar</option>
                        @foreach($markets as $market)
                            <option value="{{ $market->id }}" {{ old('market_id') == $market->id ? 'selected' : '' }}>{{ $market->name }}</option>
                        @endforeach
                    </select>
                    @error('market_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Pedagang</label>
                    <select name="trader_id" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <option value="">Pilih pedagang</option>
                        @foreach($traders as $trader)
                            <option value="{{ $trader->id }}" {{ old('trader_id') == $trader->id ? 'selected' : '' }}>{{ $trader->name }} ({{ $trader->stall_number }})</option>
                        @endforeach
                    </select>
                    @error('trader_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Retribusi</label>
                    <input type="date" name="retribution_date" value="{{ old('retribution_date') }}" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    @error('retribution_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Jumlah (Rp)</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    @error('amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Metode Pembayaran</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="Cash / Transfer">
                    @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Catatan</label>
                <textarea name="notes" rows="4" class="mt-2 w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:justify-end">
                <a href="{{ route('retributions.index') }}" class="inline-flex items-center justify-center rounded border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex items-center justify-center rounded bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">Simpan</button>
            </div>
        </form>
    </div>
</x-layouts.app>
