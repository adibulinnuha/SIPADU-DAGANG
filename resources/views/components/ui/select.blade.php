@props([
    'label' => null,
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => 'Pilih data',
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


    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' => 'sipadu-select'
        ]) }}
    >

        <option value="">
            {{ $placeholder }}
        </option>

        @foreach($options as $key => $option)

            <option
                value="{{ $key }}"
                @selected(old($name, $value) == $key)
            >
                {{ $option }}
            </option>

        @endforeach

    </select>


    @error($name)
        <p class="text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror

</div>