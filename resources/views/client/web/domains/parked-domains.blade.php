@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Dominios',
        'title' => 'Dominios aparcados',
        'description' => 'Asocia dominios adicionales para que sirvan el mismo contenido del sitio.',
        'actions' => [
            ['label' => 'Aparcar dominio', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Aparcados', 'value' => '0', 'icon' => 'ki-click'],
            ['label' => 'Principal', 'value' => $site->domain, 'icon' => 'ki-star'],
            ['label' => 'Estado', 'value' => 'Listo', 'icon' => 'ki-check'],
        ],
        'cards' => [
            ['title' => 'Alias del sitio', 'body' => 'Los dominios aparcados compartiran archivos y configuracion con el dominio principal.'],
            ['title' => 'Validacion', 'body' => 'XPanel verificara DNS antes de activar el dominio aparcado.'],
        ],
    ])
@endsection
