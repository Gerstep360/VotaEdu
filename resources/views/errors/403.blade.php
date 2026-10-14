@extends('layouts.app')
@section('title', 'Acceso restringido')
@section('content')
<div class="empty-state error-page"><x-icon name="shield" /><h1>Este espacio tiene acceso restringido.</h1><p>Tu cuenta no tiene permisos para abrir esta página.</p><a class="btn btn-primary" href="{{ auth()->check() ? route(auth()->user()->canManageSystem() ? 'admin.dashboard' : (auth()->user()->isTeacher() ? 'teacher.dashboard' : 'student.dashboard')) : route('login') }}">Volver a mi espacio</a></div>
@endsection
