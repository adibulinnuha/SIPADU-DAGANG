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

];
