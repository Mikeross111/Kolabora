<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'cohere' => [
        'api_key' => env('COHERE_API_KEY'),
    ],

    // penyedia model bahasa: 'gemini' (bawaan, ada paket gratis) atau 'anthropic'
    'llm' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model_chat' => env('GEMINI_MODEL_CHAT', 'gemini-flash-lite-latest'),
        'model_fast' => env('GEMINI_MODEL_FAST', 'gemini-flash-lite-latest'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model_chat' => env('CLAUDE_MODEL_CHAT', 'claude-sonnet-5-5'),
        'model_fast' => env('CLAUDE_MODEL_FAST', 'claude-haiku-5-5'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supabase Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk Supabase database dan storage.
    | Digunakan oleh SupabaseStorageService dan SupabaseService.
    |
    */

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_key' => env('SUPABASE_SERVICE_KEY'),
        'project_id' => env('SUPABASE_PROJECT_ID'),
        'bucket' => env('SUPABASE_BUCKET', 'kb-storage'),
        'storage_url' => env('SUPABASE_URL') ? env('SUPABASE_URL') . '/storage/v1' : null,
    ],

];
