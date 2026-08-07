<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\ChatMessageSource;
use App\Models\ChatSession;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\RAG\PromptBuilderService;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * POST /chat/sessions/{session}/messages
 *
 * Full RAG + SSE streaming pipeline (Req 5.1–5.6, 6.1–6.2, 7.1):
 *  1. Persist user message
 *  2. Retrieve top-k chunks via pgvector
 *  3. Build context-grounded prompt
 *  4. Create empty assistant message in DB
 *  5. Stream tokens directly from LLM to client via SSE (time-to-first-token optimized)
 *  6. Update assistant message with full content after stream completes
 *  7. Persist citations/sources
 *  8. Send done event with message ID + citations
 */
class ChatMessageController extends Controller
{
    public function __construct(
        private readonly RetrievalService    $retrieval,
        private readonly PromptBuilderService $promptBuilder,
        private readonly LLMGatewayInterface  $llm,
    ) {
    }

    public function store(SendMessageRequest $request, ChatSession $session): StreamedResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $userContent = $request->content;

        // 1. Persist user message
        $userMessage = $session->messages()->create([
            'role'    => 'user',
            'content' => $userContent,
        ]);

        // Auto-title session from first message (Req 7.1)
        if ($session->title === 'New Chat') {
            $session->update([
                'title'            => mb_substr($userContent, 0, 60),
                'last_activity_at' => now(),
            ]);
        } else {
            $session->update(['last_activity_at' => now()]);
        }

        // 2. Retrieve relevant chunks
        $topK      = (int) config('ai.rag.top_k', 5);
        $retrieval = $this->retrieval->retrieve($userContent, $topK);
        $chunks    = $retrieval['chunks'];
        $grounded  = $retrieval['grounded'];

        // 3. Build conversation history
        $history = $session->messages()
            ->whereNot('id', $userMessage->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // 4. Build prompt messages
        $messages = $this->promptBuilder->buildMessages(
            $history,
            $chunks,
            $userContent,
            $grounded
        );

        // 5. Stream tokens directly to SSE as they arrive from the LLM.
        //    This enables time-to-first-token optimization.
        $assistantMessage = null;
        $citations        = [];
        $streamError      = null;
        $responseContent  = '';

        // Persist assistant message (empty initially, will be updated after)
        DB::transaction(function () use ($session, &$assistantMessage) {
            $assistantMessage = $session->messages()->create([
                'role'    => 'assistant',
                'content' => '',
            ]);
        });

        // Stream tokens + done event to client
        return new StreamedResponse(function () use (
            $messages, $chunks, $session, $assistantMessage, &$responseContent, &$citations, &$streamError
        ) {
            try {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
            } catch (\Throwable) {
            }

            try {
                foreach ($this->llm->streamChat($messages) as $token) {
                    $responseContent .= $token;

                    // Stream token immediately to client
                    echo 'event: delta' . "\n";
                    echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                    flush();
                }
            } catch (\Throwable $e) {
                Log::error('ChatMessageController: LLM stream error', [
                    'session_id' => $session->id,
                    'error'      => $e->getMessage(),
                ]);
                $streamError = $e->getMessage();

                echo 'event: error' . "\n";
                echo 'data: ' . json_encode([
                    'error' => [
                        'code'    => 'AI_PROVIDER_UNAVAILABLE',
                        'message' => 'The AI service is temporarily unavailable.',
                    ],
                ]) . "\n\n";
                flush();
                return;
            }

            // Update assistant message content and persist sources after stream completes
            DB::transaction(function () use ($session, $responseContent, $chunks, $assistantMessage, &$citations) {
                // Convert markdown to HTML before saving
                try {
                    $markdownService = app(\App\Services\MarkdownService::class);
                    $htmlContent = $markdownService->toHtml($responseContent);
                    
                    // Fallback to raw content if HTML conversion fails or returns empty
                    if (empty(trim($htmlContent))) {
                        Log::warning('ChatMessageController: Markdown conversion returned empty', [
                            'markdown_length' => strlen($responseContent),
                            'session_id' => $session->id,
                        ]);
                        $htmlContent = nl2br(e($responseContent)); // Fallback: escape and preserve newlines
                    }
                } catch (\Throwable $e) {
                    Log::error('ChatMessageController: Markdown conversion failed', [
                        'error' => $e->getMessage(),
                        'session_id' => $session->id,
                    ]);
                    $htmlContent = nl2br(e($responseContent)); // Fallback: escape and preserve newlines
                }
                
                $assistantMessage->update([
                    'content' => $htmlContent,
                ]);

                foreach ($chunks as $chunk) {
                    ChatMessageSource::create([
                        'chat_message_id'  => $assistantMessage->id,
                        'content_chunk_id' => $chunk['id'],
                        'similarity_score' => $chunk['similarity'],
                    ]);
                }

                $citations = array_map(fn ($chunk) => [
                    'chunk_id'      => $chunk['id'],
                    'chapter_title' => $chunk['chapter_title'],
                    'section_title' => $chunk['section_title'],
                    'excerpt'       => mb_substr($chunk['chunk_text'], 0, 200),
                    'similarity'    => $chunk['similarity'],
                ], $chunks);
            });

            // Done event with message ID and citations
            echo 'event: done' . "\n";
            echo 'data: ' . json_encode([
                'message_id' => $assistantMessage?->id,
                'grounded'   => ! empty($citations),
                'citations'  => $citations,
            ]) . "\n\n";
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
