<?php

namespace App\Services;

use App\Models\HostBrokerResource;
use App\Models\HostInstance;
use PDO;
use RuntimeException;

class HostBrokerActionPolicy
{
    /** @param array<int, string> $arguments */
    public function authorize(HostInstance $instance, string $action, array $arguments): void
    {
        $expectedInstanceRoot = rtrim(config('xpanel.host_instances.root'), '/').'/'.$instance->uuid;
        if ($instance->instance_root !== $expectedInstanceRoot || $instance->database_path !== $expectedInstanceRoot.'/database/database.sqlite') {
            throw new RuntimeException('Las rutas registradas de la instancia no son válidas.');
        }
        if ($instance->status !== 'active' || $instance->tenant?->status !== 'active') {
            throw new RuntimeException('La instancia o su cliente están suspendidos.');
        }

        match ($action) {
            'apply' => $this->site($instance, $arguments, 12, $action),
            'remove', 'site-restart' => $this->site($instance, $arguments, 6, $action),
            'ssl-issue', 'ssl-wildcard-issue', 'ssl-delete' => $this->certificate($instance, $arguments, $action),
            'ssl-inspect' => $this->certificateInspection($instance, $arguments),
            'site-diagnose' => $this->diagnostic($instance, $arguments),
            'database-create', 'database-password', 'database-remove' => $this->database($instance, $arguments),
            'php-profile-remove' => $this->phpProfileRemove($instance, $arguments),
            'panel-domain-set' => $this->panelDomain($instance, $arguments),
            'engine-status' => $this->engineStatus($arguments),
            default => throw new RuntimeException('La acción no está permitida por el broker.'),
        };
    }

    /** @param array<int, string> $arguments */
    private function engineStatus(array $arguments): void
    {
        if (count($arguments) !== 1 || ! in_array($arguments[0], ['nginx', 'apache', 'openlitespeed'], true)) {
            throw new RuntimeException('Motor web solicitado no válido.');
        }
    }

    /** @param array<int, string> $arguments */
    private function panelDomain(HostInstance $instance, array $arguments): void
    {
        if (count($arguments) !== 1 || ! $this->domain($arguments[0]) || ! $instance->hosting_account_id) {
            throw new RuntimeException('El dominio solicitado para el panel no es válido.');
        }

        $domain = $arguments[0];
        if (HostInstance::query()->whereKeyNot($instance->id)->where('panel_domain', $domain)->exists()
            || \App\Models\HostingAccount::query()->whereKeyNot($instance->hosting_account_id)->where('custom_panel_domain', $domain)->exists()
            || HostBrokerResource::query()->where('type', 'site-domain')->where('name', $domain)->exists()) {
            throw new RuntimeException('El dominio ya pertenece a otro panel o sitio del servidor.');
        }
    }

