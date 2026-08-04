<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Template ERET
    |--------------------------------------------------------------------------
    */

    'template' => storage_path('app/templates/ERET JULI.xltx'),

    /*
    |--------------------------------------------------------------------------
    | Nama worksheet default
    |--------------------------------------------------------------------------
    */

    'default_sheet' => 'JULI',

    /*
    |--------------------------------------------------------------------------
    | Mapping Pasar -> Baris Excel
    |--------------------------------------------------------------------------
    | Nomor baris harus sesuai template ERET.
    */

    'market_rows' => [

        // Korwil Karimata
        'Karimata' => 24,
        'Pedurungan' => 25,

        // Contoh
        // 'Johar' => 26,
        // 'Peterongan' => 27,
        // 'Karangayu' => 28,
        // 'Jatingaleh' => 29,

    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Jenis Retribusi -> Kolom Excel
    |--------------------------------------------------------------------------
    */

    'columns' => [

        'kios' => 'B',
        'los' => 'C',
        'dasaran_terbuka' => 'D',
        'kebersihan' => 'E',
        'mck' => 'F',
        'listrik' => 'G',
        'total' => 'H',

    ],

    /*
    |--------------------------------------------------------------------------
    | Kolom Dashboard ERET (Spreadsheet)
    |--------------------------------------------------------------------------
    | Mapping kolom tabel ERET Harian di dashboard ke sumber jenis_retribusi.
    | Kolom 'Sampah' bersumber dari jenis_retribusi 'kebersihan' (sesuai template ERET).
    | Ubah daftar ini jika template ERET berubah di masa depan.
    */

    'dashboard_columns' => [

        'kios' => ['label' => 'Kios', 'sources' => ['kios']],
        'los' => ['label' => 'Los', 'sources' => ['los']],
        'dasaran' => ['label' => 'Dasaran', 'sources' => ['dasaran_terbuka', 'dasaran terbuka']],
        'mck' => ['label' => 'MCK', 'sources' => ['mck']],
        'sampah' => ['label' => 'Sampah', 'sources' => ['kebersihan']],
        'listrik' => ['label' => 'Listrik', 'sources' => ['listrik']],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Dashboard ERET
    |--------------------------------------------------------------------------
    */

    'dashboard_per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Entry Types (Tabel ERET)
    |--------------------------------------------------------------------------
    | Dua worksheet independent pada template ERET resmi:
    |   - 'manual' => Tabel A (Retribusi Manual)
    |   - 'eret'   => Tabel B (E-Retribusi)
    |
    | Setiap tabel memiliki baris, subtotal, dan perhitungan terpisah.
    | Hanya grand total yang menggabungkan keduanya (sesuai template ERET).
    */

    'entry_types' => [

        'manual' => [
            'label' => 'Tabel A — Retribusi Manual',
            'subtitle' => 'Pengumpulan setoran retribusi manual per pasar',
        ],

        'eret' => [
            'label' => 'Tabel B — E-Retribusi',
            'subtitle' => 'Setoran retribusi elektronik (E-Retribusi) per pasar',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Format Angka
    |--------------------------------------------------------------------------
    */

    'number_format' => '#,##0',

    /*
    |--------------------------------------------------------------------------
    | Sumber Data Bendel
    |--------------------------------------------------------------------------
    | 'legacy'   — membaca dari tabel verifications (lama)
    | 'workflow' — membaca dari retributions.status IN ('verified','approved','locked')
    |
    | Selama masa transisi, set ke 'legacy' untuk backward compatibility.
    | Beralih ke 'workflow' hanya setelah migrasi data dan regression test selesai.
    */

    'bendel_source' => env('BENDEL_SOURCE', 'legacy'),

    /*
    |--------------------------------------------------------------------------
    | ERET Engine Configuration
    |--------------------------------------------------------------------------
    | Konfigurasi untuk ERET Engine V1.1.
    |
    | dynamic_mapping: Gunakan scanning dinamis untuk menemukan pasar & kolom.
    |   Jika true, engine membaca struktur workbook secara langsung.
    |   Jika false, gunakan mapping statis dari market_rows dan columns di atas.
    |
    | strict_validation: Jika true, engine akan throw exception saat mapping gagal.
    |   Jika false, engine hanya log warning dan lanjutkan proses.
    |
    | dry_run: Jika true, engine hanya membaca & mapping tanpa menyimpan workbook.
    */

    'engine' => [
        'dynamic_mapping' => env('ERET_DYNAMIC_MAPPING', true),
        'strict_validation' => env('ERET_STRICT_VALIDATION', false),
        'dry_run' => env('ERET_DRY_RUN', false),
    ],

];
