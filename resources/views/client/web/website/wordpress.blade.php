@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Sitio web',
        'title' => 'Instalar WordPress',
        'description' => 'Prepara una instalacion WordPress sobre el dominio activo.',
        'actions' => [
            ['label' => 'Iniciar instalacion', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Version', 'value' => 'Ultima', 'icon' => 'ki-code'],
            ['label' => 'SSL', 'value' => 'Recomendado', 'icon' => 'ki-shield-tick'],
            ['label' => 'Base de datos', 'value' => 'Automatica', 'icon' => 'ki-data'],
        ],
        'cards' => [
            ['title' => 'Instalacion guiada', 'body' => 'XPanel creara archivos, base de datos y configuracion inicial para WordPress.'],
            ['title' => 'Destino', 'body' => 'La instalacion usara el root web del dominio ' . $site->domain . '.'],
        ],
    ])
@endsection
