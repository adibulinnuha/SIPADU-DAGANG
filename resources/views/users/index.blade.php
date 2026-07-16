@extends('layouts.app')

@section('content')

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

            <div class="p-6 text-gray-900">

                <div class="flex justify-between mb-6">

                    <h2 class="text-xl font-semibold">
                        Manajemen User SIPADU-DAGANG
                    </h2>

                    <a href="{{ route('users.create') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded">
                        Tambah User
                    </a>

                </div>


                @if(session('success'))

                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                        {{ session('success') }}
                    </div>

                @endif


                <table class="w-full border">

                    <thead>
                        <tr class="bg-gray-100">

                            <th class="border px-4 py-2">
                                Nama
                            </th>

                            <th class="border px-4 py-2">
                                Email
                            </th>

                            <th class="border px-4 py-2">
                                Role
                            </th>

                            <th class="border px-4 py-2">
                                Aksi
                            </th>

                        </tr>
                    </thead>


                    <tbody>

                    @foreach($users as $user)

                        <tr>

                            <td class="border px-4 py-2">
                                {{ $user->name }}
                            </td>

                            <td class="border px-4 py-2">
                                {{ $user->email }}
                            </td>

                            <td class="border px-4 py-2">
                                {{ $user->role->value ?? $user->role }}
                            </td>

                            <td class="border px-4 py-2">

                                <a href="{{ route('users.edit',$user) }}"
                                   class="text-blue-600">
                                    Edit
                                </a>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>


            </div>

        </div>

    </div>
</div>

@endsection