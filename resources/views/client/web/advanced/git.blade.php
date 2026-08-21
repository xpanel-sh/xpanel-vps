@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Git',
        'description' => 'Conecta repositorios y despliegues controlados para este sitio.',
        'actions' => [
            ['label' => 'Conectar repo', 'icon' => 'ki-git', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Repositorio', 'value' => 'No conectado', 'icon' => 'ki-git'],
            ['label' => 'Rama', 'value' => '--', 'icon' => 'ki-abstract-32'],
            ['label' => 'Deploy', 'value' => 'Manual', 'icon' => 'ki-exit-down'],
        ],
        'cards' => [
            ['title' => 'Repositorio', 'body' => 'Configura origen, rama, deploy key y carpeta destino.'],
            ['title' => 'Despliegues', 'body' => 'Historial de commits desplegados y resultado de cada ejecucion.'],
        ],
    ])
@endsection
