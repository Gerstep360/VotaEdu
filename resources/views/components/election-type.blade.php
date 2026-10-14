@props(['type'])
<span class="type-label">{{ ['curso' => 'Votación de curso', 'institucional' => 'Institucional', 'eleccion_estudiantil' => 'Elección estudiantil'][$type] ?? 'Institucional' }}</span>
