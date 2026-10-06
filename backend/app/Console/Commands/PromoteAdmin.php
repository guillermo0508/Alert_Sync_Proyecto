<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote {email} {--revoke : Quita el rol de administrador en vez de otorgarlo}';

    protected $description = 'Otorga o quita el rol de administrador a un usuario existente por correo';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$email}.");

            return self::FAILURE;
        }

        $user->role = $this->option('revoke') ? 'user' : 'admin';
        $user->save();

        $this->info($this->option('revoke')
            ? "Se quitó el rol de administrador a {$email}."
            : "{$email} ahora es administrador.");

        return self::SUCCESS;
    }
}
