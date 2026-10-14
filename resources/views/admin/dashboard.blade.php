@extends('layouts.app')
@section('title', 'Resumen general')
@section('content')
<div class="page-heading"><div><span class="eyebrow">COMUNIDAD EDUCATIVA · {{ $activeAcademicYear?->name ?: 'GESTIÓN ESCOLAR' }}</span><h1>Tu comunidad, de un vistazo.</h1><p>Revisa la participación y organiza las próximas decisiones de tu colegio.</p></div><a class="btn btn-primary" href="{{ route('admin.elections.create') }}"><x-icon name="plus" /> Nueva votación</a></div>
<div class="dashboard-context"><span><x-icon name="calendar" /> {{ $activeAcademicYear?->name ?: 'Selecciona una gestión activa' }}</span><a href="{{ route('admin.teachers.index') }}">{{ $totalTeachers }} docentes</a><a href="{{ route('admin.courses.index') }}">{{ $totalCourses }} cursos</a></div>
<div class="stats-grid expanded-stats">
@foreach([
['Estudiantes registrados',$totalStudents,$activeStudents.' habilitados · '.$inactiveStudents.' deshabilitados','users','admin.students.index',null],
['Estudiantes habilitados',$activeStudents,'Con acceso a la participación','shield','admin.students.index',null],
['Votaciones abiertas',$openElections,'Participación en curso','ballot','admin.elections.index','open'],
['En preparación',$pendingElections,'Borradores por completar','clock','admin.elections.index','pending'],
['Votaciones finalizadas',$closedElections,'Resultados y actas disponibles','chart','admin.elections.index','closed'],
['Votaciones anuladas',$cancelledElections,'Historial y motivos conservados','file','admin.elections.index','cancelled']
] as [$label,$count,$help,$icon,$route,$status])
<a class="stat" href="{{ route($route, $status ? ['status'=>$status] : []) }}"><span class="stat-icon"><x-icon :name="$icon" /></span><span class="stat-label">{{ $label }}</span><strong>{{ $count }}</strong><small>{{ $help }}</small></a>
@endforeach
</div>
<div class="scope-summary">
@foreach(['curso'=>['Votaciones de curso',$courseElectionsCount,'Decisiones del aula'],'institucional'=>['Institucionales',$institutionalElectionsCount,'Una comunidad que participa'],'eleccion_estudiantil'=>['Elecciones estudiantiles',$studentElectionsCount,'Representación de los estudiantes']] as $type=>[$label,$count,$help])
<a href="{{ route('admin.elections.index',['type'=>$type]) }}"><span><strong>{{ $label }}</strong><small>{{ $help }}</small></span><b>{{ $count }}</b><x-icon name="arrow" /></a>
@endforeach
</div>
<section class="surface"><div class="section-heading"><div><h2>Votaciones recientes</h2><p>Estado, alcance y acceso a cada decisión.</p></div><a class="text-link" href="{{ route('admin.elections.index') }}">Ver historial <x-icon name="arrow" /></a></div>@include('partials.election-table',['elections'=>$recentElections])</section>
<div class="dashboard-lower"><section class="surface form-surface"><h2>Participación reciente</h2><p class="muted mb-3">Personas que participaron respecto al padrón registrado.</p>
@forelse($recentElections->filter(fn($item) => !$item->isPending()) as $election)
@php($eligible = $election->eligible_voters_count)
@php($participants = $election->participations_count)
<div class="turnout-row"><div><strong>{{ $election->title }}</strong><small>{{ $eligible ? $participants.' de '.$eligible.' habilitados' : 'Sin padrón registrado en esta votación' }}{{ $election->isCancelled() ? ' · Anulada' : '' }}</small></div><b>{{ $eligible ? number_format($participants / $eligible * 100, 1, ',', '.').' %' : '—' }}</b></div>
@empty<p class="muted">La participación aparecerá cuando abras una votación.</p>@endforelse
</section><section class="surface form-surface"><div class="section-heading p-0 mb-3"><h2>Actividad administrativa</h2><a class="text-link" href="{{ route('admin.audit-logs.index') }}">Ver historial</a></div>@forelse($recentLogs as $log)<div class="activity-row"><span class="avatar">{{ mb_substr($log->user?->name ?: 'S',0,1) }}</span><div><strong>{{ $log->user?->name ?: 'Sistema' }}</strong><small>{{ $log->created_at->format('d/m/Y H:i') }} · Registro #{{ $log->entity_id }}</small></div></div>@empty<p class="muted">Sin acciones administrativas recientes.</p>@endforelse</section></div>
@endsection
