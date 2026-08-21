@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'PHP info',
        'description' => 'Consulta informacion del entorno PHP activo para este dominio.',
        'actions' => [
            ['label' => 'Ver phpinfo', 'icon' => 'ki-eye', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Version', 'value' => $site->php_version ?? '8.2', 'icon' => 'ki-code'],
            ['label' => 'Servidor', 'value' => $site->web_server ?? 'apache', 'icon' => 'ki-server'],
            ['label' => 'Proyecto', 'value' => $site->project_type ?? 'php', 'icon' => 'ki-element-11'],
        ],
        'cards' => [
            ['title' => 'Entorno', 'body' => 'Informacion de PHP, modulos cargados y configuracion del proceso.'],
            ['title' => 'Seguridad', 'body' => 'La vista final debera proteger phpinfo para no exponer datos publicos.'],
        ],
    ])
@endsection
