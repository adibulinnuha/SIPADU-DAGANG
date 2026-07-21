@extends('layouts.app')

@section('content')

<div class="py-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Data Retribusi
            </h1>

            <p class="text-sm text-gray-500">
                Pengelolaan transaksi retribusi SIPADU-DAGANG
            </p>
        </div>


        <div class="flex gap-2">

            <a href="{{ route('retributions.export-template') }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Download ERET JULI
            </a>


            <a href="{{ route('retributions.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Tambah Retribusi
            </a>

        </div>

    </div>



    {{-- FLASH MESSAGE --}}
    @if(session('success'))

        <div class="mb-5 p-4 rounded-lg bg-green-100 text-green-700">
            {{ session('success') }}
        </div>

    @endif



    {{-- STATISTIK --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">


        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-sm text-gray-500">
                Total Retribusi
            </p>

            <h2 class="text-3xl font-bold text-gray-800">
                {{ $retributions->total() }}
            </h2>
        </div>



        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-sm text-gray-500">
                Hari Ini
            </p>

            <h2 class="text-3xl font-bold text-blue-600">
                {{ $todayRetributionCount ?? 0 }}
            </h2>
        </div>



        <div class="bg-white shadow rounded-xl p-5">
            <p class="text-sm text-gray-500">
                Total Nominal
            </p>

            <h2 class="text-3xl font-bold text-green-600">
                Rp {{ number_format($totalAmount ?? 0,0,',','.') }}
            </h2>
        </div>


    </div>




    {{-- FILTER --}}
    <div class="bg-white shadow rounded-xl p-5 mb-6">


        <form method="GET" action="{{ route('retributions.index') }}">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">


                <div>

                    <label class="text-sm text-gray-600">
                        Pasar
                    </label>

                    <select name="market_id"
                        class="w-full rounded-lg border-gray-300">

                        <option value="">
                            Semua Pasar
                        </option>

                        @foreach($markets as $market)

                        <option value="{{ $market->id }}"
                        @selected(request('market_id')==$market->id)>

                            {{ $market->name }}

                        </option>

                        @endforeach

                    </select>

                </div>



                <div>

                    <label class="text-sm text-gray-600">
                        Dari Tanggal
                    </label>

                    <input type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-full rounded-lg border-gray-300">

                </div>



                <div>

                    <label class="text-sm text-gray-600">
                        Sampai Tanggal
                    </label>

                    <input type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-full rounded-lg border-gray-300">

                </div>



                <div class="flex items-end gap-2">

                    <button
                    class="px-4 py-2 bg-gray-800 text-white rounded-lg">
                        Filter
                    </button>


                    <a href="{{ route('retributions.index') }}"
                    class="px-4 py-2 bg-gray-200 rounded-lg">
                        Reset
                    </a>

                </div>


            </div>

        </form>


    </div>




    {{-- TABLE --}}
    <div class="bg-white shadow rounded-xl overflow-hidden">


        <table class="w-full text-sm">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-5 py-3 text-left">
                        Tanggal
                    </th>

                    <th class="px-5 py-3 text-left">
                        Pasar
                    </th>

                    <th class="px-5 py-3 text-left">
                        Jenis
                    </th>

                    <th class="px-5 py-3 text-right">
                        Nominal
                    </th>

                </tr>

            </thead>


            <tbody>


            @forelse($retributions as $item)

                <tr class="border-t">

                    <td class="px-5 py-3">
                        {{ $item->retribution_date }}
                    </td>


                    <td class="px-5 py-3">
                        {{ $item->market->name ?? '-' }}
                    </td>


                    <td class="px-5 py-3">
                        {{ $item->jenis_retribusi }}
                    </td>


                    <td class="px-5 py-3 text-right">
                        Rp {{ number_format($item->amount,0,',','.') }}
                    </td>


                </tr>


            @empty

                <tr>

                    <td colspan="4"
                    class="px-5 py-5 text-center text-gray-500">

                        Belum ada data retribusi

                    </td>

                </tr>


            @endforelse


            </tbody>

        </table>


    </div>


    <div class="mt-5">

        {{ $retributions->links() }}

    </div>


</div>


@endsection