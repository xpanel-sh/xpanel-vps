@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Bases de datos',
        'title' => 'phpMyAdmin',
        'description' => 'Accede a phpMyAdmin para inspeccionar y editar las bases del sitio.',
        'actions' => [
            ['label' => 'Abrir phpMyAdmin', 'icon' => 'ki-exit-right-corner', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Sesion', 'value' => 'No iniciada', 'icon' => 'ki-user'],
            ['label' => 'Conexion', 'value' => 'Local', 'icon' => 'ki-data'],
            ['label' => 'Permisos', 'value' => 'Seguros', 'icon' => 'ki-shield-tick'],
        ],
        'cards' => [
            [
                'title' => 'Acceso rapido',
                'body' => 'Desde aqui se abrira una sesion segura hacia phpMyAdmin.',
                'items' => [
                    ['label' => 'Dominio', 'value' => $site->domain],
                    ['label' => 'Metodo', 'value' => 'Token temporal'],
                ],
            ],
        ],
    ])
@endsection
