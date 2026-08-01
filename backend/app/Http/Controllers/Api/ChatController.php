<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Services\Chat\ChatOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Start a new chat session.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $session = ChatSession::create([
            'user_id' => $request->user()?->id, // Optional if using auth
            'title'   => $request->input('title', 'New Chat'),
        ]);

        return response()->json([
            'data' => $session,
        ], 201);
    }

    /**
     * Send a message within a chat session and get the RAG-backed AI response.
     */
    public function sendMessage(Request $request, ChatSession $session, ChatOrchestrator $orchestrator): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $result = $orchestrator->handleMessage($session, $request->input('message'));

        return response()->json([
            'data' => [
                'message'  => $result['message'],
                'sources'  => $result['chunks'],
                'grounded' => $result['grounded'],
            ],
        ]);
    }
}