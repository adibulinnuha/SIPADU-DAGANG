```blade
@php
    $markets = \App\Models\Market::orderBy('name')->get();
@endphp

<x-layouts.app :title="'Dashboard ERET'">
    <div class="space-y-6">
	@if(session('success'))
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
        {{ session('success') }}
    </div>
@endif
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard ERET</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Input retribusi harian langsung seperti lembar Excel
                </p>
            </div>

            <a href="{{ route('retributions.export-template', ['date' => request('tanggal', now()->toDateString())]) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Export Template ERET
            </a>
        </div>

        <form method="GET" class="flex items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date"
                       name="tanggal"
                       value="{{ request('tanggal', now()->toDateString()) }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Tampilkan Data
            </button>
        </form>

        <form method="POST" action="{{ route('dashboard.eret.save') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="tanggal" value="{{ request('tanggal', now()->toDateString()) }}">

            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="border px-3 py-2 text-left font-semibold">No</th>
                            <th class="border px-3 py-2 text-left font-semibold">Pasar</th>
                            <th class="border px-3 py-2 text-right font-semibold">Kios</th>
                            <th class="border px-3 py-2 text-right font-semibold">Los</th>
                            <th class="border px-3 py-2 text-right font-semibold">Dasaran</th>
                            <th class="border px-3 py-2 text-right font-semibold">MCK</th>
                            <th class="border px-3 py-2 text-right font-semibold">Sampah</th>
                            <th class="border px-3 py-2 text-right font-semibold">Listrik</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($markets as $market)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="border px-3 py-2 text-center">{{ $loop->iteration }}</td>

                                <td class="border px-3 py-2 font-medium text-gray-900 dark:text-white">
                                    {{ $market->name }}
                                    <input type="hidden" name="rows[{{ $loop->index }}][market_id]" value="{{ $market->id }}">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][kios]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][los]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][dasaran]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][mck]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][sampah]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>

                                <td class="border p-1">
                                    <input type="number" step="0.01" min="0"
                                           name="rows[{{ $loop->index }}][listrik]"
                                           class="w-28 rounded border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">
                    Simpan Semua
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
```
