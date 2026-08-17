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

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly RetrievalService     $retrieval,
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

        // Auto-title session from first message
        if ($session->title === 'New Chat') {
            $session->update([
                'title'            => mb_substr($userContent, 0, 60),
                'last_activity_at' => now(),
            ]);
        } else {
            $session->update(['last_activity_at' => now()]);
        }

        // 2. Retrieve relevant chunks scoped strictly to the current chapter
        $topK      = (int) config('ai.rag.top_k', 5);
        $chapterId = $session->chapter_id ?? null;
        $retrieval = $this->retrieval->retrieve($userContent, $topK, null, $chapterId);
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
            // Nuke ALL levels of PHP output buffering to force immediate delivery
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            try {
                foreach ($this->llm->streamChat($messages) as $token) {
                    $responseContent .= $token;

                    // Stream token immediately to client
                    echo 'event: delta' . "\n";
                    echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                    
                    // Force the web server to push the packet instantly
                    @ob_flush();
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
                @ob_flush();
                flush();
                return;
            }

            $finalHtml = '';

            // Update assistant message content and persist sources after stream completes
            DB::transaction(function () use ($session, $responseContent, $chunks, $assistantMessage, &$citations, &$finalHtml) {
                try {
                    $markdownService = app(\App\Services\MarkdownService::class);
                    $htmlContent = $markdownService->toHtml($responseContent);
                    
                    if (empty(trim($htmlContent))) {
                        $htmlContent = nl2br(e($responseContent)); 
                    }
                } catch (\Throwable $e) {
                    $htmlContent = nl2br(e($responseContent)); 
                }
                
                $finalHtml = $htmlContent;

                $assistantMessage->update([
                    'content' => $finalHtml,
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

            // Done event with message ID, citations, AND the final compiled HTML
            echo 'event: done' . "\n";
            echo 'data: ' . json_encode([
                'message_id'   => $assistantMessage?->id,
                'grounded'     => ! empty($citations),
                'citations'    => $citations,
                'html_content' => $finalHtml,
            ]) . "\n\n";
            @ob_flush();
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-transform', // Bypass proxy buffering
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}