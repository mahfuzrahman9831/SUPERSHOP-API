<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [
                $request->from,
                $request->to . ' 23:59:59'
            ]);
        }

        $logs = $query->paginate($request->per_page ?? 20);
        return $this->paginated($logs);
    }
}