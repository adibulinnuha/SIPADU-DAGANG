# SIPADU-DAGANG

**Sistem Informasi Pendataan Retribusi Pasar**

Integrated Market Management Information System — Digitalisasi pengelolaan administrasi retribusi dan operasional pasar.

---

## Requirements

- **PHP** 8.3+
- **Laravel** 13.x
- **Database** MySQL 8.0+ / MariaDB 10.6+ / SQLite
- **Extensions:** BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, Zip, GD
- **Composer** 2.x
- **Node.js** 20+ (for Vite asset compilation)

---

## Installation

```bash
# 1. Clone the repository
git clone <repo-url> sipadu-dagang
cd sipadu-dagang

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sipadu_dagang
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Database setup
php artisan migrate --seed

# 5. Storage link
php artisan storage:link

# 6. Install & build frontend assets
npm install
npm run build

# 7. Cache for production
php artisan optimize
```

---

## Deployment Checklist

- [ ] `.env` configured with production values
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` set to production URL
- [ ] `DB_CONNECTION` set to MySQL/MariaDB
- [ ] `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`
- [ ] `php artisan key:generate` run
- [ ] `php artisan migrate --force` run
- [ ] `php artisan storage:link` created
- [ ] `php artisan optimize` run (config, events, routes, views cached)
- [ ] `npm run build` compiled assets
- [ ] `public/storage` symlink points to `storage/app/public`
- [ ] Web server configured to serve from `public/`
- [ ] Queue worker running: `php artisan queue:work`
- [ ] Supervisor configured for queue worker persistence
- [ ] Scheduler configured: `* * * * * php artisan schedule:run`

---

## Features

### Workflow
Draft → Submitted → Verified → Approved → Locked  
Full audit trail with timestamps and user attribution per transition.

### Verification
Dual-write: legacy `verifications` table + `retributions` workflow fields.  
State machine prevents invalid transitions.

### Bendel Generator
Automatic bundle generation from verified/approved/locked retributions.  
Supports legacy and workflow data sources (config: `eret.bendel_source`).

### ERET Export
Template-based Excel export using `ERET JULI.xltx`.  
Configurable market row and column mappings in `config/eret.php`.

### Dashboard
Real-time monitoring with:
- Total markets, today's transactions & revenue
- Monthly revenue
- 7-day revenue chart (Chart.js)
- Top 5 markets by revenue
- Markets not yet submitted
- Recent transactions

### Reports
- Daily recap with per-market breakdown
- Monthly aggregation
- Workflow status summary
- Filters: date range, market, workflow status
- Export: Excel (XLSX), PDF

### OCR e-Ticketing
AI-assisted OCR via Gemini API for e-Ticketing input.  
Upload → OCR → Review → Store.

### Backup & Restore
- Database + storage backup as ZIP archive
- Download, delete, restore
- Configurable retention period
- Admin-only access

---

## Architecture

```
Controllers → Services → Models
  │              │           │
  └── Injected via DI ───────┘
  
Services layer contains ALL business logic.
Controllers remain thin — validation + response only.
```

### Key Services
| Service | Responsibility |
|---------|---------------|
| `WorkflowService` | Status transitions, audit trail, verified retributions |
| `AggregateService` | Daily/monthly/top markets aggregation, revenue series |
| `EretTemplateService` | Template-based ERET Excel generation |
| `BendelGenerator` | Bundle creation from verified data |
| `GeminiService` | AI OCR processing |
| `BackupService` | DB + storage backup/restore |

---

## API / Routes

All routes are web-based (no API module yet). Key route groups:

| Prefix | Middleware | Controller(s) |
|--------|-----------|---------------|
| `/dashboard` | `auth` | `DashboardController` |
| `/retributions` | `auth` | `RetributionController`, `RetributionsExportController` |
| `/verifications` | `auth` | `VerificationController` |
| `/rekap-harian` | `auth` | `RekapHarianController` |
| `/bendel` | `auth` | `BendelController` |
| `/ocr` | `auth` | `OcrController` |
| `/reports` | `auth` | `ReportsController` |
| `/backups` | `auth`, `role:admin` | `BackupController` |
| `/users` | `auth`, `role:admin` | `UserController` |
| `/markets` | `auth` | `MarketController` |
| `/petugas` | `auth` | `PetugasController` |

---

## Security

- All routes behind `auth` middleware
- Admin routes protected by `role:admin` middleware
- CSRF protection on all POST/PUT/DELETE
- XSS protection via Blade `{{ }}` escaping
- SQL injection prevented by Eloquent parameter binding
- File uploads validated (type, size, path traversal)
- Backup downloads restricted to admin
- Restore requires admin confirmation

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter=Workflow
php artisan test --filter=Bendel
php artisan test --filter=Verification

# Code quality
php vendor/bin/pint          # Laravel Pint (code style)
php vendor/bin/phpstan analyse --level=5 app routes   # Static analysis
```

Current test count: **51 tests** (158 assertions)

---

## Backup & Restore

```bash
# Via Web UI (admin only)
/backups → Create → Download → Delete → Restore

# Manual backup location
storage/app/backups/
```

Backup archives contain:
- Full database dump (SQL)
- Storage files (templates, uploads)

---

## OCR Workflow

1. Upload e-Ticketing image at `/ocr`
2. Image sent to Gemini API for OCR
3. Review extracted data at `/ocr/review`
4. Confirm to store as retribution transaction

---

## License

MIT License

