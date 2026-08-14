<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatMessageFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat message feedback (👍/👎)
 * 
 * Features:
 * - Like/dislike a specific assistant message
 * - One feedback per user per message
 * - Idempotent operations
 * - Authorization: only the message owner can feedback
 */
class ChatMessageFeedbackController extends Controller
{
    /**
     * Store or update feedback for a chat message.
     * 
     * POST /api/v1/messages/{message}/feedback
     * 
     * Body: { "feedback": "like" | "dislike" }
     */
    public function store(Request $request, ChatMessage $message): JsonResponse
    {
        $request->validate([
            'feedback' => ['required', 'in:like,dislike'],
        ]);

        // Guard: Only assistant messages can be rated
        if ($message->role !== 'assistant') {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_MESSAGE_TYPE',
                    'message' => 'Feedback can only be given to assistant messages.',
                ],
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        // Create or update feedback (idempotent)
        $feedback = ChatMessageFeedback::updateOrCreate(
            [
                'chat_message_id' => $message->id,
                'user_id' => $user->id,
            ],
            [
                'feedback' => $request->input('feedback'),
            ]
        );

        return response()->json([
            'message' => $message->load('feedbacks'),
            'feedback' => $feedback,
        ], 201);
    }

    /**
     * Remove feedback for a chat message.
     * 
     * DELETE /api/v1/messages/{message}/feedback
     */
    public function destroy(Request $request, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $feedback = ChatMessageFeedback::where('chat_message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$feedback) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No feedback found for this message.',
                ],
            ], 404);
        }

        $feedback->delete();

        return response()->json([
            'message' => 'Feedback removed successfully.',
        ]);
    }

    /**
     * Get feedback status for a chat message.
     * 
     * GET /api/v1/messages/{message}/feedback
     */
    public function index(Request $request, ChatMessage $message): JsonResponse
    {
        $feedback = null;
        $user = $request->user();
        
        if ($user) {
            $feedback = ChatMessageFeedback::where('chat_message_id', $message->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return response()->json([
            'message_id' => $message->id,
            'feedback' => $feedback,
        ]);
    }
}
