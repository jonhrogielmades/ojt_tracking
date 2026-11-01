<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PDO;
use Tests\TestCase;

class AuditAndBackupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_failed_login_is_audited_without_plaintext_credentials(): void
    {
        $administrator = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $auditLog = AuditLog::query()->where('event', 'auth.login.failed')->latest('id')->firstOrFail();
        $serialized = json_encode($auditLog->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('wrong-password', $serialized);
        $this->assertStringNotContainsString('admin@example.com', $serialized);
        $this->assertArrayHasKey('identifier_fingerprint', $auditLog->metadata);
        $this->assertNull($auditLog->actor_id);
        $this->assertSame((string) $administrator->id, $auditLog->subject_id);
    }

    public function test_auditor_can_view_audit_trail_but_coordinator_cannot(): void
    {
        AuditLog::factory()->create(['event' => 'student.updated']);
        $auditor = User::factory()->auditor()->create();
        $coordinator = User::factory()->ojtCoordinator()->create();

        $this->actingAs($auditor)->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('student.updated');
        $this->actingAs($coordinator)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_super_administrator_can_verify_sqlite_backup_integrity(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->superAdmin()->create();
        $path = 'backups/database/test.sqlite';
        $absolutePath = Storage::disk('local')->path($path);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdo = new PDO('sqlite:'.$absolutePath);
        $pdo->exec('CREATE TABLE health_check (id INTEGER PRIMARY KEY)');
        $pdo = null;
        $backup = DatabaseBackup::factory()->for($administrator, 'creator')->create([
            'path' => $path,
            'size_bytes' => filesize($absolutePath),
            'checksum' => hash_file('sha256', $absolutePath),
            'verified_at' => null,
        ]);

        $response = $this->actingAs($administrator)->post(route('admin.backups.verify', $backup));

        $response->assertRedirect();
        $this->assertNotNull($backup->fresh()->verified_at);
        $this->assertSame('completed', $backup->fresh()->status);
    }

    public function test_corrupt_sqlite_backup_is_marked_invalid_without_server_error(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->superAdmin()->create();
        $path = 'backups/database/corrupt.sqlite';
        $contents = 'This is not a SQLite database.';
        Storage::disk('local')->put($path, $contents);
        $backup = DatabaseBackup::factory()->for($administrator, 'creator')->create([
            'path' => $path,
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($administrator)->post(route('admin.backups.verify', $backup));

        $response->assertRedirect()->assertSessionHasErrors('backup');
        $backup->refresh();
        $this->assertSame('invalid', $backup->status);
        $this->assertNull($backup->verified_at);
        $this->assertSame(
            'The backup file could not be read or failed database integrity verification.',
            $backup->failure_reason,
        );
    }

    public function test_sqlite_backup_creates_private_file_and_metadata(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->superAdmin()->create();
        $sourcePath = tempnam(storage_path('framework/testing'), 'backup-source-');
        $this->assertIsString($sourcePath);
        $pdo = new PDO('sqlite:'.$sourcePath);
        $pdo->exec('CREATE TABLE source_check (id INTEGER PRIMARY KEY)');
        $pdo = null;
        config()->set('database.connections.sqlite.database', $sourcePath);

        try {
            $backup = app(DatabaseBackupService::class)->create($administrator);
        } finally {
            unlink($sourcePath);
        }

        $this->assertModelExists($backup);
        $this->assertSame('sqlite', $backup->database_driver);
        $this->assertSame($administrator->id, $backup->created_by);
        Storage::disk('local')->assertExists($backup->path);
        $this->assertSame(
            $backup->checksum,
            hash('sha256', Storage::disk('local')->get($backup->path)),
        );
    }

    public function test_non_super_administrator_cannot_manage_backups(): void
    {
        $coordinator = User::factory()->ojtCoordinator()->create();

        $this->actingAs($coordinator)->get(route('admin.backups.index'))->assertForbidden();
    }

    public function test_disabled_backups_hide_creation_and_reject_direct_requests(): void
    {
        config()->set('ojt.backups.enabled', false);
        $administrator = User::factory()->superAdmin()->create();
        $this->mock(DatabaseBackupService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('create');
        });

        $this->actingAs($administrator)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Database backup creation is disabled')
            ->assertSee('Disabled')
            ->assertDontSee('Create database backup');

        $this->actingAs($administrator)
            ->post(route('admin.backups.store'))
            ->assertRedirect()
            ->assertSessionHasErrors('backup');

        $this->assertDatabaseCount('database_backups', 0);
    }
}
