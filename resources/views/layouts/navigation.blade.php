<nav class="bg-white border-b border-gray-200">

    <div class="px-6 py-4 flex justify-between items-center">

        <div>
            <h2 class="text-lg font-semibold text-slate-800">
                Dashboard
            </h2>

            <p class="text-sm text-slate-500">
                Administrator SIPADU
            </p>
        </div>


        @auth

        <div class="flex items-center gap-4">

            <span class="text-sm text-slate-600">
                {{ auth()->user()->name }}
            </span>


            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    class="text-sm text-red-600 hover:text-red-800">
                    Logout
                </button>

            </form>

        </div>

        @endauth

    </div>

</nav>