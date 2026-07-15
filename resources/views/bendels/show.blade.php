<x-layouts.app title="Detail Bendel">

    <div class="sipadu-page">

        <div class="flex justify-between items-center mb-6">

            <div>
                <h1 class="sipadu-title">
                    📄 Detail Bendel
                </h1>

                <p class="sipadu-subtitle">
                    Informasi lengkap arsip bendel retribusi.
                </p>
            </div>

            <a href="{{ route('bendels.index') }}"
               class="sipadu-btn sipadu-btn-secondary">
                Kembali
            </a>

        </div>


        <div class="sipadu-card">

            <div class="sipadu-card-body">

                <table class="sipadu-table">

                    <tr>
                        <th>Nomor Bendel</th>
                        <td>{{ $bendel->nomor_bendel }}</td>
                    </tr>

                    <tr>
                        <th>Pasar</th>
                        <td>
                            {{ $bendel->market->name ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Tanggal</th>
                        <td>{{ $bendel->tanggal }}</td>
                    </tr>

                    <tr>
                        <th>Periode</th>
                        <td>{{ $bendel->periode }}</td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            {{ ucfirst($bendel->status) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Keterangan</th>
                        <td>
                            {{ $bendel->keterangan ?? '-' }}
                        </td>
                    </tr>

                </table>


                <div class="mt-6 flex gap-3">

                    <a href="{{ route('bendels.edit',$bendel) }}"
                       class="sipadu-btn sipadu-btn-primary">
                        Edit
                    </a>


                    <form action="{{ route('bendels.destroy',$bendel) }}"
                          method="POST"
                          onsubmit="return confirm('Hapus data bendel?')">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="sipadu-btn sipadu-btn-danger">
                            Hapus
                        </button>

                    </form>

                </div>


            </div>

        </div>

    </div>

</x-layouts.app>