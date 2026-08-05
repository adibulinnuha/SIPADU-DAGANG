/**
 * ERET Spreadsheet — Modular Alpine.js component factory.
 *
 * Provides an Excel-like grid experience for the ERET Dashboard:
 *  - Row management (insert / delete / duplicate / multi-select / drag-reorder)
 *  - Cell editing (double-click, escape-cancel, Enter/Tab/arrows, Ctrl+Enter fill)
 *  - Clipboard (copy / cut / paste, multi-row/col from Excel TSV, numeric preserved)
 *  - Auto calculation (O(1) incremental per-row + grand totals)
 *  - Auto save + dirty state + beforeunload warning
 *  - Validation (numeric-only, non-negative, inline messages)
 *  - Performance (1,000+ rows; minimal DOM churn; plain backing store)
 *
 * The component is built as a factory so it can be registered once and reused.
 * It is intentionally kept free of business logic — it only calls the configured
 * save endpoint and reports results back to the UI.
 */

export function createEretSpreadsheet(config) {
    const colKeys = config.colKeys || [];
    const markets = config.markets || [];
    const petugas = config.petugas || [];
    const tanggal = config.tanggal;
const csrfToken = config.csrfToken;
    const apiUrl = config.apiUrl;
    const entryType = config.entryType || 'manual';
    const gridId = config.gridId || 'eret-grid';

    // localStorage draft key — unsaved rows survive a browser refresh.
    const draftKey = `eret-draft-${gridId}-${tanggal}-${entryType}`;

    // ── helpers ────────────────────────────────────────────────
    const toNumber = (v) => {
        if (v === null || v === undefined || v === '') return 0;
        const cleaned = String(v).replace(/[^0-9.\-]/g, '');
        const n = parseFloat(cleaned);
        return Number.isFinite(n) ? n : 0;
    };

    // Parse a value that may be pasted from Excel (e.g. "Rp 1.000,50", "1,000").
    // Mirrors the backend EretNumberService::normalize() exactly so frontend and
    // backend produce identical results for the same input.
    const parseNumeric = (v) => {
        if (v === null || v === undefined) return 0;
        if (typeof v === 'number') return v;
        let s = String(v).trim();
        if (s === '') return 0;
        // Remove currency symbol and spaces (case-insensitive: Rp/rp/RP).
        s = s.replace(/[Rp\s]/gi, '');
        // Indonesian convention: dot = thousands separator, comma = decimal.
        s = s.replace(/\./g, '').replace(',', '.');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : NaN;
    };

const normalizeRow = (row) => {
        const r = { ...row };
        colKeys.forEach((k) => {
            if (r[k] === undefined || r[k] === null) r[k] = '';
            r['_prev_' + k] = toNumber(r[k]);
        });
        r.total = 0;
        r._dirty = false;
        return r;
    };

    /**
     * Extract human-readable error messages from a Laravel JSON error response.
     *
     * Laravel can return the `errors` field in several shapes depending on how
     * the failure occurred:
     *   - Array of { row, field, message } objects (business/dashboard validation)
     *   - Array of plain strings
     *   - Object keyed by field (FormRequest validation, HTTP 422):
     *       { "rows.0.market_id": ["The rows.0.market_id field is required."] }
     *
     * We also fall back to `data.message` (a single string) when `errors` is
     * absent, empty, or not structured, and to a generic message as a last resort.
     * This prevents `(data.errors || []).map is not a function` when `errors` is
     * an object rather than an array.
     */
    const extractErrorMessages = (data) => {
        const messages = [];

        const push = (value) => {
            if (value === null || value === undefined) return;
            if (typeof value === 'string') {
                const text = value.trim();
                if (text) messages.push(text);
            } else if (Array.isArray(value)) {
                value.forEach((item) => push(item));
            } else if (typeof value === 'object') {
                Object.values(value).forEach((item) => push(item));
            }
        };

        if (data && data.errors !== undefined) {
            if (Array.isArray(data.errors)) {
                // Array of { message } objects OR array of plain strings.
                data.errors.forEach((e) => {
                    if (e !== null && typeof e === 'object' && typeof e.message === 'string') {
                        const text = e.message.trim();
                        if (text) messages.push(text);
                    } else {
                        push(e);
                    }
                });
            } else {
                // Object keyed by field (Laravel 422 validation bag).
                push(data.errors);
            }
        }

        // Fall back to the top-level message string when no structured errors
        // were found (e.g. a 422 response only surfaces `message`).
        if (messages.length === 0 && data && typeof data.message === 'string') {
            const text = data.message.trim();
            if (text) messages.push(text);
        }

        return messages;
    };

    // Backing store (non-reactive except where Alpine needs it).
    let rows = (config.initialRows || []).map(normalizeRow);
    let colTotals = {};
    let grandTotal = 0;
    let dirtySet = new Set();

    // UI selection / editing state.
    let selectedRows = new Set();   // row indices (Set of numbers)
    let activeRow = -1;
    let activeCol = -1;
    let selectionAnchor = null;
    let selectionRange = null;
    let editing = null;             // { row, col }
    let clipboard = null;           // { cols:[], data: 2D array }
    let dragIdx = -1;
    let dropIdx = -1;
    let isSelecting = false;
    let isFillDragging = false;
    let fillSource = null;
    let fillRange = null;
    let scrollEl = null;
    let virtualStart = 0;
    let virtualEnd = 0;
    let undoStack = [];
    let redoStack = [];
    const rowHeight = 52;
    const overscan = 8;

    // ── totals ─────────────────────────────────────────────────
    const recomputeAll = () => {
        const totals = {};
        colKeys.forEach((k) => { totals[k] = 0; });
        let grand = 0;
        rows.forEach((row) => {
            let rt = 0;
            colKeys.forEach((k) => {
                const v = toNumber(row[k]);
                totals[k] += v;
                rt += v;
            });
            row.total = rt;
            grand += rt;
        });
        colTotals = totals;
        grandTotal = grand;
    };

    // O(1) incremental update for a single cell edit.
    const recalcCell = (rowIndex, col) => {
        const row = rows[rowIndex];
        if (!row) return;
        const v = toNumber(row[col]);
        const prev = toNumber(row['_prev_' + col]);
        const delta = v - prev;
        if (delta !== 0) {
            colTotals[col] = (colTotals[col] || 0) + delta;
            grandTotal += delta;
            row['_prev_' + col] = v;
        }
        // Recompute this row's total (O(colKeys)).
        let rt = 0;
        colKeys.forEach((k) => { rt += toNumber(row[k]); });
        row.total = rt;
    };

    const recalcRowTotals = (rowIndex) => {
        const row = rows[rowIndex];
        if (!row) return;
        let rt = 0;
        colKeys.forEach((k) => { rt += toNumber(row[k]); });
        row.total = rt;
    };

    const normalizeRange = (a, b) => {
        const row1 = Math.min(a.row, b.row);
        const row2 = Math.max(a.row, b.row);
        const col1 = Math.min(a.col, b.col);
        const col2 = Math.max(a.col, b.col);
        return { row1, row2, col1, col2 };
    };

    const rowsFromRange = (range) => {
        const set = new Set();
        if (!range) return set;
        for (let i = range.row1; i <= range.row2; i += 1) {
            set.add(i);
        }
        return set;
    };

    const isCellSelectedInRange = (row, col) => {
        if (!selectionRange) return false;
        return row >= selectionRange.row1 && row <= selectionRange.row2
            && col >= selectionRange.col1 && col <= selectionRange.col2;
    };

    const pushHistory = () => {
        undoStack.push({
            rows: serializeRows(),
            activeRow,
            activeCol,
            selectionRange: selectionRange ? { ...selectionRange } : null,
        });
        if (undoStack.length > 50) undoStack.shift();
        redoStack = [];
    };

    const restoreHistory = (snapshot) => {
        if (!snapshot) return;
        rows = snapshot.rows.map((r) => normalizeRow(r));
        rows.forEach((row) => {
            colKeys.forEach((k) => { row['_prev_' + k] = toNumber(row[k]); });
        });
        recomputeAll();
        activeRow = snapshot.activeRow ?? -1;
        activeCol = snapshot.activeCol ?? -1;
        selectionRange = snapshot.selectionRange ? { ...snapshot.selectionRange } : null;
        selectedRows = selectionRange ? rowsFromRange(selectionRange) : new Set();
    };

    const undo = () => {
        if (undoStack.length === 0) return;
        const snapshot = undoStack.pop();
        redoStack.push({
            rows: serializeRows(),
            activeRow,
            activeCol,
            selectionRange: selectionRange ? { ...selectionRange } : null,
        });
        restoreHistory(snapshot);
    };

    const redo = () => {
        if (redoStack.length === 0) return;
        const snapshot = redoStack.pop();
        undoStack.push({
            rows: serializeRows(),
            activeRow,
            activeCol,
            selectionRange: selectionRange ? { ...selectionRange } : null,
        });
        restoreHistory(snapshot);
    };

    const updateVirtualRange = () => {
        if (!scrollEl) return;
        const scrollTop = scrollEl.scrollTop;
        const height = scrollEl.clientHeight;
        const start = Math.max(0, Math.floor(scrollTop / rowHeight) - overscan);
        const end = Math.min(rows.length, Math.ceil((scrollTop + height) / rowHeight) + overscan);
        virtualStart = start;
        virtualEnd = Math.max(start, end);
    };

    // ── formatting ─────────────────────────────────────────────
    const fmt = (value) => {
        const n = toNumber(value);
        return 'Rp ' + n.toLocaleString('id-ID');
    };

    const fmtCell = (value) => {
        const n = toNumber(value);
        if (n === 0 && value === '') return '';
        return n.toLocaleString('id-ID');
    };

    // ── validation ─────────────────────────────────────────────
    const validateRow = (index) => {
        const row = rows[index];
        const errs = {};
        if (!row.market_id) {
            errs.market_id = 'Pasar wajib diisi.';
        }
        colKeys.forEach((k) => {
            const v = row[k];
            if (v !== '' && v !== null && isNaN(parseNumeric(v))) {
                errs[k] = 'Harus angka.';
            } else if (isFinite(parseNumeric(v)) && parseNumeric(v) < 0) {
                errs[k] = 'Tidak boleh negatif.';
            }
        });
        if (row.nomor_setor) {
            const dupe = rows.some((r, i) =>
                i !== index && r.nomor_setor && r.nomor_setor.trim().toUpperCase() === row.nomor_setor.trim().toUpperCase()
            );
            if (dupe) errs.nomor_setor = 'Nomor setor duplikat.';
        }
        return errs;
    };

    const validateAll = () => {
        let allValid = true;
        rows.forEach((row, i) => {
            const errs = validateRow(i);
            row._errors = errs;
            if (Object.keys(errs).length > 0) allValid = false;
        });
        return allValid;
    };

    // ── dirty tracking ─────────────────────────────────────────
    const markDirty = (index) => {
        const row = rows[index];
        if (row) {
            dirtySet.add(row);
            row._dirty = true;
        }
    };

const clearDirty = () => {
        dirtySet.clear();
        rows.forEach((r) => { r._dirty = false; });
    };

    // ── draft persistence (localStorage) ───────────────────────
    // Serialize only the user-facing fields; strip internal state.
    const serializeRows = () => rows.map((r) => {
        const out = {
            id: r.id,
            market_id: r.market_id,
            petugas_id: r.petugas_id,
            nomor_setor: r.nomor_setor || '',
        };
        colKeys.forEach((k) => { out[k] = r[k]; });
        return out;
    });

    const saveDraft = () => {
        try {
            localStorage.setItem(draftKey, JSON.stringify(serializeRows()));
        } catch (e) {
            // Ignore quota/Safari-private-mode errors; drafts are best-effort.
        }
    };

    const loadDraft = () => {
        try {
            const raw = localStorage.getItem(draftKey);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : null;
        } catch (e) {
            return null;
        }
    };

    const clearDraft = () => {
        try {
            localStorage.removeItem(draftKey);
        } catch (e) {
            // ignore
        }
    };

    // Restore a saved draft (if any) over the server-initialized rows.
    const restoreDraft = () => {
        const draft = loadDraft();
        if (!draft) return false;
        rows = draft.map((r) => {
            const row = normalizeRow(r);
            colKeys.forEach((k) => { row['_prev_' + k] = toNumber(r[k]); });
            row._dirty = true;
            dirtySet.add(row);
            return row;
        });
        return true;
    };

    // ── row management ─────────────────────────────────────────
    const addRow = (index, recordHistory = true) => {
        if (recordHistory) pushHistory();
        const row = { id: null, market_id: '', petugas_id: '', nomor_setor: '' };
        colKeys.forEach((k) => { row[k] = ''; row['_prev_' + k] = 0; });
        row.total = 0;
        row._dirty = true;
        if (index === undefined || index === null || index < 0) {
            rows.push(row);
        } else {
            rows.splice(index, 0, row);
        }
        dirtySet.add(row);
        recomputeAll();
        updateVirtualRange();
        return rows.length - 1;
    };

    const insertRowAt = (index) => addRow(index);

    const deleteRows = (indices) => {
        if (indices.length === 0) return;
        pushHistory();
        const target = [...new Set(indices)].sort((a, b) => b - a);
        target.forEach((i) => {
            if (i >= 0 && i < rows.length) {
                const row = rows[i];
                dirtySet.delete(row);
                rows.splice(i, 1);
            }
        });
        selectedRows.clear();
        selectionRange = null;
        selectionAnchor = null;
        recomputeAll();
        updateVirtualRange();
    };

    const duplicateRow = (index) => {
        if (index < 0 || index >= rows.length) return;
        pushHistory();
        const src = rows[index];
        const copy = normalizeRow({});
        copy.id = null;
        copy.market_id = src.market_id;
        copy.petugas_id = src.petugas_id;
        copy.nomor_setor = src.nomor_setor ? src.nomor_setor + ' (copy)' : '';
        colKeys.forEach((k) => { copy[k] = src[k]; copy['_prev_' + k] = toNumber(src[k]); });
        copy._dirty = true;
        rows.splice(index + 1, 0, copy);
        dirtySet.add(copy);
        selectedRows.clear();
        recomputeAll();
        updateVirtualRange();
    };

    const moveRow = (from, to) => {
        if (from === to) return;
        pushHistory();
        const [item] = rows.splice(from, 1);
        rows.splice(to, 0, item);
        selectedRows.clear();
        recomputeAll();
        updateVirtualRange();
    };

    const toggleSelect = (index, additive) => {
        if (!additive) {
            selectedRows = new Set([index]);
            selectionRange = { row1: index, row2: index, col1: 0, col2: colKeys.length };
            selectionAnchor = { row: index, col: 0 };
        } else if (selectedRows.has(index)) {
            selectedRows.delete(index);
            selectionRange = null;
            selectionAnchor = null;
        } else {
            selectedRows.add(index);
            selectionRange = null;
            selectionAnchor = null;
        }
    };

    const selectCell = (row, col, extend = false) => {
        if (row < 0 || row >= rows.length || col < 0 || col > colKeys.length) return;
        activeRow = row;
        activeCol = col;
        if (extend && selectionAnchor) {
            selectionRange = normalizeRange(selectionAnchor, { row, col });
        } else {
            selectionAnchor = { row, col };
            selectionRange = { row1: row, row2: row, col1: col, col2: col };
        }
        selectedRows = rowsFromRange(selectionRange);
        scrollToActiveCell();
    };

    const extendSelection = (row, col) => selectCell(row, col, true);

    const selectRange = (from, to) => {
        selectedRows = new Set();
        const lo = Math.min(from, to);
        const hi = Math.max(from, to);
        for (let i = lo; i <= hi; i += 1) selectedRows.add(i);
    };

    const stopSelection = () => {
        if (isSelecting) {
            isSelecting = false;
        }
    };

    const getCellFromEvent = (event) => {
        const el = event.target.closest('td[data-row][data-col]');
        if (!el) return null;
        return {
            row: Number(el.dataset.row),
            col: colKeyIndex(el.dataset.col),
        };
    };

    const onSelectionMouseMove = (event) => {
        if (isFillDragging) {
            const destination = getCellFromEvent(event);
            if (destination) {
                updateFillRange(destination.row, destination.col);
            }
            return;
        }
        if (!isSelecting || !scrollEl) return;
        const rect = scrollEl.getBoundingClientRect();
        const destination = getCellFromEvent(event);
        if (destination) {
            extendSelection(destination.row, destination.col);
            return;
        }
        const threshold = 20;
        let delta = 0;
        if (event.clientY < rect.top + threshold) delta = -20;
        else if (event.clientY > rect.bottom - threshold) delta = 20;
        if (delta !== 0) {
            scrollEl.scrollTop = Math.max(0, Math.min(scrollEl.scrollHeight - rect.height, scrollEl.scrollTop + delta));
            updateVirtualRange();
            const approximateRow = Math.floor((event.clientY - rect.top + scrollEl.scrollTop) / rowHeight);
            extendSelection(Math.max(0, Math.min(rows.length - 1, approximateRow)), activeCol);
        }
    };

    const startCellSelection = (row, col, extend = false) => {
        if (extend) {
            extendSelection(row, col);
        } else {
            isSelecting = true;
            selectionAnchor = { row, col };
            selectCell(row, col, false);
        }
    };

    const startFillDrag = (row, col) => {
        if (row < 0 || row >= rows.length || col < 0 || col > colKeys.length) return;
        isFillDragging = true;
        fillSource = { row, col };
        fillRange = { row1: row, row2: row, col1: col, col2: col };
    };

    const updateFillRange = (row, col) => {
        if (!fillSource) return;
        fillRange = normalizeRange(fillSource, { row, col });
        selectionRange = fillRange;
        selectedRows = rowsFromRange(selectionRange);
    };

    const stopFillDrag = () => {
        if (!isFillDragging) return;
        isFillDragging = false;
        if (fillSource && fillRange) {
            applyFillHandle();
        }
        fillSource = null;
        fillRange = null;
    };

    const clampRow = (row) => Math.max(0, Math.min(rows.length - 1, row));
    const clampCol = (col) => Math.max(0, Math.min(colKeys.length, col));

    const navigateActiveCell = (row, col, extend = false) => {
        if (rows.length === 0) return;
        const targetRow = clampRow(row);
        const targetCol = clampCol(col);
        selectCell(targetRow, targetCol, extend);
    };

    const moveActiveCell = (dRow, dCol, extend = false) => {
        const baseRow = activeRow >= 0 ? activeRow : 0;
        const baseCol = activeCol >= 0 ? activeCol : 0;
        navigateActiveCell(baseRow + dRow, baseCol + dCol, extend);
    };

    const getPageStep = () => {
        if (!scrollEl) return 10;
        return Math.max(1, Math.floor(scrollEl.clientHeight / rowHeight) - 1);
    };

    const applyFillHandle = () => {
        if (!fillSource || !fillRange) return;
        if (fillRange.row1 === fillRange.row2 && fillRange.col1 === fillRange.col2) return;
        pushHistory();
        const sourceKey = cellIndexToKey(fillSource.col);
        const sourceValue = rows[fillSource.row][sourceKey];
        const sourceNumber = parseNumeric(sourceValue);
        const isVertical = fillRange.col1 === fillRange.col2;
        const isHorizontal = fillRange.row1 === fillRange.row2;
        const sequence = [];
        if (isVertical) {
            for (let r = Math.max(fillSource.row - 1, fillRange.row1); r <= Math.min(fillSource.row + 1, fillRange.row2); r += 1) {
                sequence.push(parseNumeric(rows[r][sourceKey]));
            }
        } else if (isHorizontal) {
            for (let c = Math.max(fillSource.col - 1, fillRange.col1); c <= Math.min(fillSource.col + 1, fillRange.col2); c += 1) {
                sequence.push(parseNumeric(rows[fillSource.row][cellIndexToKey(c)]));
            }
        }
        const hasSequence = sequence.length >= 2 && sequence.every((v) => !Number.isNaN(v));
        const step = hasSequence ? sequence[sequence.length - 1] - sequence[sequence.length - 2] : 0;
        for (let r = fillRange.row1; r <= fillRange.row2; r += 1) {
            for (let c = fillRange.col1; c <= fillRange.col2; c += 1) {
                if (r === fillSource.row && c === fillSource.col) continue;
                const key = cellIndexToKey(c);
                if (!key) continue;
                if (hasSequence && isVertical && c === fillSource.col) {
                    const distance = r - fillSource.row;
                    rows[r][key] = sourceNumber + step * distance;
                } else if (hasSequence && isHorizontal && r === fillSource.row) {
                    const distance = c - fillSource.col;
                    rows[r][key] = sourceNumber + step * distance;
                } else {
                    rows[r][key] = sourceValue;
                }
                markDirty(r);
                if (key !== 'nomor_setor') recalcCell(r, key);
            }
        }
        recomputeAll();
        selectionAnchor = { row: fillSource.row, col: fillSource.col };
        selectionRange = { ...fillRange };
        selectedRows = rowsFromRange(selectionRange);
    };

const scrollToActiveCell = () => {
        if (!scrollEl || activeRow < 0) return;
        const rowTop = activeRow * rowHeight;
        const rowBottom = rowTop + rowHeight;
        if (rowTop < scrollEl.scrollTop) {
            scrollEl.scrollTop = rowTop;
            updateVirtualRange();
        } else if (rowBottom > scrollEl.scrollTop + scrollEl.clientHeight) {
            scrollEl.scrollTop = rowBottom - scrollEl.clientHeight;
            updateVirtualRange();
        }
    };

    // Scroll the grid container to the bottom so a newly appended row moves
    // into the visible virtual window (and renders for editing). Without this,
    // the row is added to the backing store but stays outside gridRows when
    // the grid overflows the viewport and the user is scrolled above the bottom.
    const scrollToBottom = () => {
        if (!scrollEl) return;
        scrollEl.scrollTop = scrollEl.scrollHeight;
        updateVirtualRange();
    };

    const clearSelectedCells = () => {
        if (!selectionRange && selectedRows.size === 0) return;
        pushHistory();
        const rowsToClear = selectionRange ?
            Array.from({ length: selectionRange.row2 - selectionRange.row1 + 1 }, (_, idx) => selectionRange.row1 + idx) :
            [...selectedRows];
        rowsToClear.forEach((r) => {
            const row = rows[r];
            if (!row) return;
            row.nomor_setor = '';
            colKeys.forEach((k) => { row[k] = ''; });
            markDirty(r);
            recalcRowTotals(r);
        });
        selectedRows.clear();
        selectionRange = null;
        selectionAnchor = null;
        recomputeAll();
        updateVirtualRange();
    };

    // ── clipboard ──────────────────────────────────────────────
    const buildClipboardText = () => {
        const cols = ['nomor_setor', ...colKeys];
        const lines = [];
        if (selectionRange) {
            for (let r = selectionRange.row1; r <= selectionRange.row2; r += 1) {
                const row = rows[r];
                const cells = [];
                for (let c = selectionRange.col1; c <= selectionRange.col2; c += 1) {
                    const key = cellIndexToKey(c);
                    cells.push(row[key] === '' || row[key] === null ? '' : String(row[key]));
                }
                lines.push(cells.join('\t'));
            }
            return lines.join('\n');
        }
        const indices = [...selectedRows].sort((a, b) => a - b);
        if (indices.length === 0) return '';
        indices.forEach((i) => {
            const row = rows[i];
            const cells = cols.map((c) => (row[c] === '' || row[c] === null ? '' : String(row[c])));
            lines.push(cells.join('\t'));
        });
        return lines.join('\n');
    };

    const copySelection = () => {
        const text = buildClipboardText();
        if (!text) return;
        const cols = ['nomor_setor', ...colKeys];
        clipboard = {
            cols,
            data: text.split('\n').map((line) => line.split('\t')),
        };
        writeClipboard(text);
    };

    const writeClipboard = (text) => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
        } catch (e) {
            // ignore
        }
        document.body.removeChild(ta);
    };

    const pasteClipboard = (text) => {
        const raw = text || clipboardTextFromEvent();
        if (!raw) {
            if (clipboard) {
                applyPaste(clipboard.cols, clipboard.data);
            }
            return;
        }
        const lines = raw.split(/\r?\n/).filter((l) => l !== '');
        const data = lines.map((l) => l.split('\t'));
        const cols = ['nomor_setor', ...colKeys];
        applyPaste(cols, data);
    };

    const clipboardTextFromEvent = () => '';
    // (Injected by the paste handler via the hidden textarea.)

    const applyPaste = (cols, data) => {
        const startRow = activeRow >= 0 ? activeRow : 0;
        const startCol = activeCol >= 0 ? activeCol : 0;
        pushHistory();
        data.forEach((rowVals, r) => {
            const targetRow = startRow + r;
            if (targetRow >= rows.length) {
                addRow(rows.length, false);
            }
            rowVals.forEach((val, c) => {
                const targetCol = startCol + c;
                if (targetCol > colKeys.length) return;
                const key = targetCol === 0 ? 'nomor_setor' : cellIndexToKey(targetCol);
                if (!key) return;
                const parsed = parseNumeric(val);
                rows[targetRow][key] = isNaN(parsed) ? val : parsed;
                markDirty(targetRow);
                if (key !== 'nomor_setor') recalcCell(targetRow, key);
            });
        });
        recomputeAll();
        selectionAnchor = { row: startRow, col: startCol };
        selectionRange = {
            row1: startRow,
            row2: Math.min(rows.length - 1, startRow + data.length - 1),
            col1: startCol,
            col2: Math.min(colKeys.length, startCol + (data[0]?.length || 1) - 1),
        };
        selectedRows = rowsFromRange(selectionRange);
    };

    // ── navigation ─────────────────────────────────────────────
    const cellGrid = () => {
        // Number of interactive cells per row: nomor_setor + colKeys.
        return 1 + colKeys.length;
    };

    const cellIndexToKey = (idx) => {
        if (idx === 0) return 'nomor_setor';
        return colKeys[idx - 1];
    };

    const focusCell = (row, col) => {
        const resolvedCol = typeof col === 'number' ? col : colKeyIndex(col);
        activeRow = row;
        activeCol = resolvedCol;
        selectionAnchor = { row, col: resolvedCol };
        selectionRange = { row1: row, row2: row, col1: resolvedCol, col2: resolvedCol };
        selectedRows = rowsFromRange(selectionRange);
        const el = thisCellEl(row, resolvedCol);
        if (el) {
            el.focus();
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                el.select();
            }
        }
    };

    const thisCellEl = (row, col) => {
        const root = document.getElementById(gridId);
        if (!root) return null;
        const key = typeof col === 'number' ? cellIndexToKey(col) : col;
        const input = root.querySelector(
            `[data-row="${row}"][data-col="${key}"] input`
        );
        return input || root.querySelector(`[data-row="${row}"][data-col="${key}"] select`);
    };

    // ── save ───────────────────────────────────────────────────
    const buildPayload = () => {
        return {
            tanggal,
            rows: rows.map((row) => {
                const payload = {
                    id: row.id,
                    market_id: row.market_id,
                    petugas_id: row.petugas_id,
                    nomor_setor: row.nomor_setor,
                    entry_type: entryType,
                };
                colKeys.forEach((k) => { payload[k] = row[k]; });
                return payload;
            }),
        };
    };

    // ── Alpine component ───────────────────────────────────────
    return {
        colKeys,
        markets,
        petugas,
        tanggal,
saveState: 'Belum disimpan',
        saveMessage: '',
        saveSuccess: true,
        saving: false,
        dirtyCount: 0,
        // Reactive render trigger. The backing store (rows, virtualStart,
        // virtualEnd, colTotals, grandTotal, activeRow) lives in plain,
        // non-reactive closure variables. Alpine's x-for therefore has no
        // reactive dependency to re-render when rows change. This counter is
        // bumped in syncState() (called after every mutation) and read by the
        // getters/methods that reflect the backing store, so the grid,
        // row-count badge and totals always re-render after any change.
        gridVersion: 0,
        dragging: false,
        dragOverRow: -1,
        draftState: 'clean',   // clean | draft | saving | saved
        hasDraft: false,

        init() {
            const restored = restoreDraft();
            if (rows.length === 0) {
                addRow(0);
                this.saveState = 'Belum ada data';
            }
            recomputeAll();
            this.syncState();
            if (restored) {
                this.draftState = 'draft';
                this.hasDraft = true;
                this.saveState = 'Draft tersimpan';
            }
            this.$nextTick(() => {
                scrollEl = this.$refs.scroll;
                if (scrollEl) {
                    // attach a single scroll handler per scroll container
                    if (!scrollEl.__eret_scroll_handler_installed) {
                        scrollEl.addEventListener('scroll', updateVirtualRange);
                        scrollEl.__eret_scroll_handler_installed = true;
                    }
                    updateVirtualRange();
                }
                const rootEl = this.$el || document;
                // Avoid attaching duplicate key/mouse handlers when multiple components mount.
                if (!rootEl.__eret_key_handler_installed) {
                    rootEl.addEventListener('keydown', (event) => this.onGlobalKeydown(event));
                    rootEl.__eret_key_handler_installed = true;
                }
                if (!document.__eret_mouse_handlers_installed) {
                    document.addEventListener('mousemove', onSelectionMouseMove);
                    document.addEventListener('mouseup', () => {
                        stopSelection();
                        stopFillDrag();
                    });
                    document.__eret_mouse_handlers_installed = true;
                }
            });
            window.addEventListener('beforeunload', (e) => {
                if (dirtySet.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

// Reactive mirrors for Alpine bindings. Called after every backing-store
        // mutation. Bumping gridVersion gives Alpine a reactive signal so the
        // grid, spacers, row-count badge and totals re-render.
        syncState() {
            this.dirtyCount = dirtySet.size;
            this.colTotals = { ...colTotals };
            this.grandTotal = grandTotal;
            this.gridVersion += 1;
        },

        get gridRows() {
            // Read gridVersion so Alpine tracks this getter as reactive and
            // re-runs the x-for when rows change (add/delete/duplicate/move).
            void this.gridVersion;
            const visible = [];
            if (virtualEnd === 0 || virtualEnd <= virtualStart) {
                updateVirtualRange();
            }
            for (let i = virtualStart; i < Math.min(rows.length, virtualEnd); i += 1) {
                visible.push({ row: rows[i], rowIndex: i });
            }
            return visible;
        },

        get topSpacerHeight() {
            void this.gridVersion;
            return virtualStart * rowHeight;
        },

        get bottomSpacerHeight() {
            void this.gridVersion;
            return Math.max(0, (rows.length - virtualEnd) * rowHeight);
        },

        get rowCount() {
            // Read gridVersion so the "N baris" badge updates after changes.
            void this.gridVersion;
            return rows.length;
        },

        // Mark component as dirty and persist a draft to localStorage.
        markChanged() {
            this.saveState = 'Perubahan belum disimpan';
            this.draftState = 'draft';
            this.hasDraft = true;
            saveDraft();
            this.syncState();
        },

addBlankRow() {
            // Call inner addRow implementation and update reactive state.
            addRow();
            this.syncState();
            this.markChanged();
            // Ensure the newly appended row enters the visible virtual window
            // so it renders and is editable, even when the grid overflows the
            // viewport and the user is currently scrolled above the bottom.
            this.$nextTick(() => {
                scrollToBottom();
            });
        },

        addRow() {
            addRow();
            this.syncState();
            this.markChanged();
            // Same scroll-to-bottom guard as addBlankRow (used by toolbar/other
            // callers that append a row at the end).
            this.$nextTick(() => {
                scrollToBottom();
            });
        },

        insertRowAt(index) {
            insertRowAt(index);
            this.syncState();
            this.markChanged();
        },

        removeRow(index, event) {
            if (event && event.stopPropagation) event.stopPropagation();
            deleteRows([index]);
            this.syncState();
            this.markChanged();
        },

        removeSelected() {
            if (selectionRange) {
                clearSelectedCells();
            } else {
                deleteRows([...selectedRows]);
            }
            this.syncState();
            this.markChanged();
        },

        duplicateRow(index) {
            duplicateRow(index);
            this.syncState();
            this.markChanged();
        },

        duplicateSelected() {
            const indices = [...selectedRows].sort((a, b) => a - b);
            // Duplicate from last to first so inserted copies land below originals.
            [...indices].reverse().forEach((i) => duplicateRow(i));
            this.syncState();
            this.markChanged();
        },

        toggleSelect(index, event) {
            toggleSelect(index, (event && event.shiftKey) || (event && event.ctrlKey) || (event && event.metaKey));
            this.syncState();
        },

        selectAll() {
            selectedRows = new Set(rows.map((_, i) => i));
            selectionRange = { row1: 0, row2: rows.length - 1, col1: 0, col2: colKeys.length };
            this.syncState();
        },

        clearSelection() {
            selectedRows.clear();
            selectionRange = null;
            this.syncState();
        },

        isSelected(index) {
            return selectedRows.has(index);
        },

        isCellInRange(index, col) {
            return isCellSelectedInRange(index, col);
        },

        isActive(index, col) {
            return activeRow === index && activeCol === col;
        },

        isDragging(index) {
            return dragIdx === index;
        },

        isDropTarget(index) {
            return dragOverRow === index;
        },

        onDragStart(index) {
            dragIdx = index;
            dragging = true;
            this.dragging = true;
        },

        onDragOver(index) {
            dragOverRow = index;
            this.dragOverRow = index;
        },

onDrop(index) {
            if (dragIdx >= 0 && dragIdx !== index) {
                moveRow(dragIdx, index);
                this.syncState();
                this.markChanged();
            }
            dragIdx = -1;
            dragOverRow = -1;
            this.dragging = false;
            this.dragOverRow = -1;
        },

        onDragEnd() {
            dragIdx = -1;
            dragOverRow = -1;
            this.dragging = false;
            this.dragOverRow = -1;
        },

        // Cell editing
        startEdit(index, col) {
            const resolvedCol = typeof col === 'number' ? cellIndexToKey(col) : col;
            editing = { row: index, col: resolvedCol };
            activeRow = index;
            activeCol = typeof col === 'number' ? col : colKeyIndex(resolvedCol);
            selectedRows = new Set([index]);
            selectionRange = { row1: index, row2: index, col1: activeCol, col2: activeCol };
            selectionAnchor = { row: index, col: activeCol };
            this.syncState();
            this.$nextTick(() => {
                const el = thisCellEl(index, resolvedCol);
                if (el) el.focus();
            });
        },

        isEditing(index, col) {
            return editing && editing.row === index && editing.col === col;
        },

commitEdit(index, col, event) {
            const value = event.target.value;
            rows[index][col] = value;
            markDirty(index);
            recalcCell(index, col);
            editing = null;
            this.syncState();
            this.markChanged();
        },

        cancelEdit() {
            editing = null;
            this.syncState();
        },

onCellInput(index, col, event) {
            rows[index][col] = event.target.value;
            markDirty(index);
            recalcCell(index, col);
            // Light reactive update: refresh the footer totals + dirty/draft
            // state WITHOUT bumping gridVersion. Bumping gridVersion on every
            // keystroke re-renders the grid x-for, which recreates the focused
            // input element and loses partially-typed values when the virtual
            // window shifts (the grid is virtualized). The value is already
            // stored in the backing row, so it persists on commit/next render.
            this.colTotals = { ...colTotals };
            this.grandTotal = grandTotal;
            this.dirtyCount = dirtySet.size;
            this.saveState = 'Perubahan belum disimpan';
            this.draftState = 'draft';
            this.hasDraft = true;
            saveDraft();
        },

        onCellMouseDown(index, col, event) {
            const colIdx = this.colKeyIndex(col);
            if (event.shiftKey && selectionAnchor) {
                extendSelection(index, colIdx);
            } else {
                startCellSelection(index, colIdx, false);
            }
            this.syncState();
        },

        onCellKeydown(index, col, event) {
            const colIdx = colKeyIndex(col);
            const ctrl = event.ctrlKey || event.metaKey;
            switch (event.key) {
                case 'Enter':
                    if (ctrl) {
                        event.preventDefault();
                        this.fillSelection(index, col);
                        return;
                    }
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        if (index > 0) this.startEdit(index - 1, col);
                    } else if (index + 1 < rows.length) {
                        this.startEdit(index + 1, col);
                    } else {
                        addRow(index + 1);
                        this.startEdit(index + 1, col);
                        this.syncState();
                    }
                    break;
                case 'Tab':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        if (colIdx > 0) this.startEdit(index, colIdx - 1);
                    } else {
                        if (colIdx < colKeys.length) this.startEdit(index, colIdx + 1);
                    }
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.cancelEdit();
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        extendSelection(Math.min(rows.length - 1, index + 1), colIdx);
                        this.syncState();
                    } else if (index + 1 < rows.length) {
                        this.startEdit(index + 1, col);
                    }
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        extendSelection(Math.max(0, index - 1), colIdx);
                        this.syncState();
                    } else if (index > 0) {
                        this.startEdit(index - 1, col);
                    }
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        extendSelection(index, Math.min(colKeys.length, colIdx + 1));
                        this.syncState();
                    } else if (colIdx < colKeys.length) {
                        this.startEdit(index, colIdx + 1);
                    }
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (event.shiftKey) {
                        extendSelection(index, Math.max(0, colIdx - 1));
                        this.syncState();
                    } else if (colIdx > 0) {
                        this.startEdit(index, colIdx - 1);
                    }
                    break;
                case 'PageDown':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    this.startEdit(Math.min(rows.length - 1, index + getPageStep()), col);
                    break;
                case 'PageUp':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    this.startEdit(Math.max(0, index - getPageStep()), col);
                    break;
                case 'Home':
                    event.preventDefault();
                    if (ctrl) {
                        this.startEdit(0, 0);
                    } else {
                        this.startEdit(index, 0);
                    }
                    break;
                case 'End':
                    event.preventDefault();
                    if (ctrl) {
                        this.startEdit(rows.length - 1, colKeys.length);
                    } else {
                        this.startEdit(index, colKeys.length);
                    }
                    break;
                case 'Delete':
                    if (!ctrl) {
                        event.preventDefault();
                        clearSelectedCells();
                        this.syncState();
                    }
                    break;
                default:
                    break;
            }
        },

        colKeyIndex(col) {
            if (typeof col === 'number') return col;
            if (col === 'nomor_setor') return 0;
            return colKeys.indexOf(col) + 1;
        },

