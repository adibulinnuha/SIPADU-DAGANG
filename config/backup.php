<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Backup
    |--------------------------------------------------------------------------
    | Direktori penyimpanan arsip backup. Relatif terhadap storage_path().
    */

    'path' => env('BACKUP_PATH', 'app/backups'),

    /*
    |--------------------------------------------------------------------------
    | Masa Retensi (hari)
    |--------------------------------------------------------------------------
    | Backup otomatis yang lebih tua dari jumlah hari ini akan dihapus saat
    | membuat backup baru (dipangkas). 0 = nonaktifkan pemangkasan.
    */

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Format Penamaan File
    |--------------------------------------------------------------------------
    | '%s' = timestamp (Y-m-d_His), '%s' kedua = tipe (database/workbook/config)
    */

    'filename_pattern' => 'sipadu_%s_%s',

    /*
    |--------------------------------------------------------------------------
    | Komponen Backup
    |--------------------------------------------------------------------------
    | Mengontrol direktori storage mana saja yang disertakan dalam arsip.
    */

    'include' => [
        'templates' => storage_path('app/templates'),
        'ocr' => storage_path('app/ocr-temp'),
        'public' => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ukuran Maksimal (byte)
    |--------------------------------------------------------------------------
    | Ambang batas kewajaran ukuran file backup. Backups yang lebih besar
    | dari ini akan tetap dibuat namun diberi peringatan pada laporan.
    */

    'max_size' => (int) env('BACKUP_MAX_SIZE', 104857600), // 100 MB

];
