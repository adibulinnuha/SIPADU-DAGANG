<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPADU-DAGANG</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col">

        <div class="p-5 border-b border-slate-700">
            <h1 class="text-xl font-bold">
                SIPADU-DAGANG
            </h1>
            <p class="text-xs text-slate-300">
                Dinas Perdagangan
            </p>
        </div>

        <nav class="flex-1 p-4 space-y-2">

            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-slate-700">
                📊 Dashboard
            </a>

            <a href="{{ route('markets.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">
                🏪 Master Pasar
            </a>

            <a href="{{ route('petugas.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">
                🛠️ Master Petugas
            </a>

            <a href="{{ route('traders.index') }}" class="block px-3 py-2 rounded hover:bg-slate-700">
                👥 Master Pedagang
            </a>

            <hr class="border-slate-700 my-3">

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                💰 Retribusi Harian
            </a>

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                ✔ Verifikasi Billing
            </a>

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                📄 Bendel
            </a>

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                📈 Laporan & Rekap
            </a>

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                💾 Backup Data
            </a>

            <a href="#" class="block px-3 py-2 rounded hover:bg-slate-700">
                ⚙ Pengaturan
            </a>

        </nav>

        <div class="p-4 border-t border-slate-700">

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button
                    type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 py-2 rounded">
                    Logout
                </button>

            </form>

        </div>

    </aside>

    <!-- Content -->
    <main class="flex-1 overflow-auto">

        <header class="bg-white shadow px-8 py-5">

            <h2 class="text-2xl font-bold">
                SIPADU-DAGANG
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