<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\AI\GroqLLMGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GlobalChatController - Platform-specific AI assistant (Req 1.1, 1.4).
 * 
 * This controller provides platform assistance (NOT book RAG).
 * It answers questions about:
 * - Platform functionality (features, navigation, settings)
 * - General knowledge (programming, smart cities)
 * - About the developers and project
 * - Topics not covered by the book RAG system
 * 
 * It does NOT use RAG or retrieve book chunks.
 */
class GlobalChatController extends Controller
{
    public function __construct(
        private readonly LLMGatewayInterface $llm,
    ) {
    }

    public function handle(Request $request): array
    {
        $request->validate([
            'message' => 'required|string',
            'route' => 'nullable|string',
            'history' => 'nullable|array'
        ]);

        $userMessage = $request->input('message');
        $currentRoute = $request->input('route', 'Unknown');
        $history = $request->input('history', []);

        // Build system prompt using platform knowledge
        $systemPrompt = $this->buildSystemPrompt($currentRoute);

        // Format messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history (limit to last 6 messages)
        $recentHistory = array_slice($history, -6);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $role = $msg['role'] === 'user' ? 'user' : 'assistant';
                $messages[] = ['role' => $role, 'content' => $msg['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            // Use the LLM gateway from config (supports groq/claude)
            $reply = $this->llm->chat($messages, [
                'max_tokens' => 500,
            ]);

            return [
                'reply' => trim($reply),
            ];
        } catch (\Throwable $e) {
            Log::error('GlobalChatController: LLM error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'reply' => 'I am having trouble connecting to my brain right now.',
            ];
        }
    }

    /**
     * Build the system prompt using platform knowledge.
     * This knowledge is about the Smart Adama platform, NOT the book content.
     */
    private function buildSystemPrompt(string $currentRoute): string
    {
        $developers = config('ai.platform.developers', [
            'Project Manager & Integration Lead: Ashenafi Deresa Feyisa',
            'Backend: Kidus Tilahun',
            'DevOps/QA: Nigusu Wario',
            'Frontend/UI: Getamesay Mekcha',
            'AI/RAG Lead: Abinet Tesfaye',
        ]);

        $developerList = implode(', ', $developers);

        return "You are the intelligent Global Assistant for the 'Smart Adama' platform. 
You are helpful, concise, and speak in a modern, professional tone.

KNOWLEDGE BASE:
- Platform Context: The user is currently viewing the path: '{$currentRoute}'. If they ask 'what page am I on' or 'what does this page do', use this route to infer your answer (e.g., /dashboard is their personal summary, /study is the book reader, /about is the team).
- About Smart Adama: It is an AI-powered learning platform and digital ecosystem designed to digitize the teachings of the Smart Adama Book.
- About the Developers: Built in a 30-day summer sprint by Group 2 engineering interns: {$developerList}.
- General Rules: You can answer general knowledge questions, programming questions, and questions about smart cities. If you don't know something, be honest. Keep responses clean and format with simple text, lists, or short paragraphs.

IMPORTANT: You are NOT the book expert. For questions about the Smart Adama book content, the user should use the Book Assistant (RAG system). Only answer platform-related questions.";
    }
}