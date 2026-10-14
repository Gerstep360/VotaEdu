<?php

namespace App\Console\Commands;

use App\Models\Election;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class PrepareVotaEdu extends Command
{
    protected $signature = 'votaedu:prepare {--demo : Cargar demostración únicamente en una base vacía}';

    protected $description = 'Crear la base MySQL si no existe y ejecutar las migraciones sin borrar datos';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Este asistente está destinado al entorno local.');

            return self::FAILURE;
        }

        if (config('database.default') !== 'mysql') {
            $this->error('Configura DB_CONNECTION=mysql antes de preparar la plataforma.');

            return self::FAILURE;
        }

        $config = config('database.connections.mysql');
        $database = $config['database'];
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            $this->error('El nombre de la base solo puede contener letras, números y guion bajo.');

            return self::FAILURE;
        }

        try {
            $connection = new PDO(
                "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",
                $config['username'], $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $connection->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info('Conexión MySQL comprobada. Base disponible.');
        } catch (Throwable $exception) {
            $this->error('No se pudo preparar la base. Comprueba que MySQL esté iniciado, las credenciales sean correctas y el usuario tenga permiso para crear la base.');

            return self::FAILURE;
        }

        DB::purge('mysql');
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->option('demo')) {
            if (User::exists() || Election::exists()) {
                $this->warn('Se conservaron los datos existentes. No se cargó la demostración.');
            } else {
                DB::transaction(fn () => $this->call('db:seed', ['--force' => true]));
                $this->info('Datos ficticios de demostración cargados.');
            }
        }

        $this->info('VotaEdu está preparado.');

        return self::SUCCESS;
    }
}
