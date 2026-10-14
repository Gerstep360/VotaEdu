@extends('layouts.app')
@section('title', 'Nueva votación')
@section('content')
<a class="back-link" href="{{ route('admin.elections.index') }}">← Volver a votaciones</a><div class="page-heading"><div><h1>Una nueva decisión comienza aquí.</h1><p>Primero crea el borrador. Después podrás agregar las opciones.</p></div></div>
<div class="form-layout"><form class="surface form-surface" method="post" action="{{ route('admin.elections.store') }}">@csrf<h2>Datos de la votación</h2>@include('admin.elections.form')</form><aside class="instruction-panel"><span class="eyebrow">PASO A PASO</span><ol class="steps-list"><li><strong>Describe la decisión</strong><p>Agrega un título y las instrucciones.</p></li><li><strong>Prepara las opciones</strong><p>Necesitas al menos dos personas, frentes o propuestas.</p></li><li><strong>Abre la votación</strong><p>Cuando esté lista, habilita la participación.</p></li></ol></aside></div>
@endsection
