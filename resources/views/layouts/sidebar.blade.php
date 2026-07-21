<aside class="w-64 min-h-screen bg-slate-800 text-white">
    <div class="p-6 border-b border-slate-700">
        <h1 class="text-xl font-bold">SIPADU-DAGANG</h1>
        <p class="text-sm text-slate-300">
            Sistem Informasi Perdagangan
        </p>
    </div>

    <nav class="p-4 space-y-2">
        <a href="{{ route('dashboard') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Dashboard
        </a>

        <a href="{{ route('markets.index') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Pasar
        </a>

        <a href="{{ route('retributions.index') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Retribusi
        </a>

        <a href="{{ route('verifications.index') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Verifikasi
        </a>

        <a href="{{ route('bendel.index') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Bendel
        </a>

        <a href="{{ route('users.index') }}"
           class="block px-4 py-2 rounded hover:bg-slate-700">
            Pengguna
        </a>
    </nav>
</aside>