```blade
@php
    $manualMarkets = collect([
        ['id' => 1, 'name' => 'REJOMULYO'],
        ['id' => 2, 'name' => 'TAMBAK LOROK'],
        ['id' => 3, 'name' => 'WARU INDAH'],
        ['id' => 4, 'name' => 'REJOMULYO IB'],
        ['id' => 5, 'name' => 'DARGO'],
        ['id' => 6, 'name' => 'BUBAKAN'],
    ]);

    $eretMarkets = collect([
        'KARIMATA 1',
        'KARIMATA 2',
        'DARGO',
        'LANGGAR',
        'WARU INDAH 1',
        'WARU INDAH 2',
    ]);

    $tanggalAktif = request('tanggal', now()->toDateString());
@endphp

<x-layouts.app :title="'Dashboard ERET'">
    <div class="space-y-8" x-data="eretDashboard()">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard ERET</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Input retribusi langsung seperti lembar Excel
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.eret.save') }}" class="space-y-8">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggalAktif }}">

            {{-- RETRIBUSI MANUAL --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Retribusi Manual</h2>
                </div>

                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="border px-3 py-2">No</th>
                            <th class="border px-3 py-2 text-left">Retribusi Manual</th>
                            <th class="border px-3 py-2 text-right">Kios</th>
                            <th class="border px-3 py-2 text-right">Los</th>
                            <th class="border px-3 py-2 text-right">Dasaran</th>
                            <th class="border px-3 py-2 text-right">Sampah</th>
                            <th class="border px-3 py-2 text-right bg-blue-50 dark:bg-blue-900/20">Total Harian Retribusi</th>
                            <th class="border px-3 py-2 text-right bg-yellow-50 dark:bg-yellow-900/20">Total Harian Kebersihan</th>
                            <th class="border px-3 py-2 text-right bg-emerald-50 dark:bg-emerald-900/20">TOTAL</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($manualMarkets as $market)
                            <tr>
                                <td class="border px-3 py-2 text-center">{{ $loop->iteration }}</td>

                                <td class="border px-3 py-2 font-medium text-gray-900 dark:text-white">
                                    {{ $market['name'] }}
                                    <input type="hidden" name="rows[{{ $loop->index }}][market_id]" value="{{ $market['id'] }}">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][kios]"
                                           x-model.number="manual[{{ $loop->index }}].kios"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][los]"
                                           x-model.number="manual[{{ $loop->index }}].los"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][dasaran]"
                                           x-model.number="manual[{{ $loop->index }}].dasaran"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][sampah]"
                                           x-model.number="manual[{{ $loop->index }}].sampah"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border bg-blue-50 px-2 py-1 text-right font-semibold text-blue-700 dark:bg-blue-900/10 dark:text-blue-300"
                                    x-text="format(manualRetribusi({{ $loop->index }}))"></td>

                                <td class="border bg-yellow-50 px-2 py-1 text-right font-semibold text-yellow-700 dark:bg-yellow-900/10 dark:text-yellow-300"
                                    x-text="format(manualKebersihan({{ $loop->index }}))"></td>

                                <td class="border bg-emerald-50 px-2 py-1 text-right font-semibold text-emerald-700 dark:bg-emerald-900/10 dark:text-emerald-300"
                                    x-text="format(manualTotal({{ $loop->index }}))"></td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="bg-emerald-100 dark:bg-emerald-900/30">
                        <tr>
                            <td colspan="2" class="border px-3 py-2 text-right font-bold">TOTAL RETRIBUSI MANUAL</td>
                            <td class="border px-3 py-2 text-right font-bold" x-text="format(sumManual('kios'))"></td>
                            <td class="border px-3 py-2 text-right font-bold" x-text="format(sumManual('los'))"></td>
                            <td class="border px-3 py-2 text-right font-bold" x-text="format(sumManual('dasaran'))"></td>
                            <td class="border px-3 py-2 text-right font-bold" x-text="format(sumManual('sampah'))"></td>
                            <td class="border px-3 py-2 text-right font-bold text-blue-700 dark:text-blue-300" x-text="format(totalManualRetribusi())"></td>
                            <td class="border px-3 py-2 text-right font-bold text-yellow-700 dark:text-yellow-300" x-text="format(totalManualKebersihan())"></td>
                            <td class="border px-3 py-2 text-right font-bold text-emerald-700 dark:text-emerald-300" x-text="format(grandManual())"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- MCK --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">MCK</h2>
                </div>

                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="border px-3 py-2">No</th>
                            <th class="border px-3 py-2 text-left">Retribusi Manual</th>
                            <th class="border px-3 py-2 text-right">Nominal MCK</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($manualMarkets as $market)
                            <tr>
                                <td class="border px-3 py-2 text-center">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $market['name'] }}</td>
                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][mck]"
                                           x-model.number="mck[{{ $loop->index }}]"
                                           class="w-32 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- LISTRIK --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Listrik</h2>
                </div>

                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="border px-3 py-2">No</th>
                            <th class="border px-3 py-2 text-left">Retribusi Manual</th>
                            <th class="border px-3 py-2 text-right">Nominal Listrik</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($manualMarkets as $market)
                            <tr>
                                <td class="border px-3 py-2 text-center">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $market['name'] }}</td>
                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           name="rows[{{ $loop->index }}][listrik]"
                                           x-model.number="listrik[{{ $loop->index }}]"
                                           class="w-32 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- E-RETRIBUSI --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">E-RETRIBUSI</h2>
                </div>

                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="border px-3 py-2">No</th>
                            <th class="border px-3 py-2 text-left">E-RETRIBUSI</th>
                            <th class="border px-3 py-2 text-right">Kios</th>
                            <th class="border px-3 py-2 text-right">Los</th>
                            <th class="border px-3 py-2 text-right">Dasaran</th>
                            <th class="border px-3 py-2 text-right">Sampah</th>
                            <th class="border px-3 py-2 text-right bg-blue-50 dark:bg-blue-900/20">Total Harian Retribusi</th>
                            <th class="border px-3 py-2 text-right bg-yellow-50 dark:bg-yellow-900/20">Total Harian Kebersihan</th>
                            <th class="border px-3 py-2 text-right bg-emerald-50 dark:bg-emerald-900/20">TOTAL</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($eretMarkets as $eretName)
                            <tr>
                                <td class="border px-3 py-2 text-center">{{ $loop->iteration }}</td>
                                <td class="border px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $eretName }}</td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           x-model.number="eret[{{ $loop->index }}].kios"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           x-model.number="eret[{{ $loop->index }}].los"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           x-model.number="eret[{{ $loop->index }}].dasaran"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" min="0" step="0.01"
                                           x-model.number="eret[{{ $loop->index }}].sampah"
                                           class="w-24 rounded border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border bg-blue-50 px-2 py-1 text-right font-semibold text-blue-700 dark:bg-blue-900/10 dark:text-blue-300"
                                    x-text="format(eretRetribusi({{ $loop->index }}))"></td>

                                <td class="border bg-yellow-50 px-2 py-1 text-right font-semibold text-yellow-700 dark:bg-yellow-900/10 dark:text-yellow-300"
                                    x-text="format(eretKebersihan({{ $loop->index }}))"></td>

                                <td class="border bg-emerald-50 px-2 py-1 text-right font-semibold text-emerald-700 dark:bg-emerald-900/10 dark:text-emerald-300"
                                    x-text="format(eretTotal({{ $loop->index }}))"></td>
                            </tr>
                        @endforeach
                    </tbody>

                    ```blade
