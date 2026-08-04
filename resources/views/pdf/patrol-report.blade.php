<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de rondín #{{ $patrol['id'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { margin-bottom: 16px; line-height: 1.5; }
        .muted { color: #555; }
        .checkpoint { margin-bottom: 16px; page-break-inside: avoid; }
        .badge { display: inline-block; padding: 2px 6px; border: 1px solid #333; border-radius: 3px; font-size: 10px; }
        ul { margin: 6px 0; padding-left: 18px; }
        .photos { margin-top: 8px; }
        .photos img { width: 160px; height: 120px; object-fit: cover; margin-right: 8px; margin-bottom: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Reporte de rondín</h1>
    <p class="muted">{{ $area['name'] }} ({{ $area['code'] }}) · {{ $round['title'] }}</p>

    <div class="meta">
        <div><strong>Guardia:</strong> {{ $patrol['guard']['name'] }}</div>
        <div><strong>Estado:</strong> {{ $patrol['status_label'] }}</div>
        <div><strong>Inicio:</strong> {{ \Illuminate\Support\Carbon::parse($patrol['started_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</div>
        <div><strong>Fin:</strong>
            @if ($patrol['finished_at'])
                {{ \Illuminate\Support\Carbon::parse($patrol['finished_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
            @else
                —
            @endif
        </div>
        <div><strong>Tiempo total:</strong> {{ $patrol['duration_label'] ?? '—' }}</div>
    </div>

    <h2>Puntos de revisión</h2>

    @forelse ($checkpoints as $checkpoint)
        <div class="checkpoint">
            <strong>{{ $checkpoint['position'] }}. {{ $checkpoint['name'] }}</strong>
            @if ($checkpoint['visited'])
                <span class="badge">{{ $checkpoint['outcome_label'] }}</span>
                <div class="muted">
                    Revisado:
                    {{ \Illuminate\Support\Carbon::parse($checkpoint['reviewed_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                </div>

                @if (count($checkpoint['answers']) > 0)
                    <ul>
                        @foreach ($checkpoint['answers'] as $answer)
                            <li><strong>{{ $answer['question'] }}</strong>: {{ $answer['option'] }}</li>
                        @endforeach
                    </ul>
                @elseif ($checkpoint['outcome'] === 'all_clear')
                    <p>Área sin novedad.</p>
                @endif

                @if (count($checkpoint['photos']) > 0)
                    <div class="photos">
                        @foreach ($checkpoint['photos'] as $photo)
                            @if ($photo['file_path'])
                                <img src="{{ $photo['file_path'] }}" alt="Evidencia {{ $photo['position'] }}">
                            @endif
                        @endforeach
                    </div>
                @endif
            @else
                <span class="badge">Pendiente</span>
            @endif
        </div>
    @empty
        <p class="muted">Este recorrido no tiene puntos activos.</p>
    @endforelse
</body>
</html>
