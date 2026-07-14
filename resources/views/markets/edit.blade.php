<x-layouts.app title="Edit Pasar">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">
            Edit Pasar
        </h1>

        <p class="mt-1 text-slate-500">
            Perbarui informasi data pasar.
        </p>
    </div>

    <div class="rounded-xl bg-white p-8 shadow">

        <form action="{{ route('markets.update', $market->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                <div>
                    <label class="mb-2 block font-medium text-slate-700">
                        Nama Pasar
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $market->name) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-2 block font-medium text-slate-700">
                        Kode Pasar
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ old('code', $market->code) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block font-medium text-slate-700">
                        Alamat
                    </label>

                    <input
                        type="text"
                        name="address"
                        value="{{ old('address', $market->address) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-2 block font-medium text-slate-700">
                        Telepon
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $market->phone) }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-2 block font-medium text-slate-700">
                        Status
                    </label>

                    <select
                        name="is_active"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">

                        <option value="1" {{ $market->is_active ? 'selected' : '' }}>
                            Aktif
                        </option>

                        <option value="0" {{ !$market->is_active ? 'selected' : '' }}>
                            Nonaktif
                        </option>

                    </select>
                </div>

            </div>

            <div class="mt-8 flex gap-3">

                <button
                    type="submit"
                    class="rounded-lg bg-amber-500 px-6 py-3 font-semibold text-white hover:bg-amber-600">
                    Update
                </button>

                <a href="{{ route('markets.index') }}"
                   class="rounded-lg bg-slate-200 px-6 py-3 font-semibold text-slate-700 hover:bg-slate-300">
                    Kembali
                </a>

            </div>

        </form>

    </div>

</x-layouts.app>