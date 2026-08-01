<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/v1/auth/password/forgot
 * POST /api/v1/auth/password/reset
 * Requirement 1.6 — no account enumeration; 60-minute token.
 */
class PasswordResetController extends Controller
{
    /**
     * Send a reset link.
     * Responds identically whether or not the email exists (Req 1.6).
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        // sendResetLink returns a status string but we never reveal whether
        // the email matched — always return the same success message.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If that email address is in our system, you will receive a password reset link shortly.',
        ]);
    }

    /**
     * Consume the reset token and set the new password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all existing tokens so any stolen sessions are invalidated
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['This password reset token is invalid or has expired.'],
            ]);
        }

        return response()->json([
            'message' => 'Password reset successfully. Please log in with your new password.',
        ]);
    }
}