fillSelection(index, col) {
            if (!selectionRange) {
                return;
            }
            pushHistory();
            const colIdx = colKeyIndex(col);
            const sourceValue = rows[index][col];
            const sourceNumber = parseNumeric(sourceValue);
            const sameCol = selectionRange.col1 === selectionRange.col2;
            for (let r = selectionRange.row1; r <= selectionRange.row2; r += 1) {
                for (let c = selectionRange.col1; c <= selectionRange.col2; c += 1) {
                    const key = cellIndexToKey(c);
                    if (!key) continue;
                    if (r === index && c === colIdx) continue;
                    if (sameCol && !Number.isNaN(sourceNumber) && key !== 'nomor_setor') {
                        rows[r][key] = sourceNumber + (r - index);
                    } else {
                        rows[r][key] = sourceValue;
                    }
                    markDirty(r);
                    if (key !== 'nomor_setor') recalcCell(r, key);
                }
            }
            recomputeAll();
            this.syncState();
            this.markChanged();
        },

        // Clipboard handlers
        onCopy() {
            copySelection();
        },

        onCut() {
            copySelection();
            const indices = [...selectedRows].sort((a, b) => b - a);
            indices.forEach((i) => {
                const row = rows[i];
                row.nomor_setor = '';
                colKeys.forEach((k) => { row[k] = ''; });
                markDirty(i);
                recalcRowTotals(i);
            });
            recomputeAll();
            this.syncState();
            this.markChanged();
        },

        onPaste(event) {
            const text = event.clipboardData ? event.clipboardData.getData('text/plain') : '';
            if (text) {
                event.preventDefault();
                pasteClipboard(text);
                this.syncState();
                this.markChanged();
            }
        },

        onGlobalKeydown(event) {
            const ctrl = event.ctrlKey || event.metaKey;
            if (ctrl && event.key.toLowerCase() === 'z') {
                event.preventDefault();
                undo();
                this.syncState();
            } else if (ctrl && event.key.toLowerCase() === 'y') {
                event.preventDefault();
                redo();
                this.syncState();
            } else if (ctrl && event.key.toLowerCase() === 'c') {
                event.preventDefault();
                copySelection();
            } else if (ctrl && event.key.toLowerCase() === 'x') {
                event.preventDefault();
                this.onCut();
            } else if (ctrl && event.key.toLowerCase() === 'v') {
                if (clipboard) {
                    event.preventDefault();
                    this.pasteClipboard();
                }
            } else if (ctrl && event.key.toLowerCase() === 'a') {
                event.preventDefault();
                selectedRows = new Set(rows.map((_, i) => i));
                selectionRange = { row1: 0, row2: rows.length - 1, col1: 0, col2: colKeys.length };
                selectionAnchor = { row: 0, col: 0 };
                this.syncState();
            } else if (event.key === 'Escape') {
                if (editing) {
                    this.cancelEdit();
                } else {
                    selectedRows.clear();
                    selectionRange = null;
                    selectionAnchor = null;
                    this.syncState();
                }
            } else if (!editing) {
                const extend = event.shiftKey;
                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault();
                        moveActiveCell(getPageStep() > 1 ? 1 : 1, 0, extend);
                        this.syncState();
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        moveActiveCell(-1, 0, extend);
                        this.syncState();
                        break;
                    case 'ArrowRight':
                        event.preventDefault();
                        moveActiveCell(0, 1, extend);
                        this.syncState();
                        break;
                    case 'ArrowLeft':
                        event.preventDefault();
                        moveActiveCell(0, -1, extend);
                        this.syncState();
                        break;
                    case 'PageDown':
                        event.preventDefault();
                        moveActiveCell(getPageStep(), 0, extend);
                        this.syncState();
                        break;
                    case 'PageUp':
                        event.preventDefault();
                        moveActiveCell(-getPageStep(), 0, extend);
                        this.syncState();
                        break;
                    case 'Home':
                        event.preventDefault();
                        if (event.ctrlKey || event.metaKey) {
                            navigateActiveCell(0, 0, extend);
                        } else {
                            navigateActiveCell(activeRow >= 0 ? activeRow : 0, 0, extend);
                        }
                        this.syncState();
                        break;
                    case 'End':
                        event.preventDefault();
                        if (event.ctrlKey || event.metaKey) {
                            navigateActiveCell(rows.length - 1, colKeys.length, extend);
                        } else {
                            navigateActiveCell(activeRow >= 0 ? activeRow : 0, colKeys.length, extend);
                        }
                        this.syncState();
                        break;
                    default:
                        break;
                }
            }
        },

        pasteClipboard() {
            // Read from the internal clipboard (e.g. after clicking "Tempel").
            if (clipboard) {
                applyPaste(clipboard.cols, clipboard.data);
                this.syncState();
                this.markChanged();
            }
        },

        // Discard the local draft and reload server data (no backend call).
        discardDraft() {
            if (!this.hasDraft && dirtySet.size === 0) return;
            if (!window.confirm('Buang draft yang belum disimpan? Perubahan akan hilang.')) {
                return;
            }
            clearDraft();
            dirtySet.clear();
            // Reset to server-initialized rows.
            rows = (config.initialRows || []).map(normalizeRow);
            if (rows.length === 0) {
                addRow(0);
            }
            recomputeAll();
            this.draftState = 'clean';
            this.hasDraft = false;
            this.saveState = 'Belum disimpan';
            this.saveMessage = '';
            this.syncState();
        },

        // Totals (reactive)
        cellTotal(index) {
            const row = rows[index];
            return row ? row.total : 0;
        },

