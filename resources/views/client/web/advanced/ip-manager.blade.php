@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'IP manager',
        'description' => 'Permite o bloquea IPs para proteger rutas y accesos del sitio.',
        'actions' => [
            ['label' => 'Nueva regla', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Reglas', 'value' => '0', 'icon' => 'ki-router'],
            ['label' => 'Bloqueadas', 'value' => '0', 'icon' => 'ki-cross'],
            ['label' => 'Permitidas', 'value' => '0', 'icon' => 'ki-check'],
        ],
        'cards' => [
            ['title' => 'Listas de acceso', 'body' => 'Controla allowlist y blocklist por IP o rango CIDR.'],
            ['title' => 'Auditoria', 'body' => 'Las coincidencias de reglas se podran revisar desde el historial.'],
        ],
    ])
@endsection
