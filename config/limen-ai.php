<?php

return [

    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'example'),

    'providers' => [
        'default' => env('LIMEN_AI_PROVIDER', 'openai'),
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'anthropic' => [
            'driver' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
        ],
        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
        ],
        'openrouter' => [
            'driver' => 'openrouter',
            'api_key' => env('OPENROUTER_API_KEY'),
        ],
    ],

    'agents' => [
        'example' => [
            'name' => 'Example Agent',
            'description' => 'Demonstration agent for package development.',
            'model' => env('LIMEN_AI_EXAMPLE_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'openai'),
            'instructions' => 'You are a helpful assistant. Use tools when needed.',
            'skills' => [],
            'tools' => ['example_echo'],
            'knowledge' => [],
            'memory' => [
                'conversation' => true,
                'user' => false,
            ],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            'output' => [
                'format' => 'text',
            ],
            'limits' => [
                'max_tool_calls' => 10,
                'max_steps' => 20,
                'timeout' => 60,
            ],
            'version' => '1.0.0',
        ],
    ],

    'tools' => [
        'example_echo' => [
            'name' => 'Example Echo',
            'description' => 'Echoes input back for testing.',
            'class' => null, // Set in host app: App\LimenAi\Tools\ExampleEchoTool::class
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => [],
            ],
            'confirmation' => false,
            'timeout' => 5,
            'version' => '1.0.0',
        ],
    ],

    'skills' => [],

    'workflows' => [],

    'knowledge' => [
        'driver' => env('LIMEN_AI_KNOWLEDGE_DRIVER', 'null'),
        'collections' => [],
    ],

    'memory' => [
        'driver' => env('LIMEN_AI_MEMORY_DRIVER', 'database'),
    ],

    'responses' => [
        'unauthenticated' => 'limen-ai::responses.unauthenticated',
        'unauthorized' => 'limen-ai::responses.unauthorized',
        'tool_unavailable' => 'limen-ai::responses.tool_unavailable',
        'session_expired' => 'limen-ai::responses.session_expired',
        'approval_required' => 'limen-ai::responses.approval_required',
        'error' => 'limen-ai::responses.error',
    ],

    'limits' => [
        'requests_per_minute' => 60,
        'max_tool_calls' => 10,
        'max_steps' => 20,
        'max_execution_time' => 120,
        'max_tokens' => 8000,
        'max_attachment_size_kb' => 10240,
        'max_attachments' => 5,
    ],

    'broadcasting' => [
        'driver' => env('LIMEN_AI_BROADCAST_DRIVER', 'pusher'),
        'channel_prefix' => 'limen-ai.conversation',
    ],

    'queue' => [
        'connection' => env('LIMEN_AI_QUEUE_CONNECTION'),
        'name' => env('LIMEN_AI_QUEUE', 'default'),
    ],

    'observability' => [
        'audit_enabled' => true,
        'usage_tracking_enabled' => true,
    ],

    'security' => [
        'ssrf' => [
            'block_private_ips' => true,
            'allowed_domains' => [],
            'blocked_domains' => [],
        ],
        'redaction' => [
            'keys' => ['password', 'token', 'secret', 'api_key'],
        ],
    ],

    'ui' => [
        'enabled' => true,
        'theme' => [
            'primary' => '#4F46E5',
            'background' => '#FFFFFF',
            'text' => '#111827',
            'radius' => '12px',
            'position' => 'bottom-right',
            'direction' => 'ltr',
            'mode' => 'light',
            'title' => 'Limen AI Assistant',
            'welcome_message' => 'How can I help you today?',
        ],
    ],

    'paths' => [
        'agents' => app_path('LimenAi/Agents'),
        'tools' => app_path('LimenAi/Tools'),
        'skills' => app_path('LimenAi/Skills'),
        'workflows' => app_path('LimenAi/Workflows'),
    ],

];
