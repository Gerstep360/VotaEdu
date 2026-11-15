<?php

namespace App\Console\Commands;

use App\Services\NetworkAccessService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

class VotaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vota
        {--port=8000 : Puerto de escucha para el servidor HTTP}
        {--host=0.0.0.0 : Interfaz de red (0.0.0.0 para permitir el acceso desde toda la red local)}
        {--ip= : Forzar una dirección IP específica para el código QR y los enlaces}
        {--no-browser : No abrir automáticamente el navegador web al iniciar}
        {--workers=4 : Cantidad de trabajadores de PHP para atender peticiones concurrentes de votantes}
        {--info : Solo mostrar la información de conexión y código QR sin iniciar el servidor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Iniciar el servidor distribuido de VotaEdu para acceso en red local (móviles y PCs) con código QR animado';

    /**
     * Execute the console command.
     */
    public function handle(NetworkAccessService $networkService): int
    {
        $port = (int) $this->option('port');
        $host = (string) $this->option('host');
        $forcedIp = $this->option('ip') ? (string) $this->option('ip') : null;
        $workers = max(1, (int) $this->option('workers'));

        // Configurar trabajadores concurrentes para el servidor CLI de PHP
        putenv("PHP_CLI_SERVER_WORKERS={$workers}");
        $_ENV['PHP_CLI_SERVER_WORKERS'] = (string) $workers;

        // Detectar la IP de la red local
        $primaryIp = $networkService->getPrimaryIp($forcedIp);
        $availableIps = $networkService->getAvailableIps();

        $networkUrl = $networkService->buildUrl($primaryIp, $port);
        $localUrl = "http://localhost:{$port}";
        $connectUrl = $networkService->buildUrl($primaryIp, $port, 'conectar');

        // Generar archivo SVG del código QR en public/
        try {
            $networkService->savePublicQrFile($networkUrl);
        } catch (Throwable) {
            // No crítico si falla escritura en disco
        }

        // Renderizar banner en terminal
        $this->output->newLine();
        $this->line('  <bg=blue;fg=white;options=bold>   VOTAEDU · SISTEMA DISTRIBUIDO DE VOTACIÓN ESCOLAR   </>');
        $this->line('  <fg=gray>U.E. Maestro Pitágoras II · Gestión Democrática</>');
        $this->output->newLine();

        $this->line("  <fg=bright-green;options=bold>● SERVIDOR ACTIVO EN RED LOCAL</> <fg=gray>(Escuchando en {$host}:{$port})</>");
        $this->output->newLine();

        $this->line('  <fg=cyan;options=bold>📱 ACCESO PARA TELÉFONOS MÓVILES Y OTRAS COMPUTADORAS:</>');
        $this->line("     ➜  <fg=bright-yellow;options=bold,underscore>{$networkUrl}</>");
        $this->output->newLine();

        $this->line('  <fg=bright-magenta;options=bold>✨ PANTALLA ANIMADA CON CÓDIGO QR Y MODO PROYECTOR:</>');
        $this->line("     ➜  <fg=bright-cyan;options=bold,underscore>{$connectUrl}</>");
        $this->output->newLine();

        $this->line('  <fg=gray>💻 Acceso directo en esta máquina: '."{$localUrl}</>");
        $this->line("  <fg=gray>⚡ Concurrencia optimizada: {$workers} trabajadores activos para votos simultáneos</>");

        if (count($availableIps) > 1) {
            $this->output->newLine();
            $this->line('  <fg=yellow>Otras interfaces de red detectadas:</>');
            foreach ($availableIps as $item) {
                if ($item['ip'] !== $primaryIp) {
                    $otherUrl = $networkService->buildUrl($item['ip'], $port);
                    $this->line("     • {$item['name']}: <fg=white>{$otherUrl}</>");
                }
            }
        }

        $this->output->newLine();
        $this->line('  <fg=gray>─────────────────────────────────────────────────────────────────</>');
        $this->line('  <fg=yellow;options=bold>CÓDIGO QR PARA ESCANEAR CON LA CÁMARA DEL CELULAR:</>');
        $this->output->newLine();

        $terminalQr = $networkService->renderTerminalQr($networkUrl);
        if ($terminalQr) {
            $this->line($terminalQr);
            $this->output->newLine();
        }

        $this->line('  <fg=gray>─────────────────────────────────────────────────────────────────</>');
        $this->line('  <fg=bright-green>✔ Dispositivos deben estar conectados a la misma red Wi-Fi o LAN.</>');
        $this->line('  <fg=gray>Presiona Ctrl+C para detener el servidor en cualquier momento.</>');
        $this->output->newLine();

        if ($this->option('info')) {
            $this->components->info('Archivo vectorial del QR generado en: '.public_path('qr-votaedu.svg'));

            return self::SUCCESS;
        }

        // Abrir navegador automáticamente a la pantalla de conexión animada
        if (! $this->option('no-browser')) {
            $this->openInBrowser($connectUrl);
        }

        // Delegar la ejecución al servidor de desarrollo de Laravel
        return $this->call('serve', [
            '--host' => $host,
            '--port' => (string) $port,
            '--no-reload' => true,
        ]);
    }

    /**
     * Abrir la URL en el navegador predeterminado del sistema operativo.
     */
    protected function openInBrowser(string $url): void
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen("start \"\" \"{$url}\"", 'r'));
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                pclose(popen("open \"{$url}\"", 'r'));
            } else {
                pclose(popen("xdg-open \"{$url}\" > /dev/null 2>&1 &", 'r'));
            }
        } catch (Throwable) {
            // Ignorar errores al abrir navegador
        }
    }
}
