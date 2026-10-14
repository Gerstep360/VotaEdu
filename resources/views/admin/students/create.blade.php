@extends('layouts.app')
@section('title', 'Registrar estudiante')
@section('content')
<a class="back-link" href="{{ route('admin.students.index') }}">← Volver a estudiantes</a><div class="page-heading"><div><h1>Sumemos una nueva voz.</h1><p>Crea su cuenta con CI y prepara su participación escolar.</p></div></div><div class="form-layout"><form class="surface form-surface" method="post" action="{{ route('admin.students.store') }}">@csrf<h2>Datos del estudiante</h2>@include('admin.students.form')</form><aside class="instruction-panel"><x-icon name="users" /><h3>Del CI a un acceso personal</h3><p>Confirma los nombres, apellidos y CI antes de guardar. La contraseña temporal se genera con los datos que registres.</p><p>Su primer ingreso incluirá un cambio obligatorio de contraseña. No necesita correo electrónico.</p><p>Los campos con * son obligatorios.</p></aside></div>
@endsection
