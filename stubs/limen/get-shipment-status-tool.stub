<?php

namespace App\LimenAi\Tools;

use App\Contracts\ShipmentService;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\BaseTool;
use LimenAi\Tools\ConfigToolDefinition;

class GetShipmentStatus extends BaseTool
{
    public function __construct(
        private readonly ShipmentService $shipments,
    ) {}

    public function key(): string
    {
        return 'get_shipment_status';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), config('limen-ai.tools.get_shipment_status', []));
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
