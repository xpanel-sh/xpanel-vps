<?php

namespace Tests\Unit;

use App\Services\HostReleaseCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostReleaseCatalogTest extends TestCase
{
    public function test_managed_host_receives_a_detailed_fifty_commit_feed(): void
    {
        Cache::forget('xpanel-host-official-commits');
        Http::fake(['api.github.com/*' => Http::response([[
            'sha' => str_repeat('a', 40),
            'commit' => [
                'message' => "Mejora de actualizaciones\nExplicación detallada del cambio.",
                'committer' => ['date' => '2026-10-06T12:30:00Z'],
            ],
        ]], 200)]);

        $commits = app(HostReleaseCatalog::class)->recent();

        $this->assertSame('Explicación detallada del cambio.', $commits[0]['details']);
        Http::assertSent(fn ($request) => $request['per_page'] === 50);
    }
}
