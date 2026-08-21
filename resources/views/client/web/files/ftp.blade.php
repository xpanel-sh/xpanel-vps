@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Archivos',
        'title' => 'Cuentas FTP',
        'description' => 'Administra accesos FTP/SFTP limitados al sitio seleccionado.',
        'actions' => [
            ['label' => 'Nueva cuenta', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Cuentas', 'value' => '0', 'icon' => 'ki-user'],
            ['label' => 'Directorio', 'value' => '/', 'icon' => 'ki-folder'],
            ['label' => 'Acceso', 'value' => 'Seguro', 'icon' => 'ki-lock'],
        ],
        'cards' => [
            [
                'title' => 'Accesos del sitio',
                'body' => 'Aqui apareceran las credenciales FTP creadas para este dominio.',
                'items' => [
                    ['label' => 'Usuario principal', 'value' => 'Sin crear'],
                    ['label' => 'Permisos', 'value' => 'Lectura/escritura'],
                    ['label' => 'Ruta base', 'value' => $site->domain],
                ],
            ],
            [
                'title' => 'Seguridad',
                'body' => 'Las cuentas podran limitarse por carpeta, IP y estado activo.',
                'items' => [
                    ['label' => 'SFTP', 'value' => 'Recomendado'],
                    ['label' => 'Rotacion', 'value' => 'Manual'],
                    ['label' => 'Logs', 'value' => 'Pendiente'],
                ],
            ],
        ],
    ])
@endsection
