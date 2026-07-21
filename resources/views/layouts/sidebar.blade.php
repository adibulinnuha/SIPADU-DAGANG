<aside class="min-h-screen w-72 bg-slate-900 text-white shadow-xl">


    <!-- Logo -->

    <div class="border-b border-slate-700 p-6">

        <div class="flex items-center gap-3">

            <div class="rounded-xl bg-blue-600 p-3">

                <svg class="h-7 w-7"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9"/>

                </svg>

            </div>


            <div>

                <h1 class="text-xl font-bold">
                    SIPADU-DAGANG
                </h1>

                <p class="text-xs text-slate-400">
                    Dinas Perdagangan
                </p>

            </div>


        </div>


        <div class="mt-4 rounded-lg bg-blue-600/20 px-3 py-2 text-xs text-blue-200">

            Sistem Informasi Retribusi Pasar

        </div>


    </div>



    <!-- Menu -->

    <nav class="space-y-2 p-4">


        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('dashboard') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                🏠
            </span>

            Dashboard

        </a>



        <a href="{{ route('markets.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('markets.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                🏪
            </span>

            Pasar

        </a>



        <a href="{{ route('retributions.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('retributions.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                💰
            </span>

            Retribusi

        </a>



        <a href="{{ route('ocr.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('ocr.*') 
                ? 'bg-purple-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                📷
            </span>

            OCR e-Ticketing

            <span class="ml-auto rounded-full bg-purple-500 px-2 py-0.5 text-xs">
                AI
            </span>

        </a>



        <a href="{{ route('verifications.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('verifications.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                ✓
            </span>

            Verifikasi

        </a>



        <a href="{{ route('bendel.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('bendel.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                📄
            </span>

            Bendel

        </a>



        <a href="{{ route('users.index') }}"
           class="flex items-center gap-3 rounded-lg px-4 py-3
           {{ request()->routeIs('users.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-300 hover:bg-slate-800' }}">

            <span>
                👤
            </span>

            Pengguna

        </a>


    </nav>


</aside>