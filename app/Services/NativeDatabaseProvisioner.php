<?php

namespace App\Services;

use App\Models\ManagedDatabase;
use App\Models\ManagedDatabaseUser;

class NativeDatabaseProvisioner
{
    public function __construct(private ServerCommandRunner $commands) {}

    public function create(ManagedDatabase $database, string $password): void
    {
        if (! $this->applies()) {
            $database->update(['status' => 'staged']);

            return;
        }

        $this->run('database-create', $database->name, $database->username, null, $password);
        $database->update(['status' => 'active']);
    }

    public function addUser(ManagedDatabase $database, string $username, string $password): void
    {
        if ($this->applies()) {
            $this->run('database-user-add', $database->name, $username, null, $password);
        }
    }

    public function removeUser(ManagedDatabase $database, string $username): void
    {
        if ($this->applies()) {
            $this->run('database-user-remove', $database->name, $username);
        }
    }

    public function rotatePassword(ManagedDatabase $database, string $username, string $password): void
    {
        if ($this->applies()) {
            $this->run('database-user-password', $database->name, $username, null, $password);
        }
    }

    /** @param array<int, string> $privileges */
    public function permissions(ManagedDatabase $database, string $username, array $privileges): void
    {
        if ($this->applies()) {
            $this->run('database-user-permissions', $database->name, $username, implode(',', $privileges));
        }
    }

    public function remove(ManagedDatabase $database): void
    {
        if (! $this->applies()) {
            return;
        }

        $database->loadMissing('dbUsers');
        $database->dbUsers
            ->where('username', '!=', $database->username)
            ->each(fn (ManagedDatabaseUser $user) => $this->removeUser($database, $user->username));
        $this->run('database-remove', $database->name, $database->username);
    }

    private function run(string $action, string $database, string $username, ?string $argument = null, ?string $input = null): void
    {
        $command = ['sudo', '-n', (string) config('xpanel.native_hosting.site_helper'), $action, strtolower($database), strtolower($username)];
        if ($argument !== null) {
            $command[] = $argument;
        }
        $this->commands->run($command, $input === null ? null : $input."\n");
    }

    private function applies(): bool
    {
        return (bool) config('xpanel.native_hosting.apply_system_changes', false);
    }
}
