

import Alpine from 'alpinejs';
import { createEretSpreadsheet } from './eret-spreadsheet';

window.Alpine = Alpine;

// Expose the spreadsheet factory so the dashboard blade can register it
// with the appropriate config (markets, petugas, initial rows, etc.).
window.createEretSpreadsheet = createEretSpreadsheet;

Alpine.start();
