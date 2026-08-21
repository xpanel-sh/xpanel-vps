@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Proteger directorios',
        'description' => 'Agrega autenticacion basica a carpetas del sitio.',
        'actions' => [
            ['label' => 'Proteger carpeta', 'icon' => 'ki-lock', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Carpetas', 'value' => '0', 'icon' => 'ki-folder'],
            ['label' => 'Usuarios', 'value' => '0', 'icon' => 'ki-user'],
            ['label' => 'Estado', 'value' => 'Sin reglas', 'icon' => 'ki-information'],
        ],
        'cards' => [
            ['title' => 'Protecciones', 'body' => 'Aqui apareceran carpetas protegidas por usuario y clave.'],
            ['title' => 'Alcance', 'body' => 'Cada regla se aplicara dentro del root del sitio seleccionado.'],
        ],
    ])
@endsection
