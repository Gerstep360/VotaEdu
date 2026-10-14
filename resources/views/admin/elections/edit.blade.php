@extends('layouts.app')
@section('title', 'Editar borrador')
@section('content')
<a class="back-link" href="{{ route('admin.elections.show', $election) }}">← Volver a la votación</a><div class="page-heading"><div><h1>Editar borrador</h1><p>Ajusta la información antes de abrir la votación.</p></div><x-status status="pending" /></div><form class="surface form-surface form-narrow" method="post" action="{{ route('admin.elections.update', $election) }}">@csrf @method('PATCH') @include('admin.elections.form')</form>
@endsection
