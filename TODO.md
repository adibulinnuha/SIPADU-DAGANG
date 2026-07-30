# SPRINT ERET ENGINE V1.2 — VALIDASI & INTEGRASI

## Ringkasan Temuan dari Audit Codebase

### Template ERET (ERET JULI.xltx)
- Total sheets: **15** (bukan 31)
- Combined sheets: "03,04,05 Juli", "10,11,12 Juli", "17,18,19 jULI"
- Misnamed sheets: "06 Juni", "07 Juni", "08 Juni" (header menyebut "JULI" tapi nama sheet "Juni")
- Sheet "21 Jul" menggunakan format singkat
- Formula cells: B45 (Listrik) dan B47 (MCK) — keduanya berisi formula, bukan input
- Tidak ada sheet untuk tanggal 22-31

### Masalah yang Ditemukan

1. **WorkbookEngine** — `findSheetByDateHeader()` sudah diperbaiki dengan regex word boundaries. File sudah direstore.
2. **MonthSimulationTest** — Masih mengekspektasi 31 hari, padahal template hanya 15 sheet. Juga menggunakan method `getReport()` yang belum ada di EretEngine.
3. **PixelPerfectValidationTest** — Membandingkan sheet "01 Juli" (template) dengan "21 Jul" (output), yang merupakan sheet berbeda dengan struktur mungkin berbeda.
4. **EretEngine** — Belum ada method `getReport()` atau `ValidationReport`. Method `fillListrik()` dan `fillMck()` akan skip karena cell B45 dan B47 berisi formula.
5. **CellMapper** — `scanMarkets()` menggunakan scanning dinamis yang perlu diverifikasi mappingnya.
6. **EretEngineTest** — Beberapa test menggunakan asumsi sheet name "21 Jul" (sekarang sudah benar karena itu sheet terakhir).

---

## PLAN

### TASK 1 — Integrasi Engine ✅ (Sudah)

Alur data sudah terhubung:
- Retribusi (database) → AggregateService::getDailyRecap() → EretEngine::generate() → WorkbookEngine → Spreadsheet
- Controller: `RetributionsExportController::template()` dengan parameter `?engine=new`

**Status:** ✅ Tidak perlu perubahan

---

### TASK 2 — Validasi Mapping

**Files to edit:**
- `app/Services/CellMapper.php` — Perbaiki scanMarkets() untuk mendeteksi semua pasar dengan benar
- `tests/Feature/EretEngineTest.php` — Tambah test mapping untuk semua pasar

**Detailed changes:**
- [ ] CellMapper::scanMarkets(): Perbaiki deteksi group "manual" vs "eret" — pastikan header "Pasar" kedua menandai E-Retribusi section
- [ ] CellMapper::scanMarkets(): Tambahkan deteksi row untuk "Rejomulyo IB" di template
- [ ] CellMapper::scanMarkets(): Pastikan "Dargo" muncul 2x (manual row 13, eret row 27)
- [ ] CellMapper::scanMarkets(): Pastikan "Karimata 1" dan "Karimata 2" masuk group eret
- [ ] CellMapper::scanMarkets(): Pastikan "Waru Indah 1" dan "Waru Indah 2" masuk group eret
- [ ] CellMapper::scanMarkets(): Pastikan "Tambak Lorok", "Waru Indah", "Rejomulyo IB", "Bubakan" masuk group manual
- [ ] Test: marketplace mapping test untuk semua pasar

---

### TASK 3 — Formula Integrity

**Files to edit:**
- `app/Services/WorkbookEngine.php` — Perbaiki isProtectedCell() untuk mendeteksi formula Listrik (B45) dan MCK (B47)
- `tests/Feature/EretEngineTest.php` — Tambah test formula integrity

**Detailed changes:**
- [ ] Pastikan B45 dan B47 terdeteksi sebagai formula cells (sudah, karena `isFormulaCell()` cek)
- [ ] Verifikasi subtotal rows (16, 20, 21, 34, 38, 39, 42, 44, 48) terproteksi di semua kolom (sudah)
- [ ] Test: verifikasi semua formula cells tetap utuh setelah generate
- [ ] Test: verifikasi subtotal rows tidak tertimpa

---

### TASK 4 — Pixel Perfect Validation

**Files to edit:**
- `tests/Feature/PixelPerfectValidationTest.php` — Perbaiki test untuk membandingkan sheet yang sama

**Detailed changes:**
- [ ] Ubah test untuk membandingkan template sheet "01 Juli" dengan output sheet "01 Juli" (bukan "21 Jul")
- [ ] Pastikan template sheet yang dibandingkan dengan output sheet yang SESUAI (sama hari)
- [ ] Test: merged cells identik
- [ ] Test: formulas identik
- [ ] Test: styles identik (font, alignment, border, fill)
- [ ] Test: row heights & column widths identik
- [ ] Test: print setup identik

