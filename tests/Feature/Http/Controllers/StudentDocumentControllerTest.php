<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use Tests\TestCase;

class StudentDocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_upload_stores_document_on_configured_private_disk(): void
    {
        Storage::fake('private-documents');
        config()->set('ojt.documents.disk', 'private-documents');
        $student = User::factory()->create();
        $file = UploadedFile::fake()->create('evaluation.pdf', 32, 'application/pdf');

        $response = $this->actingAs($student)->post(route('documents.store'), [
            'title' => 'Final Evaluation',
            'category' => 'Evaluation',
            'document' => $file,
        ]);

        $response->assertRedirectToRoute('documents.index');
        $response->assertSessionHas('success');

        $document = StudentDocument::query()->whereBelongsTo($student)->sole();
        $this->assertSame('evaluation.pdf', $document->original_name);
        $this->assertStringStartsWith("ojt-documents/{$student->id}/", $document->storage_path);
        Storage::disk('private-documents')->assertExists($document->storage_path);
    }

    public function test_failed_private_storage_write_never_creates_document_metadata(): void
    {
        config()->set('ojt.documents.disk', 'failing-documents');
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')
            ->once()
            ->andThrow(UnableToWriteFile::atLocation('ojt-documents/test.pdf'));
        $filesystem = Mockery::mock(FilesystemFactory::class);
        $filesystem->shouldReceive('disk')
            ->once()
            ->with('failing-documents')
            ->andReturn($disk);
        $this->app->instance(FilesystemFactory::class, $filesystem);
        $student = User::factory()->create();

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($student)->post(route('documents.store'), [
                'title' => 'Final Evaluation',
                'category' => 'Evaluation',
                'document' => UploadedFile::fake()->create('evaluation.pdf', 32, 'application/pdf'),
            ]);
        } catch (UnableToWriteFile) {
            $this->assertDatabaseCount('student_documents', 0);

            return;
        }

        $this->fail('The storage failure was not propagated.');
    }

    public function test_downloading_another_students_document_returns_404(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $document = StudentDocument::factory()->for($otherStudent)->create([
            'storage_path' => "ojt-documents/{$otherStudent->id}/private.pdf",
        ]);
        Storage::disk('local')->put($document->storage_path, 'private document');

        $response = $this->actingAs($student)->get(route('documents.download', $document));

        $response->assertNotFound();
    }
}
