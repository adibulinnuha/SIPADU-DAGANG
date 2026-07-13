@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tambah Pasar</h1>

    <form action="{{ route('markets.store') }}" method="POST">
        @csrf

        <div>
            <label>Nama Pasar</label>
            <input type="text" name="name">
        </div>

        <div>
            <label>Kode Pasar</label>
            <input type="text" name="code">
        </div>

        <div>
            <label>Alamat</label>
            <input type="text" name="address">
        </div>

        <div>
            <label>Telepon</label>
            <input type="text" name="phone">
        </div>

        <div>
            <label>Status</label>
            <select name="is_active">
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>

        <button type="submit">
            Simpan
        </button>

    </form>
</div>
@endsection