<?php

namespace App\Http\Controllers;

use App\Services\NetworkAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NetworkAccessController extends Controller
{
    public function __construct(
        protected NetworkAccessService $networkService
    ) {}

    /**
     * Muestra la pantalla interactiva con el código QR y datos de conexión móvil.
     */
    public function index(Request $request)
    {
        $port = (int) ($request->getPort() ?: 8000);
        $availableIps = $this->networkService->getAvailableIps();

        // Determinar qué IP usar para el QR
        $selectedIp = $request->query('ip');
        if (! $selectedIp || ! filter_var($selectedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Si el cliente ya está accediendo por una IP de red (no 127.0.0.1 ni localhost), usar esa misma IP
            $clientHost = $request->getHost();
            if (filter_var($clientHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && ! str_starts_with($clientHost, '127.')) {
                $selectedIp = $clientHost;
            } else {
                $selectedIp = $this->networkService->getPrimaryIp();
            }
        }

        $accessUrl = $this->networkService->buildUrl($selectedIp, $port);
        $loginUrl = $this->networkService->buildUrl($selectedIp, $port, 'login');
        $qrSvg = $this->networkService->generateQrSvg($accessUrl);
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);

        // Guardar archivo público para descargas
        $this->networkService->savePublicQrFile($accessUrl);

        return view('network.connect', [
            'selectedIp' => $selectedIp,
            'port' => $port,
            'accessUrl' => $accessUrl,
            'loginUrl' => $loginUrl,
            'qrSvg' => $qrSvg,
            'qrDataUri' => $qrDataUri,
            'availableIps' => $availableIps,
            'isLocalhost' => in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1']),
        ]);
    }

    /**
     * Redirigir /qr a la pantalla de conexión.
     */
    public function qrRedirect()
    {
        return redirect()->route('network.connect');
    }

    /**
     * Descargar el SVG del código QR.
     */
    public function downloadSvg(Request $request)
    {
        $port = (int) ($request->getPort() ?: 8000);
        $selectedIp = $request->query('ip') ?: $this->networkService->getPrimaryIp();
        $accessUrl = $this->networkService->buildUrl($selectedIp, $port);
        $svg = $this->networkService->generateQrSvg($accessUrl);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-votaedu.svg"',
        ]);
    }
}
