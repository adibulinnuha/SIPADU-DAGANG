<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KarimataPetugasSeeder extends Seeder
{
    public function run(): void
    {
        $markets = Market::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtoupper($name) => $id]);

        $rows = [
            ['WIYANTO', '196808112007011018', 'Pengatur Tk. I / (II.d)', 'Korwil Karimata dan Pengelola pasar Karimata', 'KARIMATA', false],
            ['TOMMY HARSONO', '197701022008011010', 'Penata Muda / (III/a)', 'Pengelola pasar Waru Indah', 'WARU INDAH', false],
            ['ANJAR SUSETYO', '196911202009011002', 'Penata Muda / (III/a)', 'Pengelola Pasar Langgar dan Juru Pungut Pasar Langgar', 'LANGGAR', true],
            ['NOOR AZIS', '197105052008011017', 'Penata Muda / (III/a)', 'Pengelola Pasar Tambak Lorok dan Juru Pungut Pasar Tambak Lorok', 'TAMBAK LOROK', true],
            ['MOCH WINARNO', '197803282009011007', 'Penata Muda / (III/a)', 'Juru Pungut Pasar Dargo', 'DARGO', true],
            ['SUTRIMAH', '197105212007012015', 'Penata Muda / (III/a)', 'Pengadministrasi Keuangan Korwil Karimata', 'KARIMATA', false],
            ['SUPARNO', '196906152007011025', 'Pengatur / (II/c)', 'Pengelola Pasar Rejomulyo', 'REJOMULYO', false],
            ['PONIMAN', '197007022007011012', 'Pengatur Muda / (II/a)', 'Juru Pungut Pasar Karimata', 'KARIMATA', true],
            ['CHAVIDZ ANDI SAPUTRA', '199402012025211024', 'Golongan V', 'Juru Pungut Pasar Dargo dan Pengadministrasi Umum Korwil Karimata', 'DARGO', true],
            ['INDRA SETIAWAN', '199307082025211010', 'Golongan V', 'Juru Pungut Pasar Waru Indah', 'WARU INDAH', true],
            ['IKA WULANDARI', '199410022025212003', 'Golongan V', 'Juru Pungut Pasar Karimata', 'KARIMATA', true],
            ['CITRA YONIT DIAN RACHMAWATI, S.E', '198306162025212004', 'Golongan IX', 'Pengadministrasi Umum Korwil Karimata', 'KARIMATA', false],
            ['LINTANG SATRIA PUTRA RAMADHAN, S.H', '199203202025211009', 'Golongan IX', 'Pengadministrasi Umum Korwil Karimata', 'KARIMATA', false],
            ['MUHAMAD BAYU MARJOKO', '199603022025211053', 'PPPK Paruh Waktu', 'Juru Pungut Pasar Waru Indah', 'WARU INDAH', true],
            ['FAHRUL RIZA WIDIYANTO', '199011012025211079', 'PPPK Paruh Waktu', 'Tenaga Kebersihan Pasar Karimata dan Juru Pungut PKL', 'KARIMATA', true],
            ['HENI LESTARI', '197511082025212011', 'PPPK Paruh Waktu', 'Juru Pungut Pasar Waru Indah', 'WARU INDAH', true],
            ['KASMURI', '197112312025211096', 'PPPK Paruh Waktu', 'Tenaga Kebersihan Pasar Langgar', 'LANGGAR', false],
            ['HENGKY', '198103162025211037', 'PPPK Paruh Waktu', 'Tenaga Kebersihan Pasar Karimata', 'KARIMATA', false],
            ['PURNOMO WIDODO', null, '-', 'Juru Pungut Pasar Rejomulyo', 'REJOMULYO', true],
            ['PRINGGO SUTJAHYO TR', '196906092025211015', 'PPPK Paruh Waktu', 'Kepala Pasar Dargo dan Pasar Bubakan', 'DARGO', false],
        ];

        foreach ($rows as [$name, $nip, $rank, $jabatan, $marketName, $isJp]) {
            $marketId = $markets[$marketName] ?? null;

            User::updateOrCreate(
                ['nip' => $nip ?: 'NO-NIP-'.md5($name)],
                [
                    'name' => $name,
                    'email' => strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9 ]/', '', $name))).'@karimata.local',
                    'password' => Hash::make('password123'),
                    'role' => UserRole::Petugas,
                    'market_id' => $marketId,
                    'rank' => $rank,
                    'jabatan' => $jabatan,
                    'is_active' => true,
                    'is_juru_pungut' => $isJp,
                ]
            );
        }
    }
}