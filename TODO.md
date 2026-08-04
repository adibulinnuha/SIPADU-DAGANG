# ERET Dual-Table Worksheet — Implementation Tasks

## Objective
Implement TWO independent ERET tables in the dashboard spreadsheet:
- **Table A = Manual Retribusi** (`entry_type = 'manual'`)
- **Table B = E-Retribusi** (`entry_type = 'eret'`)

Each table has independent editable rows, add/delete, subtotals, calculations,
and save logic. Only the final grand total combines values where the official
ERET worksheet requires it. Saving Manual must NEVER delete E-Retribusi rows and
vice versa.

## Steps

- [x] Create migration to add `entry_type` column to `retributions` (default 'manual', indexed)
- [x] Update `Retribution` model `$fillable` with `entry_type`
- [x] Update `EretDashboardService` to accept and persist `entry_type` per row
- [x] Scope delete-by-draft logic by `entry_type` (Manual save never deletes E-Retribusi rows)
- [x] Update `EretDashboardSaveRequest` to validate optional `entry_type`
- [x] Update `EretDashboardController` to pass `entry_type` from request to service
- [x] Update `DashboardController` to split manual vs eret rows and compute separate subtotals + combined grand total
- [x] Update `eret-spreadsheet.js` to include `entry_type` in payload and support unique grid id
- [x] Update `dashboard.blade.php` to render two independent spreadsheet tables
- [x] Update `config/eret.php` with group labels
- [x] Add `tests/Feature/EretTwoTableTest.php` covering manual save, eret save, separate subtotals, combined grand total, delete scope, batch save, validation
- [x] Run `php artisan test` and fix any failures
- [x] Remove debug code / dead code
- [x] Ensure `git status` is clean and commit changes
