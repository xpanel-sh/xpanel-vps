@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Analisis',
        'title' => 'Analisis',
        'description' => 'Visualiza trafico, consumo y actividad del sitio seleccionado.',
        'metrics' => [
            ['label' => 'Visitas', 'value' => '0', 'icon' => 'ki-eye'],
            ['label' => 'Ancho de banda', 'value' => '0 MB', 'icon' => 'ki-chart-line-up'],
            ['label' => 'Errores', 'value' => '0', 'icon' => 'ki-warning'],
        ],
        'cards' => [
            ['title' => 'Trafico', 'body' => 'Graficos de solicitudes, visitantes y fuentes se integraran aqui.'],
            ['title' => 'Eventos', 'body' => 'Eventos del sitio, errores HTTP y consumo quedaran agrupados.'],
        ],
    ])
@endsection