colTotal(col) {
            // Read the reactive mirror (set in syncState) so the footer
            // per-column totals re-render after row changes / edits.
            return (this.colTotals && this.colTotals[col]) || 0;
        },

        grandTotal() {
            return grandTotal;
        },

        cellError(index, col) {
            const row = rows[index];
            if (!row || !row._errors) return null;
            return row._errors[col] || null;
        },

        rowError(index) {
            const row = rows[index];
            if (!row || !row._errors) return null;
            return Object.keys(row._errors).length ? row._errors : null;
        },

        fmtCell(value) {
            return fmtCell(value);
        },

        fmt(value) {
            return fmt(value);
        },

        // Save
        async saveRows() {
            if (this.saving) return;
            if (!validateAll()) {
                this.syncState();
                this.saveMessage = 'Periksa kembali baris yang ditandai merah.';
                this.saveSuccess = false;
                return;
            }
this.saving = true;
            this.saveState = 'Menyimpan...';
            this.draftState = 'saving';
            try {
                const payload = buildPayload();
                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
const data = await res.json();
                if (res.ok && data.success) {
                    this.saveMessage = 'Data berhasil disimpan. (baru: ' + data.created +
                        ', diperbarui: ' + data.updated +
                        ', dihapus: ' + data.deleted + ')';
                    this.saveSuccess = true;
                    this.saveState = 'Tersimpan';
                    this.draftState = 'saved';
                    this.hasDraft = false;
                    clearDraft();
                    clearDirty();
                    this.syncState();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    // Handle all possible Laravel error payload shapes:
                    //   - Array of { message } objects (business validation)
                    //   - Array of plain strings
                    //   - Object keyed by field (FormRequest 422 validation)
                    //   - data.message string fallback
                    const messages = extractErrorMessages(data);
                    this.saveMessage = messages.length > 0
                        ? 'Gagal menyimpan: ' + messages.join('; ')
                        : 'Gagal menyimpan data. Silakan coba lagi.';
                    this.saveSuccess = false;
                    this.saveState = 'Gagal disimpan';
                    this.draftState = 'draft';
                }
            } catch (e) {
                this.saveMessage = 'Terjadi kesalahan saat menyimpan: ' + e.message;
                this.saveSuccess = false;
                this.saveState = 'Gagal disimpan';
                this.draftState = 'draft';
            } finally {
                this.saving = false;
            }
        },
    };
}
