@props(['title' => 'SIPADU-DAGANG'])

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-72 bg-slate-900 text-white hidden md:flex flex-col">

        <div class="px-6 py-5 border-b border-slate-700">
            <h1 class="text-xl font-bold tracking-wide">
                SIPADU-DAGANG
            </h1>

            <p class="text-xs text-slate-400 mt-1">
                Dinas Perdagangan Kota Semarang
            </p>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">

            <a href="{{ route('dashboard') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                📊 Dashboard
            </a>

            <a href="{{ route('markets.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                🏪 Master Pasar
            </a>

            <a href="{{ route('petugas.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                🛠️ Master Petugas
            </a>

            <div class="border-t border-slate-700 my-4"></div>

            <a href="{{ route('retributions.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                💰 Retribusi Harian
            </a>

            <a href="{{ route('rekap-harian.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                📋 Rekap Harian
            </a>

            <a href="{{ route('verifications.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                ✔️ Verifikasi Billing
            </a>

            <a href="{{ route('bendel.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                📄 Bendel
            </a>

            <div class="border-t border-slate-700 my-4"></div>

            <a href="{{ route('users.index') }}"
               class="block px-4 py-3 rounded-lg hover:bg-slate-800 transition">
                👥 User Management
            </a>

        </nav>

        <div class="px-6 py-4 border-t border-slate-700">

            <div class="font-semibold">
                {{ auth()->user()->name ?? 'Administrator' }}
            </div>

            <div class="text-xs text-slate-400">
                Online
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Logout
                </button>
            </form>

        </div>

    </aside>

    {{-- Main Area --}}
    <main class="flex-1 flex flex-col">

        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">

            <div>
                <h2 class="text-2xl font-bold">
                    {{ $title }}
                </h2>

                <p class="text-sm text-slate-500">
                    Dinas Perdagangan Kota Semarang
                </p>
            </div>

            <div class="text-right">
                <div class="font-semibold">
                    {{ auth()->user()->name ?? 'Administrator' }}
                </div>

                <div class="text-xs text-slate-500">
                    Online
                </div>
            </div>

        </header>

        <section class="p-6">
            {{ $slot }}
        </section>

    </main>

</div>

</body>

</html>