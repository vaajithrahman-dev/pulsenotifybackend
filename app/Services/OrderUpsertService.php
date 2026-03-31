<?php

namespace App\Services;

use App\Models\Order;

class OrderUpsertService
{
    public function upsertSnapshot(int $storePk, array $data): array
    {
        $orderId = $this->extractOrderId($data);
        if (!$orderId)
            return ['ok' => false, 'error' => 'missing_order_id'];

        // Normalize status
        $status = strtolower((string) ($this->pick($data, ['status', 'order_status', 'orderStatus']) ?? ''));
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

        $order->order_number = $this->pick($data, ['order_number', 'number', 'orderNumber']) ?? $order->order_number;
        $order->status = $status ?: ($order->status ?? 'unknown');
        $order->currency = $this->pick($data, ['currency', 'order_currency', 'orderCurrency']) ?? $order->currency;

        $order->total = $this->floatPick($data, ['total', 'order_total', 'orderTotal', 'total_price', 'totalPrice', 'grand_total', 'grandTotal']) ?? $order->total;
        $order->subtotal = $this->floatPick($data, ['subtotal', 'order_subtotal', 'orderSubtotal', 'sub_total', 'subTotal']) ?? $order->subtotal;
        $order->discount_total = $this->floatPick($data, ['discount_total', 'order_discount_total', 'discountTotal', 'discount_total_amount']) ?? $order->discount_total;
        $order->shipping_total = $this->floatPick($data, ['shipping_total', 'order_shipping_total', 'shippingTotal']) ?? $order->shipping_total;
        $order->tax_total = $this->floatPick($data, ['tax_total', 'order_tax_total', 'taxTotal']) ?? $order->tax_total;

        $order->payment_method = $this->pick($data, ['payment_method', 'paymentMethod']) ?? $order->payment_method;
        $order->payment_method_title = $this->pick($data, ['payment_method_title', 'paymentMethodTitle']) ?? $order->payment_method_title;

        $order->customer_id = isset($data['customer_id']) ? (int) $data['customer_id'] : $order->customer_id;

        $billing = $data['billing'] ?? [];
        if (is_array($billing)) {
            $order->billing_email = $billing['email'] ?? ($this->pick($data, ['billing_email', 'billingEmail', 'customer_email', 'email']) ?? $order->billing_email);
            $order->billing_first_name = $billing['first_name'] ?? ($this->pick($data, ['billing_first_name', 'billingFirstName']) ?? $order->billing_first_name);
            $order->billing_last_name = $billing['last_name'] ?? ($this->pick($data, ['billing_last_name', 'billingLastName']) ?? $order->billing_last_name);
        } else {
            // fallback if billing not provided as array
            $order->billing_email = $this->pick($data, ['billing_email', 'billingEmail', 'customer_email', 'email']) ?? $order->billing_email;
            $order->billing_first_name = $this->pick($data, ['billing_first_name', 'billingFirstName']) ?? $order->billing_first_name;
            $order->billing_last_name = $this->pick($data, ['billing_last_name', 'billingLastName']) ?? $order->billing_last_name;
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

    /**
     * Accepts multiple possible keys from upstream payloads.
     * Primary: order_id; Fallbacks: id, orderId (camelCase).
     */
    private function extractOrderId(array $data): ?int
    {
        foreach (['order_id', 'id', 'orderId'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }
        return null;
    }

    /**
     * Return the first non-null value for given keys.
     */
    private function pick(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return null;
    }

    private function floatPick(array $data, array $keys): ?float
    {
        $val = $this->pick($data, $keys);
        return is_numeric($val) ? (float) $val : null;
    }
}
