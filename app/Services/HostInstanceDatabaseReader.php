<?php

namespace App\Services;

use App\Models\HostInstance;
use PDO;
use RuntimeException;

class HostInstanceDatabaseReader
{
    private const QUERIES = [
        'site' => 'SELECT id, domain, web_server, type, php_version, php_profile_id, document_root, system_user, public_path, node_version, runtime_port, wildcard_domain, status FROM sites WHERE domain = :first LIMIT 1',
        'profile' => 'SELECT id, php_version, extensions FROM php_profiles WHERE id = :first LIMIT 1',
        'profile-site' => 'SELECT id FROM sites WHERE php_profile_id = :first LIMIT 1',
        'database' => 'SELECT id FROM site_databases WHERE name = :first AND username = :second LIMIT 1',
        'aliases' => "SELECT domain FROM domains WHERE site_id = :first AND type = 'alias'",
        'engine' => 'SELECT 1 FROM sites WHERE web_server = :first LIMIT 1',
    ];

    public function query(HostInstance $instance, string $kind, string $first, string $second = ''): array|null
    {
        $root = rtrim((string) config('xpanel.host_instances.root'), '/').'/'.$instance->uuid;
        if ($instance->instance_root !== $root || $instance->database_path !== $root.'/database/database.sqlite') {
            throw new RuntimeException('Las rutas registradas de la instancia no son válidas.');
        }
        self::validate($kind, $first, $second);

        if (! config('xpanel.native_hosting.apply_system_changes')) {
            return self::inspectPath($instance->database_path, $kind, $first, $second);
        }

        $output = app(ServerCommandRunner::class)->run([
            'sudo', '-n', (string) config('xpanel.host_instances.broker_helper'), 'inspect',
            $instance->uuid, $instance->system_user, $root, $kind, $first, $second,
        ], timeout: 15);

        try {
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('La inspección SQLite devolvió una respuesta inválida.', 0, $exception);
        }
        if ($result !== null && ! is_array($result)) {
            throw new RuntimeException('La inspección SQLite devolvió una respuesta inválida.');
        }

        return $result;
    }

    public static function inspectPath(string $path, string $kind, string $first, string $second = ''): array|null
    {
        self::validate($kind, $first, $second);
        if (! is_file($path) || is_link($path)) {
            throw new RuntimeException('La base SQLite de la instancia no está disponible.');
        }

        $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA query_only = ON');
        try {
            $statement = $pdo->prepare(self::QUERIES[$kind]);
            $parameters = ['first' => $first];
            if ($kind === 'database') {
                $parameters['second'] = $second;
            }
            $statement->execute($parameters);

            return $kind === 'aliases' ? $statement->fetchAll(PDO::FETCH_ASSOC) : ($statement->fetch(PDO::FETCH_ASSOC) ?: null);
        } catch (\PDOException $exception) {
            if ($kind === 'aliases' && str_contains($exception->getMessage(), 'no such table: domains')) {
                return [];
            }
            throw $exception;
        }
    }

    private static function validate(string $kind, string $first, string $second): void
    {
        if (! array_key_exists($kind, self::QUERIES)
            || strlen($first) > 253 || strlen($second) > 64
            || ! preg_match('/^[a-zA-Z0-9_.-]+$/D', $first)
            || ($kind === 'database' ? ! preg_match('/^[a-zA-Z0-9_]+$/D', $second) : $second !== '')) {
            throw new RuntimeException('Consulta SQLite no permitida.');
        }
    }
}
