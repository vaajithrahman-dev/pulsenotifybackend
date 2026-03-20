<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');
        $action = $request->query('action');
        $userId = $request->query('user_id');
        $start = $request->query('start');
        $end = $request->query('end');
        $limit = (int) $request->query('limit', 200);
        if ($limit < 50) $limit = 50;
        if ($limit > 1000) $limit = 1000;

        $q = AuditLog::query()->where('store_id', $activeStorePk);

        if ($action) {
            $q->where('action', $action);
        }
        if ($userId) {
            $q->where('actor_user_id', $userId);
        }
        if ($start) {
            $q->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $q->whereDate('created_at', '<=', $end);
        }

        $logs = $q->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'store_id', 'actor_user_id', 'action', 'context_json', 'created_at']);

        return Inertia::render('audit/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $action,
                'user_id' => $userId,
                'start' => $start,
                'end' => $end,
                'limit' => $limit,
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');
        $start = $request->query('start');
        $end = $request->query('end');
        $action = $request->query('action');

        $q = AuditLog::query()->where('store_id', $activeStorePk);

        if ($action) $q->where('action', $action);
        if ($start) $q->whereDate('created_at', '>=', $start);
        if ($end) $q->whereDate('created_at', '<=', $end);

        $rows = $q->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['created_at', 'actor_user_id', 'action', 'context_json']);

        $filename = 'audit_logs_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['created_at', 'actor_user_id', 'action', 'context_json']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at,
                    $r->actor_user_id,
                    $r->action,
                    $r->context_json,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}

