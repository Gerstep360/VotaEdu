@extends('layouts.app')
@section('title', 'Página no encontrada')
@section('content')
<div class="empty-state error-page"><x-icon name="search" /><h1>No encontramos esta página.</h1><p>El enlace puede haber cambiado o la votación ya no existe.</p><a class="btn btn-primary" href="{{ auth()->check() ? route(auth()->user()->canManageSystem() ? 'admin.dashboard' : (auth()->user()->isTeacher() ? 'teacher.dashboard' : 'student.dashboard')) : route('login') }}">Volver al inicio</a></div>
@endsection
