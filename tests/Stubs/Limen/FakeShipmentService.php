<?php

namespace LimenAi\Tests\Stubs\Limen;

class FakeShipmentService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function shipments(): array
    {
        return [
            '12345' => [
                'id' => '12345',
                'status' => 'in_transit',
                'customer_name' => 'Acme Retail',
                'destination' => 'Riyadh',
                'eta' => '2026-09-25',
            ],
            '67890' => [
                'id' => '67890',
                'status' => 'delayed',
                'customer_name' => 'Desert Logistics',
                'destination' => 'Jeddah',
                'eta' => '2026-09-28',
                'delay_reason' => 'Customs inspection',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $shipmentId): ?array
    {
        return $this->shipments()[$shipmentId] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function sendCustomerMessage(string $shipmentId, string $message, int $userId): array
    {
        $shipment = $this->find($shipmentId);

        if ($shipment === null) {
            return [
                'sent' => false,
                'reason' => 'shipment_not_found',
            ];
        }

        return [
            'sent' => true,
            'shipment_id' => $shipmentId,
            'customer_name' => $shipment['customer_name'],
            'message' => $message,
            'sent_by_user_id' => $userId,
            'channel' => 'email',
        ];
    }
}
