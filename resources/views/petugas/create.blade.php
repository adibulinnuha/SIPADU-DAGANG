<x-layouts.app title="Tambah Petugas">

    <div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm">

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-slate-900">
                Tambah Petugas
            </h1>
            <p class="mt-1 text-slate-500">
                Tambahkan akun petugas baru SIPADU-DAGANG.
            </p>
        </div>

        <form action="{{ route('petugas.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Nama
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >

                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Konfirmasi Password
                </label>

                <input
                    type="password"
                    name="password_confirmation"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-slate-900 focus:outline-none"
                >
            </div>

            <div class="flex justify-between pt-4">
                <a href="{{ route('petugas.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 hover:bg-slate-100">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2 font-medium text-white hover:bg-slate-800">
                    Simpan
                </button>
            </div>

        </form>

    </div>

</x-layouts.app>