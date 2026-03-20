<?php

namespace App\Services;

class EventSummaryService
{
    public function summarize(string $eventType, array $payload): string
    {
        $orderId = $this->extractOrderId($payload);
        $status = $this->extractStatus($payload);
        $amount = $this->extractTotal($payload);

        return match ($eventType) {
            'order.created', 'order.placed' => $this->format("Order created", $orderId, $status, $amount),
            'order.updated' => $this->format("Order updated", $orderId, $status, $amount),
            'order.status_changed', 'order.status' => $this->format("Order status changed", $orderId, $status, $amount, $this->extractOldStatus($payload)),
            'order.refunded' => $this->format("Order refunded", $orderId, $status, $amount),
            'order.paid' => $this->format("Order paid", $orderId, $status, $amount),
            default => $this->format("Event {$eventType}", $orderId, $status, $amount),
        };
    }

    public function extractOrderId(array $payload): ?int
    {
        $candidates = [
            $payload['order_id'] ?? null,
            $payload['data']['order_id'] ?? null,
            $payload['data']['orderId'] ?? null,
            $payload['data']['order']['order_id'] ?? null,
        ];

        foreach ($candidates as $val) {
            if (is_numeric($val)) {
                return (int) $val;
            }
        }

        return null;
    }

    private function extractStatus(array $payload): ?string
    {
        $status = $payload['status'] ?? $payload['data']['status'] ?? null;
        if (is_string($status) && $status !== '') {
            return $status;
        }
        return null;
    }

    private function extractOldStatus(array $payload): ?string
    {
        $status = $payload['old_status'] ?? $payload['data']['old_status'] ?? null;
        if (is_string($status) && $status !== '') {
            return $status;
        }
        return null;
    }

    private function extractTotal(array $payload): ?float
    {
        $total = $payload['total'] ?? $payload['data']['total'] ?? null;
        if (is_numeric($total)) {
            return (float) $total;
        }
        return null;
    }

    private function format(string $prefix, ?int $orderId, ?string $status, ?float $amount, ?string $oldStatus = null): string
    {
        $parts = [$prefix];

        if ($orderId) {
            $parts[] = "#{$orderId}";
        }

        if ($status) {
            $parts[] = $oldStatus ? "{$oldStatus} → {$status}" : $status;
        }

        if ($amount !== null) {
            $parts[] = sprintf("total %.2f", $amount);
        }

        return implode(' · ', $parts);
    }
}

