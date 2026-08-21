@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Rendimiento',
        'title' => 'Page speed',
        'description' => 'Mide tiempos de respuesta, peso de pagina y oportunidades de optimizacion.',
        'actions' => [
            ['label' => 'Medir ahora', 'icon' => 'ki-chart-line-up', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Score', 'value' => '--', 'icon' => 'ki-chart-simple'],
            ['label' => 'TTFB', 'value' => '-- ms', 'icon' => 'ki-timer'],
            ['label' => 'Peso', 'value' => '-- MB', 'icon' => 'ki-file'],
        ],
        'cards' => [
            ['title' => 'Historial', 'body' => 'Aqui se mostraran mediciones de velocidad por fecha.'],
            ['title' => 'Optimizaciones', 'body' => 'Compresion, cache y recursos estaticos se analizaran desde este panel.'],
        ],
    ])
@endsection