    /** @param array<int, string> $arguments */
    private function diagnostic(HostInstance $instance, array $arguments): void
    {
        if (count($arguments) !== 8) {
            throw new RuntimeException('Argumentos de diagnóstico inválidos.');
        }
        [$domain, $documentRoot, $systemUser, $engine, $type, $php, $expectedIp, $runtimePort] = $arguments;
        $site = $this->row($instance, 'SELECT domain, document_root, system_user, web_server, type, php_version, runtime_port FROM sites WHERE domain = :domain', ['domain' => $domain]);
        if (! $site || $site['document_root'] !== $documentRoot || $site['system_user'] !== $systemUser
            || $site['web_server'] !== $engine || $site['type'] !== $type || $site['php_version'] !== $php
            || $runtimePort !== ($type === 'node' ? (string) $site['runtime_port'] : '0')
            || ($expectedIp !== '-' && filter_var($expectedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false)) {
            throw new RuntimeException('El diagnóstico no coincide con el sitio de la instancia.');
        }
    }

    /** @param array<int, string> $arguments */
    private function certificate(HostInstance $instance, array $arguments, string $action): void
    {
        if (count($arguments) !== 5) {
            throw new RuntimeException('Argumentos de certificado inválidos.');
        }
        [$domain, $engine, $webRoot, $email, $systemUser] = $arguments;
        if (! $this->domain($domain) || ! in_array($engine, ['nginx', 'apache', 'openlitespeed'], true)) {
            throw new RuntimeException('El certificado solicitado no pertenece a un sitio válido.');
        }
        if ($engine === 'openlitespeed') {
            throw new RuntimeException('OpenLiteSpeed no está aislado para este hosting.');
        }
        $site = $this->row($instance, 'SELECT domain, web_server, document_root, public_path, system_user, wildcard_domain FROM sites WHERE domain = :domain', ['domain' => $domain]);
        if (! $site || $site['web_server'] !== $engine || $site['system_user'] !== $systemUser) {
            throw new RuntimeException('El certificado no coincide con el registro de la instancia.');
        }
        $public = trim((string) ($site['public_path'] ?? ''), '/');
        $expectedWebRoot = $public === '' ? $site['document_root'] : $site['document_root'].'/'.$public;
        if (! in_array($webRoot, [$site['document_root'], $expectedWebRoot], true)
            || ($action === 'ssl-delete' ? $email !== '-' : filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            || ($action === 'ssl-wildcard-issue' && ! (bool) $site['wildcard_domain'])) {
            throw new RuntimeException('La solicitud SSL excede los límites del sitio.');
        }
        $this->assertDomainIsNotOwnedByAnotherInstance($instance, $domain);
    }

    /** @param array<int, string> $arguments */
    private function certificateInspection(HostInstance $instance, array $arguments): void
    {
        if (count($arguments) !== 1 || ! $this->domain($arguments[0])) {
            throw new RuntimeException('Argumentos de inspección SSL inválidos.');
        }
        $site = $this->row($instance, 'SELECT domain FROM sites WHERE domain = :domain', ['domain' => $arguments[0]]);
        if (! $site) {
            throw new RuntimeException('El certificado no pertenece a la instancia.');
        }
        $this->assertDomainIsNotOwnedByAnotherInstance($instance, $arguments[0]);
    }

    /** @param array<int, string> $arguments */
    private function site(HostInstance $instance, array $arguments, int $count, string $action): void
    {
        if (count($arguments) !== $count) {
            throw new RuntimeException('Argumentos de sitio inválidos.');
        }
        [$domain, $engine, $type, $php, $documentRoot, $systemUser] = $arguments;
        $expectedRootPrefix = '/home/'.$instance->system_user.'/public_html/';
        $expectedUserPrefix = 'xps'.substr(str_replace('-', '', $instance->uuid), 0, 6);
        if (! $this->domain($domain) || ! in_array($engine, ['nginx', 'apache', 'openlitespeed'], true)
            || ! in_array($type, ['php', 'static', 'node'], true) || ! preg_match('/^8\.[2-4]$/', $php)
            || ! str_starts_with($documentRoot, $expectedRootPrefix) || str_contains($documentRoot, '..') || ! str_starts_with($systemUser, $expectedUserPrefix)
            || ! preg_match('/^xps[a-z0-9]{15,29}$/', $systemUser)) {
            throw new RuntimeException('El sitio no pertenece al espacio de la instancia.');
        }
        if ($engine === 'openlitespeed') {
            throw new RuntimeException('OpenLiteSpeed no está aislado para este hosting.');
        }

        if ($action === 'apply' && $engine !== 'nginx') {
            if ($engine !== 'apache' || $instance->access_port < 10000 || $instance->access_port > 19999) {
                throw new RuntimeException('El motor web no está aislado para este hosting.');
            }
            $apache = collect(app(NativePackageManager::class)->catalog())->firstWhere('slug', 'apache');
            if (! $apache || ! $apache['installed'] || ! $apache['enabled_for_clients']) {
                throw new RuntimeException('Apache no está habilitado para este hosting.');
            }
        }

        $site = $this->row($instance, 'SELECT id, domain, web_server, type, php_version, php_profile_id, document_root, system_user, public_path, node_version, runtime_port, wildcard_domain, status FROM sites WHERE domain = :domain', ['domain' => $domain]);
        if (! $site || ($action !== 'remove' && ($site['web_server'] !== $engine || $site['type'] !== $type || $site['php_version'] !== $php))
            || $site['document_root'] !== $documentRoot || $site['system_user'] !== $systemUser) {
            throw new RuntimeException('El sitio solicitado no coincide con el registro de la instancia.');
        }
        if ($count === 12) {
            $public = trim((string) ($site['public_path'] ?? ''), '/');
            $webRoot = $public === '' ? $documentRoot : $documentRoot.'/'.$public;
            if ($arguments[6] !== $webRoot) {
                throw new RuntimeException('El web root solicitado no pertenece al sitio.');
            }
            $expectedNodeVersion = $type === 'node' ? (string) $site['node_version'] : '-';
            $expectedPort = $type === 'node' ? (string) $site['runtime_port'] : '0';
            if ($arguments[7] !== $expectedNodeVersion || $arguments[8] !== $expectedPort || $arguments[9] !== $site['status']
                || ! in_array($site['status'], ['active', 'suspended'], true)
                || ($type === 'node' && (! in_array($expectedNodeVersion, ['20', '22', '24'], true)
                    || ! preg_match('/^[2-4][0-9]{4}$/', $expectedPort)))) {
                throw new RuntimeException('El runtime solicitado no coincide con el sitio.');
            }
            $profileKey = $arguments[10];
            $extensions = $arguments[11];
            if ($site['php_profile_id'] === null) {
                if ($profileKey !== 'system' || $extensions !== '-') {
                    throw new RuntimeException('El sitio no utiliza un perfil PHP aislado.');
                }
            } else {
                $profile = $this->row($instance, 'SELECT id, php_version, extensions FROM php_profiles WHERE id = :id', ['id' => $site['php_profile_id']]);
                $expectedKey = 'i'.substr(str_replace('-', '', $instance->uuid), 0, 12).'-p'.$site['php_profile_id'];
                $selected = json_decode((string) ($profile['extensions'] ?? '[]'), true);
                $selected = is_array($selected) ? array_values(array_unique(array_map('strval', $selected))) : [];
                sort($selected);
                $expectedExtensions = $selected === [] ? '-' : implode(',', $selected);
                if (! $profile || $profile['php_version'] !== $php || $profileKey !== $expectedKey || $extensions !== $expectedExtensions) {
                    throw new RuntimeException('El perfil PHP no coincide con el sitio de la instancia.');
                }
            }
            $this->assertDomainIsNotOwnedByAnotherInstance($instance, $domain);
        }
        $this->claimDomain($instance, $domain);
        if ($count === 12) {
            foreach ($this->siteAliases($instance, (int) $site['id']) as $alias) {
                $this->claimDomain($instance, $alias);
            }
        }
        if ($count === 12 && $type === 'node') {
            $portClaim = HostBrokerResource::firstOrCreate(
                ['type' => 'runtime-port', 'name' => (string) $site['runtime_port']],
                ['host_instance_id' => $instance->id],
            );
            if ($portClaim->host_instance_id !== $instance->id) {
                throw new RuntimeException('El puerto interno está reservado por otra instancia.');
            }
        }
        if ($count === 12 && (bool) $site['wildcard_domain']) {
            $wildcard = '*.'.$domain;
            $conflict = HostBrokerResource::query()
                ->where('type', 'site-domain')
                ->where('host_instance_id', '!=', $instance->id)
                ->where(function ($query) use ($domain): void {
                    $query->where('name', $domain)->orWhere('name', 'like', '%.'.$domain);
                })->exists();
            if ($conflict) {
                throw new RuntimeException('El wildcard contiene dominios reservados por otra instancia.');
            }
            $wildcardClaim = HostBrokerResource::firstOrCreate(
                ['type' => 'site-domain', 'name' => $wildcard],
                ['host_instance_id' => $instance->id],
            );
            if ($wildcardClaim->host_instance_id !== $instance->id) {
                throw new RuntimeException('El dominio wildcard está reservado por otra instancia.');
            }
        }
    }

    /** @param array<int, string> $arguments */
    private function phpProfileRemove(HostInstance $instance, array $arguments): void
    {
        $prefix = 'i'.substr(str_replace('-', '', $instance->uuid), 0, 12).'-p';
        if (count($arguments) !== 1 || ! str_starts_with($arguments[0], $prefix)) {
            throw new RuntimeException('El perfil PHP solicitado no pertenece a la instancia.');
        }
        $id = substr($arguments[0], strlen($prefix));
        if (! ctype_digit($id) || ! $this->row($instance, 'SELECT id FROM php_profiles WHERE id = :id', ['id' => $id])
            || $this->row($instance, 'SELECT id FROM sites WHERE php_profile_id = :id LIMIT 1', ['id' => $id])) {
            throw new RuntimeException('El perfil PHP no se puede retirar.');
        }
    }

    public function complete(HostInstance $instance, string $action, array $arguments): void
    {
        if ($action === 'remove' && isset($arguments[0])) {
            $removedSite = $this->row($instance, 'SELECT id, runtime_port FROM sites WHERE domain = :domain', ['domain' => $arguments[0]]);
            $names = [$arguments[0], '*.'.$arguments[0]];
            if ($removedSite) {
                $names = array_merge($names, $this->siteAliases($instance, (int) $removedSite['id']));
            }
            HostBrokerResource::query()
                ->where('host_instance_id', $instance->id)
                ->where('type', 'site-domain')
                ->whereIn('name', $names)
                ->delete();
            if ($removedSite && $removedSite['runtime_port'] !== null) {
                HostBrokerResource::query()
                    ->where('host_instance_id', $instance->id)
                    ->where('type', 'runtime-port')
                    ->where('name', (string) $removedSite['runtime_port'])
                    ->delete();
            }
        }
        if ($action === 'database-remove' && count($arguments) === 2) {
            HostBrokerResource::query()
                ->where('host_instance_id', $instance->id)
                ->where(function ($query) use ($arguments): void {
                    $query->where(function ($resource) use ($arguments): void {
                        $resource->where('type', 'database')->where('name', $arguments[0]);
                    })->orWhere(function ($resource) use ($arguments): void {
                        $resource->where('type', 'database-user')->where('name', $arguments[1]);
                    });
                })->delete();
        }
    }

    /** @param array<int, string> $arguments */
    private function database(HostInstance $instance, array $arguments): void
    {
        $prefix = 'xp_'.substr(str_replace('-', '', $instance->uuid), 0, 6).'_';
        if (count($arguments) !== 2 || ! preg_match('/^[a-z0-9_]{1,64}$/', $arguments[0]) || ! preg_match('/^[a-z0-9_]{1,32}$/', $arguments[1])
            || ! str_starts_with($arguments[0], $prefix) || ! str_starts_with($arguments[1], $prefix)) {
            throw new RuntimeException('Identificadores de base de datos inválidos.');
        }
        $row = $this->row($instance, 'SELECT id FROM site_databases WHERE name = :name AND username = :username', ['name' => $arguments[0], 'username' => $arguments[1]]);
        if (! $row) {
            throw new RuntimeException('La base de datos no pertenece a la instancia.');
        }
        foreach (['database' => $arguments[0], 'database-user' => $arguments[1]] as $type => $name) {
            $claim = HostBrokerResource::firstOrCreate(
                ['type' => $type, 'name' => $name],
                ['host_instance_id' => $instance->id],
            );
            if ($claim->host_instance_id !== $instance->id) {
                throw new RuntimeException('El recurso MariaDB está reservado por otra instancia.');
            }
        }
    }

    private function row(HostInstance $instance, string $query, array $parameters): array|false
    {
        if (! is_file($instance->database_path) || is_link($instance->database_path)) {
            throw new RuntimeException('La base SQLite de la instancia no está disponible.');
        }
        $pdo = new PDO('sqlite:'.$instance->database_path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA query_only = ON');
        $statement = $pdo->prepare($query);
        $statement->execute($parameters);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array<int, string> */
    private function siteAliases(HostInstance $instance, int $siteId): array
    {
        try {
            $pdo = new PDO('sqlite:'.$instance->database_path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA query_only = ON');
            $statement = $pdo->prepare("SELECT domain FROM domains WHERE site_id = :site_id AND type = 'alias'");
            $statement->execute(['site_id' => $siteId]);

            return array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'domain');
        } catch (\PDOException) {
            return [];
        }
    }

    private function claimDomain(HostInstance $instance, string $domain): void
    {
        if (! $this->domain($domain)) {
            throw new RuntimeException('La instancia contiene un alias inválido.');
        }
        $wildcards = HostBrokerResource::query()
            ->where('type', 'site-domain')->where('name', 'like', '*.%')
            ->where('host_instance_id', '!=', $instance->id)->pluck('name');
        foreach ($wildcards as $wildcard) {
            if (str_ends_with($domain, substr($wildcard, 1))) {
                throw new RuntimeException('El dominio está cubierto por el wildcard de otra instancia.');
            }
        }
        $claim = HostBrokerResource::firstOrCreate(
            ['type' => 'site-domain', 'name' => $domain],
            ['host_instance_id' => $instance->id],
        );
        if ($claim->host_instance_id !== $instance->id) {
            throw new RuntimeException('El dominio está reservado por otra instancia.');
        }
    }

    private function domain(string $domain): bool
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false && $domain === strtolower($domain);
    }

    private function assertDomainIsNotOwnedByAnotherInstance(HostInstance $instance, string $domain): void
    {
        foreach (HostInstance::query()->whereKeyNot($instance->id)->whereIn('status', ['active', 'suspended'])->get() as $other) {
            if (! is_file($other->database_path) || is_link($other->database_path)) {
                continue;
            }
            try {
                if ($this->row($other, 'SELECT id FROM sites WHERE domain = :domain', ['domain' => $domain])) {
                    throw new RuntimeException('El dominio ya pertenece a otra instancia.');
                }
            } catch (\PDOException) {
                continue;
            }
        }
    }
}
