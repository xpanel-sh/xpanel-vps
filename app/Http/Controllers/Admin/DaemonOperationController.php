<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DaemonClient;
use Illuminate\Support\Facades\Log;

class DaemonOperationController extends Controller
{
    public function index(DaemonClient $daemon)
    {
        $operations = [];
        $runtime = [];
        $error = null;

        try {
            $operations = array_slice($daemon->operations(), 0, 100);
            $runtime = $daemon->runtimeStatus();
        } catch (\Throwable $e) {
            Log::warning('Daemon operations screen failed', ['exception' => $e]);
            $error = 'No se pudo consultar el agente. Revisa los logs del panel o del servicio xpanel-daemon.';
        }

        return view('admin.daemon.operations', compact('operations', 'runtime', 'error'));
    }
}
