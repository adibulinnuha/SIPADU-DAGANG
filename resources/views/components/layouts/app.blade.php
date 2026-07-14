@props(['title' => null])

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'SIPADU-DAGANG' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>


<body class="bg-black-100">


<div class="flex min-h-screen">


    <!-- Sidebar -->

    <aside class="w-64 bg-slate-900 text-white">


        <div class="p-5 border-b border-slate-700">

            <h1 class="text-xl font-bold">
                SIPADU-DAGANG
            </h1>

            <p class="text-sm text-slate-300">
                Administrator
            </p>

        </div>



        <nav class="p-4 space-y-2">


            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                📊 Dashboard
            </a>


            <a href="{{ route('markets.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                🏪 Master Pasar
            </a>


            <a href="{{ route('petugas.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                🛠️ Master Petugas
            </a>


            <hr class="border-slate-700 my-3">


            <a href="{{ route('retributions.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                💰 Retribusi Harian
            </a>


            <a href="{{ route('verifications.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                ✔️ Verifikasi Billing
            </a>


            <a href="{{ route('bendel.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                📄 Bendel
            </a>


            <a href="{{ route('reports.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                📈 Laporan & Rekap
            </a>


            <a href="{{ route('backup.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                💾 Backup Data
            </a>


            <a href="{{ route('settings.index') }}"
               class="block px-3 py-2 rounded hover:bg-slate-700">
                ⚙️ Pengaturan
            </a>


        </nav>



        @if(auth()->check())

        <div class="absolute bottom-0 w-64 p-4 border-t border-slate-700">


            <form method="POST" action="{{ route('logout') }}">

                @csrf


                <button
                    type="submit"
                    class="w-full rounded bg-red-600 py-2 hover:bg-red-700">

                    Keluar

                </button>


            </form>


        </div>

        @endif


    </aside>




    <!-- Content -->


    <div class="flex-1">


        <header class="bg-white shadow px-6 py-4 flex justify-between">


            <h2 class="text-2xl font-bold">
                {{ $title ?? 'Dashboard' }}
            </h2>



            @if(auth()->check())

            <div class="text-right">

                <div class="font-semibold">
                    {{ auth()->user()->name }}
                </div>


                <div class="text-sm text-gray-500">

                    {{ auth()->user()->role?->label() ?? '-' }}

                </div>


            </div>

            @endif



        </header>



        <main class="p-6">

            {{ $slot }}

        </main>



    </div>


</div>


</body>

</html>