# Sprint 8 — Hardening, Recovery & Release Preparation

## Objective
Meningkatkan reliability, maintainability, recoverability, dan production readiness. 
Tidak ada fitur bisnis baru. Target: Release Candidate Final.

## Prioritas 1 — Workbook Engine Hardening
- [x] Audit WorkbookEngine + PhpSpreadsheet compatibility (Sprint 7)
- [x] Compatibility guard untuk API deprecated
- [x] Regression test workbook generation, merge, formula, number format, style, export route, integrity
- [x] Final workbook integrity test (file size wajar, opens tanpa warning, merge & formula preserved) — `WorkbookIntegrityTest` (4/4 PASS)

## Prioritas 2 — OCR Hardening
- [x] Buat `OcrService` (pipeline: Foto → OCR → Extraction → Validation → Normalization → Review → Mapping)
- [x] market_id tidak pernah null; nominal/tanggal/petugas/pasar valid
- [x] Fallback untuk gambar buram/kosong, OCR gagal, data tidak lengkap, nilai negatif, format salah
- [x] Tidak boleh ada Exception; logging informatif
- [x] Refactor `OcrController` ke `OcrService`
- [x] Test `OcrHardeningTest` (15/15 PASS)

## Prioritas 3 — Backup & Restore
- [x] `config/backup.php`
- [x] `BackupService` (DB dump + storage zip, timestamp, checksum, size, validasi)
- [x] `RestoreService` (validasi sebelum restore, rollback bila gagal, konfirmasi, logging)
- [x] `BackupController` (index, create, download, destroy, restore)
- [x] Routes admin-only
- [x] View `resources/views/backup/index.blade.php`
- [x] `BackupFeatureTest` (12/12 PASS)

## Prioritas 4 — Release Preparation
- [x] Audit environment (APP_ENV, APP_DEBUG, CACHE, QUEUE, SESSION, LOG, FILESYSTEM)
- [x] Security audit (debug=false, error page, CSRF, authorization, validation, mass assignment)
- [x] Performance audit (N+1, lazy loading, index DB, cache)
- [x] Logging audit (tidak ada error baru, warning tercatat)
- [x] Perbarui README, Deployment Guide, Backup Guide, Restore Guide, Release Notes, CHANGELOG
- [x] Health Report PASS/FAIL — `HEALTH_REPORT.md` (semua 10 area PASS)

## Quality Gate
- [x] `php artisan migrate:fresh --seed` PASS
- [x] `php artisan test` PASS (224 tests, 944 assertions)
- [x] `npm run build` PASS
- [x] Smoke Test PASS
- [x] Regression Test PASS
- [x] UAT PASS
- [x] `git status` bersih
- [x] Commit (`e16cef3`) + push ke `origin/blackboxai/sprint4-workbook-test`
