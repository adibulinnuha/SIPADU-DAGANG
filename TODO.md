# Sprint: Dashboard ERET (Spreadsheet)

## Progress Tracking

- [x] 1. Audit struktur project, controller dashboard, model Retribution, RetributionItem, route, blade dashboard
- [x] 2. Konfirmasi rencana & keputusan implementasi
- [x] 3. Tambahkan mapping kolom dashboard (config/eret.php)
- [ ] 4. Perbarui `DashboardController` — filter, sorting, pagination, ERET Harian, REKAP, footer total
- [ ] 5. Perbarui `resources/views/dashboard.blade.php` — filter bar + tabel ERET Harian + tabel REKAP (sticky header, sticky kolom pertama, horizontal scroll, keyboard friendly)
- [ ] 6. Validasi: jalankan test suite (regression)
- [ ] 7. Validasi: render dashboard (manual / artisan serve)

## Keputusan Implementasi

- Kolom **Sampah** bersumber dari `jenis_retribusi = 'kebersihan'` (sesuai template ERET) — dapat diubah via `config/eret.php > dashboard_columns`.
- Layout: Ringkasan KPI → Filter → TABEL ERET HARIAN → TABEL REKAP → section lama tetap dipertahankan.
- Pagination: 15 baris/halaman pada tabel ERET Harian.
- Tabel REKAP menampilkan breakdown status workflow per pasar (Draft/Submitted/Verified/Approved/Locked).

