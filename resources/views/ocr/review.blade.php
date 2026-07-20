<x-app-layout>

<div class="max-w-5xl mx-auto p-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">

        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold text-gray-800">
                Review Hasil OCR e-Ticketing
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Periksa data hasil pembacaan struk sebelum disimpan ke transaksi retribusi.
            </p>
        </div>


        <form action="{{ route('ocr.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Tanggal
                    </label>

                    <input type="date"
                           name="tanggal"
                           value="{{ $ocrData['tanggal'] }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nomor Tiket
                    </label>

                    <input type="text"
                           name="nomor_tiket"
                           value="{{ $ocrData['nomor_tiket'] }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Pasar
                    </label>

                    <input type="text"
                           name="pasar"
                           value="{{ $ocrData['pasar'] }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Jenis Retribusi
                    </label>

                    <select name="jenis_retribusi"
                            class="mt-1 w-full rounded-lg border-gray-300">

                        <option value="">
                            Pilih Jenis
                        </option>

                        <option value="Kios">
                            Kios
                        </option>

                        <option value="Los">
                            Los
                        </option>

                        <option value="Dasaran Terbuka">
                            Dasaran Terbuka
                        </option>

                        <option value="MCK">
                            MCK
                        </option>

                        <option value="Kebersihan">
                            Kebersihan
                        </option>

                        <option value="Listrik">
                            Listrik
                        </option>

                    </select>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nominal
                    </label>

                    <input type="number"
                           name="nominal"
                           value="{{ $ocrData['nominal'] }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>


            </div>


            <div class="mt-6 flex justify-end gap-3">

                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 rounded-lg border">
                    Batal
                </a>


                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white">
                    Simpan Transaksi
                </button>

            </div>


        </form>

    </div>

</div>

</x-app-layout>