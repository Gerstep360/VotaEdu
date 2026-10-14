<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#245ce0"><title>@yield('title', 'Inicio') · VotaEdu</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/votaedu.css') }}">
    <script src="{{ asset('assets/votaedu.js') }}" defer></script>
</head>
<body class="@guest auth-page @endguest">
<a class="skip-link" href="#main">Ir al contenido</a>
@auth
@php
    $account = auth()->user();
    $admin = $account->canManageSystem();
    $teacherRole = $account->isTeacher();
    $home = $admin ? 'admin.dashboard' : ($teacherRole ? 'teacher.dashboard' : 'student.dashboard');
    $roleLabel = $account->isDirector() ? 'Director' : ($admin ? 'Administrador' : ($teacherRole ? 'Docente' : 'Estudiante'));
    $forced = (bool) $account->must_change_password;
@endphp
<aside class="sidebar" id="navigation">
    <a class="brand" href="{{ route($forced ? 'password.change' : $home) }}"><span class="brand-mark"><x-icon /></span><span>Vota<span class="brand-light">Edu</span><small>DEMOCRACIA EN TU COLEGIO</small></span></a>
    <div class="school-label">U.E. Maestro Pitágoras II<small>Técnico Humanístico · La Guardia</small></div>
    <p class="nav-caption">{{ $admin ? 'ADMINISTRACIÓN' : ($teacherRole ? 'MI DOCENCIA' : 'MI ESPACIO') }}</p>
    <nav aria-label="Navegación principal">
        @unless($forced)
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.dashboard', 'teacher.dashboard', 'student.dashboard')]) href="{{ route($home) }}"><x-icon name="home" />{{ $admin ? 'Resumen general' : ($teacherRole ? 'Mis cursos y votaciones' : 'Mis votaciones') }}</a>
        @if($admin)
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.elections.*') && request('status') !== 'closed']) href="{{ route('admin.elections.index') }}"><x-icon />Votaciones</a>
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.students.*')]) href="{{ route('admin.students.index') }}"><x-icon name="users" />Estudiantes</a>
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.teachers.*')]) href="{{ route('admin.teachers.index') }}"><x-icon name="book" />Docentes y asignaciones</a>
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.courses.*')]) href="{{ route('admin.courses.index') }}"><x-icon name="book" />Cursos y paralelos</a>
        <a @class(['nav-item', 'selected' => request()->routeIs('admin.academic-years.*')]) href="{{ route('admin.academic-years.index') }}"><x-icon name="calendar" />Gestiones académicas</a>
        @elseif($teacherRole)
        <a @class(['nav-item', 'selected' => request()->routeIs('teacher.elections.*')]) href="{{ route('teacher.elections.create') }}"><x-icon />Crear votación de curso</a>
        @endif
        <a @class(['nav-item', 'selected' => request()->routeIs('results.*') || request('status') === 'closed']) href="{{ $admin ? route('admin.elections.index', ['status' => 'closed']) : route($home).'#resultados' }}"><x-icon name="chart" />Resultados{{ $admin || $teacherRole ? ' y actas' : '' }}</a>
        @if($admin)<a @class(['nav-item', 'selected' => request()->routeIs('admin.audit-logs.*')]) href="{{ route('admin.audit-logs.index') }}"><x-icon name="shield" />Auditoría</a>@endif
        <p class="nav-caption account-caption">MI CUENTA</p>
        <a @class(['nav-item', 'selected' => request()->routeIs('profile.*')]) href="{{ route('profile.show') }}"><x-icon name="users" />Mi perfil y participación</a>
        @endunless
        <a @class(['nav-item', 'selected' => request()->routeIs('password.*')]) href="{{ route('password.change') }}"><x-icon name="shield" />{{ $forced ? 'Activar mi acceso' : 'Cambiar contraseña' }}</a>
    </nav>
    <div class="sidebar-bottom"><span class="live-dot"></span> Plataforma escolar <span class="year">{{ now()->year }}</span></div>
</aside>
<div class="workspace">
    <header class="topbar">
        <button class="menu-button" aria-label="Mostrar navegación" aria-controls="navigation" aria-expanded="false"><x-icon name="menu" /></button>
        <span class="topbar-label">{{ $admin ? 'Panel de administración' : ($teacherRole ? 'Portal docente' : 'Portal estudiantil') }}</span>
        <div class="user-profile"><a class="profile-link" href="{{ route($forced ? 'password.change' : 'profile.show') }}" aria-label="Mi perfil"><span class="avatar">{{ mb_substr($account->name, 0, 1) }}</span><span class="user-name">{{ $account->name }}<small>{{ $roleLabel }}</small></span></a><form method="post" action="{{ route('logout') }}">@csrf<button class="logout-button" title="Cerrar sesión" aria-label="Cerrar sesión"><x-icon name="logout" /></button></form></div>
    </header>
    <main id="main" class="main-content">
        @include('partials.messages')
        @yield('content')
        <footer class="page-footer"><span>VotaEdu · U.E. Maestro Pitágoras II</span><span>Participación que construye comunidad.</span></footer>
    </main>
</div>
@else
<main id="main">@include('partials.messages') @yield('content')</main>
@endauth
<dialog id="confirmation" class="confirmation-dialog" aria-labelledby="confirm-title">
    <div class="dialog-symbol"><x-icon name="shield" /></div><h2 id="confirm-title">Confirma tu decisión</h2><p id="confirm-message"></p>
    <div id="confirm-selection" class="selection-preview" hidden></div>
    <div class="dialog-actions"><button class="btn btn-light" type="button" id="confirm-cancel">Volver</button><button class="btn btn-primary" type="button" id="confirm-accept">Confirmar</button></div>
</dialog>
</body>
</html>
