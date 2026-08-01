<?php

namespace App\Providers;

use App\Services\AI\ClaudeLLMGateway;
use App\Services\AI\GroqLLMGateway;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\AI\Contracts\LLMGatewayInterface;
use App\Services\AI\OpenAIEmbeddingProvider;
use App\Services\AI\VoyageEmbeddingProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // LLM Gateway — resolved from config('ai.llm_provider')
        $this->app->bind(LLMGatewayInterface::class, function ($app) {
            $provider = config('ai.llm_provider', 'groq');
            
            return match ($provider) {
                'groq'   => $app->make(\App\Services\AI\GroqLLMGateway::class),
                'claude' => $app->make(\App\Services\AI\ClaudeLLMGateway::class),
                default  => $app->make(\App\Services\AI\GroqLLMGateway::class),
            };
        });

        // Embedding Provider — resolved from config('ai.embedding_provider')
        $this->app->bind(EmbeddingProviderInterface::class, function ($app) {
            return match (config('ai.embedding_provider')) {
                'voyage' => $app->make(VoyageEmbeddingProvider::class),
                'openai' => $app->make(OpenAIEmbeddingProvider::class),
                default  => $app->make(VoyageEmbeddingProvider::class),
            };
        });
    }

    public function boot(): void
    {
        // Point Laravel's password-reset notification at the Vue SPA reset page
        // instead of the non-existent Blade route (API-only app, Req 1.6).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return "{$frontend}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        // Sanctum must not authenticate soft-deleted users (Req 2.4).
        Sanctum::authenticateAccessTokensUsing(
            static function ($token, bool $isValid) {
                return $isValid && ! $token->tokenable->trashed();
            }
        );
    }
}