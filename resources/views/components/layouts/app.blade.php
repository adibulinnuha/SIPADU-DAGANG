<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'SIPADU-DAGANG') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-100">

<div class="min-h-screen flex">

    @include('layouts.sidebar')

    <div class="flex-1">

        @include('layouts.navigation')

        <main class="p-6">
            {{ $slot }}
        </main>

    </div>

</div>

@stack('scripts')

</body>
</html><!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'SIPADU-DAGANG') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-100">

<div class="min-h-screen flex">

    @include('layouts.sidebar')

    <div class="flex-1">

        @include('layouts.navigation')

        <main class="p-6">
            {{ $slot }}
        </main>

    </div>

</div>

@stack('scripts')

</body>
</html>