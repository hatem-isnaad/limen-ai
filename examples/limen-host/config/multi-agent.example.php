<?php

/**
 * Multi-agent layout example — merge patterns into config/limen-ai.php.
 *
 * Register ALL tools in tools.*; each agent exposes only what it needs.
 * See docs/scaling-agents-and-tools.md
 */
return [
    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'app_assistant'),

    'agents' => [
        'app_assistant' => [
            'name' => 'App Assistant',
            'description' => 'Public-facing help and light lookups.',
            'model' => env('LIMEN_AI_APP_ASSISTANT_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'openai'),
            'instructions' => 'Help users with general questions. Escalate complex shipment issues to support workflows.',
            'skills' => ['general_assistance'],
            'tools' => [
                'search_help',
                'get_account_summary',
                'create_support_ticket',
            ],
            'authorization' => [
                'required' => false,
                'guest_allowed' => true,
                'abilities' => [],
            ],
            'limits' => [
                'max_tool_calls' => 6,
                'max_steps' => 12,
                'max_history_messages' => 20,
            ],
        ],

        'support_agent' => [
            'name' => 'Support Agent',
            'description' => 'Authenticated staff — shipments and tickets.',
            'model' => env('LIMEN_AI_SUPPORT_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'openai'),
            'instructions' => 'Help support staff with shipments and tickets. Always verify shipment IDs via tools.',
            'skills' => ['logistics_support'],
            'tools' => [
                'get_shipment_status',
                'list_open_tickets',
                'add_ticket_note',
            ],
            'authorization' => [
                'required' => true,
                'abilities' => ['agents.support'],
                'guest_allowed' => false,
            ],
            'limits' => [
                'max_tool_calls' => 10,
                'max_steps' => 16,
            ],
        ],

        'admin_agent' => [
            'name' => 'Admin Agent',
            'description' => 'Privileged operations — approvals required.',
            'model' => env('LIMEN_AI_ADMIN_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'openai'),
            'instructions' => 'Execute privileged actions only when the user is authorized. Prefer workflows for multi-step changes.',
            'tools' => [
                'send_customer_message',
                'refund_order',
                'update_shipment_status',
            ],
            'authorization' => [
                'required' => true,
                'abilities' => ['agents.admin'],
                'guest_allowed' => false,
            ],
            'limits' => [
                'max_tool_calls' => 8,
                'max_steps' => 14,
            ],
        ],
    ],
];
