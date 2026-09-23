<?php

namespace LimenAi\Tests\Stubs\Limen;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\ConfigToolDefinition;

class GetShipmentStatusTool implements Tool
{
    public function __construct(
        private readonly FakeShipmentService $shipments,
    ) {}

    public function key(): string
    {
        return 'get_shipment_status';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), [
            'name' => 'Get Shipment Status',
            'description' => 'Look up the current status of a shipment by ID.',
            'class' => self::class,
            'input_schema' => [
                'shipment_id' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(array $input, ToolExecutionContext $context): array
    {
        $shipmentId = (string) ($input['shipment_id'] ?? '');
        $shipment = $this->shipments->find($shipmentId);

        if ($shipment === null) {
            return [
                'found' => false,
                'shipment_id' => $shipmentId,
            ];
        }

        return [
            'found' => true,
            'shipment' => $shipment,
        ];
    }
}
