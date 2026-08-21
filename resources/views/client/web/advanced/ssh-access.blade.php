@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Acceso SSH',
        'description' => 'Gestiona acceso shell seguro para operaciones avanzadas del sitio.',
        'actions' => [
            ['label' => 'Crear acceso', 'icon' => 'ki-key', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Usuarios', 'value' => '0', 'icon' => 'ki-user'],
            ['label' => 'Puerto', 'value' => '22', 'icon' => 'ki-router'],
            ['label' => 'Estado', 'value' => 'Desactivado', 'icon' => 'ki-lock'],
        ],
        'cards' => [
            ['title' => 'Credenciales', 'body' => 'Aqui se administraran usuarios SSH limitados al sitio.'],
            ['title' => 'Llaves', 'body' => 'Se podran registrar llaves publicas para acceso seguro.'],
        ],
    ])
@endsection
