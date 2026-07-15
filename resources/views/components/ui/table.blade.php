@props([
    'headers' => [],
])

<div class="overflow-x-auto sipadu-card">

    <table {{ $attributes->merge([
        'class' => 'sipadu-table'
    ]) }}>

        <thead>
            <tr>
                @foreach($headers as $header)

                    <th>
                        {{ $header }}
                    </th>

                @endforeach
            </tr>
        </thead>


        <tbody>

            {{ $slot }}

        </tbody>

    </table>

</div>