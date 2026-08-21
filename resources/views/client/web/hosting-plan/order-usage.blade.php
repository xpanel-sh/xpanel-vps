@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Plan de hosting',
        'title' => 'Uso del pedido',
        'description' => 'Monitorea consumo de recursos del sitio dentro del plan.',
        'metrics' => [
            ['label' => 'CPU', 'value' => '0%', 'icon' => 'ki-technology-2'],
            ['label' => 'Memoria', 'value' => '0 MB', 'icon' => 'ki-chart'],
            ['label' => 'Disco', 'value' => '0 MB', 'icon' => 'ki-save-2'],
        ],
        'cards' => [
            ['title' => 'Uso reciente', 'body' => 'Graficos de consumo por hora y dia apareceran aqui.'],
            ['title' => 'Limites', 'body' => 'Alertas de limite y recomendaciones se mostraran en este panel.'],
        ],
    ])
@endsection
