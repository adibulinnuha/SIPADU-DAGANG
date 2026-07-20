@extends('layouts.app')

@section('content')

<div class="container mx-auto px-4 py-6">

    <div class="bg-white shadow rounded-lg p-6">

        <h1 class="text-2xl font-bold mb-4">
            OCR Struk e-Ticketing
        </h1>

        <p class="text-gray-600 mb-6">
            Upload foto struk untuk membaca data retribusi secara otomatis.
        </p>


        <form action="{{ route('ocr.process') }}" 
              method="POST" 
              enctype="multipart/form-data">

            @csrf

            <div class="mb-4">

                <label class="block font-semibold mb-2">
                    Foto Struk
                </label>

                <input type="file"
                       name="image"
                       accept="image/*"
                       class="border rounded p-2 w-full"
                       required>

            </div>


            <div class="mb-4">

                <img id="preview"
                     class="hidden w-64 rounded border"
                     alt="Preview">

            </div>


            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded">
                Proses OCR
            </button>

        </form>


        @if(session('ocr_result'))

        <div class="mt-6">

            <h2 class="font-bold text-lg mb-3">
                Hasil Pembacaan
            </h2>

            <table class="w-full border">

                <tbody>

                    @foreach(session('ocr_result') as $key => $value)

                    <tr class="border">

                        <td class="p-2 font-semibold">
                            {{ $key }}
                        </td>

                        <td class="p-2">
                            {{ $value }}
                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>


            <button class="mt-4 bg-green-600 text-white px-5 py-2 rounded">
                Gunakan Data
            </button>

        </div>

        @endif


    </div>

</div>


<script>

const input = document.querySelector('input[name="image"]');
const preview = document.getElementById('preview');


input.addEventListener('change', function(event){

    const file = event.target.files[0];

    if(file){

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');

    }

});

</script>


@endsection