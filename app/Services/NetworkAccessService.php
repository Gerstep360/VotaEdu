<?php

namespace App\Services;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

class NetworkAccessService
{
    /**
     * Obtener la lista de direcciones IPv4 disponibles en las interfaces de red.
     *
     * @return array<int, array{ip: string, name: string, is_private: bool}>
     */
    public function getAvailableIps(): array
    {
        $ips = [];
        $seen = [];

        // 1. Método preferido: net_get_interfaces() de PHP
        if (function_exists('net_get_interfaces')) {
            $interfaces = @net_get_interfaces() ?: [];
            foreach ($interfaces as $name => $info) {
                if (isset($info['up']) && ! $info['up']) {
                    continue;
                }
                if (empty($info['unicast'])) {
                    continue;
                }
                foreach ($info['unicast'] as $u) {
                    $family = $u['family'] ?? null;
                    $isIpv4 = ($family === 2) || (defined('AF_INET') && $family === AF_INET);
                    if ($isIpv4) {
                        $addr = trim($u['address'] ?? '');
                        if ($this->isValidLanIp($addr) && ! isset($seen[$addr])) {
                            $seen[$addr] = true;
                            $ips[] = [
                                'ip' => $addr,
                                'name' => $info['description'] ?? (is_string($name) ? $name : 'Interfaz de red'),
                                'is_private' => $this->isPrivateIp($addr),
                            ];
                        }
                    }
                }
            }
        }

        // 2. Fallback: Conexión de socket UDP saliente
        if (empty($ips)) {
            $socketIp = $this->detectIpViaSocket();
            if ($socketIp && ! isset($seen[$socketIp])) {
                $seen[$socketIp] = true;
                $ips[] = [
                    'ip' => $socketIp,
                    'name' => 'Conexión activa de red',
                    'is_private' => $this->isPrivateIp($socketIp),
                ];
            }
        }

        // 3. Fallback en Windows via PowerShell Get-NetIPAddress
        if (empty($ips) && PHP_OS_FAMILY === 'Windows') {
            $psOutput = @shell_exec('powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike \'127.*\' -and $_.IPAddress -notlike \'169.254.*\' }).IPAddress"');
            if ($psOutput) {
                foreach (preg_split('/\r\n|\r|\n/', trim($psOutput)) as $line) {
                    $addr = trim($line);
                    if ($this->isValidLanIp($addr) && ! isset($seen[$addr])) {
                        $seen[$addr] = true;
                        $ips[] = [
                            'ip' => $addr,
                            'name' => 'Adaptador Windows',
                            'is_private' => $this->isPrivateIp($addr),
                        ];
                    }
                }
            }
        }

        // 4. Fallback estándar hostname
        if (empty($ips)) {
            $hostname = @gethostname();
            if ($hostname) {
                $hostIp = @gethostbyname($hostname);
                if ($this->isValidLanIp($hostIp) && ! isset($seen[$hostIp])) {
                    $seen[$hostIp] = true;
                    $ips[] = [
                        'ip' => $hostIp,
                        'name' => 'Host local',
                        'is_private' => $this->isPrivateIp($hostIp),
                    ];
                }
            }
        }

        // Ordenar dando prioridad a redes privadas comunes de aula/hogar (192.168.x.x primero, luego 10.x.x.x, luego 172.x)
        usort($ips, function ($a, $b) {
            $scoreA = $this->ipPriorityScore($a['ip']);
            $scoreB = $this->ipPriorityScore($b['ip']);

            return $scoreB <=> $scoreA;
        });

        return $ips;
    }

