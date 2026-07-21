<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIPADU-DAGANG') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-100">

<div class="min-h-screen flex">

    @include('layouts.sidebar')

    <div class="flex-1">

        @include('layouts.navigation')

        <main class="p-6">
            @yield('content')
        </main>

    </div>

</div>

@stack('scripts')

</body>
</html>
{{-- Layout lama tidak digunakan --}}