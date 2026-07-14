<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIPADU-DAGANG' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col">

        <div class="border-b border-slate-700 p-5">
            <h1 class="text-xl font-bold">
                SIPADU-DAGANG
            </h1>

            <p class="text-xs text-slate-300">
                Dinas Perdagangan Kota Semarang
            </p>
        </div>

        <nav class="flex-1 space-y-2 p-4">

            <a href="{{ route('dashboard') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                📊 Dashboard
            </a>

            <a href="{{ route('markets.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                🏪 Master Pasar
            </a>

            <a href="{{ route('petugas.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                🛠️ Master Petugas
            </a>

            <hr class="my-3 border-slate-700">

            <a href="{{ route('retributions.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                💰 Retribusi Harian
            </a>

            <a href="{{ route('verifications.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                ✔ Verifikasi Billing
            </a>

            <a href="{{ route('bendel.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                📄 Bendel
            </a>

            <a href="{{ route('reports.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                📈 Laporan & Rekap
            </a>

            <a href="{{ route('backup.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                💾 Backup Data
            </a>

            <a href="{{ route('settings.index') }}"
               class="block rounded px-3 py-2 hover:bg-slate-700">
                ⚙️ Pengaturan
            </a>

        </nav>

        <div class="border-t border-slate-700 p-4">

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded bg-red-600 py-2 font-semibold hover:bg-red-700">
                    Logout
                </button>

            </form>

        </div>

    </aside>

    <!-- Content -->
    <main class="flex-1 overflow-auto">

        <header class="bg-white px-8 py-5 shadow">

            <h2 class="text-2xl font-bold text-slate-800">
                {{ $title ?? 'Dashboard' }}
            </h2>

        </header>

        <section class="p-8">

            {{ $slot ?? '' }}

            @yield('content')

        </section>

    </main>

</div>

</body>
</html>