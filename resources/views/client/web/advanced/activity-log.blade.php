@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Activity log',
        'description' => 'Consulta eventos recientes del sitio, cambios y acciones ejecutadas.',
        'metrics' => [
            ['label' => 'Eventos', 'value' => '0', 'icon' => 'ki-notepad'],
            ['label' => 'Errores', 'value' => '0', 'icon' => 'ki-warning'],
            ['label' => 'Periodo', 'value' => '24h', 'icon' => 'ki-time'],
        ],
        'cards' => [
            ['title' => 'Historial', 'body' => 'Aqui se mostraran cambios de archivos, despliegues y acciones del panel.'],
            ['title' => 'Filtros', 'body' => 'Podras filtrar por usuario, tipo de evento, fecha y severidad.'],
        ],
    ])
@endsection
