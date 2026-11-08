<?php

namespace App\Services;

use RuntimeException;

class DatabaseEngineManager
{
    public const ENGINES = [
        'mariadb' => ['label' => 'MariaDB', 'description' => 'Motor relacional predeterminado para todas las instancias.', 'port' => 3306],
        'postgresql' => ['label' => 'PostgreSQL', 'description' => 'Motor relacional opcional compartido por el nodo.', 'port' => 5432],
        'mongodb' => ['label' => 'MongoDB', 'description' => 'Motor documental NoSQL opcional compartido por el nodo.', 'port' => 27017],
        'dbgate' => ['label' => 'DbGate', 'description' => 'Binario común; cada Host ejecuta una sesión aislada.', 'port' => null],
    ];

    public function __construct(private readonly ServerCommandRunner $commands) {}

    /** @return array<string, array<string, mixed>> */
    public function statuses(): array
    {
        $result = [];
        foreach (self::ENGINES as $slug => $definition) {
            try {
                $output = $this->commands->run(['sudo', '-n', '/opt/xpanel-vps/scripts/xpanel-instance-helper.sh', 'database-engine-status', $slug], timeout: 60);
                $metadata = [];
                foreach (preg_split('/\R/', $output) ?: [] as $line) {
                    [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
                    if ($key !== '' && $value !== null) $metadata[$key] = $value;
                }
                $result[$slug] = $definition + ['installed' => ($metadata['installed'] ?? 'false') === 'true', 'enabled' => ($metadata['enabled'] ?? 'true') === 'true', 'version' => $metadata['version'] ?? null, 'error' => null];
            } catch (\Throwable $exception) {
                $result[$slug] = $definition + ['installed' => false, 'enabled' => false, 'version' => null, 'error' => $exception->getMessage()];
            }
        }
        return $result;
    }

    public function install(string $engine): void
    {
        if (! isset(self::ENGINES[$engine])) throw new RuntimeException('Motor no compatible.');
        $this->commands->run(['sudo', '-n', '/opt/xpanel-vps/scripts/xpanel-instance-helper.sh', 'database-engine-install', $engine], timeout: 1800);
    }

    public function setDbGateEnabled(bool $enabled): void
    {
        $this->commands->run(['sudo', '-n', '/opt/xpanel-vps/scripts/xpanel-instance-helper.sh', 'dbgate-set-enabled', $enabled ? 'true' : 'false'], timeout: 120);
    }
}
