<?php

namespace App\Http\Controllers;

use App\Services\HostBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class HostBrokerController extends Controller
{
    public function __invoke(Request $request, HostBroker $broker): JsonResponse
    {
        try {
            $output = $broker->execute($request->json()->all(), (string) $request->header('X-XPanel-Signature'));

            return response()->json(['ok' => true, 'output' => $output]);
        } catch (Throwable $exception) {
            Log::warning('Host broker operation rejected', [
                'instance_id' => $request->input('instance_id'),
                'action' => $request->input('action'),
                'exception' => $exception,
            ]);

            $message = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'La operación privilegiada fue rechazada.';

            return response()->json(['ok' => false, 'message' => $message], 422);
        }
    }
}
