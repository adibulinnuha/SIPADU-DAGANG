<x-layouts.app title="Dashboard">

    <div class="sipadu-page">


        <div class="mb-6">

            <h1 class="sipadu-title">
                Dashboard SIPADU-DAGANG
            </h1>

            <p class="sipadu-subtitle">
                Ringkasan aktivitas Dinas Perdagangan Kota Semarang.
            </p>

        </div>



        {{-- Statistik Utama --}}

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">


            <div class="sipadu-card">
                <div class="sipadu-card-body">

                    <p class="text-sm text-slate-500">
                        Total Pasar
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $marketCount }}
                    </h2>

                </div>
            </div>



            <div class="sipadu-card">
                <div class="sipadu-card-body">

                    <p class="text-sm text-slate-500">
                        Retribusi Hari Ini
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $todayRetributionCount }}
                    </h2>

                </div>
            </div>



            <div class="sipadu-card">
                <div class="sipadu-card-body">

                    <p class="text-sm text-slate-500">
                        Nominal Hari Ini
                    </p>

                    <h2 class="text-2xl font-bold mt-2">
                        Rp {{ number_format($todayRetributionTotal,0,',','.') }}
                    </h2>

                </div>
            </div>



            <div class="sipadu-card">
                <div class="sipadu-card-body">

                    <p class="text-sm text-slate-500">
                        Status Sistem
                    </p>

                    <h2 class="text-xl font-bold mt-2 text-green-600">
                        ONLINE
                    </h2>

                </div>
            </div>


        </div>




        {{-- Menu Cepat --}}

        <div class="sipadu-card mb-8">

            <div class="sipadu-card-header">

                <h3>
                    Menu Cepat
                </h3>

            </div>


            <div class="sipadu-card-body">

                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">


                    <a href="{{ route('markets.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        🏪 Pasar

                    </a>


                    <a href="{{ route('petugas.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        🛠 Petugas

                    </a>


                    <a href="{{ route('retributions.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        💰 Retribusi

                    </a>


                    <a href="{{ route('verifications.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        ✔ Verifikasi

                    </a>


                    <a href="{{ route('bendels.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        📄 Bendel

                    </a>


                    <a href="{{ route('reports.index') }}"
                       class="sipadu-btn sipadu-btn-secondary">

                        📈 Laporan

                    </a>


                </div>

            </div>

        </div>




        {{-- To Do Hari Ini --}}

        <div class="sipadu-card">


            <div class="sipadu-card-header">

                <h3>
                    To Do Hari Ini
                </h3>

            </div>


            <div class="sipadu-card-body">

                <ul class="space-y-3 text-slate-700">


                    <li>
                        ☐ Input Excel Retribusi Harian
                    </li>


                    <li>
                        ☐ Input e-Retribusi Perdagangan
                    </li>


                    <li>
                        ☐ Verifikasi Billing
                    </li>


                    <li>
                        ☐ Penyusunan Bendel Retribusi
                    </li>


                    <li>
                        ☐ Rekap Laporan Harian
                    </li>


                </ul>

            </div>


        </div>


    </div>


</x-layouts.app>