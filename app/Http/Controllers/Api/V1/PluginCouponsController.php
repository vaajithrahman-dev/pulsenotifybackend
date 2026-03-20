<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Http\Request;

class PluginCouponsController extends Controller
{
    public function bulk(Request $request)
    {
        /** @var Store $store */
        $store = $request->attributes->get('store');

        $payload = $request->json()->all();
        $coupons = $payload['coupons'] ?? null;

        if (!is_array($coupons)) {
            return response()->json(['ok' => false, 'error' => 'coupons_must_be_array'], 422);
        }

        $upserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($coupons as $c) {
            if (!is_array($c)) {
                $errors++;
                continue;
            }

            $code = $c['code'] ?? null;
            if (!$code || !is_string($code)) {
                $errors++;
                continue;
            }

            $code = trim($code);
            if ($code === '') {
                $errors++;
                continue;
            }

            // Normalize
            $discountType = $c['discount_type'] ?? null;
            $amount = isset($c['amount']) ? (float) $c['amount'] : null;

            $expires = null;
            if (!empty($c['date_expires_gmt'])) {
                $expires = date('Y-m-d H:i:s', strtotime($c['date_expires_gmt']));
            }

            $usageCount = isset($c['usage_count']) ? (int) $c['usage_count'] : null;
            $usageLimit = isset($c['usage_limit']) ? (int) $c['usage_limit'] : null;

            $json = json_encode($c, JSON_UNESCAPED_SLASHES);

            // Upsert by (store_id, code)
            $coupon = Coupon::where('store_id', $store->id)
                ->where('code', $code)
                ->first();

            if (!$coupon) {
                $coupon = new Coupon();
                $coupon->store_id = $store->id;
                $coupon->code = $code;
            }

            $coupon->discount_type = $discountType ?: $coupon->discount_type;
            $coupon->amount = $amount ?? $coupon->amount;
            $coupon->date_expires_gmt = $expires;
            $coupon->usage_count = $usageCount;
            $coupon->usage_limit = $usageLimit;
            $coupon->coupon_json = $json;
            $coupon->save();

            $upserted++;
        }

        return response()->json([
            'ok' => true,
            'upserted' => $upserted,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}
