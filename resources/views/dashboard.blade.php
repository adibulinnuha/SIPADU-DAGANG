<x-layouts.app title="Dashboard">

<div class="mb-6">
    <h1 class="text-3xl font-bold">Dashboard</h1>
    <p class="text-gray-500">
        Selamat datang di SIPADU-DAGANG.
    </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500">Total Pasar</div>
        <div class="text-4xl font-bold mt-3">
            {{ $marketCount }}
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500">Total Pedagang</div>
        <div class="text-4xl font-bold mt-3">
            {{ $traderCount }}
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500">Retribusi Hari Ini</div>
        <div class="text-4xl font-bold mt-3">
            {{ $todayRetributionCount }}
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500">Nominal Hari Ini</div>
        <div class="text-3xl font-bold text-green-600 mt-3">
            Rp {{ number_format($todayRetributionTotal,0,',','.') }}
        </div>
    </div>

</div>

<div class="mt-8 bg-white rounded-xl shadow p-6">

    <h2 class="text-xl font-semibold mb-4">
        Menu Cepat
    </h2>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

        <a href="{{ route('retributions.index') }}" class="rounded-lg bg-blue-600 text-white text-center py-4 hover:bg-blue-700">
            Retribusi
        </a>

        <a href="{{ route('verifications.index') }}" class="rounded-lg bg-green-600 text-white text-center py-4 hover:bg-green-700">
            Verifikasi
        </a>

        <a href="{{ route('bendel.index') }}" class="rounded-lg bg-orange-500 text-white text-center py-4 hover:bg-orange-600">
            Bendel
        </a>

        <a href="{{ route('reports.index') }}" class="rounded-lg bg-purple-600 text-white text-center py-4 hover:bg-purple-700">
            Laporan
        </a>

        <a href="{{ route('backup.index') }}" class="rounded-lg bg-slate-700 text-white text-center py-4 hover:bg-slate-800">
            Backup
        </a>

        <a href="{{ route('settings.index') }}" class="rounded-lg bg-gray-700 text-white text-center py-4 hover:bg-gray-800">
            Pengaturan
        </a>

    </div>

</div>

</x-layouts.app>