<?php

namespace App\Services;

use App\Models\Order;

class OrderUpsertService
{
    public function upsertSnapshot(int $storePk, array $data): array
    {
        $orderId = $data['order_id'] ?? null;
        if (!$orderId)
            return ['ok' => false, 'error' => 'missing_order_id'];

        // Normalize status
        $status = strtolower((string) ($data['status'] ?? ''));
        $status = preg_replace('/^wc-/', '', $status);
        $status = str_replace('_', '-', $status);

        // Coupon codes normalization
        $couponCodes = $data['coupon_codes'] ?? null;
        if (is_array($couponCodes))
            $couponCodes = implode(',', array_values($couponCodes));
        elseif (!is_string($couponCodes))
            $couponCodes = null;

        // Parse timestamps
        $createdAt = !empty($data['date_created_gmt']) ? date('Y-m-d H:i:s', strtotime($data['date_created_gmt'])) : null;
        $paidAt = !empty($data['date_paid_gmt']) ? date('Y-m-d H:i:s', strtotime($data['date_paid_gmt'])) : null;
        $modifiedAt = !empty($data['date_modified_gmt']) ? date('Y-m-d H:i:s', strtotime($data['date_modified_gmt'])) : null;

        // Snapshot quality (prevents partial overwrite)
        $quality = 0;
        if (!empty($data['billing']) && is_array($data['billing']))
            $quality += 25;
        if (!empty($data['shipping']) && is_array($data['shipping']))
            $quality += 25;
        if (!empty($data['line_items']) && is_array($data['line_items']))
            $quality += 25;
        if (!empty($couponCodes))
            $quality += 10;
        if (!empty($data['total']))
            $quality += 15;
        if ($quality > 100)
            $quality = 100;

        $payloadJson = json_encode($data, JSON_UNESCAPED_SLASHES);

        $order = Order::where('store_id', $storePk)->where('order_id', (int) $orderId)->first();

        if ($order) {
            $existingModified = $order->modified_at_gmt ? strtotime($order->modified_at_gmt) : null;
            $incomingModified = $modifiedAt ? strtotime($modifiedAt) : null;

            $shouldUpdate = false;
            if ($incomingModified && (!$existingModified || $incomingModified >= $existingModified))
                $shouldUpdate = true;
            elseif ($quality >= (int) $order->snapshot_quality)
                $shouldUpdate = true;

            if (!$shouldUpdate)
                return ['ok' => true, 'order_id' => (int) $orderId, 'skipped' => true];
        } else {
            $order = new Order();
            $order->store_id = $storePk;
            $order->order_id = (int) $orderId;
        }

        $order->order_number = $data['order_number'] ?? $order->order_number;
        $order->status = $status ?: ($order->status ?? 'unknown');
        $order->currency = $data['currency'] ?? $order->currency;

        $order->total = isset($data['total']) ? (float) $data['total'] : $order->total;
        $order->subtotal = isset($data['subtotal']) ? (float) $data['subtotal'] : $order->subtotal;
        $order->discount_total = isset($data['discount_total']) ? (float) $data['discount_total'] : $order->discount_total;
        $order->shipping_total = isset($data['shipping_total']) ? (float) $data['shipping_total'] : $order->shipping_total;
        $order->tax_total = isset($data['tax_total']) ? (float) $data['tax_total'] : $order->tax_total;

        $order->payment_method = $data['payment_method'] ?? $order->payment_method;
        $order->payment_method_title = $data['payment_method_title'] ?? $order->payment_method_title;

        $order->customer_id = isset($data['customer_id']) ? (int) $data['customer_id'] : $order->customer_id;

        $billing = $data['billing'] ?? [];
        if (is_array($billing)) {
            $order->billing_email = $billing['email'] ?? ($data['billing_email'] ?? $order->billing_email);
            $order->billing_first_name = $billing['first_name'] ?? ($data['billing_first_name'] ?? $order->billing_first_name);
            $order->billing_last_name = $billing['last_name'] ?? ($data['billing_last_name'] ?? $order->billing_last_name);
        }

        $order->coupon_codes = $couponCodes;
        $order->created_at_gmt = $createdAt;
        $order->paid_at_gmt = $paidAt;
        $order->modified_at_gmt = $modifiedAt;
        $order->snapshot_quality = $quality;
        $order->order_json = $payloadJson ?: $order->order_json;
        $order->synced_at = now();
        $order->save();

        return ['ok' => true, 'order_id' => (int) $orderId, 'updated' => true];
    }
}
