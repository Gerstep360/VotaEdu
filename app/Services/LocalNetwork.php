<?php

namespace App\Services;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class LocalNetwork
{
    public function address(): ?string
    {
        $command = match (PHP_OS_FAMILY) {
            'Windows' => ['powershell', '-NoProfile', '-NonInteractive', '-Command', '$route = Get-NetRoute -AddressFamily IPv4 -DestinationPrefix "0.0.0.0/0" | Sort-Object @{Expression={$_.RouteMetric + $_.InterfaceMetric}} | Select-Object -First 1; (Get-NetIPAddress -InterfaceIndex $route.InterfaceIndex -AddressFamily IPv4 | Where-Object { $_.AddressState -eq "Preferred" -and -not $_.SkipAsSource } | Select-Object -First 1).IPAddress'],
            'Linux' => ['ip', '-4', 'route', 'get', '1.1.1.1'],
            'Darwin' => ['sh', '-c', 'ipconfig getifaddr "$(route -n get default | awk \'/interface:/{print $2}\')"'],
            default => null,
        };
        if ($command !== null) {
            try {
                $process = new Process($command);
                $process->setTimeout(15)->run();
                $output = trim($process->getOutput());
                if (PHP_OS_FAMILY === 'Linux') {
                    preg_match('/\bsrc\s+(\d+\.\d+\.\d+\.\d+)/', $output, $matches);
                    $output = $matches[1] ?? '';
                }
                if ($this->isLocalAddress($output)) {
                    return $output;
                }
            } catch (RuntimeException $exception) {
                // Fall back to host addresses
            }
        }
        foreach (gethostbynamel(gethostname()) ?: [] as $address) {
            if ($this->isLocalAddress($address)) {
                return $address;
            }
        }

        return null;
    }

    public function isLocalAddress(?string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return str_starts_with($address, '10.')
            || str_starts_with($address, '192.168.')
            || (preg_match('/^172\.(\d+)\./', $address, $matches) === 1 && (int) $matches[1] >= 16 && (int) $matches[1] <= 31);
    }

    public function configureWindowsFirewall(int $port): bool
    {
        $process = new Process(['powershell', '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden', '-ExecutionPolicy', 'Bypass', '-File', resource_path('votaedu-network.ps1'), '-PhpPath', PHP_BINARY, '-Port', (string) $port]);
        $process->setTimeout(180);

        try {
            return $process->run() === 0;
        } catch (RuntimeException $exception) {
            return false;
        }
    }

    public function responds(string $url): bool
    {
        $context = stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true, 'follow_location' => 0]]);
        $stream = @fopen($url.'/up', 'r', false, $context);
        if ($stream === false) {
            return false;
        }
        $headers = stream_get_meta_data($stream)['wrapper_data'] ?? [];
        fclose($stream);

        return isset($headers[0]) && preg_match('/^HTTP\/\S+ 200\b/', $headers[0]) === 1;
    }
}
