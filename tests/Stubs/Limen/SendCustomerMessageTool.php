<?php

namespace LimenAi\Tests\Stubs\Limen;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\BaseTool;
use LimenAi\Tools\ConfigToolDefinition;

class SendCustomerMessageTool extends BaseTool
{
    public function __construct(
        private readonly FakeShipmentService $shipments,
    ) {}

    public function key(): string
    {
        return 'send_customer_message';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), [
            'name' => 'Send Customer Message',
            'description' => 'Send an outbound message to a shipment customer. Requires human approval.',
            'class' => self::class,
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
                'message' => ['type' => 'string', 'required' => true],
            ],
            'confirmation' => true,
        ]);
    }

    public function authorize(array $input, ToolExecutionContext $context): bool
    {
        return $context->userId() !== null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(array $input, ToolExecutionContext $context): array
    {
        $userId = $context->userId();

        if ($userId === null) {
            return [
                'sent' => false,
                'reason' => 'unauthenticated',
            ];
        }

        return $this->shipments->sendCustomerMessage(
            (string) ($input['shipment_id'] ?? ''),
            (string) ($input['message'] ?? ''),
            $userId,
        );
    }
}
