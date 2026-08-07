<?php

use App\Models\User;
use App\Services\BackupService;
use App\Services\RestoreService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

/**
 * Helper: make an admin user.
 */
function backupAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

/**
 * Helper: make a non-admin (petugas) user.
 */
function backupPetugas(): User
{
    return User::factory()->create(['role' => UserRole::Petugas]);
}

/**
 * Point the backup path to a temp directory so tests don't pollute the
 * real storage/app/backups directory.
 */
function backupTempPath(): string
{
    $dir = storage_path('app/testing_backups_'.uniqid());

    Config::set('backup.path', str_replace(storage_path().DIRECTORY_SEPARATOR, '', $dir));

    return $dir;
}

/**
 * Backup route access — only admin can access.
 */
test('backup index requires admin role', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $petugas = backupPetugas();
    $this->actingAs($petugas)
        ->get(route('backup.index'))
        ->assertForbidden();
});

test('admin dapat mengakses halaman backup', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $admin = backupAdmin();
    $this->actingAs($admin)
        ->get(route('backup.index'))
        ->assertOk()
        ->assertSee('Backup & Restore');
});

/**
 * BackupService — create, list, validate, delete.
 */
test('backup service membuat arsip zip dengan metadata', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $service = app(BackupService::class);
    $backup = $service->create();

    expect($backup)->toHaveKeys(['path', 'filename', 'size', 'checksum', 'type', 'created_at'])
        ->and(file_exists($backup['path']))->toBeTrue()
        ->and($backup['size'])->toBeGreaterThan(0)
        ->and(strlen($backup['checksum']))->toBe(64)
        ->and($backup['type'])->toBe('full');

    // List should include it
    $list = $service->list();
    expect(collect($list)->contains('filename', $backup['filename']))->toBeTrue();

    // Validate
    $validation = $service->validate($backup['filename']);
    expect($validation['valid'])->toBeTrue()
        ->and($validation['has_sql'])->toBeTrue()
        ->and($validation['has_manifest'])->toBeTrue();

    // Delete
    $service->delete($backup['filename']);
    expect(file_exists($backup['path']))->toBeFalse();
});

test('backup service validate menolak file bukan zip', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    // Create a fake .zip file that is not a real zip
    file_put_contents($dir.DIRECTORY_SEPARATOR.'fake.zip', 'not a real zip');

    $service = app(BackupService::class);

    $this->expectException(RuntimeException::class);
    $service->validate('fake.zip');
});

test('backup service menolak nama file dengan path traversal', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $service = app(BackupService::class);

    $this->expectException(RuntimeException::class);
    $service->path('../etc/passwd');
});

/**
 * RestoreService — confirmation gate and validation.
 */
test('restore memerlukan konfirmasi', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $backup = app(BackupService::class)->create();

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/konfirmasi/i');

    app(RestoreService::class)->restore($backup['filename'], false);
});

test('restore menolak archive yang tidak valid', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    // Create an empty zip (no sql/manifest) — same path pattern as other tests
    $zipPath = $dir.DIRECTORY_SEPARATOR.'empty.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->close();

    // Restore must reject an empty/invalid archive (either because it is
    // structurally invalid or cannot be validated).
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/tidak (valid|ditemukan)/i');

    app(RestoreService::class)->restore('empty.zip', true);
});

test('restore berhasil dengan konfirmasi dan archive valid', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $backup = app(BackupService::class)->create();

    $result = app(RestoreService::class)->restore($backup['filename'], true);

    expect($result['success'])->toBeTrue()
        ->and($result['steps'])->toBeArray()
        ->and(count($result['steps']))->toBeGreaterThan(0);
});

/**
 * BackupController — HTTP flow.
 */
test('admin dapat membuat backup via HTTP', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $admin = backupAdmin();
    $this->actingAs($admin)
        ->post(route('backup.create'), ['type' => 'full'])
        ->assertRedirect(route('backup.index'))
        ->assertSessionHas('success');

    // Verify a backup file was created in the temp dir
    $files = glob($dir.DIRECTORY_SEPARATOR.'*.zip') ?: [];
    expect(count($files))->toBeGreaterThan(0);
});

test('non-admin tidak dapat membuat backup via HTTP', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $petugas = backupPetugas();
    $this->actingAs($petugas)
        ->post(route('backup.create'), ['type' => 'full'])
        ->assertForbidden();
});

test('admin dapat mengunduh backup', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $backup = app(BackupService::class)->create();

    $admin = backupAdmin();
    $this->actingAs($admin)
        ->get(route('backup.download', $backup['filename']))
        ->assertOk();
});

test('admin dapat menghapus backup via HTTP', function () {
    $dir = backupTempPath();
    mkdir($dir, 0755, true);

    $backup = app(BackupService::class)->create();

    $admin = backupAdmin();
    $this->actingAs($admin)
        ->delete(route('backup.destroy', $backup['filename']))
        ->assertRedirect(route('backup.index'))
        ->assertSessionHas('success');

    expect(file_exists($backup['path']))->toBeFalse();
});
