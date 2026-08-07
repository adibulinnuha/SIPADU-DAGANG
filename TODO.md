# Sprint 7 — Workbook Engine & Excel Export Stabilization

## Objective
Harden `WorkbookEngine` for PhpSpreadsheet 1.30.6 compatibility and add regression protection. No refactor of stable modules.

## Audit Findings
- Installed PhpSpreadsheet: **1.30.6**
- `Coordinate::coordinateIsInsideRange()` **does not exist** in 1.30.6 (removed in 1.x).
- Current `WorkbookEngine::isMergedCell()` already uses a manual, compatible range-parsing implementation.
- No deprecated APIs (`getCellByColumnAndRow`, `setCellValueByColumnAndRow`, etc.) in use.
- All 181 existing tests pass.

## Steps
- [x] Audit WorkbookEngine and PhpSpreadsheet API usage
- [x] Confirm root cause of `coordinateIsInsideRange()` incompatibility
- [x] Add `assertPhpSpreadsheetCompatible()` guard to WorkbookEngine
- [x] Add `isCellInRange()` compatible helper; refactor `isMergedCell()` to use it
- [x] Add `getFormattedValue()` for number-format verification
- [x] Add regression tests (workbook generation, merged cells, formulas, styles, row heights, column widths, number formats, export success, compatibility guard)
- [x] Run `php artisan test` (193 passed)
- [x] Run `npm run build`
- [x] Verify export from official ERET template (no errors/warnings)
- [ ] Commit: `test: strengthen WorkbookEngine compatibility and regression coverage`
