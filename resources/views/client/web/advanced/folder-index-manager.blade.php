@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Folder index manager',
        'description' => 'Controla si las carpetas sin index muestran listado publico.',
        'actions' => [
            ['label' => 'Configurar', 'icon' => 'ki-setting-2', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Indexacion', 'value' => 'Desactivada', 'icon' => 'ki-folder'],
            ['label' => 'Carpetas', 'value' => 'Global', 'icon' => 'ki-abstract-26'],
            ['label' => 'Seguridad', 'value' => 'Recomendada', 'icon' => 'ki-shield-tick'],
        ],
        'cards' => [
            ['title' => 'Listado de carpetas', 'body' => 'Activa o desactiva directory listing por sitio o carpeta.'],
            ['title' => 'Excepciones', 'body' => 'Permite reglas especificas sin exponer todo el root.'],
        ],
    ])
@endsection
