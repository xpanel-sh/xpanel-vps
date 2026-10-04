<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostBrokerOperation;
use App\Services\NativeRuntimeMetrics;

class DaemonOperationController extends Controller
{
    public function index(NativeRuntimeMetrics $metrics)
    {
        $operations = HostBrokerOperation::query()
            ->with('instance')
            ->latest()
            ->limit(100)
            ->get();
        $runtime = $metrics->snapshot();

        return view('admin.daemon.operations', compact('operations', 'runtime'));
    }
}
