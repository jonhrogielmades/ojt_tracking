<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_upload_stores_document_on_private_local_disk(): void
    {
        Storage::fake('local');
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
        Storage::disk('local')->assertExists($document->storage_path);
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
