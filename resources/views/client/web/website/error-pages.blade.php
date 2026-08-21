@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Sitio web',
        'title' => 'Paginas de error',
        'description' => 'Personaliza respuestas 404, 403 y errores del sitio.',
        'actions' => [
            ['label' => 'Editar 404', 'icon' => 'ki-pencil', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => '404', 'value' => 'Default', 'icon' => 'ki-information'],
            ['label' => '403', 'value' => 'Default', 'icon' => 'ki-lock'],
            ['label' => '500', 'value' => 'Default', 'icon' => 'ki-warning'],
        ],
        'cards' => [
            ['title' => 'Plantillas', 'body' => 'Administra paginas de error propias para mejorar la experiencia del usuario.'],
            ['title' => 'Publicacion', 'body' => 'Los cambios se aplicaran solo al dominio seleccionado.'],
        ],
    ])
@endsection
