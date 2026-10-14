@extends('layouts.app')
@section('title', 'Iniciar sesión')
@section('content')
<div class="login-shell">
    <section class="login-story">
        <a class="brand" href="{{ route('login') }}"><span class="brand-mark"><x-icon /></span><span>Vota<span class="brand-light">Edu</span></span></a>
        <div class="story-content"><span class="eyebrow">TU COLEGIO. TU VOZ.</span><h1>Las grandes ideas<br>comienzan con<br><span>tu elección.</span></h1><p>Un espacio para participar, elegir y construir juntos el futuro de nuestra comunidad educativa.</p>
        <div class="ballot-art" aria-hidden="true"><div class="ballot-paper"><span></span><i>✓</i><span></span><b></b></div><div class="ballot-box"><x-icon /><span>CADA VOZ CUENTA</span></div><span class="art-spark spark-one">+</span><span class="art-spark spark-two">+</span><div class="art-caption"><span class="live-dot"></span> Participar es dar el primer paso</div></div></div>
        <div class="story-footer">U.E. Técnico Humanístico Maestro Pitágoras II<span>La Guardia, Santa Cruz · Bolivia</span></div>
    </section>
    <section class="login-form-panel"><div class="login-form-content"><span class="small-label">PLATAFORMA DE VOTACIÓN ESCOLAR</span><h2>Bienvenido a VotaEdu</h2><p class="muted">Ingresa con la cuenta que te proporcionó tu colegio.</p>
    <form method="post" action="{{ route('login') }}" class="mt-4">@csrf
        <div class="field"><label for="username">Carnet de identidad o usuario</label><input id="username" name="username" class="form-control" value="{{ old('username') }}" placeholder="Ingresa tu CI o usuario anterior" autocomplete="username" required autofocus aria-describedby="username-help"><small id="username-help">Si tu cuenta aún no tiene CI, utiliza tu usuario habitual.</small></div>
        <div class="field"><label for="password">Contraseña</label><div class="password-wrap"><input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required placeholder="Ingresa tu contraseña"><button type="button" data-password="password" aria-label="Mostrar contraseña">Mostrar</button></div></div>
        <button class="btn btn-primary login-submit" type="submit">Iniciar sesión <x-icon name="arrow" /></button>
    </form>
    <div class="login-help"><x-icon name="users" /><div><strong>¿Necesitas ayuda para ingresar?</strong><p>Si olvidaste tu contraseña o aún no tienes una cuenta, consulta con tu docente o administrador.</p></div></div>
    <div class="login-trust"><x-icon name="shield" /> Tu elección no aparece vinculada a tu nombre.</div>
    </div><span class="login-bottom">Una plataforma sencilla. Una comunidad que participa.</span></section>
</div>
@endsection