<tfoot class="bg-blue-100 dark:bg-blue-900/30">
    {{-- TOTAL KARIMATA --}}
    <tr class="bg-slate-100 dark:bg-slate-800">
        <td colspan="2" class="border px-3 py-2 text-right font-semibold">TOTAL KARIMATA</td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[0].kios || 0) + (eret[1].kios || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[0].los || 0) + (eret[1].los || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[0].dasaran || 0) + (eret[1].dasaran || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[0].sampah || 0) + (eret[1].sampah || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-blue-700 dark:text-blue-300" x-text="format(eretRetribusi(0) + eretRetribusi(1))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-yellow-700 dark:text-yellow-300" x-text="format(eretKebersihan(0) + eretKebersihan(1))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300" x-text="format(eretTotal(0) + eretTotal(1))"></td>
    </tr>

    {{-- TOTAL WARU INDAH --}}
    <tr class="bg-slate-100 dark:bg-slate-800">
        <td colspan="2" class="border px-3 py-2 text-right font-semibold">TOTAL WARU INDAH</td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[4].kios || 0) + (eret[5].kios || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[4].los || 0) + (eret[5].los || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[4].dasaran || 0) + (eret[5].dasaran || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold" x-text="format((eret[4].sampah || 0) + (eret[5].sampah || 0))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-blue-700 dark:text-blue-300" x-text="format(eretRetribusi(4) + eretRetribusi(5))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-yellow-700 dark:text-yellow-300" x-text="format(eretKebersihan(4) + eretKebersihan(5))"></td>
        <td class="border px-3 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300" x-text="format(eretTotal(4) + eretTotal(5))"></td>
    </tr>

    {{-- TOTAL E-RETRIBUSI --}}
    <tr>
        <td colspan="2" class="border px-3 py-2 text-right font-bold">TOTAL E-RETRIBUSI</td>
        <td class="border px-3 py-2 text-right font-bold" x-text="format(sumEret('kios'))"></td>
        <td class="border px-3 py-2 text-right font-bold" x-text="format(sumEret('los'))"></td>
        <td class="border px-3 py-2 text-right font-bold" x-text="format(sumEret('dasaran'))"></td>
        <td class="border px-3 py-2 text-right font-bold" x-text="format(sumEret('sampah'))"></td>
        <td class="border px-3 py-2 text-right font-bold text-blue-700 dark:text-blue-300" x-text="format(totalEretRetribusi())"></td>
        <td class="border px-3 py-2 text-right font-bold text-yellow-700 dark:text-yellow-300" x-text="format(totalEretKebersihan())"></td>
        <td class="border px-3 py-2 text-right font-bold text-emerald-700 dark:text-emerald-300" x-text="format(grandEret())"></td>
    </tr>
</tfoot>
```

                </table>
            </div>

            {{-- GRAND TOTAL --}}
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 shadow-sm dark:border-emerald-700 dark:bg-emerald-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-emerald-900 dark:text-emerald-100">GRAND TOTAL KESELURUHAN</h2>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">
                            Total Retribusi Manual + Total E-RETRIBUSI + Total MCK + Total Listrik
                        </p>
                    </div>

                    <div class="text-right">
                        <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Total Hari Ini</div>
                        <div class="text-3xl font-extrabold text-emerald-700 dark:text-emerald-300"
                             x-text="format(grandKeseluruhan())"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">
                    Simpan Semua
                </button>
            </div>
        </form>
    </div>

    <script>
        function eretDashboard() {
            return {
                manual: Array.from({ length: {{ $manualMarkets->count() }} }, () => ({ kios: 0, los: 0, dasaran: 0, sampah: 0 })),
                eret: Array.from({ length: {{ $eretMarkets->count() }} }, () => ({ kios: 0, los: 0, dasaran: 0, sampah: 0 })),
                mck: Array.from({ length: {{ $manualMarkets->count() }} }, () => 0),
                listrik: Array.from({ length: {{ $manualMarkets->count() }} }, () => 0),

                format(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
                },

                manualRetribusi(i) {
                    const r = this.manual[i];
                    return (r.kios || 0) + (r.los || 0) + (r.dasaran || 0);
                },

                manualKebersihan(i) {
                    return this.manual[i].sampah || 0;
                },

                manualTotal(i) {
                    return this.manualRetribusi(i) + this.manualKebersihan(i);
                },

                eretRetribusi(i) {
                    const r = this.eret[i];
                    return (r.kios || 0) + (r.los || 0) + (r.dasaran || 0);
                },

                eretKebersihan(i) {
                    return this.eret[i].sampah || 0;
                },

                eretTotal(i) {
                    return this.eretRetribusi(i) + this.eretKebersihan(i);
                },

                sumManual(key) {
                    return this.manual.reduce((sum, row) => sum + Number(row[key] || 0), 0);
                },

                sumEret(key) {
                    return this.eret.reduce((sum, row) => sum + Number(row[key] || 0), 0);
                },

                totalManualRetribusi() {
                    return this.manual.reduce((sum, _, i) => sum + this.manualRetribusi(i), 0);
                },

                totalManualKebersihan() {
                    return this.sumManual('sampah');
                },

                grandManual() {
                    return this.totalManualRetribusi() + this.totalManualKebersihan();
                },

                totalEretRetribusi() {
                    return this.eret.reduce((sum, _, i) => sum + this.eretRetribusi(i), 0);
                },

                totalEretKebersihan() {
                    return this.sumEret('sampah');
                },

                grandEret() {
                    return this.totalEretRetribusi() + this.totalEretKebersihan();
                },

                totalMck() {
                    return this.mck.reduce((sum, value) => sum + Number(value || 0), 0);
                },

                totalListrik() {
                    return this.listrik.reduce((sum, value) => sum + Number(value || 0), 0);
                },

                grandKeseluruhan() {
                    return this.grandManual() + this.grandEret() + this.totalMck() + this.totalListrik();
                }
            }
        }
    </script>
</x-layouts.app>
```
