<x-layouts.app title="Bendel">

    <div class="sipadu-page">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="sipadu-title">
                    📄 Bendel Retribusi
                </h1>
                <p class="sipadu-subtitle">
                    Arsip dan monitoring dokumen bendel retribusi.
                </p>
            </div>

            <a href="{{ route('bendels.create') }}"
               class="sipadu-btn sipadu-btn-primary">
                + Tambah Bendel
            </a>
        </div>


        <div class="sipadu-card">

            <div class="sipadu-card-body">

                <div class="overflow-x-auto">

                    <table class="sipadu-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor Bendel</th>
                                <th>Pasar</th>
                                <th>Tanggal</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse($bendels as $bendel)

                            <tr>
                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td class="font-semibold">
                                    {{ $bendel->nomor_bendel }}
                                </td>

                                <td>
                                    {{ $bendel->market->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $bendel->tanggal }}
                                </td>

                                <td>
                                    {{ $bendel->periode }}
                                </td>

                                <td>

                                    @if($bendel->status == 'selesai')
                                        <span class="sipadu-badge sipadu-badge-success">
                                            Selesai
                                        </span>
                                    @elseif($bendel->status == 'terverifikasi')
                                        <span class="sipadu-badge sipadu-badge-success">
                                            Terverifikasi
                                        </span>
                                    @else
                                        <span class="sipadu-badge sipadu-badge-warning">
                                            Draft
                                        </span>
                                    @endif

                                </td>

                                <td>
                                    <a href="{{ route('bendels.show',$bendel) }}"
                                       class="text-blue-600 hover:underline">
                                        Detail
                                    </a>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center text-slate-500">
                                    Belum ada data bendel.
                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-4">
                    {{ $bendels->links() }}
                </div>

            </div>

        </div>

    </div>

</x-layouts.app>