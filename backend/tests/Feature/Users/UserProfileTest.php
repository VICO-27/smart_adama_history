<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ── GET /api/v1/users/me ─────────────────────────────────────────────────────

it('returns the authenticated user profile with progress summary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure([
            'user' => [
                'id', 'name', 'email', 'role', 'created_at',
                'progress_summary' => [
                    'completed_chapters',
                    'total_chapters',
                    'completion_pct',
                    'average_quiz_score',
                ],
            ],
        ]);
});

it('returns 401 without auth', function () {
    $this->getJson('/api/v1/users/me')->assertStatus(401);
});

// ── PATCH /api/v1/users/me ───────────────────────────────────────────────────

it('updates the user name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->patchJson('/api/v1/users/me', ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('user.name', 'New Name');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
});

it('rejects invalid locale length', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/users/me', ['locale' => 'toolong'])
        ->assertStatus(422);
});

// ── POST /api/v1/users/me/avatar ─────────────────────────────────────────────

it('uploads a valid avatar image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(500);

    $this->actingAs($user)
        ->postJson('/api/v1/users/me/avatar', ['avatar' => $file])
        ->assertOk()
        ->assertJsonStructure(['avatar_url']);

    expect($user->fresh()->avatar_url)->not->toBeNull();
});

it('rejects avatar files over 2 MB', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('big.jpg')->size(3000); // 3 MB

    $this->actingAs($user)
        ->postJson('/api/v1/users/me/avatar', ['avatar' => $file])
        ->assertStatus(422);
});

it('rejects non-image files as avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->postJson('/api/v1/users/me/avatar', ['avatar' => $file])
        ->assertStatus(422);
});

// ── DELETE /api/v1/users/me ──────────────────────────────────────────────────

it('soft-deletes the account and revokes all tokens', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/users/me')
        ->assertStatus(204);

    // Account is soft-deleted
    $this->assertSoftDeleted('users', ['id' => $user->id]);

    // Token row is gone from the database — it was revoked
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id'   => $user->id,
        'tokenable_type' => get_class($user),
    ]);
});