    /**
     * Obtener la IP principal para distribuir el sistema.
     */
    public function getPrimaryIp(?string $forcedIp = null): string
    {
        if ($forcedIp && filter_var($forcedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $forcedIp;
        }

        $ips = $this->getAvailableIps();
        if (! empty($ips)) {
            return $ips[0]['ip'];
        }

        return '127.0.0.1';
    }

    /**
     * Construir la URL completa para el acceso móvil / distribuido.
     */
    public function buildUrl(string $ip, int $port = 8000, string $path = ''): string
    {
        $portSuffix = ($port === 80) ? '' : ":{$port}";
        $cleanPath = $path ? '/'.ltrim($path, '/') : '';

        return "http://{$ip}{$portSuffix}{$cleanPath}";
    }

    /**
     * Generar el código QR en formato SVG limpio.
     */
    public function generateQrSvg(string $content): string
    {
        try {
            $options = new QROptions([
                'outputInterface' => QRMarkupSVG::class,
                'outputBase64' => false,
                'svgAddXmlHeader' => false,
                'quietzoneSize' => 2,
            ]);

            return (new QRCode($options))->render($content);
        } catch (Throwable $e) {
            // En caso excepcional generar un SVG de respaldo legible
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text x="10" y="50">Error QR</text></svg>';
        }
    }

    /**
     * Generar el código QR como Data-URI para etiquetas <img>.
     */
    public function generateQrDataUri(string $content): string
    {
        $svg = $this->generateQrSvg($content);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Guardar una copia del SVG del código QR en el directorio público.
     */
    public function savePublicQrFile(string $content, string $filename = 'qr-votaedu.svg'): string
    {
        $svg = $this->generateQrSvg($content);
        $destination = public_path($filename);
        @file_put_contents($destination, $svg);

        return $destination;
    }

    /**
     * Renderizar el código QR en caracteres Unicode para terminal de comandos.
     */
    public function renderTerminalQr(string $content): string
    {
        try {
            $qrcode = new QRCode();
            $matrix = $qrcode->getQRMatrix($content);
            $size = $matrix->getSize();
            $margin = 2;
            $fullSize = $size + ($margin * 2);

            $isDark = function (int $x, int $y) use ($matrix, $size, $margin): bool {
                $mx = $x - $margin;
                $my = $y - $margin;
                if ($mx < 0 || $mx >= $size || $my < 0 || $my >= $size) {
                    return false;
                }

                return $matrix->check($mx, $my);
            };

            $lines = [];
            for ($y = 0; $y < $fullSize; $y += 2) {
                $line = '    ';
                for ($x = 0; $x < $fullSize; $x++) {
                    $topDark = $isDark($x, $y);
                    $bottomDark = ($y + 1 < $fullSize) ? $isDark($x, $y + 1) : false;

                    if ($topDark && $bottomDark) {
                        $line .= "\033[30;47m█\033[0m";
                    } elseif ($topDark && ! $bottomDark) {
                        $line .= "\033[30;47m▀\033[0m";
                    } elseif (! $topDark && $bottomDark) {
                        $line .= "\033[30;47m▄\033[0m";
                    } else {
                        $line .= "\033[30;47m \033[0m";
                    }
                }
                $lines[] = $line;
            }

            return implode(PHP_EOL, $lines);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Verifica si es una IP válida para red local (excluye loopback y link-local 169.254).
     */
    protected function isValidLanIp(?string $ip): bool
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.') || $ip === '0.0.0.0') {
            return false;
        }

        return true;
    }

    /**
     * Comprueba si la dirección pertenece a un rango privado.
     */
    protected function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    /**
     * Puntuación de prioridad para ordenar interfaces de red.
     */
    protected function ipPriorityScore(string $ip): int
    {
        if (str_starts_with($ip, '192.168.')) {
            return 100;
        }
        if (str_starts_with($ip, '10.')) {
            return 80;
        }
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
            return 60;
        }

        return 10;
    }

    /**
     * Intentar detectar la IP mediante socket saliente UDP.
     */
    protected function detectIpViaSocket(): ?string
    {
        $sock = @fsockopen('udp://8.8.8.8', 53, $errno, $errstr, 1);
        if ($sock) {
            $name = @stream_socket_get_name($sock, false);
            @fclose($sock);
            if ($name && str_contains($name, ':')) {
                $ip = explode(':', $name)[0];
                if ($this->isValidLanIp($ip)) {
                    return $ip;
                }
            }
        }

        return null;
    }
}
