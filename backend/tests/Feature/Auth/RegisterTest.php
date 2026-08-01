<?php

use App\Models\User;

// ── POST /api/v1/auth/register ───────────────────────────────────────────────

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'     => 'Jane Citizen',
        'email'    => 'jane@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'user'  => ['id', 'name', 'email', 'role', 'created_at'],
            'token',
        ]);

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    expect($response->json('user.role'))->toBe('learner');
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name'     => 'Another Jane',
        'email'    => 'jane@example.com',
        'password' => 'secret123',
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');

    expect(User::where('email', 'jane@example.com')->count())->toBe(1);
});

it('rejects a password with no number', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'     => 'Jane Citizen',
        'email'    => 'jane@example.com',
        'password' => 'onlyletters',
    ])->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});

it('rejects a password shorter than 8 characters', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'     => 'Jane Citizen',
        'email'    => 'jane@example.com',
        'password' => 'abc1',
    ])->assertStatus(422);
});

it('rejects missing required fields', function () {
    $this->postJson('/api/v1/auth/register', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});
