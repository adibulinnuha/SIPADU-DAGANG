<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $title ?? 'SIPADU-DAGANG' }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body class="bg-slate-50">


<div class="flex min-h-screen">


    <!-- Sidebar -->

    <aside class="w-72 bg-slate-900 text-white flex flex-col">


        <div class="px-6 py-5 border-b border-slate-700">

            <h1 class="text-xl font-bold tracking-wide">
                SIPADU-DAGANG
            </h1>

            <p class="mt-1 text-xs text-slate-400">
                Dinas Perdagangan Kota Semarang
            </p>

        </div>



        <nav class="flex-1 px-4 py-5 space-y-1">


            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm hover:bg-slate-800">

                <span>
                    Dashboard
                </span>

            </a>



            <a href="{{ route('markets.index') }}"
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm hover:bg-slate-800">

                <span>
                    Master Pasar
                </span>

            </a>



            <a href="{{ route('petugas.index') }}"
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm hover:bg-slate-800">

                <span>
                    Master Petugas
                </span>

            </a>



            <div class="my-4 border-t border-slate-700"></div>



            <a href="{{ route('retributions.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Retribusi Harian

            </a>



            <a href="{{ route('verifications.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Verifikasi Billing

            </a>



            <a href="{{ route('bendels.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Bendel

            </a>



            <a href="{{ route('reports.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Laporan & Rekap

            </a>



            <a href="{{ route('backup.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Backup Data

            </a>



            <a href="{{ route('settings.index') }}"
               class="rounded-lg px-4 py-3 text-sm hover:bg-slate-800 block">

                Pengaturan

            </a>


        </nav>




        <div class="border-t border-slate-700 p-4">


            <form action="{{ route('logout') }}" method="POST">

                @csrf


                <button
                    type="submit"
                    class="w-full rounded-lg bg-red-600 py-2.5 text-sm font-semibold hover:bg-red-700">

                    Logout

                </button>


            </form>


        </div>


    </aside>





    <!-- Main Content -->

    <main class="flex-1">


        <header class="bg-white border-b border-slate-200 px-8 py-5">

            <h2 class="text-2xl font-bold text-slate-900">

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
