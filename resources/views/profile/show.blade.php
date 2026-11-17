@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">MI CUENTA</span>
        <h1>Mi perfil y configuración</h1>
        <p>Administra tus datos personales, credenciales de acceso e historial de participación.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('password.change') }}"><x-icon name="shield" /> Cambiar contraseña</a>
</div>

<section class="surface profile-summary">
    <span class="avatar large-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
    <div>
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->isDirector() ? 'Director' : ($user->isAdmin() ? 'Administrador' : ($user->isTeacher() ? 'Docente' : 'Estudiante')) }}</p>
    </div>
    <x-status :status="$user->is_active ? 'active' : 'inactive'" />
</section>

<dl class="detail-grid profile-details">
    <div>
        <dt>Nombre de usuario</dt>
        <dd><strong>{{ $user->username }}</strong></dd>
    </div>
    <div>
        <dt>Carnet de identidad</dt>
        <dd>{{ $user->ci ?: 'Pendiente de registrar' }}</dd>
    </div>
    <div>
        <dt>Curso y paralelo</dt>
        <dd>{{ $user->course ? $user->course.' '.$user->parallel : ($user->isStudent() ? 'Sin curso asignado' : 'No corresponde') }}</dd>
    </div>
    <div>
        <dt>Participaciones registradas</dt>
        <dd>{{ $participations->count() }}</dd>
    </div>
</dl>

<div class="form-layout mb-4">
    <form class="surface form-surface" method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')
        
        <h2>Modificar mis datos personales</h2>
        <p class="muted" style="margin-top: -0.5rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
            Actualiza tu nombre visible, nombres y apellidos, usuario de acceso o carnet de identidad.
        </p>

        <div class="field">
            <label for="name">Nombre completo *</label>
            <input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="150" autocomplete="name" placeholder="Tu nombre completo">
            <small>Este es el nombre visible que se mostrará en las votaciones, actas y en tu panel.</small>
        </div>

        <div class="row">
            <div class="col-sm-4 field">
                <label for="first_name">Nombres</label>
                <input id="first_name" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" maxlength="100" autocomplete="given-name" placeholder="Ej. Juan">
            </div>
            <div class="col-sm-4 field">
                <label for="paternal_surname">Apellido paterno</label>
                <input id="paternal_surname" name="paternal_surname" class="form-control" value="{{ old('paternal_surname', $user->paternal_surname) }}" maxlength="80" autocomplete="family-name" placeholder="Ej. Pérez">
            </div>
            <div class="col-sm-4 field">
                <label for="maternal_surname">Apellido materno</label>
                <input id="maternal_surname" name="maternal_surname" class="form-control" value="{{ old('maternal_surname', $user->maternal_surname) }}" maxlength="80" autocomplete="additional-name" placeholder="Ej. Gómez">
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 field">
                <label for="username">Nombre de usuario para iniciar sesión *</label>
                <input id="username" name="username" class="form-control" value="{{ old('username', $user->username) }}" required maxlength="100" autocomplete="username" placeholder="tu.usuario">
                <small>Úsalo para ingresar a la plataforma junto a tu contraseña.</small>
            </div>
            <div class="col-sm-6 field">
                <label for="ci">Carnet de identidad (CI)</label>
                <input id="ci" name="ci" class="form-control" value="{{ old('ci', $user->ci) }}" maxlength="20" autocomplete="off" placeholder="Ej. 12345678">
                <small>También puedes iniciar sesión usando directamente tu CI.</small>
            </div>
        </div>

        @if($user->isStudent())
            <div class="row">
                <div class="col-sm-8 field">
                    <label for="course">Curso escolar</label>
                    <input id="course" name="course" class="form-control" value="{{ old('course', $user->course) }}" maxlength="50" placeholder="Ej. 6to de Secundaria" list="courses">
                    <datalist id="courses">
                        @foreach(range(1, 6) as $grade)
                            <option value="{{ $grade }}{{ $grade === 1 ? 'ro' : ($grade === 2 ? 'do' : ($grade === 3 ? 'ro' : 'to')) }} de Secundaria">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-sm-4 field">
                    <label for="parallel">Paralelo</label>
                    <input id="parallel" name="parallel" class="form-control" value="{{ old('parallel', $user->parallel) }}" maxlength="20" placeholder="Ej. A">
                </div>
            </div>

            @if(isset($courses) && $courses->isNotEmpty())
                <div class="field">
                    <label for="registered-course">Elegir de los cursos registrados del colegio</label>
                    <select class="form-select" id="registered-course" data-fill-course>
                        <option value="">Selecciona para autocompletar curso y paralelo</option>
                        @foreach($courses as $registeredCourse)
                            <option value="{{ $registeredCourse->id }}" 
                                    data-grade="{{ $registeredCourse->grade }}" 
                                    data-parallel="{{ $registeredCourse->parallel }}">
                                {{ $registeredCourse->name }} · {{ $registeredCourse->academicYear?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        @endif

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <x-icon name="check" /> Guardar cambios en mi perfil
            </button>
            <a class="btn btn-light" href="{{ route('password.change') }}">
                <x-icon name="shield" /> Cambiar contraseña
            </a>
        </div>
    </form>

    <aside class="instruction-panel">
        <x-icon name="users" />
        <h3>Tus datos de cuenta</h3>
        <p>Al guardar tus cambios, tu <strong>nombre visible</strong> y tu <strong>usuario de acceso</strong> se actualizarán inmediatamente.</p>
        <p>Asegúrate de recordar tu nuevo nombre de usuario para tu próximo inicio de sesión, o utiliza tu número de carnet de identidad.</p>
        <p>Para mayor seguridad, recuerda no compartir tus credenciales con terceras personas.</p>
    </aside>
</div>

<section class="surface">
    <div class="section-heading">
        <div>
            <h2>Mis participaciones</h2>
            <p>Se registra que participaste. La opción que elegiste se mantiene en estricto secreto.</p>
        </div>
    </div>
    @forelse($participations as $participation)
        @if($participation->election)
            <div class="closed-election">
                <span class="table-icon"><x-icon name="check" /></span>
                <div>
                    <h3>{{ $participation->election->title }}</h3>
                    <p><x-election-type :type="$participation->election->type" /> · Participación registrada</p>
                </div>
                <x-status :status="$participation->election->status" />
                @if($participation->election->isClosed())
                    <a class="btn btn-light" href="{{ route('results.show', $participation->election) }}">Ver resultados</a>
                @endif
            </div>
        @endif
    @empty
        <div class="empty-state">
            <x-icon />
            <h3>Tu primera participación está por llegar</h3>
            <p>Cuando votes, encontrarás aquí el registro de esa votación.</p>
        </div>
    @endforelse
</section>
@endsection
