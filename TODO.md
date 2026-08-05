# BUG FIX — "Tambah Baris" button / newly added row not behaving correctly

## Root cause CONFIRMED (via Playwright)
When the grid overflows the viewport and the user is scrolled above the bottom, `addBlankRow()` appends a row outside the visible virtual window (`gridRows.length < rowCount`). The new row does not render and cannot be edited. Observed: after adding 20 rows while scrolled to top, rowCount=35 but gridRowsLength=20.

## Steps
- [x] Verify root cause via browser (Playwright) — CONFIRMED
- [x] Implement scrollToBottom() + updateVirtualRange() + syncState() after adding a row in resources/js/eret-spreadsheet.js
- [x] Rebuild assets (npm run build)
- [x] Browser-verify: Add Row → visible → editable → cursor in first cell → totals correct → draft updates
- [x] Regression: php artisan test → 164 passed / 675 assertions / 0 failed
- [x] Final report (root cause, files, browser results, test results)

## Production validation
- [x] Verify resources/js/eret-spreadsheet.js builds successfully (no syntax errors) — `npm run build` ✓
- [x] Run the complete test suite and confirm no regressions — 164 passed / 675 assertions / 0 failed ✓
- [x] Remove any temporary/debug code, helper scripts, console.log, debugger statements, screenshots, or verification artifacts not required for production — removed `_verify.mjs`, `_verify2.mjs`, `_make_test_user.php`, `_seed_many.php`, and reverted the Playwright devDependency ✓
- [x] Verify the working tree contains only production-ready changes — only `resources/js/eret-spreadsheet.js` and `resources/views/dashboard.blade.php` (plus this TODO) are modified ✓

## Final report
- **Root cause:** The virtualized grid only renders `virtualStart`–`virtualEnd`. When "Tambah Baris" appended a row while the user was scrolled above the bottom, the new row landed outside the visible virtual window and never rendered/edited.
- **Fix:** Added `scrollToBottom()` + `updateVirtualRange()` + `syncState()` after appending a row, exposed `addBlankRow()` on the Alpine component, wired the toolbar button to it, and added a `gridVersion` reactive counter so the grid re-renders after row mutations. Also moved `onCellInput` to a light reactive update to avoid losing keystrokes mid-typing on the virtualized grid.
- **Why it works:** The newly appended row is scrolled into the virtual window and the reactive counter forces Alpine's `x-for` to re-render, so the row is visible and editable. Totals recompute via `recomputeAll()`.
- **Test results:** 164 passed / 675 assertions / 0 failed. `npm run build` succeeds with no syntax errors.
- **Remaining risks:** None material. The fix is confined to the spreadsheet component and its dashboard wiring; no backend, routes, controllers, or database were changed.
