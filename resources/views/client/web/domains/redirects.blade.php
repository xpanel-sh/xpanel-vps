@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Dominios',
        'title' => 'Redirecciones',
        'description' => 'Configura redirecciones 301, 302 o reglas simples para este sitio.',
        'actions' => [
            ['label' => 'Nueva redireccion', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Reglas', 'value' => '0', 'icon' => 'ki-route'],
            ['label' => 'Tipo default', 'value' => '301', 'icon' => 'ki-arrow-right'],
            ['label' => 'Estado', 'value' => 'Sin reglas', 'icon' => 'ki-information'],
        ],
        'cards' => [
            ['title' => 'Reglas activas', 'body' => 'Aqui se mostraran las redirecciones creadas para rutas o dominios.'],
            ['title' => 'Buenas practicas', 'body' => 'Usa 301 para cambios permanentes y 302 para pruebas temporales.'],
        ],
    ])
@endsection
