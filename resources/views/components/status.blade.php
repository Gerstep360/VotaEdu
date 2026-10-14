@props(['status'])
@php($status = ['borrador' => 'pending', 'abierta' => 'open', 'cerrada' => 'closed', 'anulada' => 'cancelled'][$status] ?? $status)
<span class="state state-{{ $status }}"><span></span>{{ ['pending' => 'Borrador', 'open' => 'Abierta', 'closed' => 'Finalizada', 'cancelled' => 'Anulada', 'active' => 'Habilitado', 'inactive' => 'Deshabilitado', 'voted' => 'Ya participaste'][$status] ?? $status }}</span>
