@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Rendimiento',
        'title' => 'AI troubleshooter',
        'description' => 'Diagnostico asistido para errores de carga, lentitud y configuracion del sitio.',
        'actions' => [
            ['label' => 'Analizar sitio', 'icon' => 'ki-artificial-intelligence', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Incidencias', 'value' => '0', 'icon' => 'ki-warning'],
            ['label' => 'Salud', 'value' => 'Normal', 'icon' => 'ki-pulse'],
            ['label' => 'Ultimo analisis', 'value' => 'Nunca', 'icon' => 'ki-time'],
        ],
        'cards' => [
            ['title' => 'Revision automatica', 'body' => 'El agente podra revisar logs, configuracion y archivos comunes del sitio.'],
            ['title' => 'Sugerencias', 'body' => 'Aqui apareceran recomendaciones accionables para corregir problemas.'],
        ],
    ])
@endsection
