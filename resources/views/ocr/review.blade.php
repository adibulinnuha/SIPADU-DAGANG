<x-layouts.app title="Review OCR e-Ticketing">

<div class="space-y-6">


    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-purple-600 p-6 text-white shadow">

        <h1 class="text-2xl font-bold">
            Review Hasil OCR
        </h1>

        <p class="mt-2 text-blue-100">
            Periksa dan koreksi data sebelum masuk sistem retribusi.
        </p>

    </div>



    <div class="rounded-2xl border bg-white p-6 shadow-sm">


        <form action="{{ route('ocr.store') }}"
              method="POST">

            @csrf



            <div class="grid gap-5 md:grid-cols-2">


                <div>

                    <label class="font-semibold">
                        Nomor Setor
                    </label>

                    <input type="text"
                           name="nomor_setor"
                           value="{{ $ocr['nomor_setor'] ?? '' }}"
                           class="mt-2 w-full rounded-lg border p-3">

                </div>



                <div>

                    <label class="font-semibold">
                        Tanggal
                    </label>

                    <input type="date"
                           name="tanggal"
                           value="{{ $ocr['tanggal'] ?? '' }}"
                           class="mt-2 w-full rounded-lg border p-3">

                </div>



                <div>

                    <label class="font-semibold">
                        Pasar
                    </label>

                    <input type="text"
                           name="pasar"
                           value="{{ $ocr['pasar'] ?? '' }}"
                           class="mt-2 w-full rounded-lg border p-3">

                </div>



                <div>

                    <label class="font-semibold">
                        Jenis Retribusi
                    </label>

                    <input type="text"
                           name="jenis_retribusi"
                           value="{{ $ocr['jenis_retribusi'] ?? '' }}"
                           class="mt-2 w-full rounded-lg border p-3">

                </div>



                <div>

                    <label class="font-semibold">
                        Nominal
                    </label>

                    <input type="number"
                           name="nominal"
                           value="{{ $ocr['nominal'] ?? '' }}"
                           class="mt-2 w-full rounded-lg border p-3">

                </div>



            </div>



            <div class="mt-6 flex gap-3">


                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-6 py-3 text-white">

                    Simpan Retribusi

                </button>



                <a href="{{ route('ocr.index') }}"
                   class="rounded-lg bg-slate-600 px-6 py-3 text-white">

                    Kembali

                </a>


            </div>


        </form>


    </div>


</div>


</x-layouts.app>