@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
    'footer' => null,
])

<div {{ $attributes->class(['sipadu-card']) }}>

    @if($title || $subtitle)
        <div class="sipadu-card-header">
            @if($title)
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $title }}
                </h2>
            @endif

            @if($subtitle)
                <p class="mt-1 text-sm text-slate-500">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    @endif

    <div @class([$padding ? 'sipadu-card-body' : ''])>
        {{ $slot }}
    </div>

    @if($footer)
        <div class="border-t border-slate-200 px-6 py-4">
            {{ $footer }}
        </div>
    @endif

</div>