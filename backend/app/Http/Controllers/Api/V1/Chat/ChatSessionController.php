<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatSessionRequest;
use App\Http\Requests\Chat\UpdateChatSessionRequest;
use App\Http\Resources\ChatSessionResource;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat session CRUD (Req 7.1 – 7.5)
 */
class ChatSessionController extends Controller
{
    /**
     * GET /chat/sessions — paginated, most-recent-first (Req 7.2)
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->chatSessions()
            ->orderByDesc('last_activity_at')
            ->paginate(20);

        return response()->json([
            'sessions' => ChatSessionResource::collection($sessions->items()),
            'meta'     => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    /**
     * POST /chat/sessions — create a new session (Req 7.1)
     * Title auto-set from the first message; "New Chat" until then.
     */
    public function store(StoreChatSessionRequest $request): JsonResponse
    {
        $session = $request->user()->chatSessions()->create([
            'title'            => $request->title ?? 'New Chat',
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'session' => new ChatSessionResource($session),
        ], 201);
    }

    /**
     * GET /chat/sessions/{session} — full message history (Req 7.3)
     */
    public function show(Request $request, ChatSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $session->load(['messages.sources.chunk.section.chapter']);

        return response()->json([
            'session' => new ChatSessionResource($session),
        ]);
    }

    /**
     * PATCH /chat/sessions/{session} — rename (Req 7.5)
     */
    public function update(UpdateChatSessionRequest $request, ChatSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $session->update(['title' => $request->title]);

        return response()->json([
            'session' => new ChatSessionResource($session),
        ]);
    }

    /**
     * DELETE /chat/sessions/{session} — soft-delete (Req 7.4)
     */
    public function destroy(Request $request, ChatSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $session->delete();

        return response()->json(null, 204);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeSession(Request $request, ChatSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'You do not have access to this chat session.');
        }
    }
}
