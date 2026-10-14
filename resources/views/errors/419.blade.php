@extends('layouts.app')
@section('title', 'Sesión vencida')
@section('content')
<div class="empty-state error-page"><x-icon name="clock" /><h1>Tu sesión necesita actualizarse.</h1><p>Vuelve a ingresar para continuar. Si estabas votando, revisa primero si tu participación quedó registrada.</p><a class="btn btn-primary" href="{{ route('login') }}">Volver a ingresar</a></div>
@endsection
