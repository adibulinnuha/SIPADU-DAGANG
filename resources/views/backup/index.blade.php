<x-layouts.app title="Backup & Restore">

    <div class="space-y-6">

        <!-- Header -->
        <div class="rounded-2xl bg-gradient-to-r from-emerald-700 to-teal-600 p-6 text-white shadow">
            <h1 class="text-2xl font-bold">
                💾 Backup & Restore
            </h1>
            <p class="mt-2 text-emerald-100">
                Kelola backup database, workbook, dan konfigurasi aplikasi.
            </p>
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700">
                ❌ {{ session('error') }}
            </div>
        @endif

        <!-- Create Backup -->
        <div class="rounded-2xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-slate-800">
                Buat Backup Baru
            </h2>

            <form action="{{ route('backup.create') }}" method="POST" class="flex flex-wrap items-end gap-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-600">Jenis Backup</label>
                    <select name="type" class="rounded-lg border p-3">
                        <option value="full">Full (DB + Workbook + Config)</option>
                        <option value="database">Database</option>
                        <option value="workbook">Workbook</option>
                        <option value="config">Konfigurasi</option>
                    </select>
                </div>

                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700">
                    Buat Backup
                </button>
            </form>

            <p class="mt-3 text-xs text-slate-400">
                Backup disimpan di <code>storage/app/backups</code> dengan nama file ber-timestamp, checksum SHA-256, dan validasi struktur.
            </p>
        </div>

        <!-- Backup List -->
        <div class="rounded-2xl border bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-slate-800">
                Daftar Backup
            </h2>

            @if(empty($backups))
                <p class="text-slate-500">
                    Belum ada backup. Buat backup pertama dengan tombol di atas.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b bg-slate-50 text-left">
                                <th class="p-3 font-semibold text-slate-600">File</th>
                                <th class="p-3 font-semibold text-slate-600">Tipe</th>
                                <th class="p-3 font-semibold text-slate-600">Ukuran</th>
                                <th class="p-3 font-semibold text-slate-600">Dibuat</th>
                                <th class="p-3 font-semibold text-slate-600">Checksum</th>
                                <th class="p-3 font-semibold text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($backups as $backup)
                            <tr class="border-b">
                                <td class="p-3 font-mono text-xs">{{ $backup['filename'] }}</td>
                                <td class="p-3">
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                                        {{ $backup['type'] }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-700">{{ number_format($backup['size']) }} B</td>
                                <td class="p-3 text-slate-700">{{ $backup['created_at'] }}</td>
                                <td class="p-3 font-mono text-xs text-slate-500">{{ substr($backup['checksum'], 0, 12) }}…</td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('backup.download', $backup['filename']) }}"
                                           class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                            ⬇️ Download
                                        </a>

                                        {{-- Delete --}}
                                        <form action="{{ route('backup.destroy', $backup['filename']) }}" method="POST"
                                              onsubmit="return confirm('Hapus backup ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                                🗑️ Hapus
                                            </button>
                                        </form>

                                        {{-- Restore --}}
                                        <form action="{{ route('backup.restore', $backup['filename']) }}" method="POST"
                                              onsubmit="return confirm('Yakin restore backup ini? Data saat ini akan ditimpa. Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            <input type="hidden" name="confirmed" value="1">
                                            <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                                ♻️ Restore
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</x-layouts.app>
