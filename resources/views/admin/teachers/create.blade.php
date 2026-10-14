@extends('layouts.app')
@section('title', 'Registrar docente')
@section('content')
<a class="back-link" href="{{ route('admin.teachers.index') }}">← Volver a docentes</a><div class="page-heading"><div><h1>Una cuenta para acompañar.</h1><p>Registra al docente y asigna los cursos de su gestión.</p></div></div><div class="form-layout"><form class="surface form-surface" method="post" action="{{ route('admin.teachers.store') }}">@csrf<h2>Datos del docente</h2>@include('admin.teachers.form')</form><aside class="instruction-panel"><x-icon name="book" /><h3>Permisos por curso</h3><p>El docente podrá crear votaciones para sus cursos asignados. Revisa las asignaciones antes de guardar.</p><p>Los campos con * son obligatorios.</p></aside></div>
@endsection
