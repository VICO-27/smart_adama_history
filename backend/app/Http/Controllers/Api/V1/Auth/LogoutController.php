<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/logout
 * Requirement 1.5 — revoke current token immediately.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate this request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
