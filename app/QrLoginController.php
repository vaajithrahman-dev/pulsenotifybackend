<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\QrLogin;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QrLoginController extends Controller
{
    private const TTL_MINUTES = 5;

    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Ensure there is an active store for scoping
        $storePk = (int) $request->attributes->get('active_store_id', (int) $request->session()->get('active_store_id', 0));
        if ($storePk <= 0) {
            return redirect('/app/stores/switch')->with('error', 'Select a store before generating a QR login.');
        }

        $store = Store::findOrFail($storePk);

        $token = $this->issueToken($user->id, $storePk);
        $tokenUrl = url("/qr-login/{$token}");

        return Inertia::render('auth/QrLogin', [
            'token_url' => $tokenUrl,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'store' => [
                'id' => $store->id,
                'store_id' => $store->store_id,
                'store_name' => $store->store_name,
            ],
        ]);
    }

    private function issueToken(int $userId, int $storePk): string
    {
        $payload = [
            'u' => $userId,
            's' => $storePk,
            'n' => Str::random(16),
        ];

        $token = rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);

        QrLogin::create([
            'store_id' => $storePk,
            'token_hash' => $hash,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'used_at' => null,
        ]);

        return $token;
    }

    public function consume(Request $request, string $token)
    {
        $hash = hash('sha256', $token);

        $record = QrLogin::where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return redirect()->route('login')->with('error', 'QR code expired or already used. Generate a new one.');
        }

        $payload = $this->decodeToken($token);
        if (!$payload || empty($payload['u']) || empty($payload['s'])) {
            return redirect()->route('login')->with('error', 'Invalid QR token.');
        }

        $storePk = (int) $payload['s'];
        if ($storePk !== (int) $record->store_id) {
            return redirect()->route('login')->with('error', 'QR token scope mismatch.');
        }

        $user = User::find((int) $payload['u']);
        if (!$user) {
            return redirect()->route('login')->with('error', 'User for this QR code no longer exists.');
        }

        // Ensure membership with this store
        Membership::firstOrCreate(
            ['user_id' => $user->id, 'store_id' => $storePk],
            ['role' => 'owner']
        );

        $record->used_at = now();
        $record->save();

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('active_store_id', $storePk);

        return redirect()->intended('/dashboard')->with('success', 'Signed in via QR.');
    }

    private function decodeToken(string $token): ?array
    {
        $padded = strtr($token, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);

        $json = base64_decode($padded, true);
        if ($json === false) {
            return null;
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
