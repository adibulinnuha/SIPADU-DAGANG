# TODO — Master Petugas Korwil + ERET Active Juru Pungut Integration

## Backend
- [ ] Migration: add petugas fields to `users` (`market_id`, `nip`, `rank`, `jabatan`, `phone`, `notes`, `is_active`, `is_juru_pungut`)
- [ ] Model `User`: fillable, casts, `belongsTo(Market)`, `forMarket()` factory state
- [ ] Model `Market`: `hasMany(User)`
- [ ] `PetugasController`: full CRUD + `activePetugas(Market)` JSON endpoint
- [ ] `EretDashboardService`: validate inactive petugas; role=petugas must belong to market
- [ ] `EretDashboardSaveRequest`: `petugas_id` required
- [ ] `DashboardController`: pass only active petugas list
- [ ] `UserFactory`: new fields + `forMarket()`
- [ ] `PetugasSeeder` (idempotent) + register in `DatabaseSeeder`

## Routes
- [ ] `GET /api/markets/{market}/active-petugas` auth route

## Frontend
- [ ] `dashboard.blade.php`: per-row petugas list, loading indicator
- [ ] `eret-spreadsheet.js`: market-change AJAX, clear petugas, loading state

## Views / UI
- [ ] `petugas/index` (search, pagination, Aktif/Nonaktif badge)
- [ ] `petugas/create`, `petugas/edit`
- [ ] sidebar + navigation links to Master Petugas

## Tests
- [ ] `PetugasTest` (CRUD, active filter, market filter, JSON shape, save validation)

## Docs
- [ ] README updates (Master Petugas, seeder, API, migrations, tests)

## Verification
- [ ] `php artisan test` pass
- [ ] `php artisan migrate:fresh --seed` works
- [ ] `npm run build` works
- [ ] `git status` clean
- [ ] commit `feat: complete Master Petugas Korwil and ERET active collector integration`

