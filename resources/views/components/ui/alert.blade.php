@props([
    'type' => 'success',
    'title' => null,
])

@php
    $types = [
        'success' => [
            'class' => 'bg-green-50 border-green-200 text-green-800',
            'icon' => '✓',
        ],

        'warning' => [
            'class' => 'bg-amber-50 border-amber-200 text-amber-800',
            'icon' => '!',
        ],

        'danger' => [
            'class' => 'bg-red-50 border-red-200 text-red-800',
            'icon' => '×',
        ],

        'info' => [
            'class' => 'bg-blue-50 border-blue-200 text-blue-800',
            'icon' => 'i',
        ],
    ];

    $alert = $types[$type] ?? $types['success'];
@endphp


<div {{ $attributes->merge([
    'class' => 'border rounded-lg p-4 flex gap-3 ' . $alert['class']
]) }}>

    <div class="flex-shrink-0 font-bold">
        {{ $alert['icon'] }}
    </div>


    <div>

        @if($title)
            <h3 class="font-semibold">
                {{ $title }}
            </h3>
        @endif


        <div class="text-sm mt-1">
            {{ $slot }}
        </div>

    </div>

</div>