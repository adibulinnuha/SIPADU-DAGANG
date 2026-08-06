<aside class="min-h-screen w-72 bg-slate-900 text-white shadow-2xl">

    <!-- Header -->
    <div class="border-b border-slate-700 p-6">

        <div class="flex items-center gap-4">

            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-lg">

                <img
                    src="{{ asset('images/logo-kota-semarang.png') }}"
                    alt="Logo Kota Semarang"
                    class="h-12 w-12 object-contain">

            </div>

            <div class="leading-tight">

                <h1 class="text-xl font-extrabold tracking-wide text-white">
                    SIPADU-DAGANG
                </h1>

                <p class="text-sm text-slate-300">
                    Dinas Perdagangan
                </p>

                <p class="text-xs text-slate-500">
                    Sistem Informasi Retribusi Pasar
                </p>

            </div>

        </div>

        <div class="mt-5 rounded-xl border border-slate-700 bg-slate-800 p-4">

            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                Pemerintah Kota Semarang
            </p>

            <p class="mt-1 font-semibold text-white">
                ERET v2
            </p>

        </div>

    </div>

    <!-- Menu -->
    <nav class="space-y-2 p-4">

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">🏠</span>
            <span>Dashboard</span>

        </a>

        <a href="{{ route('markets.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('markets.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">🏪</span>
            <span>Master Pasar</span>

        </a>

<a href="{{ route('retributions.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('retributions.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">💰</span>
            <span>Retribusi</span>

        </a>

        <a href="{{ route('petugas.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('petugas.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">🧑‍🤝‍🧑</span>
            <span>Master Petugas</span>

        </a>

        <a href="{{ route('ocr.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('ocr.*') ? 'bg-purple-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">📷</span>

            <span>OCR e-Ticketing</span>

            <span class="ml-auto rounded-full bg-purple-500 px-2 py-0.5 text-[10px] font-bold">
                AI
            </span>

        </a>

        <a href="{{ route('verifications.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('verifications.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">✔️</span>
            <span>Verifikasi</span>

        </a>

        <a href="{{ route('bendel.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('bendel.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">📄</span>
            <span>Bendel</span>

        </a>

        <a href="{{ route('rekap-harian.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('rekap-harian.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">📊</span>
            <span>Rekap Harian</span>

        </a>

        <a href="{{ route('users.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200
           {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">

            <span class="text-lg">👤</span>
            <span>Pengguna</span>

        </a>

    </nav>

</aside>