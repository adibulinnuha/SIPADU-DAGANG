<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed the initial Korwil Karimata personnel into the Master Petugas table.
 *
 * Officials are imported into the existing `users` table (role = Petugas, the
 * existing Korwil role, kept for backward compatibility). Each personnel row is
 * linked to a market via the single `market_id` foreign key. Personnel whose
 * assignment includes "Juru Pungut" have `is_juru_pungut = true` so the ERET
 * dropdown can show only active Juru Pungut for the selected market.
 *
 * The seeder is idempotent: it uses `updateOrCreate()` keyed on `nip` (or
 * `email` when NIP is not available) so it is safe to run any number of times.
 */
class PetugasSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $markets = Market::query()
            ->get()
            ->mapWithKeys(fn (Market $m) => [mb_strtoupper(trim($m->name), 'UTF-8') => $m->id]);

        $roster = [
            // name, nip, rank, position, market, is_juru_pungut
            ['WIYANTO',                                     '196808112007011018', 'Pembina',              'Pengadministrasi Perkantoran',        'REJOMULYO',        false],
            ['TOMMY HARSONO',                               '197701022008011010', 'Penata',               'Pengelola Pasar Waru Indah',          'WARU INDAH',       false],
            ['ANJAR SUSETYO',                               '196911202009011002', 'Penata',               'Pengelola Pasar Langgar',             'LANGGAR',          true],
            ['NOOR AZIS',                                   '197105052008011017', 'Penata',               'Pengelola Pasar Tambak Lorok',        'TAMBAK LOROK',     true],
            ['MOCH WINARNO',                                '197803282009011007', 'Penata',               'Juru Pungut Pasar Dargo',             'DARGO',            true],
            ['SUTRIMAH',                                    '197105212007012015', 'Penata',               'Pengadministrasi Keuangan Korwil',    'REJOMULYO',        false],
            ['SUPARNO',                                     '196906152007011025', 'Pembina',              'Pengelola Pasar Rejomulyo',           'REJOMULYO',        false],
            ['PONIMAN',                                     '197007022007011012', 'Penata',               'Juru Pungut Pasar Karimata',          'KARIMATA 1',       true],
            ['CHAVIDZ ANDI SAPUTRA',                        '199402012025211024', 'Pranata',              'Juru Pungut Pasar Dargo',             'DARGO',            true],
            ['INDRA SETIAWAN',                              '199307082025211010', 'Pranata',              'Juru Pungut Pasar Waru Indah',        'WARU INDAH',       true],
            ['IKA WULANDARI',                               '199410022025212003', 'Pranata',              'Juru Pungut Pasar Karimata',          'KARIMATA 1',       true],
            ['CITRA YONIT DIAN RACHMAWATI, S.E.',           '198306162025212004', 'Penata',               'Pengadministrasi Umum Korwil',        'REJOMULYO',        false],
            ['LINTANG SATRIA PUTRA RAMADHAN, S.H.',         '199203202025211009', 'Pranata',              'Pengadministrasi Umum Korwil',        'REJOMULYO',        false],
            ['MUHAMAD BAYU MARJOKO',                        '199603022025211053', 'Pranata',              'Juru Pungut Pasar Waru Indah',        'WARU INDAH',       true],
            ['FAHRUL RIZA WIDIYANTO',                       '199011012025211079', 'Pranata',              'Tenaga Kebersihan Pasar Karimata',    'KARIMATA 1',       true],
            ['HENI LESTARI',                                '197511082025212011', 'Penata',               'Juru Pungut Pasar Waru Indah',        'WARU INDAH',       true],
            ['KASMURI',                                     '197112312025211096', 'Tenaga Kebersihan',    'Tenaga Kebersihan Pasar Langgar',     'LANGGAR',          false],
            ['HENGKY',                                      '198103162025211037', 'Tenaga Kebersihan',    'Tenaga Kebersihan Pasar Karimata',    'KARIMATA 1',       false],
            ['PURNOMO WIDODO',                              null,                 null,                   'Juru Pungut Pasar Rejomulyo',        'REJOMULYO',        true],
            ['PRINGGO SUTJAHYO TR',                         '196906092025211015', 'Penata',               'Kepala Pasar Dargo',                  'DARGO',            false],
        ];

        foreach ($roster as [$name, $nip, $rank, $jabatan, $market, $isJuruPungut]) {
            $marketId = $markets[mb_strtoupper(trim((string) $market), 'UTF-8')] ?? null;

            $base = [
                'name' => $name,
                'jabatan' => $jabatan,
                'rank' => $rank,
                'market_id' => $marketId,
                'role' => UserRole::Petugas,
                'email' => $this->emailFor($name, $nip),
                'password' => $password,
                'is_active' => true,
                'is_juru_pungut' => $isJuruPungut,
                'phone' => null,
                'notes' => null,
            ];

            if ($nip) {
                User::updateOrCreate(['nip' => $nip], $base);
            } else {
                User::updateOrCreate(['email' => $base['email']], $base);
            }
        }
    }

    /**
     * Build a stable, predictable email for a personnel record so that
     * updateOrCreate can key idempotently even when a NIP is absent.
     */
    protected function emailFor(string $name, ?string $nip): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '.', $name));
        $slug = trim($slug, '.');

        return ($nip ?: $slug).'@korwil.local';
    }
}

