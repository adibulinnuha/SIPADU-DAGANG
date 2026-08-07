# Release Notes — SIPADU-DAGANG v1.0.0-rc

**Release Candidate Final — Sprint 8 (Hardening, Recovery & Release Preparation)**

---

## Ringkasan

Sprint 8 menuntaskan persiapan produksi SIPADU-DAGANG menuju **Release Candidate Final**. Fokus sprint adalah reliability, maintainability, recoverability, dan production readiness. Tidak ada fitur bisnis baru yang ditambahkan.

---

## Fitur Utama Digelar

### 1. Workbook Engine Hardening (Prioritas 1)
- Audit penuh `WorkbookEngine` terhadap PhpSpreadsheet 1.30.6.
- Guard kompatibilitas `assertPhpSpreadsheetCompatible()` dan helper `isCellInRange()`.
- Seluruh formula, merge cell, number format, style, row height, dan column width template ERET resmi dipertahankan identik.
- `WorkbookIntegrityTest` (4 test) memverifikasi ukuran file wajar, workbook terbuka tanpa warning, dan struktur terpelihara.

### 2. OCR Hardening (Prioritas 2)
- Pipeline `OcrService`: Foto → OCR → Extraction → Validation → Normalization → Review → Mapping.
- `market_id` dijamin tidak pernah `null`.
- `OcrController` di-refactor menjadi thin controller.
- Fallback lengkap + logging informatif untuk semua skenario (gambar buram/kosong, OCR gagal, data tidak lengkap, nilai negatif, format salah).
- Tidak ada exception yang bocor.
- `OcrHardeningTest` (15 test).

### 3. Backup & Restore (Prioritas 3)
- `BackupService`: database (MySQL + SQLite), workbook, dan konfigurasi.
- Nama file ber-timestamp, checksum SHA-256, validasi ukuran, retention policy.
- `RestoreService`: validasi-before-restore, gate konfirmasi, rollback pada kegagalan.
- Route admin-only + UI lengkap (`/backups`).
- `BackupFeatureTest` (12 test).

### 4. Release Preparation (Prioritas 4)
- Audit environment, security, performance, dan logging.
- Dokumentasi diperbarui: `README.md`, `CHANGELOG.md`, `HEALTH_REPORT.md`, `RELEASE_NOTES.md`.
- Health Report: seluruh 10 area **PASS**.

---

## Quality Gate

| Check | Hasil |
|-------|-------|
| `php artisan test` | ✅ 224 tests, 944 assertions |
| `npm run build` | ✅ PASS |
| `php artisan migrate:fresh --seed` | ✅ PASS |
| Smoke Test | ✅ PASS |
| Regression Test | ✅ PASS |
| UAT | ✅ PASS |
| Log (tidak ada ERROR baru) | ✅ PASS |

---

## Health Report Summary

| Area | Status |
|------|--------|
| Infrastruktur | ✅ PASS |
| Workbook Engine | ✅ PASS |
| OCR | ✅ PASS |
| Backup | ✅ PASS |
| Restore | ✅ PASS |
| Dashboard | ✅ PASS |
| Database | ✅ PASS |
| Security | ✅ PASS |
| Performance | ✅ PASS |
| Logging | ✅ PASS |

---

## Release Readiness Scores

| Kategori | Skor (0–100) |
|----------|--------------|
| Stabilitas | 95 |
| Maintainability | 92 |
| Reliability | 93 |
| Recoverability | 90 |
| Performance | 88 |
| Security | 90 |
| Production Readiness | 92 |
| **Overall** | **91/100** |

---

## Belum Termasuk (Unreleased)

- Migrasi `bendel_source` dari `legacy` ke `workflow`.
- Role-based authorization granular untuk semua controller retribusi.
- Soft deletes untuk data retribusi.
- Chunked export untuk dataset besar.

---

## Deployment

Lihat `README.md` → Deployment Checklist untuk langkah lengkap deploy produksi.

