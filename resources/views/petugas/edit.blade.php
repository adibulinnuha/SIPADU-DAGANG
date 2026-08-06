<x-layouts.app title="Edit Petugas">

    <div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm">

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">
                Edit Petugas
            </h1>
            <p class="mt-1 text-slate-500">
                Perbarui data petugas (Korwil / Juru Pungut) SIPADU-DAGANG.
            </p>
        </div>

        <form action="{{ route('petugas.update', $petugas) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Nama <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $petugas->name) }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Email <span class="text-red-500">*</span>
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $petugas->email) }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        NIP
                    </label>

                    <input
                        type="text"
                        name="nip"
                        value="{{ old('nip', $petugas->nip) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                    >

                    @error('nip')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Pangkat
                    </label>

                    <input
                        type="text"
                        name="rank"
                        value="{{ old('rank', $petugas->rank) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                    >

                    @error('rank')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Jabatan
                </label>

                <input
                    type="text"
                    name="jabatan"
                    value="{{ old('jabatan', $petugas->jabatan) }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('jabatan')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Pasar
                </label>

                <select
                    name="market_id"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >
                    <option value="">-- Pilih Pasar --</option>
                    @foreach($markets as $market)
                        <option value="{{ $market->id }}" @selected(old('market_id', $petugas->market_id) == $market->id)>
                            {{ $market->name }}
                        </option>
                    @endforeach
                </select>

                @error('market_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Nomor HP
                </label>

                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone', $petugas->phone) }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Keterangan
                </label>

                <textarea
                    name="notes"
                    rows="3"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >{{ old('notes', $petugas->notes) }}</textarea>

                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Password Baru
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Kosongkan jika tidak ingin mengubah password.
                    </p>

                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Konfirmasi Password Baru
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                    >
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $petugas->is_active))
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                    >
                    <span class="text-sm font-medium text-slate-700">Status Aktif</span>
                </label>

                <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3">
                    <input
                        type="checkbox"
                        name="is_juru_pungut"
                        value="1"
                        @checked(old('is_juru_pungut', $petugas->is_juru_pungut))
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600"
                    >
                    <span class="text-sm font-medium text-slate-700">Juru Pungut (tampil di ERET)</span>
                </label>
            </div>

            <div class="flex justify-between pt-4">
                <a href="{{ route('petugas.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 hover:bg-slate-100">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2 font-medium text-white hover:bg-slate-800">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

</x-layouts.app>
