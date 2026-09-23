<?php

namespace App\Services;

use App\Contracts\ShipmentService as ShipmentServiceContract;
use App\Models\Shipment;

class ShipmentService implements ShipmentServiceContract
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(string $shipmentId): ?array
    {
        $shipment = Shipment::query()->find($shipmentId);

        if ($shipment === null) {
            return null;
        }

        return [
            'id' => (string) $shipment->getKey(),
            'status' => (string) $shipment->status,
            'customer_name' => (string) $shipment->customer_name,
            'destination' => (string) $shipment->destination,
            'eta' => optional($shipment->eta)->toDateString(),
            'delay_reason' => $shipment->delay_reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendCustomerMessage(string $shipmentId, string $message, int $userId): array
    {
        $shipment = Shipment::query()->find($shipmentId);

        if ($shipment === null) {
            return [
                'sent' => false,
                'reason' => 'shipment_not_found',
            ];
        }

        // Dispatch your notification job / mailer here.
        // Notification::send($shipment->customer, new ShipmentUpdate($message));

        return [
            'sent' => true,
            'shipment_id' => (string) $shipment->getKey(),
            'customer_name' => (string) $shipment->customer_name,
            'message' => $message,
            'sent_by_user_id' => $userId,
            'channel' => 'email',
        ];
    }
}
