@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Dominios',
        'title' => 'Subdominios',
        'description' => 'Crea subdominios vinculados a carpetas o aplicaciones dentro de este sitio.',
        'actions' => [
            ['label' => 'Nuevo subdominio', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Subdominios', 'value' => '0', 'icon' => 'ki-click'],
            ['label' => 'DNS', 'value' => 'Pendiente', 'icon' => 'ki-router'],
            ['label' => 'SSL', 'value' => 'Pendiente', 'icon' => 'ki-shield-tick'],
        ],
        'cards' => [
            ['title' => 'Subdominios del sitio', 'body' => 'Aqui se listaran los subdominios de ' . $site->domain . '.'],
            ['title' => 'Destino', 'body' => 'Cada subdominio podra apuntar a una carpeta, app o redireccion.'],
        ],
    ])
@endsection
