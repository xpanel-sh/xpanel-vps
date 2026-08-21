@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Rendimiento',
        'title' => 'CDN',
        'description' => 'Configura distribucion de contenido y cache perimetral para este sitio.',
        'actions' => [
            ['label' => 'Configurar CDN', 'icon' => 'ki-setting-2', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Estado', 'value' => 'Inactivo', 'icon' => 'ki-cloud'],
            ['label' => 'Cache', 'value' => 'Default', 'icon' => 'ki-archive'],
            ['label' => 'Purgas', 'value' => '0', 'icon' => 'ki-trash'],
        ],
        'cards' => [
            ['title' => 'Reglas de cache', 'body' => 'Define TTL, purga y excepciones por ruta.'],
            ['title' => 'Origen', 'body' => 'El origen sera el contenedor o servidor activo del dominio.'],
        ],
    ])
@endsection
