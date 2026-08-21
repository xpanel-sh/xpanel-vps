@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Sitio web',
        'title' => 'Migrar sitio web',
        'description' => 'Importa archivos y bases desde otro proveedor hacia este dominio.',
        'actions' => [
            ['label' => 'Nueva migracion', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Migraciones', 'value' => '0', 'icon' => 'ki-arrows-circle'],
            ['label' => 'Origen', 'value' => 'Externo', 'icon' => 'ki-exit-left'],
            ['label' => 'Destino', 'value' => $site->domain, 'icon' => 'ki-exit-right'],
        ],
        'cards' => [
            ['title' => 'Asistente de migracion', 'body' => 'El asistente recopilara acceso FTP, base de datos y dominio origen.'],
            ['title' => 'Verificacion', 'body' => 'Antes de publicar se podran revisar archivos importados y estado del sitio.'],
        ],
    ])
@endsection
