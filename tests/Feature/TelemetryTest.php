<?php

namespace Tests\Feature;

use App\Services\TelemetryReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_vps_sends_only_selected_categories(): void
    {
        $reporter = app(TelemetryReporter::class);
        $this->assertTrue($reporter->preferences()['enabled']);
        $reporter->save(true, ['version', 'workload']);
        Http::fake(['xpanel.sh/*' => Http::response(['accepted' => true], 202)]);

        $this->assertTrue($reporter->send());
        Http::assertSent(fn ($request) => $request['product'] === 'xpanel-vps'
            && isset($request['version'], $request['workload'])
            && ! isset($request['os'], $request['compute'], $request['gpu'], $request['ip'], $request['domain']));
    }

    public function test_disabled_telemetry_never_calls_the_receiver(): void
    {
        app(TelemetryReporter::class)->save(false, TelemetryReporter::CATEGORIES);
        Http::fake();

        $this->assertFalse(app(TelemetryReporter::class)->send());
        Http::assertNothingSent();
    }
}
