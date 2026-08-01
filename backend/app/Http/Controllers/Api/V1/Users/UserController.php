<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use App\Jobs\AnonymizeUserJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserProfileResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'user' => new UserProfileResource($user->fresh()),
        ]);
    }

    // --- NEW: Password Update Method ---
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
        ]);

        $user = $request->user();

        if ($user->avatar_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
            Storage::delete($oldPath);
        }

        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
        $url  = Storage::url($path);

        $user->update(['avatar_url' => $url]);

        return response()->json([
            'avatar_url' => $url,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();
        AnonymizeUserJob::dispatch($user->id)->delay(now()->addDays(30));

        return response()->json(null, 204);
    }
}