<?php

return [

    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'example'),

    'providers' => [
        'default' => env('LIMEN_AI_PROVIDER', 'fake'),
        'drivers' => [
            'fake' => LimenAi\Providers\Fake\FakeLlmProvider::class,
            'openai' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
            'openrouter' => LimenAi\Providers\OpenAi\OpenAiProvider::class,
            'anthropic' => LimenAi\Providers\Anthropic\AnthropicProvider::class,
            'gemini' => LimenAi\Providers\Gemini\GeminiProvider::class,
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

    'agent_defaults' => [
        'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
        'model' => env('LIMEN_AI_DEFAULT_MODEL', 'gpt-4.1-mini'),
        'persona' => [
            'tone' => env('LIMEN_AI_DEFAULT_TONE', 'professional'),
            'language' => env('LIMEN_AI_DEFAULT_LANGUAGE', 'auto'),
            'response_style' => 'concise',
            'gender' => env('LIMEN_AI_DEFAULT_GENDER', 'neutral'),
            'region' => env('LIMEN_AI_DEFAULT_REGION', 'international'),
            'formality' => env('LIMEN_AI_DEFAULT_FORMALITY', 'neutral'),
            'voice' => env('LIMEN_AI_DEFAULT_VOICE', ''),
        ],
        'output' => [
            'format' => 'text',
            'max_response_chars' => 4000,
        ],
        'limits' => [
            'max_tool_calls' => 10,
            'max_steps' => 20,
            'timeout' => 60,
            'temperature' => 0.2,
            'max_tokens' => 1200,
            'max_history_messages' => 30,
        ],
        'authorization' => [
            'required' => true,
            'abilities' => [],
            'guest_allowed' => false,
        ],
        'memory' => [
            'conversation' => true,
            'user' => false,
            'limit' => 10,
            'max_value_length' => 256,
        ],
    ],

    'agent_presets' => [
        'genders' => [
            'male' => [
                'label' => 'Male',
                'guidance' => 'Use a professional male assistant voice. In Arabic, use masculine agreement and phrasing.',
            ],
            'female' => [
                'label' => 'Female',
                'guidance' => 'Use a professional female assistant voice. In Arabic, use feminine agreement and phrasing.',
            ],
            'neutral' => [
                'label' => 'Neutral',
                'guidance' => 'Keep phrasing gender-neutral in all languages.',
            ],
        ],
        'regions' => [
            'international' => [
                'label' => 'International',
                'guidance' => 'Use clear modern Arabic or English without a strong local dialect unless the user prefers one.',
            ],
            'eg' => [
                'label' => 'Egypt',
                'guidance' => 'When speaking Arabic, prefer natural Egyptian Arabic vocabulary and phrasing.',
            ],
            'sa' => [
                'label' => 'Saudi Arabia',
                'guidance' => 'When speaking Arabic, prefer Saudi Arabic vocabulary and phrasing.',
            ],
            'ae' => [
                'label' => 'UAE',
                'guidance' => 'When speaking Arabic, prefer Gulf/UAE-friendly vocabulary and phrasing.',
            ],
            'jo' => [
                'label' => 'Jordan',
                'guidance' => 'When speaking Arabic, prefer Levantine/Jordanian phrasing.',
            ],
            'us' => [
                'label' => 'United States',
                'guidance' => 'Use American English spelling and phrasing.',
            ],
            'uk' => [
                'label' => 'United Kingdom',
                'guidance' => 'Use British English spelling and phrasing.',
            ],
        ],
        'formality' => [
            'casual' => [
                'label' => 'Casual',
                'guidance' => 'Friendly and conversational, but still respectful.',
            ],
            'neutral' => [
                'label' => 'Neutral',
                'guidance' => 'Balanced professional tone suitable for support and sales.',
            ],
            'formal' => [
                'label' => 'Formal',
                'guidance' => 'Formal, polished, and business-appropriate language.',
            ],
        ],
    ],

    'agents' => [
        'example' => [
            'name' => 'Example Agent',
            'description' => 'Demonstration agent for package development.',
            'model' => env('LIMEN_AI_EXAMPLE_MODEL', 'gpt-4.1-mini'),
            'provider' => 'fake',
            'instructions' => 'You are a helpful assistant. Use tools when needed.',
            'persona' => [
                'display_name' => 'Example Agent',
                'tone' => 'friendly',
                'language' => env('LIMEN_AI_EXAMPLE_LANGUAGE', 'auto'),
                'response_style' => 'concise',
                'gender' => env('LIMEN_AI_EXAMPLE_GENDER', 'neutral'),
                'region' => env('LIMEN_AI_EXAMPLE_REGION', 'international'),
                'formality' => env('LIMEN_AI_EXAMPLE_FORMALITY', 'casual'),
                'voice' => env('LIMEN_AI_EXAMPLE_VOICE', 'warm and helpful'),
                'ui' => [],
                'rules' => [
                    'Use tools only when they add value.',
                    'Never invent shipment, order, or account data.',
                    'Support Arabic and English. Switch language immediately when the user asks.',
                ],
                'forbidden' => [
                    'Legal advice',
                    'Medical advice',
                ],
            ],
            'skills' => ['general_assistance'],
            'tools' => ['example_echo'],
            'knowledge' => ['getting_started'],
            'memory' => [
                'conversation' => true,
                'user' => false,
                'limit' => 10,
                'allowed_keys' => ['preferred_language', 'timezone'],
                'max_value_length' => 256,
            ],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            'output' => [
                'format' => 'text',
                'max_response_chars' => 4000,
            ],
            'limits' => [
                'max_tool_calls' => 10,
                'max_steps' => 20,
                'timeout' => 60,
                'temperature' => 0.2,
                'max_tokens' => 1200,
                'max_history_messages' => 30,
            ],
            'version' => '1.0.0',
        ],
        ...require __DIR__.'/limen-ai-provider-agents.php',
        'limen_3pl' => [
            'name' => 'Limen 3PL Assistant',
            'description' => 'Helps operators look up shipments and send approved customer updates.',
            'model' => env('LIMEN_AI_LIMEN_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
            'instructions' => 'You are the Limen 3PL logistics assistant. Use get_shipment_status for lookups and send_customer_message only for approved outbound customer updates.',
            'persona' => [
                'display_name' => 'Limen 3PL Assistant',
                'tone' => 'professional',
                'language' => env('LIMEN_AI_LIMEN_LANGUAGE', 'auto'),
                'response_style' => 'concise',
                'gender' => env('LIMEN_AI_LIMEN_GENDER', 'female'),
                'region' => env('LIMEN_AI_LIMEN_REGION', 'eg'),
                'formality' => env('LIMEN_AI_LIMEN_FORMALITY', 'formal'),
                'voice' => env('LIMEN_AI_LIMEN_VOICE', 'clear operational support'),
                'ui' => [
                    'title' => 'Limen 3PL Support',
                    'subtitle' => 'Shipment lookups and approved customer updates',
                    'welcome_message' => 'How can I help with your shipment today?',
                ],
                'rules' => [
                    'Reference shipment IDs explicitly.',
                    'Escalate to a human when data is missing or ambiguous.',
                ],
                'forbidden' => [
                    'Promising delivery dates without tool confirmation',
                    'Sending customer messages without approval',
                ],
            ],
            'skills' => ['logistics_support'],
            'tools' => ['get_shipment_status', 'send_customer_message'],
            'knowledge' => ['limen_3pl_ops'],
            'memory' => [
                'conversation' => true,
                'user' => true,
                'limit' => 15,
                'allowed_keys' => ['preferred_language', 'timezone', 'warehouse_id'],
                'max_value_length' => 512,
            ],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            'output' => [
                'format' => 'text',
                'max_response_chars' => 3000,
            ],
            'limits' => [
                'max_tool_calls' => 8,
                'max_steps' => 16,
                'timeout' => 90,
                'temperature' => 0.1,
                'max_tokens' => 1500,
                'max_history_messages' => 24,
            ],
            'version' => '1.0.0',
        ],
    ],

    'persistence' => [
        'driver' => env('LIMEN_AI_PERSISTENCE_DRIVER', 'memory'),
    ],

    'runtime' => [
        'run_repository' => env('LIMEN_AI_RUN_REPOSITORY'),
        'checkpoint_store' => env('LIMEN_AI_CHECKPOINT_STORE'),
        'approval_repository' => env('LIMEN_AI_APPROVAL_REPOSITORY'),
    ],

    'conversations' => [
        'repository' => env('LIMEN_AI_CONVERSATION_REPOSITORY'),
        'message_repository' => env('LIMEN_AI_MESSAGE_REPOSITORY'),
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
        'agent' => LimenAi\Agents\ConfigAgentRepository::class,
        'tool' => LimenAi\Tools\ConfigToolRepository::class,
        'skill' => LimenAi\Skills\ConfigSkillRepository::class,
        'workflow' => LimenAi\Workflows\ConfigWorkflowRepository::class,
        'knowledge' => LimenAi\Knowledge\ConfigKnowledgeRepository::class,
    ],

    'authorization' => [
        'enforce_context_user_match' => true,
        'guest' => [
            'validator' => LimenAi\Authorization\CacheGuestSessionValidator::class,
            'cache_prefix' => 'limen-ai:guest:',
        ],
    ],

    'memory' => [
        'store' => LimenAi\Memory\InMemoryMemoryStore::class,
        'retriever' => LimenAi\Memory\DefaultMemoryRetriever::class,
        'limit' => 20,
        'strict' => [
            'enforce_allowlist' => env('LIMEN_AI_MEMORY_STRICT', true),
            'max_key_length' => 64,
            'max_value_length' => 512,
            'allowed_key_pattern' => '/^[a-z][a-z0-9_]*$/',
        ],
    ],

    'quality' => [
        'default_tone' => env('LIMEN_AI_DEFAULT_TONE', 'professional'),
        'default_language' => env('LIMEN_AI_DEFAULT_LANGUAGE', 'en'),
        'save_tokens' => env('LIMEN_AI_SAVE_TOKENS', true),
        'output_validator' => env('LIMEN_AI_OUTPUT_VALIDATOR'),
        'output_moderation_enabled' => env('LIMEN_AI_OUTPUT_MODERATION', false),
        'output_moderator' => env('LIMEN_AI_OUTPUT_MODERATOR'),
    ],

    'responses' => [
        'unauthenticated' => 'limen-ai::responses.unauthenticated',
        'unauthorized' => 'limen-ai::responses.unauthorized',
        'tool_unavailable' => 'limen-ai::responses.tool_unavailable',
        'session_expired' => 'limen-ai::responses.session_expired',
        'approval_required' => 'limen-ai::responses.approval_required',
        'error' => 'limen-ai::responses.error',
    ],

    'attachments' => [
        'enabled' => env('LIMEN_AI_ATTACHMENTS_ENABLED', true),
        'store' => LimenAi\Attachments\InMemoryAttachmentStore::class,
        'disk' => env('LIMEN_AI_ATTACHMENTS_DISK', 'local'),
        'path' => env('LIMEN_AI_ATTACHMENTS_PATH', 'limen-ai/attachments'),
        'max_size_kb' => (int) env('LIMEN_AI_ATTACHMENTS_MAX_SIZE_KB', 10240),
        'max_count' => (int) env('LIMEN_AI_ATTACHMENTS_MAX_COUNT', 5),
        'allowed_mime_types' => [
            'text/plain',
            'text/markdown',
            'text/csv',
            'application/json',
            'application/xml',
            'text/xml',
        ],
        'rag' => [
            'enabled' => env('LIMEN_AI_ATTACHMENT_RAG_ENABLED', true),
            'chunk_size' => 1000,
            'collection_prefix' => 'attachment',
        ],
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

    'observability' => [
        'audit_enabled' => env('LIMEN_AI_AUDIT_ENABLED', true),
        'usage_tracking_enabled' => env('LIMEN_AI_USAGE_TRACKING_ENABLED', true),
        'trace_enabled' => env('LIMEN_AI_TRACE_ENABLED', true),
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
        'output_moderation' => [
            'patterns' => [
                '/\b(?:password|token|secret|api[_-]?key)\s*[:=]\s*\S+/i',
                '/\bsk-[A-Za-z0-9]{10,}\b/',
            ],
        ],
    ],

    'ui' => [
        'enabled' => env('LIMEN_AI_UI_ENABLED', true),
        'route_prefix' => env('LIMEN_AI_ROUTE_PREFIX', 'limen-ai'),
        'middleware' => ['web'],
        'auth_middleware' => env('LIMEN_AI_UI_REQUIRE_AUTH', true),
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
                'radius' => '16px',
                'position' => 'bottom-right',
                'direction' => 'ltr',
                'font_family' => '"Cairo", sans-serif',
                'title' => 'Limen AI Assistant',
                'subtitle' => 'Typically replies in a few seconds',
                'welcome_message' => 'How can I help you today?',
            ],
            'arabic' => [
                'direction' => 'rtl',
                'font_family' => '"Cairo", sans-serif',
                'title' => 'مساعد Limen AI',
                'welcome_message' => 'كيف يمكنني مساعدتك اليوم؟',
            ],
        ],
        'theme' => [
            'preset' => env('LIMEN_AI_THEME_PRESET', 'default'),
            'mode' => env('LIMEN_AI_THEME_MODE', 'light'),
            'allow_mode_toggle' => env('LIMEN_AI_THEME_TOGGLE', false),
            'overrides' => [],
            'radius' => env('LIMEN_AI_UI_RADIUS'),
            'position' => env('LIMEN_AI_UI_POSITION'),
            'direction' => env('LIMEN_AI_UI_DIRECTION'),
            'title' => env('LIMEN_AI_UI_TITLE'),
            'subtitle' => env('LIMEN_AI_UI_SUBTITLE'),
            'welcome_message' => env('LIMEN_AI_UI_WELCOME_MESSAGE'),
            'avatar_url' => env('LIMEN_AI_UI_AVATAR_URL'),
            'user_avatar_url' => env('LIMEN_AI_UI_USER_AVATAR_URL'),
            'font_family' => env('LIMEN_AI_UI_FONT_FAMILY'),
        ],

        'sounds' => [
            'enabled' => env('LIMEN_AI_UI_SOUNDS_ENABLED', true),
            'volume' => (float) env('LIMEN_AI_UI_SOUND_VOLUME', 0.35),
            'on_send' => env('LIMEN_AI_UI_SOUND_SEND', true),
            'on_receive' => env('LIMEN_AI_UI_SOUND_RECEIVE', true),
            'on_open' => env('LIMEN_AI_UI_SOUND_OPEN', true),
            'on_notification' => env('LIMEN_AI_UI_SOUND_NOTIFICATION', true),
        ],

        'animations' => [
            'enabled' => env('LIMEN_AI_UI_ANIMATIONS_ENABLED', true),
            'duration_ms' => (int) env('LIMEN_AI_UI_ANIMATION_MS', 280),
            'message_entrance' => env('LIMEN_AI_UI_ANIMATE_MESSAGES', true),
            'typing_indicator' => env('LIMEN_AI_UI_TYPING_INDICATOR', true),
            'launcher_pulse' => env('LIMEN_AI_UI_LAUNCHER_PULSE', true),
            'panel_entrance' => env('LIMEN_AI_UI_PANEL_ENTRANCE', true),
        ],

        'widget' => [
            'launcher_label' => env('LIMEN_AI_UI_LAUNCHER_LABEL', ''),
            'show_unread_badge' => env('LIMEN_AI_UI_UNREAD_BADGE', true),
            'close_on_escape' => env('LIMEN_AI_UI_CLOSE_ON_ESCAPE', true),
            'show_header_controls' => true,
        ],

        'composer' => [
            'max_rows' => (int) env('LIMEN_AI_UI_COMPOSER_ROWS', 4),
            'show_char_count' => env('LIMEN_AI_UI_CHAR_COUNT', false),
            'max_length' => (int) env('LIMEN_AI_UI_MAX_MESSAGE_LENGTH', 4000),
        ],

        'messages' => [
            'show_timestamps' => env('LIMEN_AI_UI_TIMESTAMPS', true),
            'show_avatars' => env('LIMEN_AI_UI_AVATARS', true),
            'show_role_labels' => env('LIMEN_AI_UI_ROLE_LABELS', false),
            'time_format' => env('LIMEN_AI_UI_TIME_FORMAT', 'short'),
        ],

        'guest' => [
            'enabled' => env('LIMEN_AI_UI_GUEST_ENABLED', false),
            'session_ttl_minutes' => (int) env('LIMEN_AI_UI_GUEST_SESSION_TTL', 10080),
            'storage_key' => 'limen-ai-guest-token',
            'form' => [
                'name' => [
                    'label' => 'Name',
                    'placeholder' => 'Your name',
                    'required' => true,
                    'max' => 120,
                ],
                'email' => [
                    'label' => 'Email',
                    'placeholder' => 'you@example.com',
                    'required' => true,
                    'max' => 255,
                ],
                'phone' => [
                    'label' => 'Phone',
                    'placeholder' => '+1 555 000 0000',
                    'required' => false,
                    'max' => 40,
                ],
            ],
        ],

        'history' => [
            'enabled' => env('LIMEN_AI_UI_HISTORY_ENABLED', true),
            'show_preview' => env('LIMEN_AI_UI_HISTORY_PREVIEW', true),
            'resume_last_conversation' => env('LIMEN_AI_UI_RESUME_CONVERSATION', true),
            'defer_until_open' => env('LIMEN_AI_UI_DEFER_UNTIL_OPEN', true),
            'storage_key' => 'limen-ai-active-conversation',
        ],

        'i18n' => [
            'enabled' => env('LIMEN_AI_UI_I18N_ENABLED', true),
            'default_locale' => env('LIMEN_AI_UI_DEFAULT_LOCALE', 'en'),
            'storage_key' => 'limen-ai-locale',
            'supported' => ['en', 'ar'],
            'labels' => [
                'en' => [
                    'placeholder' => 'Type your message...',
                    'send' => 'Send',
                    'typing' => 'Typing...',
                    'history' => 'Conversations',
                    'new_chat' => 'New chat',
                    'guest_title' => 'Start a conversation',
                    'guest_copy' => 'Enter your details to continue.',
                    'guest_continue' => 'Continue',
                ],
                'ar' => [
                    'placeholder' => 'اكتب رسالتك...',
                    'send' => 'إرسال',
                    'typing' => 'يكتب...',
                    'history' => 'المحادثات',
                    'new_chat' => 'محادثة جديدة',
                    'guest_title' => 'ابدأ المحادثة',
                    'guest_copy' => 'أدخل بياناتك للمتابعة.',
                    'guest_continue' => 'متابعة',
                ],
            ],
        ],
    ],

    'paths' => [
        'agents' => app_path('LimenAi/Agents'),
        'tools' => app_path('LimenAi/Tools'),
        'skills' => app_path('LimenAi/Skills'),
        'workflows' => app_path('LimenAi/Workflows'),
        'providers' => app_path('LimenAi/Providers'),
        'memory' => app_path('LimenAi/Memory'),
        'knowledge' => app_path('LimenAi/Knowledge'),
        'connectors' => app_path('LimenAi/Connectors'),
    ],

];
