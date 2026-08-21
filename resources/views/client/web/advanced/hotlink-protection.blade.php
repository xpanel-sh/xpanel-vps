@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Hotlink protection',
        'description' => 'Evita que otros dominios consuman recursos estaticos del sitio.',
        'actions' => [
            ['label' => 'Activar proteccion', 'icon' => 'ki-shield-tick', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Estado', 'value' => 'Inactivo', 'icon' => 'ki-shield'],
            ['label' => 'Dominios permitidos', 'value' => '1', 'icon' => 'ki-click'],
            ['label' => 'Recursos', 'value' => 'Imagenes', 'icon' => 'ki-picture'],
        ],
        'cards' => [
            ['title' => 'Proteccion de recursos', 'body' => 'Controla imagenes, videos y descargas enlazadas desde terceros.'],
            ['title' => 'Dominios permitidos', 'body' => 'El dominio principal siempre quedara permitido.'],
        ],
    ])
@endsection
