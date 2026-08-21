@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Cache manager',
        'description' => 'Controla cache de aplicacion, paginas y recursos estaticos del sitio.',
        'actions' => [
            ['label' => 'Purgar cache', 'icon' => 'ki-trash', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Estado', 'value' => 'Default', 'icon' => 'ki-archive'],
            ['label' => 'TTL', 'value' => 'Auto', 'icon' => 'ki-time'],
            ['label' => 'Purgas', 'value' => '0', 'icon' => 'ki-trash'],
        ],
        'cards' => [
            ['title' => 'Reglas', 'body' => 'Define cache por extension, ruta o tipo de contenido.'],
            ['title' => 'Exclusiones', 'body' => 'Rutas dinamicas y paneles de admin podran excluirse del cache.'],
        ],
    ])
@endsection
