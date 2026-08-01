<?php

use App\Services\RAG\PromptBuilderService;

beforeEach(function () {
    $this->builder = new PromptBuilderService();
});

// ── PromptBuilderService ─────────────────────────────────────────────────────

it('includes a system message as the first element', function () {
    $messages = $this->builder->buildMessages([], [], 'What is Smart Adama?', false);

    expect($messages[0]['role'])->toBe('system');
});

it('appends the user query as the last message', function () {
    $query    = 'What does Smart Adama say about leadership?';
    $messages = $this->builder->buildMessages([], [], $query, false);

    $last = end($messages);
    expect($last['role'])->toBe('user')
        ->and($last['content'])->toBe($query);
});

it('uses the no-context system prompt when grounded=false', function () {
    $messages = $this->builder->buildMessages([], [], 'Some query', false);

    expect($messages[0]['content'])->toContain('No content has been ingested');
});

it('uses the full system prompt with context when grounded=true', function () {
    $chunks = [[
        'chunk_text'    => 'Smart Adama is a visionary framework.',
        'chapter_title' => 'Chapter 1',
        'section_title' => 'Introduction',
    ]];

    $messages = $this->builder->buildMessages([], $chunks, 'Tell me about Smart Adama', true);

    expect($messages[0]['content'])
        ->toContain('CONTEXT PASSAGES')
        ->toContain('Smart Adama is a visionary framework.')
        ->toContain('Chapter 1 — Introduction');
});

it('injects conversation history between system and current user message', function () {
    $history = [
        ['role' => 'user',      'content' => 'Hello'],
        ['role' => 'assistant', 'content' => 'Hi there!'],
    ];

    $messages = $this->builder->buildMessages($history, [], 'Follow-up question', false);

    // system, user(Hello), assistant(Hi), user(Follow-up)
    expect(count($messages))->toBe(4)
        ->and($messages[1]['role'])->toBe('user')
        ->and($messages[2]['role'])->toBe('assistant');
});

it('limits history to the most recent 20 messages', function () {
    // Build 30 messages alternating user/assistant
    $history = [];
    for ($i = 0; $i < 30; $i++) {
        $history[] = ['role' => $i % 2 === 0 ? 'user' : 'assistant', 'content' => "msg {$i}"];
    }

    $messages = $this->builder->buildMessages($history, [], 'New query', false);

    // system + up to 20 history + 1 user query = max 22
    expect(count($messages))->toBeLessThanOrEqual(22);
});

it('builds a numbered context block from multiple chunks', function () {
    $chunks = [
        ['chunk_text' => 'First chunk text.', 'chapter_title' => 'Ch1', 'section_title' => 'S1'],
        ['chunk_text' => 'Second chunk text.', 'chapter_title' => 'Ch1', 'section_title' => 'S2'],
    ];

    $messages = $this->builder->buildMessages([], $chunks, 'query', true);

    expect($messages[0]['content'])
        ->toContain('[1] Ch1 — S1')
        ->toContain('[2] Ch1 — S2');
});
