<?php

$dbPersistence = filter_var(env('LIMEN_AI_DB_PERSISTENCE', false), FILTER_VALIDATE_BOOLEAN);

return [

    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'example'),

    'providers' => [
        'default' => env('LIMEN_AI_PROVIDER', 'fake'),
        'failover_chain' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('LIMEN_AI_FAILOVER', '')),
        ))),
        'drivers' => [
            'fake' => LimenAi\Providers\Fake\FakeLlmProvider::class,
            'openai' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
            'openrouter' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
            'anthropic' => LimenAi\Providers\Anthropic\AnthropicProvider::class,
            'gemini' => LimenAi\Providers\Gemini\GeminiProvider::class,
            'bedrock' => LimenAi\Providers\Bedrock\BedrockProvider::class,
            'groq' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
            'xai' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
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
            'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
            'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
            'timeout' => 60,
        ],
        'gemini' => [
            'driver' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'image_model' => env('GEMINI_IMAGE_MODEL', 'imagen-3.0-generate-002'),
            'search_model' => env('GEMINI_SEARCH_MODEL', 'gemini-2.0-flash'),
            'timeout' => 60,
        ],
        'openrouter' => [
            'driver' => 'openrouter',
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'headers' => array_filter([
                'HTTP-Referer' => env('OPENROUTER_HTTP_REFERER', env('APP_URL')),
                'X-Title' => env('OPENROUTER_APP_NAME', env('APP_NAME')),
            ]),
            'timeout' => 60,
        ],
        'cohere' => [
            'driver' => 'openai',
            'api_key' => env('COHERE_API_KEY'),
            'base_url' => env('COHERE_BASE_URL', 'https://api.cohere.com/v1'),
            'rerank_model' => env('COHERE_RERANK_MODEL', 'rerank-v3.5'),
        ],
        'jina' => [
            'driver' => 'openai',
            'api_key' => env('JINA_API_KEY'),
            'base_url' => env('JINA_BASE_URL', 'https://api.jina.ai/v1'),
            'rerank_model' => env('JINA_RERANK_MODEL', 'jina-reranker-v2-base-multilingual'),
        ],
        'bedrock' => [
            'driver' => 'bedrock',
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'model' => env('BEDROCK_MODEL', 'anthropic.claude-3-5-haiku-20241022-v1:0'),
            'max_tokens' => (int) env('BEDROCK_MAX_TOKENS', 4096),
            'timeout' => 60,
        ],
        'elevenlabs' => [
            'api_key' => env('ELEVENLABS_API_KEY'),
            'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
            'voice_id' => env('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
            'model' => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
            'stt_model' => env('ELEVENLABS_STT_MODEL', 'scribe_v1'),
        ],
        'groq' => [
            'driver' => 'groq',
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'timeout' => 60,
        ],
        'xai' => [
            'driver' => 'xai',
            'api_key' => env('XAI_API_KEY'),
            'base_url' => env('XAI_BASE_URL', 'https://api.x.ai/v1'),
            'timeout' => 60,
        ],
        'voyage' => [
            'api_key' => env('VOYAGE_API_KEY'),
            'rerank_model' => env('VOYAGE_RERANK_MODEL', 'rerank-2'),
        ],
    ],

    'embeddings' => [
        'default' => env('LIMEN_AI_EMBEDDING_PROVIDER', 'fake'),
        'drivers' => [
            'fake' => LimenAi\Providers\Fake\FakeEmbeddingProvider::class,
            'openai' => LimenAi\Providers\OpenAi\OpenAiEmbeddingProvider::class,
        ],
        'providers' => [
            'fake' => [
                'driver' => 'fake',
            ],
            'openai' => [
                'driver' => 'openai',
                'api_key' => env('OPENAI_API_KEY'),
                'organization' => env('OPENAI_ORGANIZATION'),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
                'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
                'timeout' => 60,
            ],
        ],
    ],

    'agents' => [
        'example' => [
            'enabled' => env('LIMEN_AI_EXAMPLE_AGENT_ENABLED', true),
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
        'limen_3pl' => [
            'name' => 'Limen 3PL Assistant',
            'description' => 'Helps operators look up shipments and send approved customer updates.',
            'model' => env('LIMEN_AI_LIMEN_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
            'instructions' => 'You are the Limen 3PL logistics assistant. Use get_shipment_status for lookups and send_customer_message only for approved outbound customer updates.',
            'skills' => ['logistics_support'],
            'tools' => ['get_shipment_status', 'send_customer_message'],
            'knowledge' => ['limen_3pl_ops'],
            'memory' => [
                'conversation' => true,
                'user' => true,
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
                'max_tool_calls' => 8,
                'max_steps' => 16,
                'timeout' => 90,
            ],
            'version' => '1.0.0',
        ],
    ],

    'persistence' => [
        'database' => $dbPersistence,
        'connection' => env('LIMEN_AI_DB_CONNECTION'),
    ],

    'runtime' => [
        'run_repository' => $dbPersistence
            ? LimenAi\Runtime\DatabaseRunRepository::class
            : LimenAi\Runtime\InMemoryRunRepository::class,
        'checkpoint_store' => $dbPersistence
            ? LimenAi\Runtime\DatabaseCheckpointStore::class
            : LimenAi\Runtime\ArrayCheckpointStore::class,
        'approval_repository' => $dbPersistence
            ? LimenAi\Authorization\DatabaseApprovalRepository::class
            : LimenAi\Authorization\InMemoryApprovalRepository::class,
    ],

    'conversations' => [
        'repository' => $dbPersistence
            ? LimenAi\Conversations\DatabaseConversationRepository::class
            : LimenAi\Conversations\InMemoryConversationRepository::class,
        'message_repository' => $dbPersistence
            ? LimenAi\Conversations\DatabaseMessageRepository::class
            : LimenAi\Conversations\InMemoryMessageRepository::class,
        'history_limit' => 50,
        'summarizer' => env('LIMEN_AI_LLM_SUMMARIZER', false)
            ? LimenAi\Conversations\LlmConversationSummarizer::class
            : LimenAi\Conversations\NullConversationSummarizer::class,
        'summarize_after' => (int) env('LIMEN_AI_SUMMARIZE_AFTER', 20),
        'summarizer_agent' => env('LIMEN_AI_SUMMARIZER_AGENT'),
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
            'class' => LimenAi\Tools\BuiltIn\ExampleEchoTool::class,
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
        'get_shipment_status' => [
            'name' => 'Get Shipment Status',
            'description' => 'Look up the current status of a shipment by ID.',
            'class' => null, // Host: App\LimenAi\Tools\GetShipmentStatus::class
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => [],
            ],
            'confirmation' => false,
            'timeout' => 10,
            'version' => '1.0.0',
        ],
        'send_customer_message' => [
            'name' => 'Send Customer Message',
            'description' => 'Send an outbound message to a shipment customer. Requires human approval.',
            'class' => null, // Host: App\LimenAi\Tools\SendCustomerMessage::class
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
                'message' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => [],
            ],
            'confirmation' => true,
            'timeout' => 15,
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
        'logistics_support' => [
            'name' => 'Logistics Support',
            'instructions' => 'Be concise, operational, and accurate. Reference shipment IDs explicitly.',
            'tools' => ['get_shipment_status', 'send_customer_message'],
            'knowledge' => ['limen_3pl_ops'],
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
            'name' => 'Shipment Delay Notification',
            'version' => '1.1.0',
            'start' => 'draft',
            'steps' => [
                'draft' => [
                    'type' => 'agent',
                    'agent' => 'limen_3pl',
                    'message' => 'Draft a short, professional customer delay notification.',
                    'next' => 'approve_send',
                ],
                'approve_send' => [
                    'type' => 'approval',
                    'message' => 'Approve sending the customer notification?',
                    'next' => 'send',
                ],
                'send' => [
                    'type' => 'tool',
                    'tool' => 'send_customer_message',
                    'input' => [
                        'shipment_id' => '{{ input.shipment_id }}',
                        'message' => '{{ step_outputs.draft.output }}',
                    ],
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
            'limen_3pl_ops' => [
                'name' => 'Limen 3PL Operations',
                'description' => 'Operational guidance for shipment support agents.',
                'documents' => [
                    [
                        'content' => 'Shipment statuses include: pending, in_transit, delayed, delivered, and cancelled.',
                        'metadata' => ['source' => 'ops-handbook'],
                    ],
                    [
                        'content' => 'Customer notifications must be approved by an authenticated operator before send_customer_message executes.',
                        'metadata' => ['source' => 'ops-handbook'],
                    ],
                ],
            ],
        ],
    ],

    'repositories' => [
        'agent' => LimenAi\Agents\CompositeAgentRepository::class,
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

    'performance' => [
        'cache_resolved_agents' => env('LIMEN_AI_CACHE_RESOLVED_AGENTS', true),
    ],

    'broadcasting' => [
        'enabled' => env('LIMEN_AI_BROADCASTING_ENABLED', true),
        'driver' => env('LIMEN_AI_BROADCAST_DRIVER', 'null'),
        'connection' => env('LIMEN_AI_BROADCAST_CONNECTION'),
        'channel_prefix' => env('LIMEN_AI_BROADCAST_CHANNEL_PREFIX', 'limen-ai.conversation'),
    ],

    'queue' => [
        'connection' => env('LIMEN_AI_QUEUE_CONNECTION'),
        'name' => env('LIMEN_AI_QUEUE', 'default'),
        'agent_runs' => env('LIMEN_AI_QUEUE_AGENT_RUNS', false),
    ],

    'version' => '2.5.0',

    'api' => [
        'enabled' => env('LIMEN_AI_API_ENABLED', true),
        'route_prefix' => env('LIMEN_AI_ROUTE_PREFIX', 'limen-ai'),
        'middleware' => ['web', 'auth'],
        'admin_middleware' => ['web', 'auth'],
        'admin_ability' => env('LIMEN_AI_ADMIN_ABILITY', 'manageLimenAiAgents'),
    ],

    'streaming' => [
        'enabled' => env('LIMEN_AI_STREAMING_ENABLED', true),
        'allow_with_tools' => env('LIMEN_AI_STREAMING_WITH_TOOLS', false),
    ],

    'observability' => [
        'audit_enabled' => env('LIMEN_AI_AUDIT_ENABLED', true),
        'usage_tracking_enabled' => env('LIMEN_AI_USAGE_TRACKING_ENABLED', true),
        'usage_persist_database' => env('LIMEN_AI_USAGE_PERSIST_DB', true),
        'trace_enabled' => env('LIMEN_AI_TRACE_ENABLED', true),
    ],

    'webhooks' => [
        'enabled' => env('LIMEN_AI_WEBHOOKS_ENABLED', false),
        'urls' => array_filter(explode(',', (string) env('LIMEN_AI_WEBHOOK_URLS', ''))),
        'events' => ['AgentCompleted', 'AgentFailed'],
        'timeout' => (int) env('LIMEN_AI_WEBHOOK_TIMEOUT', 5),
    ],

    'security' => [
        'ssrf' => [
            'block_private_ips' => true,
            'resolve_dns' => true,
            'allow_redirects' => false,
            'max_redirects' => 0,
            'allowed_domains' => [],
            'blocked_domains' => [],
        ],
        'injection' => [
            'enabled' => true,
            'wrap_untrusted' => true,
            'patterns' => [
                '/ignore\s+(all\s+)?(previous|prior)\s+instructions/i',
                '/^system\s*:/im',
                '/^assistant\s*:/im',
                '/\[INST\]/i',
            ],
        ],
        'redaction' => [
            'keys' => ['password', 'token', 'secret', 'api_key'],
        ],
    ],

    'ui' => [
        'enabled' => env('LIMEN_AI_UI_ENABLED', true),
        'route_prefix' => env('LIMEN_AI_ROUTE_PREFIX', 'limen-ai'),
        'middleware' => ['web', 'auth'],
        'palettes' => [
            'light' => [
                'primary' => '#4F46E5',
                'background' => '#FFFFFF',
                'text' => '#111827',
                'surface' => '#F9FAFB',
                'border' => 'rgba(17, 24, 39, 0.08)',
                'muted' => 'rgba(17, 24, 39, 0.65)',
            ],
            'dark' => [
                'primary' => '#818CF8',
                'background' => '#0F172A',
                'text' => '#F8FAFC',
                'surface' => '#1E293B',
                'border' => 'rgba(148, 163, 184, 0.18)',
                'muted' => 'rgba(148, 163, 184, 0.85)',
            ],
        ],
        'presets' => [
            'default' => [
                'radius' => '12px',
                'position' => 'bottom-right',
                'direction' => 'ltr',
                'font_family' => 'ui-sans-serif, system-ui, sans-serif',
                'title' => 'Limen AI Assistant',
                'welcome_message' => 'How can I help you today?',
            ],
            'arabic' => [
                'direction' => 'rtl',
                'font_family' => '"Noto Sans Arabic", "Segoe UI", Tahoma, sans-serif',
                'title' => 'مساعد Limen AI',
                'welcome_message' => 'كيف يمكنني مساعدتك اليوم؟',
            ],
        ],
        'theme' => [
            'preset' => env('LIMEN_AI_THEME_PRESET', 'default'),
            'mode' => env('LIMEN_AI_THEME_MODE', 'light'),
            'allow_mode_toggle' => env('LIMEN_AI_THEME_TOGGLE', false),
            'overrides' => [],
            'radius' => '12px',
            'position' => 'bottom-right',
            'direction' => 'ltr',
            'title' => 'Limen AI Assistant',
            'welcome_message' => 'How can I help you today?',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Class-based agents (Laravel AI SDK style)
    |--------------------------------------------------------------------------
    |
    | Map agent keys to PHP classes implementing LimenAi\Ai\Contracts\Agent.
    | Config-based agents under "agents" remain fully supported (v1).
    |
    */
    'agent_classes' => [
        // 'support' => App\Ai\Agents\SupportAgent::class,
    ],

    'agent_middleware' => [
        // LimenAi\Runtime\Middleware\ExampleAgentMiddleware::class,
    ],

    'deferred_tool_loading' => env('LIMEN_AI_DEFERRED_TOOLS', false),

    'protocols' => [
        'vercel_chat' => env('LIMEN_AI_VERCEL_CHAT', false),
        'ag_ui' => env('LIMEN_AI_AG_UI', false),
    ],

    'mcp' => [
        'enabled' => env('LIMEN_AI_MCP_ENABLED', false),
        'servers' => [
            // 'docs' => [
            //     'transport' => 'http',
            //     'url' => 'https://mcp.example.com/rpc',
            // ],
            // 'local' => [
            //     'transport' => 'stdio',
            //     'command' => ['npx', '-y', '@modelcontextprotocol/server-filesystem', storage_path('app')],
            // ],
        ],
        'register_tools' => env('LIMEN_AI_MCP_REGISTER_TOOLS', true),
    ],

    'rerank' => [
        'default' => env('LIMEN_AI_RERANK_PROVIDER', 'fake'),
    ],

    'provider_tools' => [
        'enabled' => env('LIMEN_AI_PROVIDER_TOOLS', false),
        'web_search' => [
            'enabled' => env('LIMEN_AI_TOOL_WEB_SEARCH', true),
            'driver' => env('LIMEN_AI_WEB_SEARCH_DRIVER', 'duckduckgo'),
            'endpoint' => env('LIMEN_AI_WEB_SEARCH_ENDPOINT', 'https://api.duckduckgo.com/'),
        ],
        'web_fetch' => [
            'enabled' => env('LIMEN_AI_TOOL_WEB_FETCH', true),
            'max_chars' => (int) env('LIMEN_AI_WEB_FETCH_MAX_CHARS', 12000),
        ],
        'file_search' => [
            'enabled' => env('LIMEN_AI_TOOL_FILE_SEARCH', true),
        ],
    ],

    'agent_storage' => [
        'definition_sources' => ['config', 'database'],
        'database' => [
            'enabled' => env('LIMEN_AI_DB_AGENTS', false),
            'connection' => env('LIMEN_AI_DB_CONNECTION'),
            'table' => 'limen_ai_agent_definitions',
            'cache' => env('LIMEN_AI_DB_AGENTS_CACHE', true),
            'cache_ttl' => (int) env('LIMEN_AI_DB_AGENTS_CACHE_TTL', 300),
        ],
    ],

    'paths' => [
        'agents' => app_path('LimenAi/Agents'),
        'agent_classes' => app_path('Ai/Agents'),
        'agent_namespace' => 'App\\Ai\\Agents',
        'agent_config' => config_path('limen-ai/agents'),
        'tools' => app_path('Ai/Tools'),
        'tool_namespace' => 'App\\Ai\\Tools',
        'skills' => app_path('LimenAi/Skills'),
        'workflows' => app_path('LimenAi/Workflows'),
    ],

];
