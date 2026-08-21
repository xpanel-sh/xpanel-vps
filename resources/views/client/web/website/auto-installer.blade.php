@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Sitio web',
        'title' => 'Instalador automatico',
        'description' => 'Instala aplicaciones populares con configuracion asistida.',
        'metrics' => [
            ['label' => 'Apps', 'value' => 'Pronto', 'icon' => 'ki-abstract-26'],
            ['label' => 'Runtime', 'value' => $site->project_type ?? 'php', 'icon' => 'ki-code'],
            ['label' => 'Servidor', 'value' => $site->web_server ?? 'apache', 'icon' => 'ki-setting'],
        ],
        'cards' => [
            ['title' => 'Catalogo', 'body' => 'Aqui apareceran instaladores para CMS, frameworks y herramientas del sitio.'],
            ['title' => 'Provisionamiento', 'body' => 'Cada instalador podra crear archivos, bases y tareas necesarias.'],
        ],
    ])
@endsection
