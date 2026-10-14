@foreach(['success' => 'Listo', 'error' => 'No se pudo completar', 'warning' => 'Ten en cuenta', 'info' => 'Información'] as $type => $label)
@if(session($type))<div class="notice notice-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}"><strong>{{ $label }}</strong><span>{{ session($type) }}</span></div>@endif
@endforeach
@if($errors->any())<div class="notice notice-error" role="alert"><strong>Revisa los datos para continuar</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
