<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

// ── POST /api/v1/admin/books ─────────────────────────────────────────────────

it('admin can create a book', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/books', ['title' => 'Smart Adama'])
        ->assertStatus(201)
        ->assertJsonPath('book.title', 'Smart Adama')
        ->assertJsonPath('book.status', 'draft');

    $this->assertDatabaseHas('books', ['title' => 'Smart Adama']);
});

it('admin can upload a manuscript with the book', function () {
    Storage::fake('s3');
    $admin = User::factory()->admin()->create();
    $file  = UploadedFile::fake()->create('manuscript.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/admin/books', [
            'title'      => 'Smart Adama',
            'manuscript' => $file,
        ]);

    $response->assertStatus(201);
    $book = Book::first();
    expect($book->source_file_path)->not->toBeNull();
});

it('non-admin cannot create a book', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->postJson('/api/v1/admin/books', ['title' => 'Test'])
        ->assertStatus(403);
});

it('rejects book creation with missing title', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/books', [])
        ->assertStatus(422);
});

// ── GET /api/v1/admin/books/{book} ───────────────────────────────────────────

it('admin can view a book with chapters and ingestion status', function () {
    $admin   = User::factory()->admin()->create();
    $book    = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'ingestion_status' => 'ready']);

    $this->actingAs($admin)
        ->getJson("/api/v1/admin/books/{$book->id}")
        ->assertOk()
        ->assertJsonPath('book.id', $book->id)
        ->assertJsonStructure(['book' => ['id', 'title', 'status', 'chapters']]);
});

// ── POST /api/v1/admin/books/{book}/chapters ─────────────────────────────────

it('admin can create a chapter for a book', function () {
    $admin = User::factory()->admin()->create();
    $book  = Book::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/books/{$book->id}/chapters", [
            'title' => 'Chapter 1: The Foundation',
            'order' => 1,
        ])
        ->assertStatus(201)
        ->assertJsonPath('chapter.title', 'Chapter 1: The Foundation')
        ->assertJsonPath('chapter.ingestion_status', 'draft');
});

// ── PATCH /api/v1/admin/chapters/{chapter} ───────────────────────────────────

it('admin can update a chapter title', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create(['title' => 'Old Title']);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/chapters/{$chapter->id}", [
            'title' => 'New Title',
        ])
        ->assertOk()
        ->assertJsonPath('chapter.title', 'New Title');
});

it('editing an ingested chapter resets its ingestion_status to draft', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create(['ingestion_status' => 'ready']);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/chapters/{$chapter->id}", ['title' => 'Updated'])
        ->assertOk();

    expect($chapter->fresh()->ingestion_status)->toBe('draft');
});

// ── POST /api/v1/admin/chapters/{chapter}/publish ────────────────────────────

it('publish enqueues the ingestion job without blocking', function () {
    Queue::fake();

    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();
    Section::factory()->create(['chapter_id' => $chapter->id, 'raw_text' => 'Some content.']);

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/publish")
        ->assertOk()
        ->assertJsonPath('ingestion_status', 'queued');

    Queue::assertPushed(\App\Jobs\IngestChapterJob::class, fn ($job) =>
        $job->chapterId === $chapter->id
    );
});

it('publish fails when chapter has no sections with content', function () {
    Queue::fake();

    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/publish")
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'NO_CONTENT');

    Queue::assertNothingPushed();
});

// ── Admin section management ─────────────────────────────────────────────────

it('admin can create a section in a chapter', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/sections", [
            'title'    => 'Section 1.1',
            'raw_text' => 'This is the section content.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('section.title', 'Section 1.1');
});

it('updating section raw_text marks parent chapter ingestion_status as draft', function () {
    $admin   = User::factory()->admin()->create();
    $chapter = Chapter::factory()->create(['ingestion_status' => 'ready']);
    $section = Section::factory()->create(['chapter_id' => $chapter->id]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/sections/{$section->id}", [
            'raw_text' => 'Updated content.',
        ])
        ->assertOk();

    expect($chapter->fresh()->ingestion_status)->toBe('draft');
});
