<?php

/**
 * Black-box host defaults — public FAQ widget agent + sample knowledge collection.
 * Merged into config/limen-ai.php on package install.
 */
return [
    'agents' => [
        'app_assistant' => [
            'name' => 'App Assistant',
            'description' => 'Public support widget — answers from your knowledge base.',
            'model' => env('LIMEN_AI_APP_ASSISTANT_MODEL', env('LIMEN_AI_DEFAULT_MODEL', 'gpt-4.1-mini')),
            'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
            'instructions' => 'You are a helpful support assistant. Answer from the attached knowledge base. Be concise and accurate. If you do not know, say so — never invent policies or prices.',
            'persona' => [
                'display_name' => 'Support',
                'tone' => 'friendly',
                'language' => env('LIMEN_AI_APP_ASSISTANT_LANGUAGE', 'auto'),
                'response_style' => 'concise',
                'rules' => [
                    'Prefer knowledge base facts over general knowledge.',
                    'Support Arabic and English when the user switches language.',
                ],
                'forbidden' => [
                    'Inventing return policies, prices, or delivery dates',
                ],
            ],
            'skills' => [],
            'tools' => [],
            'knowledge' => ['product_help'],
            'memory' => [
                'conversation' => true,
                'user' => false,
                'limit' => 10,
                'allowed_keys' => ['preferred_language'],
                'max_value_length' => 128,
            ],
            'authorization' => [
                'required' => env('LIMEN_AI_REQUIRE_AUTH', false),
                'abilities' => [],
                'guest_allowed' => env('LIMEN_AI_UI_GUEST_ENABLED', true),
            ],
            'output' => [
                'format' => 'text',
                'max_response_chars' => 3000,
            ],
            'limits' => [
                'max_tool_calls' => 0,
                'max_steps' => 12,
                'timeout' => 60,
                'temperature' => 0.2,
                'max_tokens' => 1200,
                'max_history_messages' => 24,
            ],
            'version' => '1.0.0',
        ],
    ],
    'knowledge_collections' => [
        'product_help' => [
            'name' => 'Product Help',
            'description' => 'Public FAQ for the support widget.',
            'documents' => [
                [
                    'content' => 'We ship to Saudi Arabia, UAE, and Egypt. Standard delivery takes 3–5 business days.',
                    'metadata' => ['topic' => 'shipping'],
                ],
                [
                    'content' => 'Free shipping on orders over 500 SAR.',
                    'metadata' => ['topic' => 'shipping'],
                ],
                [
                    'content' => 'Returns are accepted within 14 days of delivery for unused items in original packaging.',
                    'metadata' => ['topic' => 'returns'],
                ],
            ],
        ],
    ],
];
