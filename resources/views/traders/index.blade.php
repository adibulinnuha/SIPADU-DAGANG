<x-layouts.app>
    <h1>Master Pedagang</h1>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pedagang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($traders as $trader)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $trader->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Belum ada data pedagang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-layouts.app>