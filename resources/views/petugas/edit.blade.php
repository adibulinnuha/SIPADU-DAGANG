<x-layouts.app title="Edit Petugas">
    <div class="max-w-2xl rounded-lg bg-white p-8 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit Petugas</h1>
            <p class="text-sm text-slate-500">Perbarui data petugas.</p>
        </div>

        <form action="{{ route('petugas.update', $petuga) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700">Nama</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $petuga->name) }}"
                    class="mt-2 block w-full rounded border border-slate-300 bg-slate-50 px-4 py-2 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $petuga->email) }}"
                    class="mt-2 block w-full rounded border border-slate-300 bg-slate-50 px-4 py-2 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Password Baru</label>
                <input
                    type="password"
                    name="password"
                    class="mt-2 block w-full rounded border border-slate-300 bg-slate-50 px-4 py-2 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    class="mt-2 block w-full rounded border border-slate-300 bg-slate-50 px-4 py-2 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
            </div>

            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('petugas.index') }}" class="text-sm text-slate-500 hover:text-slate-900">Batal</a>
                <button type="submit" class="rounded bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
