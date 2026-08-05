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
        colKeys.forEach((k) => { if (r[k] === undefined || r[k] === null) r[k] = ''; });
        r.total = 0;
        r._dirty = false;
        return r;
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
    let editing = null;             // { row, col }
    let clipboard = null;           // { cols:[], data: 2D array }
    let dragIdx = -1;
    let dropIdx = -1;

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
    const addRow = (index) => {
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
        return rows.length - 1;
    };

    const insertRowAt = (index) => addRow(index);

    const deleteRows = (indices) => {
        const target = [...new Set(indices)].sort((a, b) => b - a);
        target.forEach((i) => {
            if (i >= 0 && i < rows.length) {
                const row = rows[i];
                dirtySet.delete(row);
                rows.splice(i, 1);
            }
        });
        selectedRows.clear();
        recomputeAll();
    };

    const duplicateRow = (index) => {
        if (index < 0 || index >= rows.length) return;
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
    };

    const moveRow = (from, to) => {
        if (from === to) return;
        const [item] = rows.splice(from, 1);
        rows.splice(to, 0, item);
        selectedRows.clear();
        recomputeAll();
    };

    const toggleSelect = (index, additive) => {
        if (!additive) {
            selectedRows = new Set([index]);
        } else if (selectedRows.has(index)) {
            selectedRows.delete(index);
        } else {
            selectedRows.add(index);
        }
    };

    const selectRange = (from, to) => {
        selectedRows = new Set();
        const lo = Math.min(from, to);
        const hi = Math.max(from, to);
        for (let i = lo; i <= hi; i++) selectedRows.add(i);
    };

    // ── clipboard ──────────────────────────────────────────────
    const buildClipboardText = () => {
        const indices = [...selectedRows].sort((a, b) => a - b);
        if (indices.length === 0) return '';
        const cols = ['nomor_setor', ...colKeys];
        const lines = [];
        indices.forEach((i) => {
            const row = rows[i];
            const cells = cols.map((c) => {
                if (c === 'nomor_setor') return row.nomor_setor || '';
                return row[c] === '' || row[c] === null ? '' : String(row[c]);
            });
            lines.push(cells.join('\t'));
        });
        return lines.join('\n');
    };

    const copySelection = () => {
        const text = buildClipboardText();
        if (!text) return;
        const cols = ['nomor_setor', ...colKeys];
        // Store structured clipboard for internal paste.
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
            // fall back to internal clipboard
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
        const colIndex = cols.map((c) => colKeys.indexOf(c));
        data.forEach((rowVals, r) => {
            const targetRow = startRow + r;
            if (targetRow >= rows.length) {
                addRow(rows.length);
            }
            rowVals.forEach((val, c) => {
                const targetCol = startCol + c;
                if (targetCol >= colKeys.length) return;
                const key = colKeys[targetCol];
                const parsed = parseNumeric(val);
                rows[targetRow][key] = isNaN(parsed) ? val : parsed;
                markDirty(targetRow);
                recalcCell(targetRow, key);
            });
        });
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
        activeRow = row;
        activeCol = col;
        selectedRows = new Set([row]);
        const el = thisCellEl(row, col);
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
        const key = cellIndexToKey(col);
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
        dragging: false,
        dragOverRow: -1,
        draftState: 'clean',   // clean | draft | saving | saved
        hasDraft: false,

        init() {
            // Restore a previously saved draft (if any) before rendering.
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
            window.addEventListener('beforeunload', (e) => {
                if (dirtySet.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        // Reactive mirrors for Alpine bindings.
        syncState() {
            this.dirtyCount = dirtySet.size;
            this.colTotals = { ...colTotals };
            this.grandTotal = grandTotal;
        },

        // Mark component as dirty and persist a draft to localStorage.
        markChanged() {
            this.saveState = 'Perubahan belum disimpan';
            this.draftState = 'draft';
            this.hasDraft = true;
            saveDraft();
            this.syncState();
        },

        get gridRows() {
            return rows;
        },

addRow() {
            addRow();
            this.syncState();
            this.markChanged();
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
            deleteRows([...selectedRows]);
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
            this.syncState();
        },

        clearSelection() {
            selectedRows.clear();
            this.syncState();
        },

        isSelected(index) {
            return selectedRows.has(index);
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
            editing = { row: index, col };
            activeRow = index;
            activeCol = col;
            selectedRows = new Set([index]);
            this.syncState();
            this.$nextTick(() => {
                const el = thisCellEl(index, col);
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
            this.syncState();
            this.markChanged();
        },

        onCellKeydown(index, col, event) {
            const colIdx = colKeyIndex(col);
            switch (event.key) {
                case 'Enter':
                    if (event.ctrlKey) {
                        // Ctrl+Enter: fill selected cells with current value.
                        event.preventDefault();
                        this.fillSelection(index, col);
                        return;
                    }
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (index + 1 < rows.length) {
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
                    if (index + 1 < rows.length) this.startEdit(index + 1, col);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (index > 0) this.startEdit(index - 1, col);
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (colIdx < colKeys.length) this.startEdit(index, colIdx + 1);
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    this.commitEdit(index, col, event);
                    if (colIdx > 0) this.startEdit(index, colIdx - 1);
                    break;
                default:
                    break;
            }
        },

        colKeyIndex(col) {
            if (col === 'nomor_setor') return 0;
            return colKeys.indexOf(col) + 1;
        },

fillSelection(index, col) {
            const value = rows[index][col];
            selectedRows.forEach((i) => {
                if (i === index) return;
                rows[i][col] = value;
                markDirty(i);
                recalcCell(i, col);
            });
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
            return colTotals[col] || 0;
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
                if (data.success) {
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
                    this.saveMessage = 'Sebagian baris gagal disimpan: ' +
                        (data.errors || []).map((e) => e.message).join('; ');
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
