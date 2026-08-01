<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Renders all API exceptions into the standard JSON error envelope:
 *
 *  {
 *    "error": {
 *      "code": "SNAKE_CASE_CODE",
 *      "message": "Human readable message.",
 *      "details": { ... }   <- optional
 *    }
 *  }
 */
class Handler
{
    public static function renderApiException(Throwable $e, Request $request): JsonResponse
    {
        // Validation — 422
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code'    => 'VALIDATION_FAILED',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ],
            ], 422);
        }

        // Unauthenticated — 401
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code'    => 'UNAUTHENTICATED',
                    'message' => 'Authentication required. Please provide a valid Bearer token.',
                ],
            ], 401);
        }

        // Forbidden — 403
        if ($e instanceof AccessDeniedHttpException) {
            return response()->json([
                'error' => [
                    'code'    => 'FORBIDDEN',
                    'message' => 'You do not have permission to perform this action.',
                ],
            ], 403);
        }

        // Not found — 404
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'error' => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                ],
            ], 404);
        }

        // Model not found — also 404
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'error' => [
                    'code'    => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                ],
            ], 404);
        }

        // Rate limited — 429
        if ($e instanceof TooManyRequestsHttpException) {
            return response()->json([
                'error' => [
                    'code'    => 'TOO_MANY_REQUESTS',
                    'message' => 'Rate limit exceeded. Please slow down.',
                ],
            ], 429, ['Retry-After' => $e->getHeaders()['Retry-After'] ?? 60]);
        }

        // AI provider unavailable — 503 (never leak provider error internals)
        if ($e instanceof \App\Exceptions\AiProviderException) {
            return response()->json([
                'error' => [
                    'code'    => 'AI_PROVIDER_UNAVAILABLE',
                    'message' => 'The AI service is temporarily unavailable. Please try again shortly.',
                ],
            ], 503);
        }

        // Generic server error — 500 in debug, generic in production
        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return response()->json([
            'error' => [
                'code'    => 'SERVER_ERROR',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred. Please try again.',
            ],
        ], $status >= 400 ? $status : 500);
    }
}
