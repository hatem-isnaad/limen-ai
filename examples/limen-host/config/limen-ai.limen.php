<?php

use App\LimenAi\Tools\GetShipmentStatus;
use App\LimenAi\Tools\SendCustomerMessage;

/**
 * Merge this file into config/limen-ai.php in the Limen host application.
 */
return [
    'default_agent' => env('LIMEN_AI_DEFAULT_AGENT', 'limen_3pl'),

    'agents' => [
        'limen_3pl' => [
            'name' => 'Limen 3PL Assistant',
            'description' => 'Helps operators look up shipments and send approved customer updates.',
            'model' => env('LIMEN_AI_LIMEN_MODEL', 'gpt-4.1-mini'),
            'provider' => env('LIMEN_AI_PROVIDER', 'openai'),
            'instructions' => <<<'TEXT'
You are the Limen 3PL logistics assistant. Help warehouse and operations staff look up shipment status and draft customer communications.

Rules:
- Always use get_shipment_status before answering status questions.
- Never invent shipment data.
- Use send_customer_message only after drafting the exact message for the user to review.
- Customer messages require human approval; tell the user when approval is pending.
TEXT,
            'skills' => ['logistics_support'],
            'tools' => ['get_shipment_status', 'send_customer_message'],
            'knowledge' => ['limen_3pl_ops'],
            'memory' => [
                'conversation' => true,
                'user' => true,
            ],
            'authorization' => [
                'required' => true,
                'abilities' => ['agents.limen_3pl'],
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

    'tools' => [
        'get_shipment_status' => [
            'name' => 'Get Shipment Status',
            'description' => 'Look up the current status of a shipment by ID.',
            'class' => GetShipmentStatus::class,
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => ['shipments.view'],
            ],
            'confirmation' => false,
            'timeout' => 10,
            'version' => '1.0.0',
        ],
        'send_customer_message' => [
            'name' => 'Send Customer Message',
            'description' => 'Send an outbound message to a shipment customer. Requires human approval.',
            'class' => SendCustomerMessage::class,
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
                'message' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => ['shipments.notify'],
            ],
            'confirmation' => true,
            'timeout' => 15,
            'version' => '1.0.0',
        ],
    ],

    'skills' => [
        'logistics_support' => [
            'name' => 'Logistics Support',
            'instructions' => 'Be concise, operational, and accurate. Reference shipment IDs explicitly.',
            'tools' => ['get_shipment_status', 'send_customer_message'],
            'knowledge' => ['limen_3pl_ops'],
            'version' => '1.0.0',
        ],
    ],

    'workflows' => [
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
                        'message' => '{{ steps.draft.output }}',
                    ],
                ],
            ],
        ],
    ],

    'knowledge' => [
        'collections' => [
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
];
