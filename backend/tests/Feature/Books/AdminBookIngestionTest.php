<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── GET /api/v1/admin/book-ingestion ─────────────────────────────────────────

it('returns book ingestion status with canonical chapters', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create(['title' => 'Smart Adama']);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/book-ingestion');

    $response->assertOk()
        ->assertJsonStructure([
            'book' => ['id', 'title'],
            'canonical_chapters',
            'chapters',
            'verification',
        ]);

    // Should auto-create all 11 canonical chapters
    expect(Chapter::where('book_id', $book->id)->count())->toBe(11);
    expect($response->json('chapters'))->toHaveCount(11);
});

it('returns empty state when no book exists', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/book-ingestion');

    $response->assertOk()
        ->assertJson([
            'book' => null,
            'chapters' => [],
            'verification' => null,
        ]);
});

// ── PUT /api/v1/admin/chapters/{chapter}/content ──────────────────────────────

it('admin can save chapter draft content', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $content = str_repeat('Test content for chapter 1. ', 50); // 1500+ chars

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/admin/chapters/{$chapter->id}/content", [
            'content' => $content,
        ]);

    $response->assertOk();
    // Laravel TrimStrings middleware will trim the content
    expect($chapter->fresh()->content)->toBe(trim($content));
});

it('returns 404 for non-existent chapter', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->putJson('/api/v1/admin/chapters/invalid-id/content', [
            'content' => 'test',
        ]);

    $response->assertStatus(404);
});

it('requires content field', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/admin/chapters/{$chapter->id}/content", []);

    $response->assertStatus(422);
});

// ── POST /api/v1/admin/chapters/{chapter}/validate ───────────────────────────

it('validates chapter content successfully', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1, 'title' => 'Introduction']);

    $content = "1.1 Section One\n" . str_repeat('Valid chapter content. ', 50);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/validate", [
            'content' => $content,
        ]);

    $response->assertOk()
        ->assertJson(['valid' => true])
        ->assertJsonPath('errors', []);
});

it('rejects empty content', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/validate", [
            'content' => '',
        ]);

    $response->assertOk()
        ->assertJson(['valid' => false])
        ->assertJsonPath('errors.0', 'Chapter content is empty.');
});

it('rejects content that is too short', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/validate", [
            'content' => 'Short',
        ]);

    $response->assertOk()
        ->assertJson(['valid' => false]);

    expect($response->json('errors'))->toContain('Chapter content is too short (minimum 100 characters).');
});

it('rejects content with front matter markers', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $content = "Contents\n" . str_repeat('Some content here. ', 50);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/validate", [
            'content' => $content,
        ]);

    $response->assertOk()
        ->assertJson(['valid' => false]);

    expect($response->json('errors'))->toContain('Content contains front matter marker: Contents');
});

it('rejects content with System Context', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $content = "System Context: Smart Adama Platform\n" . str_repeat('Some content. ', 50);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/validate", [
            'content' => $content,
        ]);

    $response->assertOk()
        ->assertJson(['valid' => false]);

    expect($response->json('errors'))->toContain('Content contains "System Context" which is not allowed in Book RAG.');
});

// ── POST /api/v1/admin/chapters/{chapter}/preview ────────────────────────────

it('previews chapter ingestion without embedding', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1]);

    $content = "1.1 Introduction\nThis is section one content.\n\n1.2 Background\nThis is section two content.";

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/preview", [
            'content' => $content,
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'preview' => [
                'sections',
                'estimated_chunks',
                'estimated_batches',
            ],
        ]);

    expect($response->json('preview.sections'))->toBeArray();
    expect($response->json('preview.estimated_chunks'))->toBeInt();
});

// ── POST /api/v1/admin/chapters/{chapter}/ingest ─────────────────────────────

it('rejects ingestion when chapter has no content', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    $chapter = Chapter::factory()->create(['book_id' => $book->id, 'order' => 1, 'content' => '']);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/chapters/{$chapter->id}/ingest");

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'NO_CONTENT');
});

// ── POST /api/v1/admin/books/{book}/verify ───────────────────────────────────

it('verifies book ingestion status', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();

    // Create some chapters
    Chapter::factory()->count(3)->create(['book_id' => $book->id]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/books/{$book->id}/verify");

    $response->assertOk()
        ->assertJsonStructure([
            'verification' => [
                'total_chapters',
                'canonical_chapters',
                'populated_chapters',
                'total_sections',
                'total_chunks',
                'ready_chunks',
                'pending_chunks',
                'failed_chunks',
                'null_embeddings',
                'invalid_chapters',
                'is_complete',
                'status',
            ],
        ]);
});

it('verification detects incomplete state with zero chunks', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();
    Chapter::factory()->count(11)->sequence(
        ...array_map(fn($i) => ['order' => $i, 'ingestion_status' => 'ready'], range(1, 11))
    )->create(['book_id' => $book->id]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/books/{$book->id}/verify");

    $response->assertOk();
    $verification = $response->json('verification');

    expect($verification['total_chunks'])->toBe(0);
    expect($verification['is_complete'])->toBe(false);
    expect($verification['status'])->toBe('incomplete');
});

it('verification detects invalid chapters', function () {
    $admin = User::factory()->admin()->create();
    $book = Book::factory()->create();

    // Create canonical chapters 1-11
    Chapter::factory()->count(11)->sequence(
        ...array_map(fn($i) => ['order' => $i], range(1, 11))
    )->create(['book_id' => $book->id]);

    // Add invalid chapter 0
    Chapter::factory()->create(['book_id' => $book->id, 'order' => 0]);

    $response = $this->actingAs($admin)
        ->postJson("/api/v1/admin/books/{$book->id}/verify");

    $response->assertOk();
    $verification = $response->json('verification');

    expect($verification['invalid_chapters'])->toBe(1);
    expect($verification['is_complete'])->toBe(false);
});

// ── Authorization tests ──────────────────────────────────────────────────────

it('non-admin cannot access book ingestion endpoints', function () {
    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->getJson('/api/v1/admin/book-ingestion')
        ->assertStatus(403);
});

it('unauthenticated users cannot access book ingestion endpoints', function () {
    $this->getJson('/api/v1/admin/book-ingestion')
        ->assertStatus(401);
});
