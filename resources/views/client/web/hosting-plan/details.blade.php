@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Plan de hosting',
        'title' => 'Detalles del pedido',
        'description' => 'Resumen del plan, servidor y recursos asociados a este sitio.',
        'metrics' => [
            ['label' => 'Plan', 'value' => 'Actual', 'icon' => 'ki-dollar'],
            ['label' => 'Servidor', 'value' => $site->web_server ?? 'apache', 'icon' => 'ki-server'],
            ['label' => 'PHP', 'value' => $site->php_version ?? '8.2', 'icon' => 'ki-code'],
        ],
        'cards' => [
            ['title' => 'Informacion del servicio', 'body' => 'Aqui se mostraran datos del plan contratado para este sitio.'],
            ['title' => 'Recursos incluidos', 'body' => 'Espacio, bases, correos y limites se integraran en esta vista.'],
        ],
    ])
@endsection
