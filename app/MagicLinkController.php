<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MagicLinkController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()) {
            return redirect('/dashboard');
        }

        return Inertia::render('auth/MagicLink', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        // Create token + store hashed for lookup
        $token = Str::random(48);
        $hash = hash('sha256', $token);

        $link = new MagicLink();
        $link->email = $email;
        $link->token_hash = $hash;
        $link->expires_at = now()->addMinutes(15);
        $link->used_at = null;
        $link->ip = $request->ip();
        $link->user_agent = substr((string) $request->userAgent(), 0, 1000);
        $link->save();

        $url = url("/magic-link/{$token}");

        // Send via configured mail driver (logs in local by default)
        Mail::raw(
            "Here is your magic login link for " . config('app.name') . ":\n\n{$url}\n\nThis link expires in 15 minutes. If you did not request it, you can ignore this message.",
            function ($message) use ($email) {
                $message->to($email)->subject(config('app.name') . ' login link');
            }
        );

        // Helpful for local dev; remove in prod if noisy
        logger()->info('Magic link issued', ['email' => $email, 'url' => $url]);

        return back()->with('success', 'If that email exists, we just sent a magic login link (valid for 15 minutes).');
    }

    public function consume(Request $request, string $token)
    {
        $hash = hash('sha256', $token);

        $link = MagicLink::where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$link) {
            return redirect('/magic-link')->with('error', 'That link expired or was already used. Please request a new one.');
        }

        // Mark as used before logging in to prevent replay
        $link->used_at = now();
        $link->ip = $request->ip();
        $link->user_agent = substr((string) $request->userAgent(), 0, 1000);
        $link->save();

        $user = User::where('email', $link->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => explode('@', $link->email)[0] ?: 'User',
                'email' => $link->email,
                'password' => bcrypt(Str::random(32)),
            ]);

            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard')->with('success', 'You are now signed in.');
    }
}
