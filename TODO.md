# TODO — Fix ERET Spreadsheet Save Error

## Task
Fix `(data.errors || []).map is not a function` when saving the ERET spreadsheet.

## Root Cause
Frontend `saveRows()` in `resources/js/eret-spreadsheet.js` assumes `data.errors`
is always an array. Laravel FormRequest validation (HTTP 422) returns `errors` as
an **object** keyed by field, causing `data.errors.map(...)` to throw a TypeError.

## Steps
- [x] 1. Investigate the save request (controller, service, request, JS).
- [x] 2. Confirm root cause (frontend-only: `data.errors` shape mismatch).
- [x] 3. Add robust error-message formatter in `resources/js/eret-spreadsheet.js`
     handling all shapes:
     - Array of `{message}` objects
     - Array of plain strings
     - Object (Laravel 422 validation, keyed by field)
     - `data.message` string fallback
- [x] 4. Wire the formatter into `saveRows()` and check `res.ok`/HTTP status.
- [ ] 5. Rebuild assets.
- [ ] 6. Run the full test suite.
- [ ] 7. Report root cause, files changed, example JSON, test results.
