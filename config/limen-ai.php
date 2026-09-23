<?php

return [

    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'example'),

    'providers' => [
        'default' => env('LIMEN_AI_PROVIDER', 'fake'),
        'drivers' => [
            'fake' => LimenAi\Providers\Fake\FakeLlmProvider::class,
            'openai' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
        ],
        'fake' => [
            'driver' => 'fake',
        ],
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => 60,
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

    'embeddings' => [
        'default' => env('LIMEN_AI_EMBEDDING_PROVIDER', 'fake'),
        'drivers' => [
            'fake' => LimenAi\Providers\Fake\FakeEmbeddingProvider::class,
        ],
        'providers' => [
            'fake' => [
                'driver' => 'fake',
            ],
        ],
    ],

    'agents' => [
        'example' => [
            'name' => 'Example Agent',
            'description' => 'Demonstration agent for package development.',
            'model' => env('LIMEN_AI_EXAMPLE_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
            'instructions' => 'You are a helpful assistant. Use tools when needed.',
            'skills' => ['general_assistance'],
            'tools' => ['example_echo'],
            'knowledge' => ['getting_started'],
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

    'runtime' => [
        'run_repository' => LimenAi\Runtime\InMemoryRunRepository::class,
        'checkpoint_store' => LimenAi\Runtime\ArrayCheckpointStore::class,
        'approval_repository' => LimenAi\Authorization\InMemoryApprovalRepository::class,
    ],

    'conversations' => [
        'repository' => LimenAi\Conversations\InMemoryConversationRepository::class,
        'message_repository' => LimenAi\Conversations\InMemoryMessageRepository::class,
        'history_limit' => 50,
        'summarizer' => LimenAi\Conversations\NullConversationSummarizer::class,
    ],

    'tool_pipeline' => [
        'idempotency' => [
            'driver' => env('LIMEN_AI_IDEMPOTENCY_DRIVER', 'cache'),
            'ttl' => 3600,
        ],
    ],

    'integrations' => [
        'connectors' => [
            'example_api' => [
                'base_url' => env('LIMEN_EXAMPLE_API_URL', 'https://api.example.com'),
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'authentication' => [
                    'type' => 'bearer',
                    'token' => env('LIMEN_EXAMPLE_API_TOKEN'),
                ],
            ],
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
        'example_http_status' => [
            'name' => 'Example HTTP Status',
            'description' => 'Fetch a resource status from the example API connector.',
            'integration' => [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/status/{{ input.resource }}',
                'query' => [
                    'include' => '{{ input.include }}',
                ],
            ],
            'input_schema' => [
                'resource' => ['type' => 'string', 'required' => true],
                'include' => ['type' => 'string', 'required' => false],
            ],
            'authorization' => [
                'abilities' => [],
            ],
            'confirmation' => false,
            'timeout' => 10,
            'version' => '1.0.0',
        ],
    ],

    'skills' => [
        'general_assistance' => [
            'name' => 'General Assistance',
            'instructions' => 'Provide helpful, concise responses.',
            'tools' => ['example_echo'],
            'knowledge' => [],
            'version' => '1.0.0',
        ],
    ],

    'workflows' => [
        'example_flow' => [
            'name' => 'Example Workflow',
            'version' => '1.0.0',
            'start' => 'greet',
            'steps' => [
                'greet' => [
                    'type' => 'agent',
                    'agent' => 'example',
                    'message' => 'Reply with a short greeting.',
                    'next' => 'check_mode',
                ],
                'check_mode' => [
                    'type' => 'branch',
                    'condition' => [
                        'field' => 'input.mode',
                        'operator' => 'equals',
                        'value' => 'tool',
                    ],
                    'then' => 'echo',
                    'else' => 'finish',
                ],
                'echo' => [
                    'type' => 'tool',
                    'tool' => 'example_echo',
                    'input' => ['message' => 'workflow tool branch'],
                    'next' => 'finish',
                ],
                'finish' => [
                    'type' => 'agent',
                    'agent' => 'example',
                    'message' => 'Summarize the workflow in one sentence.',
                ],
            ],
        ],
        'shipment_notify' => [
            'name' => 'Shipment Notification',
            'version' => '1.0.0',
            'start' => 'draft',
            'steps' => [
                'draft' => [
                    'type' => 'agent',
                    'agent' => 'example',
                    'message' => 'Draft a short customer delay notification.',
                    'next' => 'approve_send',
                ],
                'approve_send' => [
                    'type' => 'approval',
                    'message' => 'Approve sending the customer notification?',
                    'next' => 'send',
                ],
                'send' => [
                    'type' => 'tool',
                    'tool' => 'example_echo',
                    'input' => ['message' => 'notification sent'],
                ],
            ],
        ],
    ],

    'knowledge' => [
        'driver' => env('LIMEN_AI_KNOWLEDGE_DRIVER', 'config'),
        'vector_store' => LimenAi\Knowledge\InMemoryVectorStore::class,
        'limit' => 5,
        'collections' => [
            'getting_started' => [
                'name' => 'Getting Started',
                'description' => 'Introductory knowledge for the example agent.',
                'documents' => [
                    [
                        'content' => 'Limen AI is a Laravel-native agent framework. Tools require Laravel authorization before execution.',
                        'metadata' => ['source' => 'docs'],
                    ],
                    [
                        'content' => 'Use the example_echo tool to echo messages during development and testing.',
                        'metadata' => ['source' => 'docs'],
                    ],
                ],
            ],
        ],
    ],

    'repositories' => [
        'agent' => LimenAi\Agents\ConfigAgentRepository::class,
        'tool' => LimenAi\Tools\ConfigToolRepository::class,
        'skill' => LimenAi\Skills\ConfigSkillRepository::class,
        'workflow' => LimenAi\Workflows\ConfigWorkflowRepository::class,
        'knowledge' => LimenAi\Knowledge\ConfigKnowledgeRepository::class,
    ],

    'authorization' => [
        'enforce_context_user_match' => true,
        'guest' => [
            'validator' => LimenAi\Authorization\NullGuestSessionValidator::class,
            'cache_prefix' => 'limen-ai:guest:',
        ],
    ],

    'memory' => [
        'store' => LimenAi\Memory\InMemoryMemoryStore::class,
        'retriever' => LimenAi\Memory\DefaultMemoryRetriever::class,
        'limit' => 20,
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
