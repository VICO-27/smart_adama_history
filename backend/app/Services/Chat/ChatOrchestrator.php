<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatSession;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\MarkdownService;
use App\Services\RAG\PromptBuilderService;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatOrchestrator
{
    public function __construct(
        private readonly RetrievalService     $retriever,
        private readonly PromptBuilderService $promptBuilder,
        private readonly LLMGatewayInterface  $llm,
        private readonly MarkdownService      $markdown,
    ) {
    }

    /**
     * Process a user message within a chat session and generate an AI response.
     */
    public function handleMessage(ChatSession $session, string $query): array
    {
        // Extract chapter ID if the session is tied to a specific chapter
        $chapterId = $session->chapter_id ?? null;

        // 1. Retrieve relevant RAG context chunks, scoped securely to the chapter/book
        $retrievalResult = $this->retriever->retrieve($query, 5, null, $chapterId);
        $chunks   = $retrievalResult['chunks'];
        $grounded = $retrievalResult['grounded'];

        // 2. Gather conversation history
        $history = $session->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => [
                'role'    => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // 3. Build structured messages payload using PromptBuilderService
        $messages = $this->promptBuilder->buildMessages($history, $chunks, $query, $grounded);

        // 4. Call LLM gateway (non-streaming for standard response or use streamChat for real-time)
        $aiResponseText = $this->llm->chat($messages);

        // 5. Convert markdown response to sanitized HTML
        $aiResponseHtml = $this->markdown->toHtml($aiResponseText);

        // 6. Persist user message, assistant response, and sources transactionally
        $assistantMessage = DB::transaction(function () use ($session, $query, $aiResponseHtml, $chunks, $grounded) {
            // Save user prompt
            $session->messages()->create([
                'role'    => 'user',
                'content' => $query,
            ]);

            // Save assistant reply with HTML content for rendering
            /** @var ChatMessage $assistantMsg */
            $assistantMsg = $session->messages()->create([
                'role'     => 'assistant',
                'content'  => $aiResponseHtml,
                'metadata' => [
                    'grounded'    => $grounded,
                    'chunk_count' => count($chunks),
                    'is_markdown' => true,
                ],
            ]);

            // Save source chunk citations if grounded
            if ($grounded && !empty($chunks)) {
                foreach ($chunks as $chunk) {
                    ChatMessageSource::create([
                        'chat_message_id' => $assistantMsg->id,
                        'content_chunk_id' => $chunk['id'],
                        'similarity_score' => $chunk['similarity'],
                    ]);
                }
            }

            return $assistantMsg;
        });

        return [
            'message'  => $assistantMessage,
            'chunks'   => $chunks,
            'grounded' => $grounded,
        ];
    }
}