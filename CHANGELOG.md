# Changelog

Semua perubahan penting pada SIPADU-DAGANG dicatat di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0-rc] - 2026-07

### Sprint 8 — Hardening, Recovery & Release Preparation

#### Added
- **Backup & Restore Module** (`BackupService`, `RestoreService`, `BackupController`)
  - Database, workbook, dan konfigurasi backup.
  - Nama file ber-timestamp (`sipadu_Y-m-d_His_type.zip`), checksum SHA-256, dan validasi size.
  - Restore dengan validasi-before-restore, gate konfirmasi, dan rollback pada kegagalan.
  - Route admin-only (`role:admin`), UI lengkap (index, create, download, delete, restore).
  - `config/backup.php` untuk jalur, format nama, retention policy.
  - `tests/Feature/BackupFeatureTest.php` (12 test).
- **OCR Hardening** (`OcrService`)
  - Pipeline lengkap: Foto → OCR → Extraction → Validation → Normalization → Review → Mapping.
  - Fallback untuk gambar buram/kosong, OCR gagal, data tidak lengkap, nilai negatif, format salah.
  - `market_id` dijamin tidak pernah `null`.
  - Tidak ada exception yang bocor; logging informatif di setiap tahap.
  - `tests/Feature/OcrHardeningTest.php` (15 test).
- **Workbook Integrity Test** (`tests/Feature/WorkbookIntegrityTest.php`)
  - Ukuran file wajar, workbook terbuka tanpa error, merge & formula & number format dipertahankan.

#### Changed
- `OcrController` di-refactor menjadi thin controller yang mendelegasikan ke `OcrService`.
- `routes/web.php` menambahkan route backup di dalam group middleware `role:admin`.
- `resources/views/layouts/sidebar.blade.php` menambahkan menu Backup untuk admin.
- `resources/views/backup/index.blade.php` menggantikan placeholder dengan UI backup penuh.
- `README.md` memperbarui jumlah test (224 tests, 944 assertions).

#### Added
- `HEALTH_REPORT.md` — laporan kesehatan sprint (infrastruktur, workbook, OCR, backup, restore, dashboard, database, security, performance, logging) dengan status PASS.

### Sprint 7 — Workbook Engine & Excel Export Stabilization

- Audit `WorkbookEngine` dan kompatibilitas PhpSpreadsheet 1.30.6.
- Guard `assertPhpSpreadsheetCompatible()`, helper `isCellInRange()`, `getFormattedValue()`.
- Regression test workbook generation, merged cells, formulas, styles, number formats, export route.
- Commit: `test: strengthen WorkbookEngine compatibility and regression coverage`.

### Sprint 6 — RC Preparation

- Dead code removal (`RetributionTemplateExport`).
- Perbaikan README route listing.
- Security review, deployment checklist, rollback strategy.

### Sprint 5B — Production Hardening

- Dead code removed (`BendelController::store`).
- DB facade import standardized.
- Role validation tightened (`UserController`).

---

## [Unreleased]

### Planned for Release Candidate Final
- Pergantian `bendel_source` dari `legacy` ke `workflow` setelah migrasi data selesai.
- Role-based authorization granular untuk controller retribusi/verifikasi/bendel.
- Soft deletes untuk data retribusi.
- Chunked export untuk dataset besar.

