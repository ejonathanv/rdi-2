<x-mail::message>
# Recorridos del {{ $digest['date'] }} — {{ $digest['area']['name'] }}

Área: **{{ $digest['area']['name'] }}** ({{ $digest['area']['code'] }})

@foreach ($digest['patrols'] as $patrol)
- **{{ $patrol['round'] }}** · {{ $patrol['guard'] }} · {{ $patrol['status'] }}  
  Inicio: {{ $patrol['started_at'] }}@if($patrol['finished_at']) · Fin: {{ $patrol['finished_at'] }}@endif  
  Duración: {{ $patrol['duration_label'] ?? '—' }} · Puntos visitados: {{ $patrol['visits_count'] }}
@endforeach

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