---

### TASK 5 — Simulasi 1 Bulan (15 Hari)

**Files to create/modify:**
- `tests/Feature/MonthSimulationTest.php` — Perbaiki test untuk 15 sheet (bukan 31)

**Detailed changes:**
- [ ] Ubah test untuk generate hanya tanggal yang memiliki sheet (1-21, kecuali weekend/gabungan)
- [ ] Mapping sheet: day 1→"01 Juli", day 2→"02 Juli", day 3→"03,04,05 Juli", day 4→"03,04,05 Juli", day 5→"03,04,05 Juli", day 6→"06 Juni", day 7→"07 Juni", day 8→"08 Juni", day 9→"09 Juli", day 10→"10,11,12 Juli", day 11→"10,11,12 Juli", day 12→"10,11,12 Juli", day 13→"13 Juli", day 14→"14 Juli", day 15→"15 Juli", day 16→"16 Juli", day 17→"17,18,19 jULI", day 18→"17,18,19 jULI", day 19→"17,18,19 jULI", day 20→"20 Juli", day 21→"21 Jul"
- [ ] Test: semua sheet berhasil diisi
- [ ] Test: tidak ada exception
- [ ] Test: sequential generation tidak memory leak

---

### TASK 6 — Audit Log

**Files to edit:**
- `app/Services/EretEngine.php` — Tambah logging yang lebih detail
- `app/Services/WorkbookEngine.php` — Tambah logging untuk operasi workbook

**Detailed changes:**
- [ ] EretEngine: log "Workbook loaded" dengan path
- [ ] EretEngine: log "Sheet ditemukan" dengan nama sheet
- [ ] EretEngine: log "Market ditemukan" dengan nama market, row, group
- [ ] EretEngine: log "Cell diisi" dengan cell address dan value
- [ ] EretEngine: log "Formula dilewati" dengan cell address
- [ ] EretEngine: log "Workbook disimpan" dengan path
- [ ] EretEngine: log warning "market tidak ditemukan" (sudah ada)
- [ ] EretEngine: log warning "formula terproteksi" (sudah ada di WorkbookEngine)
- [ ] EretEngine: log warning "sheet tidak ditemukan" (sudah ada)

---

### TASK 7 — Business Validation

**Files to create:**
- `tests/Feature/BusinessValidationTest.php` — Test aturan bisnis

**Detailed changes:**
- [ ] Test: Template Excel adalah source of truth (gunakan template yang sama)
- [ ] Test: SIPADU mengikuti template (data dari database, mapping sesuai)
- [ ] Test: Formula Excel tidak boleh diubah (perbandingan pre/post generate)
- [ ] Test: Manual dan E-Retribusi adalah dua kelompok berbeda (group detection)
- [ ] Test: DARGO muncul dua kali dan bukan duplikasi (manual + eret)
- [ ] Test: MCK dan Listrik mengikuti struktur workbook (formula cells)
- [ ] Test: Engine hanya mengisi cell input (tidak ada formula yang tertimpa)
- [ ] Test: Nama sheet mengikuti tanggal pada header
- [ ] Test: Workbook hasil export identik dengan template

---

### TEST — Regression Tests

**Files to create:**
- `tests/Feature/RegressionMappingTest.php`

**Test cases:**
- [ ] nilai kosong (retribution tanpa items)
- [ ] nilai nol
- [ ] market tidak ditemukan
- [ ] DARGO Manual (row 13, group manual)
- [ ] DARGO E-Retribusi (row 27, group eret)
- [ ] KARIMATA 1 & 2 (group eret)
- [ ] WARU INDAH 1 & 2 (group eret)
- [ ] Listrik (cell B45, formula)
- [ ] MCK (cell B47, formula)
- [ ] Combined date sheets (3,4,5; 10,11,12; 17,18,19)
- [ ] Misnamed sheets (06 Juni, 07 Juni, 08 Juni)

---

## RINGKASAN FILE YANG AKAN DIEDIT/DIBUAT

| File | Tindakan | Task |
|------|----------|------|
| `app/Services/CellMapper.php` | EDIT | Task 2 |
| `app/Services/EretEngine.php` | EDIT | Task 6 |
| `app/Services/WorkbookEngine.php` | ✅ SUDAH | Task 3 |
| `tests/Feature/MonthSimulationTest.php` | EDIT | Task 5 |
| `tests/Feature/PixelPerfectValidationTest.php` | EDIT | Task 4 |
| `tests/Feature/EretEngineTest.php` | EDIT | Task 2, 3 |
| `tests/Feature/BusinessValidationTest.php` | BUAT | Task 7 |
| `tests/Feature/RegressionMappingTest.php` | BUAT | TEST |
