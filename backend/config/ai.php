<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LLM Provider
    |--------------------------------------------------------------------------
    | Supported: "groq", "claude"
    | Default: "groq" (llama-3.3-70b-versatile via Groq).
    | Swap by setting AI_LLM_PROVIDER in .env and ensuring the gateway is bound
    | in AppServiceProvider.
    */
    'llm_provider' => env('AI_LLM_PROVIDER', 'groq'),

    'groq' => [
        'api_key'    => env('GROQ_API_KEY'),
        'model'      => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url'   => 'https://api.groq.com/openai/v1/',
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 2048),
        'timeout'    => 60,
    ],

    'claude' => [
        'api_key'    => env('ANTHROPIC_API_KEY'),
        'model'      => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        'base_url'   => 'https://api.anthropic.com/v1',
        'max_tokens' => 2048,
        'timeout'    => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Provider
    |--------------------------------------------------------------------------
    | Supported: "voyage", "openai"
    | Default: "voyage" (voyage-3-lite, 1024 dimensions).
    |
    | CRITICAL: The dimension here MUST match the vector() column in the
    | content_chunks migration. voyage-3-lite outputs 1024-dimensional vectors.
    | Changing the model or dimension requires re-running migrations and
    | re-ingesting all book content.
    */
    'embedding_provider' => env('AI_EMBEDDING_PROVIDER', 'voyage'),

    'voyage' => [
        'api_key'    => env('VOYAGE_API_KEY'),
        'model'      => env('VOYAGE_MODEL', 'voyage-3-lite'),
        'base_url'   => 'https://api.voyageai.com',
        'dimension'  => (int) env('VOYAGE_EMBEDDING_DIMENSION', 1024),
        'batch_size' => 1,  // Free tier: 1 chunk per request to spread load
        'timeout'    => 30,
        // Conservative delay: 3 RPM = 1 request every 20s, we use 25s to be safe
        'request_delay_seconds' => (int) env('VOYAGE_REQUEST_DELAY_SECONDS', 25),
        // 429 retry: start at 60s, double on subsequent retries
        'rate_limit_retry_seconds' => (int) env('VOYAGE_RATE_LIMIT_RETRY_SECONDS', 60),
        'rate_limit_max_retries' => 5,  // More retries for free tier
    ],

    'openai' => [
        'api_key'   => env('OPENAI_API_KEY'),
        'model'     => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'base_url'  => 'https://api.openai.com/v1',
        'dimension' => (int) env('OPENAI_EMBEDDING_DIMENSION', 1536),
        'timeout'   => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG Settings
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'top_k'               => (int) env('RAG_TOP_K', 5),
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.35),
        'chunk_target_tokens'  => (int) env('RAG_CHUNK_TARGET_TOKENS', 700),
        'chunk_overlap_ratio'  => (float) env('RAG_CHUNK_OVERLAP_RATIO', 0.15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings (for external provider calls)
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'times'   => 3,
        'backoff' => [500, 1000, 2000], // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'chat_rate_limit' => [
        'max_attempts' => (int) env('CHAT_RATE_LIMIT_PER_5_MIN', 20),
        'decay_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ingestion Pipeline
    |--------------------------------------------------------------------------
    | Rate-limit pause between sections during embedding ingestion.
    | Set to 0 in test environments where you mock the embedding provider.
    */
    'ingestion_sleep_seconds' => (int) env('INGESTION_SLEEP_SECONDS', 2),

    /*
    |--------------------------------------------------------------------------
    | Platform Knowledge (Global Assistant)
    |--------------------------------------------------------------------------
    | Platform-specific knowledge for the Global Assistant (not book RAG).
    */
    'platform' => [
        'developers' => [
            'Project Manager & Integration Lead: Ashenafi Deresa Feyisa',
            'Backend: Kidus Tilahun',
            'DevOps/QA: Nigusu Wario',
            'Frontend/UI: Getamesay Mekcha',
            'AI/RAG Lead: Abinet Tesfaye',
        ],
    ],

];
