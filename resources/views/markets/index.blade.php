@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Master Pasar</h1>

    <a href="{{ route('markets.create') }}">
        Tambah Pasar
    </a>

    <br><br>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Pasar</th>
                <th>Alamat</th>
                <th>Telepon</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($markets as $market)

            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $market->code }}</td>
                <td>{{ $market->name }}</td>
                <td>{{ $market->address }}</td>
                <td>{{ $market->phone }}</td>
                <td>
                    {{ $market->is_active ? 'Aktif' : 'Nonaktif' }}
                </td>

                <td>
                    <a href="{{ route('markets.edit', $market->id) }}">
                        Edit
                    </a>

                    <form action="{{ route('markets.destroy', $market->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')

                        <button type="submit">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>

            @empty

            <tr>
                <td colspan="7">
                    Belum ada data pasar
                </td>
            </tr>

            @endforelse
        </tbody>

    </table>

</div>
@endsection