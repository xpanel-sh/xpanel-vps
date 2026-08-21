<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapAdmin extends Command
{
    protected $signature = 'xpanel:admin-bootstrap
        {--name=Administrador}
        {--email=admin@xpanel.local}
        {--password-stdin : Lee la contraseña inicial desde la entrada estándar}
        {--status-only : Solo informa si falta el administrador}';

    protected $description = 'Crea el primer administrador de XPanel VPS sin exponer la contraseña en los argumentos';

    public function handle(): int
    {
        if ($this->option('status-only')) {
            $this->line(User::query()->where('role', 'admin')->exists() ? 'configured' : 'missing');

            return self::SUCCESS;
        }

        if (User::query()->where('role', 'admin')->exists()) {
            $this->error('Ya existe un administrador de XPanel VPS.');

            return self::FAILURE;
        }

        $password = $this->option('password-stdin')
            ? rtrim((string) stream_get_contents(STDIN), "\r\n")
            : '';
        $data = [
            'name' => (string) $this->option('name'),
            'email' => strtolower((string) $this->option('email')),
            'password' => $password,
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:16', 'max:128'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ]);
        $this->line('created');

        return self::SUCCESS;
    }
}
