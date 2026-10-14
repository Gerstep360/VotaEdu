@extends('layouts.app')
@section('title', 'Editar docente')
@section('content')
<a class="back-link" href="{{ route('admin.teachers.index') }}">← Volver a docentes</a><div class="page-heading"><div><h1>Datos y cursos del docente</h1><p>{{ $teacher->name }}</p></div></div><form class="surface form-surface form-narrow" method="post" action="{{ route('admin.teachers.update', $teacher) }}">@csrf @method('PATCH') @include('admin.teachers.form')</form>
@endsection
