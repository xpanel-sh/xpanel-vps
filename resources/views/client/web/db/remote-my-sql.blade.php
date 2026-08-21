@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Bases de datos',
        'title' => 'Remote MySQL',
        'description' => 'Controla IPs y hosts autorizados para conectarse a las bases de datos del sitio.',
        'actions' => [
            ['label' => 'Agregar host', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Hosts', 'value' => '0', 'icon' => 'ki-router'],
            ['label' => 'Modo', 'value' => 'Cerrado', 'icon' => 'ki-lock'],
            ['label' => 'Auditoria', 'value' => 'Activa', 'icon' => 'ki-notepad'],
        ],
        'cards' => [
            [
                'title' => 'Hosts permitidos',
                'body' => 'Lista de IPs autorizadas para conexiones externas.',
                'items' => [
                    ['label' => 'Wildcard', 'value' => 'No permitido'],
                    ['label' => 'Recomendacion', 'value' => 'IP fija'],
                ],
            ],
        ],
    ])
@endsection
