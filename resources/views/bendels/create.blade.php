<x-layouts.app title="Tambah Bendel">

    <div class="sipadu-page">

        <div class="mb-6">
            <h1 class="sipadu-title">
                📄 Tambah Bendel Retribusi
            </h1>

            <p class="sipadu-subtitle">
                Input arsip bendel retribusi.
            </p>
        </div>


        <div class="sipadu-card">

            <div class="sipadu-card-body">

                <form action="{{ route('bendels.store') }}" method="POST">

                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                        <div>
                            <label class="block mb-2 font-semibold">
                                Pasar
                            </label>

                            <select name="market_id"
                                class="sipadu-select">

                                <option value="">
                                    Pilih Pasar
                                </option>

                                @foreach($markets as $market)

                                    <option value="{{ $market->id }}">
                                        {{ $market->name }}
                                    </option>

                                @endforeach

                            </select>
                        </div>


                        <div>
                            <label class="block mb-2 font-semibold">
                                Nomor Bendel
                            </label>

                            <input type="text"
                                name="nomor_bendel"
                                class="sipadu-input"
                                placeholder="Contoh: BD-001">
                        </div>


                        <div>
                            <label class="block mb-2 font-semibold">
                                Tanggal
                            </label>

                            <input type="date"
                                name="tanggal"
                                class="sipadu-input">
                        </div>


                        <div>
                            <label class="block mb-2 font-semibold">
                                Periode
                            </label>

                            <input type="text"
                                name="periode"
                                class="sipadu-input"
                                placeholder="Contoh: Juli 2026">
                        </div>


                        <div>
                            <label class="block mb-2 font-semibold">
                                Status
                            </label>

                            <select name="status"
                                class="sipadu-select">

                                <option value="draft">
                                    Draft
                                </option>

                                <option value="selesai">
                                    Selesai
                                </option>

                                <option value="terverifikasi">
                                    Terverifikasi
                                </option>

                            </select>
                        </div>


                    </div>


                    <div class="mt-6">

                        <label class="block mb-2 font-semibold">
                            Keterangan
                        </label>

                        <textarea name="keterangan"
                            rows="4"
                            class="sipadu-textarea"
                            placeholder="Keterangan bendel"></textarea>

                    </div>


                    <div class="mt-6 flex gap-3">

                        <button type="submit"
                            class="sipadu-btn sipadu-btn-primary">
                            Simpan Bendel
                        </button>


                        <a href="{{ route('bendels.index') }}"
                           class="sipadu-btn sipadu-btn-secondary">
                            Kembali
                        </a>

                    </div>


                </form>

            </div>

        </div>

    </div>

</x-layouts.app>