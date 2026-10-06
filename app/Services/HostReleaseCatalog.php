<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HostReleaseCatalog
{
    /** @return array<int, array{sha:string, title:string, details:string, date:string, url:string}> */
    public function recent(): array
    {
        return Cache::remember('xpanel-host-official-commits', now()->addMinutes(5), function (): array {
            try {
                $response = Http::acceptJson()->withHeaders(['User-Agent' => 'XPanel-VPS'])
                    ->timeout(8)->get('https://api.github.com/repos/xpanel-sh/xpanel-host/commits', [
                        'sha' => 'main', 'per_page' => 10,
                    ]);
                if (! $response->successful()) {
                    return [];
                }

                return collect($response->json())->filter(fn ($item) => is_array($item) && preg_match('/^[a-f0-9]{40}$/', $item['sha'] ?? ''))
                    ->map(function (array $item): array {
                        $message = trim((string) data_get($item, 'commit.message', ''));
                        [$title, $details] = array_pad(explode("\n", $message, 2), 2, '');

                        return [
                            'sha' => $item['sha'],
                            'title' => mb_substr($title, 0, 160),
                            'details' => mb_substr(trim($details), 0, 800),
                            'date' => (string) data_get($item, 'commit.committer.date', ''),
                            'url' => 'https://github.com/xpanel-sh/xpanel-host/commit/'.$item['sha'],
                        ];
                    })->values()->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }
}
