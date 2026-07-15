@props([
    'label' => null,
    'name',
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
    'required' => false,
])

<div class="space-y-2">

    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}

            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif


    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' => 'sipadu-textarea'
        ]) }}
    >{{ old($name, $value) }}</textarea>


    @error($name)
        <p class="text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror

</div>