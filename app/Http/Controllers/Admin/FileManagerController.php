<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\NativeFileManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FileManagerController extends Controller
{
    public function __construct(private NativeFileManager $files) {}

    public function index(Request $request, ?string $domain = null)
    {
        $domain = $this->normalizeDomain($domain);
        $site = $domain ? Site::where('domain', $domain)->firstOrFail() : null;
        $sites = Site::with('tenant')->orderBy('domain')->get(['id', 'tenant_id', 'domain']);

        return view('admin.files.index', compact('site', 'sites', 'domain'));
    }

    public function list(Request $request)
    {
        if (! $this->normalizeDomain($request->query('domain')) && in_array($request->query('path', '/'), ['', '/'], true)) {
            return response()->json($this->siteRootList());
        }

        [$domain, $path, $virtualPrefix] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '/')
        );

        try {
            $payload = $this->files->list($this->site($domain), $path);
            if ($virtualPrefix) {
                $payload['path'] = $virtualPrefix.(($path === '/' || $path === '') ? '' : $path);
                $payload['entries'] = collect($payload['entries'] ?? [])
                    ->map(function (array $entry) use ($virtualPrefix) {
                        $entry['path'] = $virtualPrefix.($entry['path'] ?? '/');

                        return $entry;
                    })
                    ->values()
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            Log::warning('Admin FileManager list failed', ['domain' => $domain, 'path' => $path, 'error' => $e->getMessage()]);

            return $this->failure($e);
        }
    }

    public function read(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '')
        );
        if (empty($path)) {
            return response()->json(['error' => 'path required'], 400);
        }
        try {
            return response()->json($this->files->read($this->site($domain), $path));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function write(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
            'content' => ['present', 'nullable', 'string'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);

            return response()->json($this->files->write($this->site($domain), $path, $validated['content'] ?? ''));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function mkdir(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);

            return response()->json($this->files->mkdir($this->site($domain), $path));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:file,dir'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);

            return response()->json($this->files->create($this->site($domain), $path, $validated['name'], $validated['type']));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);

            return response()->json($this->files->delete($this->site($domain), $path));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function rename(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'old_path' => ['required', 'string', 'max:2048'],
            'new_path' => ['required', 'string', 'max:2048'],
        ]);
        try {
            [$domain, $oldPath] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['old_path']);
            [$newDomain, $newPath] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['new_path']);
            if ($domain !== $newDomain) {
                return response()->json(['error' => 'cross-site moves are not supported'], 422);
            }

            return response()->json($this->files->rename($this->site($domain), $oldPath, $newPath));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'],
            'path' => ['required', 'string'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget(
                $this->normalizeDomain($request->input('domain')),
                $request->input('path', '/')
            );

            return response()->json($this->files->upload($this->site($domain), $path, $request->file('file')));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function extract(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:2048'],
            'overwrite' => ['nullable', 'boolean'],
        ]);
        try {
            [$domain, $path] = $this->resolveOperationTarget($this->normalizeDomain($validated['domain'] ?? null), $validated['path']);

            return response()->json($this->files->extract($this->site($domain), $path, (bool) ($validated['overwrite'] ?? false)));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:2048'],
            'query' => ['required', 'string', 'max:255'],
            'include_content' => ['nullable', 'boolean'],
            'case_sensitive' => ['nullable', 'boolean'],
        ]);

        try {
            [$domain, $path, $virtualPrefix] = $this->resolveOperationTarget(
                $this->normalizeDomain($validated['domain'] ?? null),
                $validated['path'] ?? '/'
            );
            $payload = $this->files->search(
                $this->site($domain),
                $path ?: '/',
                $validated['query'],
                (bool) ($validated['include_content'] ?? true),
                (bool) ($validated['case_sensitive'] ?? false)
            );
            if ($virtualPrefix) {
                $payload['path'] = $virtualPrefix.(($path === '/' || $path === '') ? '' : $path);
                $payload['results'] = collect($payload['results'] ?? [])
                    ->map(function (array $result) use ($virtualPrefix) {
                        $result['path'] = $virtualPrefix.($result['path'] ?? '/');

                        return $result;
                    })
                    ->values()
                    ->all();
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function download(Request $request)
    {
        [$domain, $path] = $this->resolveOperationTarget(
            $this->normalizeDomain($request->query('domain')),
            $request->query('path', '')
        );
        if (empty($path)) {
            abort(400, 'path required');
        }
        try {
            $file = $this->files->downloadPath($this->site($domain), $path);

            return $request->boolean('inline') ? response()->file($file) : response()->download($file);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    private function failure(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            throw $exception;
        }

        return response()->json(['error' => $exception->getMessage()], 500);
    }

    private function normalizeDomain(?string $domain): ?string
    {
        $domain = trim((string) $domain);

        return $domain === '' ? null : strtolower($domain);
    }

    private function site(?string $domain): Site
    {
        abort_if(! $domain, 422, 'Selecciona un sitio primero.');

        return Site::where('domain', $domain)->firstOrFail();
    }

    private function siteRootList(): array
    {
        return [
            'path' => '/',
            'entries' => Site::query()->orderBy('domain')->get(['domain', 'updated_at'])->map(fn (Site $site) => [
                'name' => $site->domain,
                'path' => '/'.$site->domain,
                'is_dir' => true,
                'size' => 0,
                'mode' => '0750',
                'mod_time' => optional($site->updated_at)->toIso8601String(),
            ])->all(),
        ];
    }

    private function resolveOperationTarget(?string $domain, string $path): array
    {
        if ($domain) {
            Site::where('domain', $domain)->firstOrFail();

            return [$domain, $this->normalizeSitePath($path), null];
        }

        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', trim($path, '/'))), fn ($part) => $part !== ''));
        if (! $parts) {
            abort(422, 'Selecciona un sitio primero.');
        }

        $candidateDomain = $this->normalizeDomain(array_shift($parts));
        if (! $candidateDomain || ! Site::where('domain', $candidateDomain)->exists()) {
            abort(422, 'Selecciona un sitio valido.');
        }

        return [$candidateDomain, $this->normalizeSitePath('/'.implode('/', $parts)), '/'.$candidateDomain];
    }

    private function normalizeSitePath(string $path): string
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        $clean = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($clean);

                continue;
            }
            $clean[] = $part;
        }

        return $clean ? '/'.implode('/', $clean) : '/';
    }
}
