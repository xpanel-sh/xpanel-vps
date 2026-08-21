<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DaemonClient
{
    protected $baseUrl;

    protected $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.xpanel_daemon.url', 'http://127.0.0.1:7070'), '/');
        $this->token = config('services.xpanel_daemon.token');
    }

    /**
     * Check daemon status
     */
    public function getStatus()
    {
        try {
            return Http::get("{$this->baseUrl}/status");
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Daemon Unreachable'], 503);
        }
    }

    // ── Software packages (webserver engines + PHP-FPM versions) ───────────────

    public function packagesList(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/packages/list");

        if (! $response->successful()) {
            Log::warning('Daemon packagesList failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon packagesList failed.');
        }

        return $response->json() ?? [];
    }

    public function packageInstall(string $slug): array
    {
        return $this->post('/api/packages/install', ['slug' => $slug], 'Daemon packageInstall failed');
    }

    public function packageEnable(string $slug): array
    {
        return $this->post('/api/packages/enable', ['slug' => $slug], 'Daemon packageEnable failed');
    }

    public function packageDisable(string $slug): array
    {
        return $this->post('/api/packages/disable', ['slug' => $slug], 'Daemon packageDisable failed');
    }

    public function siteStatus(string $containerName): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/site/status", [
            'name' => $containerName,
        ]);

        if (! $response->successful()) {
            Log::warning('Daemon siteStatus failed', [
                'name' => $containerName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon siteStatus failed.');
        }

        return $response->json() ?? [];
    }

    public function restartSite(string $containerName): array
    {
        return $this->post('/api/site/restart', [
            'name' => $containerName,
        ], 'Daemon restartSite failed');
    }

    public function deleteSite(string $containerName): array
    {
        return $this->post('/api/site/delete', [
            'name' => $containerName,
        ], 'Daemon deleteSite failed');
    }

    public function createDatabase(string $name, string $username, string $password, string $engine): array
    {
        return $this->post('/api/database/create', [
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'engine' => $engine,
        ], 'Daemon createDatabase failed');
    }

    public function deleteDatabase(string $name, string $username, string $engine): array
    {
        return $this->post('/api/database/delete', [
            'name' => $name,
            'username' => $username,
            'engine' => $engine,
        ], 'Daemon deleteDatabase failed');
    }

    public function addDatabaseUser(string $database, string $username, string $password, string $engine): array
    {
        return $this->post('/api/database/user/add', [
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'engine' => $engine,
        ], 'Daemon addDatabaseUser failed');
    }

    public function removeDatabaseUser(string $database, string $username, string $engine): array
    {
        return $this->post('/api/database/user/remove', [
            'database' => $database,
            'username' => $username,
            'engine' => $engine,
        ], 'Daemon removeDatabaseUser failed');
    }

    public function changeDatabaseUserPassword(string $username, string $password, string $engine): array
    {
        return $this->post('/api/database/user/password', [
            'username' => $username,
            'password' => $password,
            'engine' => $engine,
        ], 'Daemon changeDatabaseUserPassword failed');
    }

    public function updateDatabasePermissions(string $name, string $username, string $engine, array $privileges): array
    {
        return $this->post('/api/database/permissions', [
            'name' => $name,
            'username' => $username,
            'engine' => $engine,
            'privileges' => $privileges,
        ], 'Daemon updateDatabasePermissions failed');
    }

    // ===========================
    // Mail Proxy Methods (IMAP/SMTP via daemon)
    // ===========================

    public function mailFolders(string $account): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/mail/folders", ['account' => $account]);
        if (! $response->successful()) {
            throw new \RuntimeException('Daemon mailFolders failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function mailMessages(string $account, string $folder, int $page = 1, int $perPage = 25): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/mail/messages", [
            'account' => $account,
            'folder' => $folder,
            'page' => $page,
            'per_page' => $perPage,
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('Daemon mailMessages failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function mailMessage(string $account, string $folder, int $uid): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/mail/message", [
            'account' => $account,
            'folder' => $folder,
            'uid' => $uid,
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('Daemon mailMessage failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function mailFlag(string $account, string $folder, int $uid, string $flag, bool $set): array
    {
        return $this->post('/api/mail/flag', compact('account', 'folder', 'uid', 'flag', 'set'), 'Daemon mailFlag failed');
    }

    public function mailMove(string $account, string $folder, int $uid, string $targetFolder): array
    {
        return $this->post('/api/mail/move', [
            'account' => $account,
            'folder' => $folder,
            'uid' => $uid,
            'target_folder' => $targetFolder,
        ], 'Daemon mailMove failed');
    }

    public function mailDelete(string $account, string $folder, int $uid): array
    {
        return $this->post('/api/mail/delete', compact('account', 'folder', 'uid'), 'Daemon mailDelete failed');
    }

    public function mailSend(array $payload): array
    {
        return $this->post('/api/mail/send', $payload, 'Daemon mailSend failed');
    }

    public function mailFolderCreate(string $account, string $name): array
    {
        return $this->post('/api/mail/folder/create', compact('account', 'name'), 'Daemon mailFolderCreate failed');
    }

    public function mailFolderDelete(string $account, string $name): array
    {
        return $this->post('/api/mail/folder/delete', compact('account', 'name'), 'Daemon mailFolderDelete failed');
    }

    public function createEmailAccount(string $email, string $domain, int $quotaMb, string $password): array
    {
        return $this->post('/api/mail/account/create', [
            'email' => $email,
            'domain' => $domain,
            'quota_mb' => $quotaMb,
            'password' => $password,
        ], 'Daemon createEmailAccount failed');
    }

    public function deleteEmailAccount(string $email): array
    {
        return $this->post('/api/mail/account/delete', [
            'email' => $email,
        ], 'Daemon deleteEmailAccount failed');
    }

    public function resetEmailPassword(string $email, string $password): array
    {
        return $this->post('/api/mail/account/reset-password', [
            'email' => $email,
            'password' => $password,
        ], 'Daemon resetEmailPassword failed');
    }

    public function upsertDnsRecord(array $record): array
    {
        return $this->post('/api/dns/record/upsert', $record, 'Daemon upsertDnsRecord failed');
    }

    public function deleteDnsRecord(array $record): array
    {
        return $this->post('/api/dns/record/delete', $record, 'Daemon deleteDnsRecord failed');
    }

    public function applyNameservers(array $nameservers): array
    {
        return $this->post('/api/dns/nameservers/apply', [
            'nameservers' => $nameservers,
        ], 'Daemon applyNameservers failed');
    }

    public function nsLookup(string $domain): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/dns/ns-lookup", [
            'domain' => $domain,
        ]);
        if (! $response->successful()) {
            return ['domain' => $domain, 'nameservers' => [], 'a_records' => []];
        }

        return $response->json() ?? ['domain' => $domain, 'nameservers' => [], 'a_records' => []];
    }

    public function cloudflareDNSUpsert(string $apiToken, string $domain, string $type, string $name, string $value, int $ttl = 1, bool $proxied = false): array
    {
        return $this->post('/api/dns/cloudflare/upsert', [
            'api_token' => $apiToken,
            'domain' => $domain,
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'ttl' => $ttl,
            'proxied' => $proxied,
        ], 'Daemon cloudflareDNSUpsert failed');
    }

    public function cloudflareDNSDelete(string $apiToken, string $domain, string $type, string $name): array
    {
        return $this->post('/api/dns/cloudflare/delete', [
            'api_token' => $apiToken,
            'domain' => $domain,
            'type' => $type,
            'name' => $name,
        ], 'Daemon cloudflareDNSDelete failed');
    }

    public function cloudflareZoneID(string $apiToken, string $domain): array
    {
        return $this->post('/api/dns/cloudflare/zone-id', [
            'api_token' => $apiToken,
            'domain' => $domain,
        ], 'Daemon cloudflareZoneID failed');
    }

    public function sslIssue(string $domain, string $mode, string $cfToken = '', string $webroot = ''): array
    {
        return $this->post('/api/ssl/issue', [
            'domain' => $domain,
            'mode' => $mode,
            'cf_token' => $cfToken,
            'webroot' => $webroot,
        ], 'Daemon sslIssue failed');
    }

    public function sslStatus(string $domain): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/ssl/status", [
            'domain' => $domain,
        ]);
        if (! $response->successful()) {
            return ['domain' => $domain, 'issued' => false];
        }

        return $response->json() ?? ['domain' => $domain, 'issued' => false];
    }

    public function operations(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/operations");

        if (! $response->successful()) {
            Log::warning('Daemon operations failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon operations failed.');
        }

        return $response->json() ?? [];
    }

    public function runtimeStatus(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/runtime/status");

        if (! $response->successful()) {
            Log::warning('Daemon runtime status failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Daemon runtime status failed.');
        }

        return $response->json() ?? [];
    }

    // ===========================
    // File Manager Methods
    // ===========================

    public function fileList(?string $domain, string $path = '/'): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/list", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (! $response->successful()) {
            Log::warning('Daemon fileList failed', ['domain' => $domain, 'path' => $path, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileList failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function fileRead(?string $domain, string $path): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/read", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (! $response->successful()) {
            Log::warning('Daemon fileRead failed', ['domain' => $domain, 'path' => $path, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileRead failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function fileWrite(?string $domain, string $path, string $content): array
    {
        return $this->post('/api/files/write', ['domain' => $domain, 'path' => $path, 'content' => $content], 'Daemon fileWrite failed');
    }

    public function fileMkdir(?string $domain, string $path): array
    {
        return $this->post('/api/files/mkdir', ['domain' => $domain, 'path' => $path], 'Daemon fileMkdir failed');
    }

    public function fileDelete(?string $domain, string $path): array
    {
        return $this->post('/api/files/delete', ['domain' => $domain, 'path' => $path], 'Daemon fileDelete failed');
    }

    public function fileRename(?string $domain, string $oldPath, string $newPath): array
    {
        return $this->post('/api/files/rename', ['domain' => $domain, 'old_path' => $oldPath, 'new_path' => $newPath], 'Daemon fileRename failed');
    }

    public function fileExtract(?string $domain, string $path): array
    {
        return $this->post('/api/files/extract', ['domain' => $domain, 'path' => $path], 'Daemon fileExtract failed');
    }

    public function fileSearch(?string $domain, string $path, string $query, bool $includeContent = true, bool $caseSensitive = false): array
    {
        return $this->post('/api/files/search', [
            'domain' => $domain,
            'path' => $path,
            'query' => $query,
            'include_content' => $includeContent,
            'case_sensitive' => $caseSensitive,
            'max_results' => 200,
        ], 'Daemon fileSearch failed');
    }

    public function fileUpload(?string $domain, string $destDir, \Illuminate\Http\UploadedFile $file): array
    {
        $response = $this->http()
            ->attach('file', $file->getContent(), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/api/files/upload", [
                'domain' => $domain,
                'path' => $destDir,
            ]);
        if (! $response->successful()) {
            Log::warning('Daemon fileUpload failed', ['domain' => $domain, 'body' => $response->body()]);
            throw new \RuntimeException('Daemon fileUpload failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function fileDownloadProxy(?string $domain, string $path): \Illuminate\Http\Client\Response
    {
        $response = $this->http()->get("{$this->baseUrl}/api/files/download", [
            'domain' => $domain,
            'path' => $path,
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('Daemon fileDownload failed: '.$response->body());
        }

        return $response;
    }

    private function post(string $path, array $payload, string $errorPrefix): array
    {
        $response = $this->http()->post("{$this->baseUrl}{$path}", $payload);

        if (! $response->successful()) {
            Log::warning($errorPrefix, [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException($errorPrefix.': '.trim($response->body()));
        }

        return $response->json() ?? [];
    }

    // ── Docker App ────────────────────────────────────────────────────────────

    public function dockerAppCreate(string $tenantCode, string $slug, string $composeYaml): array
    {
        return $this->post('/api/docker-app/create', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
            'compose_yaml' => $composeYaml,
        ], 'Daemon dockerAppCreate failed');
    }

    public function dockerAppUpdate(string $tenantCode, string $slug, string $composeYaml): array
    {
        return $this->post('/api/docker-app/update', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
            'compose_yaml' => $composeYaml,
        ], 'Daemon dockerAppUpdate failed');
    }

    public function dockerAppStart(string $tenantCode, string $slug): array
    {
        return $this->post('/api/docker-app/start', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
        ], 'Daemon dockerAppStart failed');
    }

    public function dockerAppStop(string $tenantCode, string $slug): array
    {
        return $this->post('/api/docker-app/stop', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
        ], 'Daemon dockerAppStop failed');
    }

    public function dockerAppRestart(string $tenantCode, string $slug): array
    {
        return $this->post('/api/docker-app/restart', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
        ], 'Daemon dockerAppRestart failed');
    }

    public function dockerAppDelete(string $tenantCode, string $slug): array
    {
        return $this->post('/api/docker-app/delete', [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
        ], 'Daemon dockerAppDelete failed');
    }

    public function dockerAppStatus(string $tenantCode, string $slug): array
    {
        $response = $this->http()->get("{$this->baseUrl}/api/docker-app/status", [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
        ]);

        if (! $response->successful()) {
            Log::warning('Daemon dockerAppStatus failed', [
                'tenant_code' => $tenantCode,
                'slug' => $slug,
                'status' => $response->status(),
            ]);

            return ['status' => 'error', 'services' => []];
        }

        return $response->json() ?? ['status' => 'unknown', 'services' => []];
    }

    public function dockerAppLogs(string $tenantCode, string $slug, int $tail = 150): string
    {
        $response = $this->http()->get("{$this->baseUrl}/api/docker-app/logs", [
            'tenant_code' => $tenantCode,
            'slug' => $slug,
            'tail' => $tail,
        ]);

        if (! $response->successful()) {
            return '';
        }

        return $response->json('logs') ?? '';
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function http()
    {
        $client = Http::timeout(15);

        if ($this->token) {
            $client = $client->withToken($this->token)->withHeaders([
                'X-XPanel-Token' => $this->token,
            ]);
        }

        return $client;
    }
}
