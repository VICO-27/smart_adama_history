<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\User;

// ── GET /api/v1/books — learner view ─────────────────────────────────────────

it('returns published books for learners', function () {
    $learner = User::factory()->create();

    Book::factory()->published()->count(2)->create();
    Book::factory()->create(['status' => 'draft']); // should not appear

    $this->actingAs($learner)
        ->getJson('/api/v1/books')
        ->assertOk()
        ->assertJsonCount(2, 'books');
});

it('returns an empty books array when no content is ingested (Req 3.7)', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->getJson('/api/v1/books')
        ->assertOk()
        ->assertJsonCount(0, 'books');
});

it('requires authentication to list books', function () {
    $this->getJson('/api/v1/books')
        ->assertStatus(401);
});

// ── GET /api/v1/chapters/{chapter} — learner reading view ────────────────────

it('learner can view a chapter with its sections', function () {
    $learner = User::factory()->create();
    $chapter = Chapter::factory()->create();
    Section::factory()->count(3)->create(['chapter_id' => $chapter->id]);

    $this->actingAs($learner)
        ->getJson("/api/v1/chapters/{$chapter->id}")
        ->assertOk()
        ->assertJsonPath('chapter.id', $chapter->id)
        ->assertJsonCount(3, 'chapter.sections');
});

it('chapter response includes null progress when not started', function () {
    $learner = User::factory()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($learner)
        ->getJson("/api/v1/chapters/{$chapter->id}")
        ->assertOk()
        ->assertJsonPath('progress', null);
});

// ── POST /api/v1/chapters/{chapter}/read ─────────────────────────────────────

it('learner can mark a chapter as read', function () {
    $learner = User::factory()->create();
    $chapter = Chapter::factory()->create();

    $this->actingAs($learner)
        ->postJson("/api/v1/chapters/{$chapter->id}/read")
        ->assertOk()
        ->assertJsonPath('message', 'Chapter marked as read.');

    $this->assertDatabaseHas('user_progress', [
        'user_id'    => $learner->id,
        'chapter_id' => $chapter->id,
    ]);
});
