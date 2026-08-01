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
    
    'openai' => [
        'key' => env('OPENAI_API_KEY'),

        // Model phiên âm bài Nói. Để trong env vì tên/giá model OpenAI đổi rất
        // nhanh — đổi được bằng 1 dòng .env thay vì phải sửa code rồi deploy lại.
        'transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-mini-transcribe'),

        // Công tắc tắt nhanh chấm Nói bằng AI mà không cần gỡ code.
        // Tắt = bài mới rơi về luồng giáo viên chấm tay như trước.
        'speaking_ai_enabled' => env('SPEAKING_AI_ENABLED', true),
    ],

];
