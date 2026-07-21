<nav class="border-b border-slate-200 bg-white shadow-sm">

    <div class="flex items-center justify-between px-6 py-4">


        <!-- Judul -->

        <div>

            <h2 class="text-xl font-bold text-slate-800">

                Dashboard

            </h2>


            <div class="mt-1 flex items-center gap-2">


                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>


                <p class="text-sm text-slate-500">

                    SIPADU-DAGANG • Sistem Aktif

                </p>


            </div>


        </div>




        @auth


        <!-- User Area -->

        <div class="flex items-center gap-5">


            <div class="hidden text-right sm:block">


                <p class="text-sm font-semibold text-slate-700">

                    {{ auth()->user()->name }}

                </p>


                <p class="text-xs text-slate-500">

                    Administrator

                </p>


            </div>



            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white font-bold">

                {{ strtoupper(substr(auth()->user()->name,0,1)) }}

            </div>



            <form method="POST" action="{{ route('logout') }}">

                @csrf


                <button
                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">

                    Logout

                </button>


            </form>


        </div>


        @endauth


    </div>


</nav>