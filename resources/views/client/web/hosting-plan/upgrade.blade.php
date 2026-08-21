@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Plan de hosting',
        'title' => 'Mejorar plan',
        'description' => 'Compara opciones de mejora para aumentar recursos del sitio.',
        'actions' => [
            ['label' => 'Ver planes', 'icon' => 'ki-arrow-up', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Plan actual', 'value' => 'Base', 'icon' => 'ki-dollar'],
            ['label' => 'Recomendado', 'value' => 'Pro', 'icon' => 'ki-star'],
            ['label' => 'Cambio', 'value' => 'Pendiente', 'icon' => 'ki-arrows-circle'],
        ],
        'cards' => [
            ['title' => 'Comparativa', 'body' => 'Aqui se listaran planes disponibles y diferencias principales.'],
            ['title' => 'Aplicacion', 'body' => 'El upgrade podra programarse sin afectar la configuracion del sitio.'],
        ],
    ])
@endsection
