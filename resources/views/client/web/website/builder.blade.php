@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Sitio web',
        'title' => 'Creador de sitios',
        'description' => 'Constructor visual preparado para futuras herramientas de edicion del sitio.',
        'actions' => [
            ['label' => 'Abrir builder', 'icon' => 'ki-screen', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Paginas', 'value' => '0', 'icon' => 'ki-document'],
            ['label' => 'Tema', 'value' => 'Default', 'icon' => 'ki-color-swatch'],
            ['label' => 'Estado', 'value' => 'Preparado', 'icon' => 'ki-check'],
        ],
        'cards' => [
            ['title' => 'Diseño visual', 'body' => 'Este modulo alojara el creador visual sin mezclarse con el gestor ikode.'],
            ['title' => 'Publicacion', 'body' => 'Los cambios podran publicarse en el root del dominio activo.'],
        ],
    ])
@endsection
