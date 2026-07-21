<x-layouts.app title="OCR e-Ticketing">

<div class="container mx-auto max-w-3xl px-4 py-8">

    <div class="bg-white shadow rounded-xl p-6">

        <h1 class="text-2xl font-bold mb-2">
            OCR e-Ticketing SIPADU-DAGANG
        </h1>

        <p class="text-gray-600 mb-6">
            Upload foto e-Ticketing untuk dibaca oleh AI.
        </p>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4">
                <ul class="list-disc list-inside text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('ocr.process') }}"
            method="POST"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="mb-6">

                <label class="block font-semibold mb-2">
                    Foto e-Ticketing
                </label>

                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/*"
                    class="block w-full border rounded-lg p-3"
                    required
                >

            </div>

            <div class="mb-6">

                <img
                    id="preview"
                    class="hidden w-full rounded-lg border"
                >

            </div>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold"
            >
                Proses OCR
            </button>

        </form>

    </div>

</div>

@push('scripts')
<script>
document.getElementById('image').addEventListener('change', function (e) {

    const file = e.target.files[0];

    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (event) {

        const preview = document.getElementById('preview');

        preview.src = event.target.result;
        preview.classList.remove('hidden');

    };

    reader.readAsDataURL(file);

});
</script>
@endpush

</x-layouts.app>