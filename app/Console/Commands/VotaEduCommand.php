<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('votaedu {--ip= : IP local que verá el teléfono} {--port=8000 : Puerto de acceso} {--no-browser : No abrir automáticamente el navegador web} {--workers=4 : Trabajadores concurrentes}')]
#[Description('Alias de "php artisan vota" para iniciar VotaEdu en red local con código QR')]
class VotaEduCommand extends Command
{
    public function handle(): int
    {
        $parameters = [
            '--port' => $this->option('port') ?: '8000',
            '--workers' => $this->option('workers') ?: '4',
        ];

        if ($this->option('ip')) {
            $parameters['--ip'] = $this->option('ip');
        }

        if ($this->option('no-browser')) {
            $parameters['--no-browser'] = true;
        }

        return $this->call('vota', $parameters);
    }
}
