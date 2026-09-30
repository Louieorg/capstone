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
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
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
    'recaptcha' => [
        'verify' => false,
    ],
    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 20),
        'connect_timeout' => (int) env('OLLAMA_CONNECT_TIMEOUT', 5),
        // Keep new enhancements disabled until input, throttling, output, and failure handling are hardened.
        'enhance_enabled' => env('OLLAMA_ENHANCE_ENABLED', false),

        // Cluster explanation ("What People Are Experiencing"). Reads an
        // evidence package produced by the DSS and explains it. It never
        // decides, scores, qualifies, clusters, or alters a DSS result.
        //
        // Every value below defaults to the shared OLLAMA_* setting, so the
        // behaviour is identical to the legacy flat keys until these are set
        // explicitly. num_predict -1 is Ollama's own unlimited default and
        // keep_alive null omits the field entirely.
        'synthesis' => [
            'model' => env('OLLAMA_SYNTHESIS_MODEL', env('OLLAMA_MODEL', 'llama3.2')),
            'timeout' => (int) env('OLLAMA_SYNTHESIS_TIMEOUT', env('OLLAMA_TIMEOUT', 20)),
            'connect_timeout' => (int) env('OLLAMA_SYNTHESIS_CONNECT_TIMEOUT', env('OLLAMA_CONNECT_TIMEOUT', 5)),
            'num_predict' => (int) env('OLLAMA_SYNTHESIS_NUM_PREDICT', -1),
            'keep_alive' => env('OLLAMA_SYNTHESIS_KEEP_ALIVE'),
            'queue' => env('OLLAMA_SYNTHESIS_QUEUE', 'ai-synthesis'),
        ],
    ],
];
