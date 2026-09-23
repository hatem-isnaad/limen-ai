<?php

namespace App\Contracts;

interface ShipmentService
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(string $shipmentId): ?array;

    /**
     * @return array<string, mixed>
     */
    public function sendCustomerMessage(string $shipmentId, string $message, int $userId): array;
}
