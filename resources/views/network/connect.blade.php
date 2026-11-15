<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172844">
    <title>Punto de Conexión Móvil · VotaEdu</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/bootstrap.min.css') }}">
    <style>
        :root {
            --brand-primary: #245ce0;
            --brand-navy: #0f172a;
            --brand-deep: #070d1e;
            --brand-accent: #38bdf8;
            --brand-emerald: #10b981;
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            background: radial-gradient(circle at 50% 10%, #1e293b 0%, #0f172a 50%, #070d1e 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Fondo animado con orbes difuminados */
        .ambient-glow {
            position: absolute;
            width: 650px;
            height: 650px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(36, 92, 224, 0.25) 0%, rgba(56, 189, 248, 0.08) 50%, transparent 70%);
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            filter: blur(40px);
            pointer-events: none;
            z-index: 0;
            animation: pulse-ambient 8s ease-in-out infinite alternate;
        }

        .ambient-glow-2 {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            bottom: -100px;
            right: 5%;
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
        }

        @keyframes pulse-ambient {
            0% { transform: translateX(-50%) scale(0.95); opacity: 0.7; }
            100% { transform: translateX(-50%) scale(1.15); opacity: 1; }
        }

        /* Barra de navegación superior */
        .connect-navbar {
            position: relative;
            z-index: 10;
            padding: 1.25rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.6);
        }

        .connect-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: #fff;
        }

        .brand-badge {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--brand-primary), #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 14px rgba(36, 92, 224, 0.4);
        }

        .brand-title {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .brand-title span {
            color: var(--brand-accent);
        }

        .brand-subtitle {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #f1f5f9;
            padding: 0.55rem 1.15rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-enter {
            background: linear-gradient(135deg, var(--brand-primary), #1d4ed8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            box-shadow: 0 4px 14px rgba(36, 92, 224, 0.3);
        }

        .btn-enter:hover {
            background: linear-gradient(135deg, #2d68f0, #2563eb);
            box-shadow: 0 6px 20px rgba(36, 92, 224, 0.5);
            color: #fff;
        }

        /* Contenedor principal */
        .connect-main {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }

        .connect-grid {
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 2.5rem;
            align-items: center;
        }

        /* Tarjeta del Código QR */
        .qr-card-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: card-appear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes card-appear {
            0% { opacity: 0; transform: translateY(30px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .qr-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 40px rgba(36, 92, 224, 0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 420px;
            width: 100%;
        }

        .qr-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 30px 70px -12px rgba(0, 0, 0, 0.7), 0 0 50px rgba(36, 92, 224, 0.35);
        }

        /* Retículas de visor de cámara */
        .qr-viewport {
            position: relative;
            background: #ffffff;
            padding: 1.25rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            width: 100%;
            aspect-ratio: 1;
        }

        .scanner-corner {
            position: absolute;
            width: 28px;
            height: 28px;
            border: 3.5px solid var(--brand-primary);
            pointer-events: none;
            z-index: 4;
            transition: all 0.3s ease;
        }

        .corner-tl { top: 6px; left: 6px; border-right: none; border-bottom: none; border-top-left-radius: 12px; }
        .corner-tr { top: 6px; right: 6px; border-left: none; border-bottom: none; border-top-right-radius: 12px; }
        .corner-bl { bottom: 6px; left: 6px; border-right: none; border-top: none; border-bottom-left-radius: 12px; }
        .corner-br { bottom: 6px; right: 6px; border-left: none; border-top: none; border-bottom-right-radius: 12px; }

        /* Rayo de escaneo láser animado */
        .laser-scan-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #245ce0 20%, #38bdf8 50%, #245ce0 80%, transparent);
            box-shadow: 0 0 15px 3px rgba(56, 189, 248, 0.8), 0 0 30px 6px rgba(36, 92, 224, 0.5);
            z-index: 5;
            animation: laser-sweep 3s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes laser-sweep {
            0% { top: 6%; opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { top: 92%; opacity: 0; }
        }

        .qr-viewport svg {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: 8px;
        }

        /* Insignia sobre el QR */
        .qr-card-header {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .qr-school-tag {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brand-primary);
            background: #eff6ff;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            display: inline-block;
            margin-bottom: 0.4rem;
        }

        .qr-card-title {
            color: var(--text-dark);
            font-size: 1.15rem;
            font-weight: 700;
        }

        /* Pill de estado bajo el QR */
        .qr-status-pill {
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1.1rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            background: var(--brand-emerald);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--brand-emerald);
            animation: status-pulse 2s infinite;
        }

        @keyframes status-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Columna de Información e Instrucciones */
        .info-panel {
            animation: info-appear 0.9s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes info-appear {
            0% { opacity: 0; transform: translateX(25px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        .info-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.28);
            color: var(--brand-accent);
            padding: 0.4rem 0.95rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .info-title {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 0.9rem;
        }

        .info-title span {
            background: linear-gradient(135deg, #60a5fa, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .info-desc {
            color: #94a3b8;
            font-size: 1.05rem;
            line-height: 1.55;
            margin-bottom: 1.75rem;
        }

        /* Caja de Enlace Rápido con Copiar */
        .url-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 0.85rem 1.15rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
            transition: all 0.2s ease;
        }

        .url-box:hover {
            border-color: rgba(56, 189, 248, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }

        .url-text-group {
            overflow: hidden;
        }

        .url-caption {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-bottom: 2px;
            font-weight: 700;
        }

        .url-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 1.15rem;
            font-weight: 700;
            color: #38bdf8;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            display: block;
        }

        .btn-copy {
            background: var(--brand-primary);
            color: #fff;
            border: none;
            padding: 0.6rem 1.15rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-copy:hover {
            background: #2b6bf5;
            transform: scale(1.03);
        }

        .btn-copy.copied {
            background: var(--brand-emerald);
        }

        /* Pasos de conexión */
        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 14px;
            padding: 1rem;
            transition: all 0.2s ease;
        }

        .step-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }

        .step-num {
            width: 34px;
            height: 34px;
            background: rgba(36, 92, 224, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #38bdf8;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.92rem;
            flex-shrink: 0;
        }

        .step-body h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 0.2rem;
        }

        .step-body p {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.4;
            margin: 0;
        }

        /* Interfaces selector si hay más de una IP */
        .interfaces-bar {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.82rem;
        }

        .interface-badge {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            padding: 0.28rem 0.65rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .interface-badge:hover, .interface-badge.active {
            background: var(--brand-primary);
            color: #fff;
            border-color: transparent;
        }

        /* Modo proyector / Pantalla completa */
        body.fullscreen-mode {
            background: #000000;
            padding: 0;
        }

        body.fullscreen-mode .connect-navbar,
        body.fullscreen-mode .info-panel,
        body.fullscreen-mode .ambient-glow,
        body.fullscreen-mode .ambient-glow-2,
        body.fullscreen-mode .connect-footer {
            display: none !important;
        }

        body.fullscreen-mode .connect-main {
            padding: 1rem;
            height: 100vh;
        }

        body.fullscreen-mode .connect-grid {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 100%;
        }

        body.fullscreen-mode .qr-card {
            max-width: 820px;
            width: 90vw;
            height: 90vh;
            max-height: 840px;
            justify-content: center;
            padding: 2.5rem;
        }

        body.fullscreen-mode .qr-viewport {
            max-width: 580px;
            max-height: 580px;
        }

        body.fullscreen-mode .exit-fs-btn {
            display: flex !important;
        }

        .exit-fs-btn {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }

        /* Pie de página */
        .connect-footer {
            position: relative;
            z-index: 10;
            padding: 1.25rem 2rem;
            text-align: center;
            font-size: 0.82rem;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .connect-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .info-title {
                font-size: 1.9rem;
            }

            .connect-navbar {
                padding: 1rem 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    <!-- Botón flotante para salir de pantalla completa -->
    <button class="btn-glass exit-fs-btn" id="exit-fullscreen-btn" onclick="toggleProjectorMode()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
        Salir de pantalla completa
    </button>

    <!-- Barra de navegación -->
    <header class="connect-navbar">
        <a href="{{ route('network.connect') }}" class="connect-brand">
            <div class="brand-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 12h16v9H4zM8 15h8m-9-7 5-5 5 5-5 5z"/></svg>
            </div>
            <div>
                <div class="brand-title">Vota<span>Edu</span></div>
                <span class="brand-subtitle">Red Distribuida de Votación</span>
            </div>
        </a>

        <div class="nav-actions">
            <button type="button" class="btn-glass" onclick="toggleProjectorMode()" title="Ampliar para proyector o pantalla de aula">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                <span>Modo Proyector</span>
            </button>

            <a href="{{ route('network.qr.svg', ['ip' => $selectedIp]) }}" class="btn-glass" download="qr-votaedu.svg" title="Descargar código QR en formato SVG de alta calidad">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5 5 5-5m-5 5V3"/></svg>
                <span>Descargar QR</span>
            </a>

            <a href="{{ route('login') }}" class="btn-glass btn-enter">
                <span>Ingresar en esta PC</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
            </a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="connect-main">
        <div class="connect-grid">
            
            <!-- Tarjeta Visual del Código QR con Animación de Escaneo -->
            <div class="qr-card-container">
                <div class="qr-card">
                    <div class="qr-card-header">
                        <span class="qr-school-tag">U.E. Maestro Pitágoras II</span>
                        <h2 class="qr-card-title">Escanea para Votar</h2>
                    </div>

                    <div class="qr-viewport">
                        <div class="scanner-corner corner-tl"></div>
                        <div class="scanner-corner corner-tr"></div>
                        <div class="scanner-corner corner-bl"></div>
                        <div class="scanner-corner corner-br"></div>
                        <div class="laser-scan-line"></div>

                        {!! $qrSvg !!}
                    </div>

                    <div class="qr-status-pill">
                        <span class="status-dot"></span>
                        <span>Servidor activo en <strong>{{ $selectedIp }}:{{ $port }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Panel de Información e Instrucciones -->
            <div class="info-panel">
                <div class="info-pill">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
                    <span>Acceso Inalámbrico en Red Local</span>
                </div>

                <h1 class="info-title">Vota desde tu <span>teléfono móvil</span> o computadora</h1>
                <p class="info-desc">No necesitas instalar ninguna aplicación. Conecta tu dispositivo a la red Wi-Fi y escanea el código con tu cámara.</p>

                <!-- Cuadro con URL interactiva y botón de copiar -->
                <div class="url-box">
                    <div class="url-text-group">
                        <div class="url-caption">Enlace directo de la plataforma</div>
                        <span class="url-value" id="platform-url">{{ $accessUrl }}</span>
                    </div>
                    <button class="btn-copy" id="copy-btn" onclick="copyAccessUrl()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copy-icon"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <span id="copy-text">Copiar</span>
                    </button>
                </div>

                <!-- Pasos de conexión -->
                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <h4>Conéctate a la misma red Wi-Fi</h4>
                            <p>Tu celular o computadora debe estar conectado a la red local donde corre este servidor.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <h4>Apunta con la cámara del teléfono</h4>
                            <p>Abre la cámara o Google Lens, enfoca el código QR y toca el enlace que aparece en pantalla.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <h4>Ingresa tus datos y ejerce tu voto</h4>
                            <p>Inicia sesión con tu carnet de identidad o usuario. Tu elección es 100% secreta y segura.</p>
                        </div>
                    </div>
                </div>

                <!-- Selector de adaptadores / interfaces si hay más de una -->
                @if(count($availableIps) > 1)
                <div class="interfaces-bar">
                    <span style="color: #94a3b8; font-weight: 600;">Interfaces de red:</span>
                    @foreach($availableIps as $item)
                        <a href="{{ route('network.connect', ['ip' => $item['ip']]) }}" 
                           class="interface-badge {{ $item['ip'] === $selectedIp ? 'active' : '' }}">
                           {{ $item['name'] }}: <strong>{{ $item['ip'] }}</strong>
                        </a>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </main>

    <!-- Pie de página -->
    <footer class="connect-footer">
        <div>VotaEdu · U.E. Técnico Humanístico Maestro Pitágoras II · La Guardia</div>
        <div>Votación escolar segura y distribuida sin necesidad de internet externo.</div>
    </footer>

    <script>
        function copyAccessUrl() {
            const urlText = document.getElementById('platform-url').innerText;
            const btn = document.getElementById('copy-btn');
            const btnText = document.getElementById('copy-text');
            const icon = document.getElementById('copy-icon');

            navigator.clipboard.writeText(urlText).then(() => {
                btn.classList.add('copied');
                btnText.innerText = '¡Copiado!';
                icon.innerHTML = '<path d="M20 6 9 17l-5-5"/>';

                setTimeout(() => {
                    btn.classList.remove('copied');
                    btnText.innerText = 'Copiar';
                    icon.innerHTML = '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>';
                }, 2200);
            }).catch(() => {
                alert('No se pudo copiar automáticamente. Puedes seleccionar el enlace manualmente: ' + urlText);
            });
        }

        function toggleProjectorMode() {
            document.body.classList.toggle('fullscreen-mode');
            if (document.body.classList.contains('fullscreen-mode')) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(() => {});
                }
            } else {
                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
            }
        }

        // Salir con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.classList.contains('fullscreen-mode')) {
                toggleProjectorMode();
            }
        });

        // Manejar cambio nativo de pantalla completa (F11)
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement && document.body.classList.contains('fullscreen-mode')) {
                document.body.classList.remove('fullscreen-mode');
            }
        });
    </script>
</body>
</html>
