@extends('layouts.app')
@section('title', 'No pudimos abrir la página')
@section('content')
<div class="empty-state error-page"><x-icon name="clock" /><h1>No pudimos abrir esta página.</h1><p>Intenta nuevamente. Si el problema continúa, consulta con el administrador del colegio.</p><a class="btn btn-primary" href="{{ route('login') }}">Volver al inicio</a></div>
@endsection
