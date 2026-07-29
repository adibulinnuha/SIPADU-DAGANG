<x-layouts.app title="OCR e-Ticketing">

<div class="space-y-6">

    <!-- Header -->
    <div class="rounded-2xl bg-gradient-to-r from-purple-700 to-blue-600 p-6 text-white shadow">
        <h1 class="text-2xl font-bold">
            OCR e-Ticketing
        </h1>
        <p class="mt-2 text-purple-100">
            Upload foto tiket retribusi untuk pembacaan data otomatis.
        </p>
    </div>

    <!-- Upload -->
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-800">
            Upload Foto Struk
        </h2>

        <form action="{{ route('ocr.process') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="rounded-xl border-2 border-dashed border-slate-300 p-6">
                <label class="mb-3 block font-semibold">
                    Pilih Foto e-Ticketing
                </label>

                <input type="file" name="image" accept="image/*" required class="w-full rounded-lg border p-3">

                <div class="mt-5">
                    <img id="preview" class="hidden max-h-96 rounded-xl border shadow" alt="Preview Tiket">
                </div>
            </div>

            <button type="submit" class="mt-5 rounded-lg bg-purple-600 px-6 py-3 font-semibold text-white hover:bg-purple-700">
                Proses OCR
            </button>
        </form>
    </div>

    <!-- Hasil OCR -->
    @if(session('ocr_result'))
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold">Review Hasil OCR</h2>
            <span class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-700">Berhasil Dibaca</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b bg-slate-50">
                        <th class="w-1/3 p-3 text-left font-semibold text-slate-600">Field</th>
                        <th class="p-3 text-left font-semibold text-slate-600">Value</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(session('ocr_result') as $key => $value)
                    @if($key === 'image')
                        @continue
                    @endif
                    <tr class="border-b">
                        <td class="p-3 font-semibold text-slate-600">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td class="p-3 text-slate-800">{{ $value }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <form action="{{ route('ocr.store') }}" method="POST" class="mt-5">
            @csrf
            @foreach(session('ocr_result') as $key => $value)
                @if($key !== 'image')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700">
                Simpan ke Retribusi
            </button>
        </form>
    </div>
    @endif

</div>

@push('scripts')
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
@endpush

</x-layouts.app>

