<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * POST /api/v1/auth/register
 * Requirement 1.1, 1.2
 */
class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'learner',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
