# SPRINT 5 — ERET Dual Table (Excel Mode) — TODO

## Status Baseline
- [x] Verify baseline: 164 tests passed, 0 failed
- [x] Analyze existing ERET spreadsheet implementation

## FITUR 5 — Autosave Draft (localStorage)
- [x] Add `draftKey` config in createEretSpreadsheet
- [x] Add `saveDraft()` / `loadDraft()` / `clearDraft()` helpers
- [x] Persist draft on edit / add / delete / duplicate / paste
- [x] Restore draft on `init()`
- [x] Clear draft after successful save
- [x] Add `draftState` reactive state (clean | draft | saving | saved)
- [x] Add `discardDraft()` method (confirm + reset, no backend call)

## FITUR 6 — Validation Tooltip
- [x] Add `title` hover tooltip on invalid cells
- [x] Keep red border + inline error text

## Clipboard
- [x] Modern Clipboard API paste with fallback to hidden textarea
- [x] Preserve Indonesian Excel numeric format

## Dashboard UI
- [x] Add "Draft" amber badge indicator
- [x] Add "Saving..." blue badge during save
- [x] Add "Saved" green badge after success
- [x] Add "Buang Draft" button with confirmation

## Verification
- [ ] Run `php artisan test` — confirm 164 passed
- [ ] Final report
