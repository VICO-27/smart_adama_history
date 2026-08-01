<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback($provider)
    {
        // Temporarily removing the try/catch so we can see the exact Laravel error screen
        $socialUser = Socialite::driver($provider)->stateless()->user();
        
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? 'User',
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'password' => null,
            ]
        );

        if (!$user->provider) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Ensure this perfectly matches your running Vite port!
        // Change this:
        // $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        // To this explicitly:
        $frontendUrl = 'http://localhost:5173';
        return redirect()->to("{$frontendUrl}/auth/callback?token={$token}");
    }
}