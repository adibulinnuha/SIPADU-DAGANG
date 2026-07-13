@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Edit Pasar</h1>

    <form action="{{ route('markets.update', $market->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label>Nama Pasar</label>
            <input type="text" name="name" value="{{ $market->name }}">
        </div>

        <br>

        <div>
            <label>Kode Pasar</label>
            <input type="text" name="code" value="{{ $market->code }}">
        </div>

        <br>

        <div>
            <label>Alamat</label>
            <input type="text" name="address" value="{{ $market->address }}">
        </div>

        <br>

        <div>
            <label>Telepon</label>
            <input type="text" name="phone" value="{{ $market->phone }}">
        </div>

        <br>

        <div>
            <label>Status</label>
            <select name="is_active">
                <option value="1" {{ $market->is_active ? 'selected' : '' }}>
                    Aktif
                </option>

                <option value="0" {{ !$market->is_active ? 'selected' : '' }}>
                    Nonaktif
                </option>
            </select>
        </div>

        <br>

        <button type="submit">
            Update
        </button>

    </form>

</div>
@endsection