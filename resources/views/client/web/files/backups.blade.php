@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Archivos',
        'title' => 'Backups',
        'description' => 'Gestiona copias de seguridad del sitio, puntos de restauracion y descargas de respaldo.',
        'actions' => [
            ['label' => 'Crear backup', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Ultimo backup', 'value' => 'Pendiente', 'icon' => 'ki-time'],
            ['label' => 'Retencion', 'value' => '7 dias', 'icon' => 'ki-calendar'],
            ['label' => 'Estado', 'value' => 'Listo', 'icon' => 'ki-shield-tick'],
        ],
        'cards' => [
            [
                'title' => 'Backups del sitio',
                'body' => 'Aqui se listaran las copias disponibles para este dominio.',
                'items' => [
                    ['label' => 'Archivos web', 'value' => 'Programado'],
                    ['label' => 'Bases de datos', 'value' => 'Incluidas'],
                    ['label' => 'Restauracion', 'value' => 'Manual'],
                ],
            ],
            [
                'title' => 'Politica',
                'body' => 'La politica definira frecuencia, retencion y destino de almacenamiento.',
                'items' => [
                    ['label' => 'Frecuencia', 'value' => 'Diaria'],
                    ['label' => 'Destino', 'value' => 'Servidor actual'],
                    ['label' => 'Cifrado', 'value' => 'Pendiente'],
                ],
            ],
        ],
    ])
@endsection
