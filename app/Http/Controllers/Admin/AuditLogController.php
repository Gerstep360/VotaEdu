<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display listing of administrative audit logs.
     */
    public function index(Request $request): View
    {
        $action = $request->input('action');

        $logs = AdministrativeAuditLog::with('user')
            ->when($action, fn ($q) => $q->where('action', 'like', "%{$action}%"))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-logs.index', compact('logs', 'action'));
    }
}
