@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Fix file ownership',
        'description' => 'Repara propietarios y permisos de archivos del sitio.',
        'actions' => [
            ['label' => 'Reparar permisos', 'icon' => 'ki-wrench', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Estado', 'value' => 'Sin revisar', 'icon' => 'ki-information'],
            ['label' => 'Archivos', 'value' => '--', 'icon' => 'ki-file'],
            ['label' => 'Carpetas', 'value' => '--', 'icon' => 'ki-folder'],
        ],
        'cards' => [
            ['title' => 'Reparacion', 'body' => 'La accion ajustara ownership y permisos seguros dentro del root del sitio.'],
            ['title' => 'Precaucion', 'body' => 'El proceso debe respetar archivos sensibles y permisos especiales.'],
        ],
    ])
@endsection
