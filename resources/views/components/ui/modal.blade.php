@props([
    'id',
    'title' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
@endphp


<div
    id="{{ $id }}"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4"
>

    <div class="w-full {{ $sizes[$size] ?? $sizes['md'] }} bg-white rounded-xl shadow-xl">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

            @if($title)
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $title }}
                </h2>
            @endif


            <button
                type="button"
                onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                class="text-slate-500 hover:text-slate-700"
            >
                ×
            </button>

        </div>


        <div class="p-6">

            {{ $slot }}

        </div>


    </div>

</div>