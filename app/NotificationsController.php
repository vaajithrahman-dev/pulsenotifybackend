<?php

namespace App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $activeStorePk = (int) $request->attributes->get('active_store_id', 0);

        $notifications = $user->notifications()
            ->when($activeStorePk > 0, function ($q) use ($activeStorePk) {
                $q->where('data->store_id', $activeStorePk);
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Mark any unread notifications as read
        $user->unreadNotifications->markAsRead();

        $items = $notifications->map(function ($n) {
            $data = $n->data ?? [];

            return [
                'id' => $n->id,
                'summary' => $data['summary'] ?? '',
                'event_type' => $data['event_type'] ?? '',
                'event_id' => $data['event_id'] ?? '',
                'order_id' => $data['order_id'] ?? null,
                'link' => $data['link'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at?->toISOString(),
            ];
        });

        return Inertia::render('notifications/Index', [
            'notifications' => $items,
            'active_store_id' => $activeStorePk,
        ]);
    }
}

