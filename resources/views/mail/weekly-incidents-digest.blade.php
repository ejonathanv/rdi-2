<x-mail::message>
# Resumen semanal de incidencias — {{ $digest['area']['name'] }}

Periodo: **{{ $digest['period']['from'] }}** a **{{ $digest['period']['to'] }}**  
Área: **{{ $digest['area']['name'] }}** ({{ $digest['area']['code'] }})

## Totales

- Total: {{ $digest['totals']['total'] }}
- Abiertas: {{ $digest['totals']['open'] }}
- Resueltas: {{ $digest['totals']['resolved'] }}
- Descartadas: {{ $digest['totals']['discarded'] }}
- Urgentes: {{ $digest['totals']['urgent'] }}

@if (count($digest['incidents']) === 0)
No se registraron incidencias en esta semana.
@else
## Detalle

@foreach ($digest['incidents'] as $incident)
- **#{{ $incident['id'] }}** · {{ $incident['created_at'] }} · {{ $incident['status'] }}@if($incident['is_urgent']) · Urgente@endif@if($incident['category']) · {{ $incident['category'] }}@endif  
  {{ $incident['message'] }}  
  Guardia: {{ $incident['guard'] }}
@endforeach
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
