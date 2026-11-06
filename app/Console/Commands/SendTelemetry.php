<?php

namespace App\Console\Commands;

use App\Services\TelemetryReporter;
use Illuminate\Console\Command;

class SendTelemetry extends Command
{
    protected $signature = 'xpanel:telemetry-send';

    protected $description = 'Send the anonymous installation heartbeat when enabled';

    public function handle(TelemetryReporter $reporter): int
    {
        $reporter->send();

        return self::SUCCESS;
    }
}
