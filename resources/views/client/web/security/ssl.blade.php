@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Seguridad',
        'title' => 'SSL',
        'description' => 'Administra certificados SSL y redireccion HTTPS del dominio activo.',
        'actions' => [
            ['label' => 'Emitir SSL', 'icon' => 'ki-shield-tick', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Certificado', 'value' => 'Pendiente', 'icon' => 'ki-award'],
            ['label' => 'HTTPS', 'value' => 'Pendiente', 'icon' => 'ki-lock'],
            ['label' => 'Renovacion', 'value' => 'Automatica', 'icon' => 'ki-arrows-circle'],
        ],
        'cards' => [
            ['title' => 'Certificado', 'body' => 'Informacion del certificado, emisor y vencimiento aparecera aqui.'],
            ['title' => 'Politicas', 'body' => 'Configura HTTPS forzado y renovacion automatica.'],
        ],
    ])
@endsection
