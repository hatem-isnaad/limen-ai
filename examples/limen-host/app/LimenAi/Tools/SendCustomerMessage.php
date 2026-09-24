<?php

namespace App\LimenAi\Tools;

use App\Contracts\ShipmentService;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\ConfigToolDefinition;

class SendCustomerMessage implements Tool
{
    public function __construct(
        private readonly ShipmentService $shipments,
    ) {}

    public function key(): string
    {
        return 'send_customer_message';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), config('limen-ai.tools.send_customer_message', []));
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
