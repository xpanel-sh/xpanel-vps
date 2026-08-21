@extends('layouts.client')

@section('content')
    @include('layouts.partials.client.web-module-page', [
        'sectionLabel' => 'Avanzado',
        'title' => 'Cron jobs',
        'description' => 'Programa tareas recurrentes para ejecutar comandos o URLs del sitio.',
        'actions' => [
            ['label' => 'Nueva tarea', 'icon' => 'ki-plus', 'style' => 'kt-btn-primary'],
        ],
        'metrics' => [
            ['label' => 'Tareas', 'value' => '0', 'icon' => 'ki-calendar'],
            ['label' => 'Activas', 'value' => '0', 'icon' => 'ki-check'],
            ['label' => 'Errores', 'value' => '0', 'icon' => 'ki-warning'],
        ],
        'cards' => [
            ['title' => 'Programacion', 'body' => 'Define frecuencia, comando y usuario de ejecucion.'],
            ['title' => 'Historial', 'body' => 'Las ultimas ejecuciones y salidas se mostraran aqui.'],
        ],
    ])
@endsection
