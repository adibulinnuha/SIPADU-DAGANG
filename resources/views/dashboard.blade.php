<x-layouts.app title="Dashboard">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">
            Dashboard
        </h1>

        <p class="text-slate-500">
            Selamat datang di SIPADU-DAGANG.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-gray-500">
                Total Pasar
            </p>

            <h2 class="mt-3 text-4xl font-bold text-blue-600">
                {{ $marketCount }}
            </h2>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-gray-500">
                Retribusi Hari Ini
            </p>

            <h2 class="mt-3 text-4xl font-bold text-emerald-600">
                {{ $todayRetributionCount }}
            </h2>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <p class="text-gray-500">
                Nominal Hari Ini
            </p>

            <h2 class="mt-3 text-3xl font-bold text-orange-600">
                Rp {{ number_format($todayRetributionTotal, 0, ',', '.') }}
            </h2>
        </div>

    </div>

    <div class="mt-8 rounded-xl bg-white p-6 shadow">

        <h2 class="mb-5 text-xl font-semibold">
            Menu Cepat
        </h2>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">

            <a href="{{ route('markets.index') }}"
                class="rounded-lg bg-sky-600 py-4 text-center font-semibold text-white hover:bg-sky-700">
                Master Pasar
            </a>

            <a href="{{ route('petugas.index') }}"
                class="rounded-lg bg-indigo-600 py-4 text-center font-semibold text-white hover:bg-indigo-700">
                Master Petugas
            </a>

            <a href="{{ route('retributions.index') }}"
                class="rounded-lg bg-emerald-600 py-4 text-center font-semibold text-white hover:bg-emerald-700">
                Retribusi
            </a>

            <a href="{{ route('verifications.index') }}"
                class="rounded-lg bg-amber-500 py-4 text-center font-semibold text-white hover:bg-amber-600">
                Verifikasi
            </a>

            <a href="{{ route('reports.index') }}"
                class="rounded-lg bg-purple-600 py-4 text-center font-semibold text-white hover:bg-purple-700">
                Laporan
            </a>

            <a href="{{ route('backup.index') }}"
                class="rounded-lg bg-slate-700 py-4 text-center font-semibold text-white hover:bg-slate-800">
                Backup
            </a>

        </div>

    </div>

</x-layouts.app>