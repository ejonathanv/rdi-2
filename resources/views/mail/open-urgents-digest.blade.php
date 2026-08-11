<x-mail::message>
# Urgentes pendientes — {{ $digest['area']['name'] }}

Hay elementos urgentes sin atender en el área **{{ $digest['area']['name'] }}** ({{ $digest['area']['code'] }}).

@if (count($digest['incidents']) > 0)
## Incidencias urgentes abiertas ({{ count($digest['incidents']) }})

@foreach ($digest['incidents'] as $incident)
- **#{{ $incident['id'] }}** · {{ $incident['created_at'] }} · {{ $incident['status'] }}@if($incident['category']) · {{ $incident['category'] }}@endif  
  {{ $incident['message'] }}  
  Guardia: {{ $incident['guard'] }}
@endforeach
@endif

@if (count($digest['visits']) > 0)
## Puntos de revisión urgentes sin atender ({{ count($digest['visits']) }})

@foreach ($digest['visits'] as $visit)
- **{{ $visit['checkpoint'] }}** ({{ $visit['round'] }}) · {{ $visit['reviewed_at'] }}  
  Guardia: {{ $visit['guard'] }}@if($visit['notes'])  
  Notas: {{ $visit['notes'] }}@endif
@endforeach
@endif

Este correo se reenvía diariamente mientras haya urgentes pendientes.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
