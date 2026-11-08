@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
 <div class="flex flex-col grow kt-scrollable-y pt-7"><main class="grow"><div class="kt-container-fluid grid gap-6">
  <div><h1 class="text-xl font-semibold text-mono">Motores de bases de datos</h1><p class="mt-1 text-sm text-muted-foreground">Los motores se instalan una vez en el nodo y quedan disponibles para todas las instancias Host. Cada cuenta conserva sus propias bases, usuarios y permisos.</p></div>
  @if(session('status'))<div class="rounded-lg border border-success/20 bg-success/10 px-4 py-3 text-sm text-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="rounded-lg border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>@endif
  <div class="grid gap-5 md:grid-cols-3">
   @foreach(collect($engines)->except('dbgate') as $slug => $engine)
    <section class="kt-card"><div class="kt-card-content flex h-full flex-col gap-4 p-6">
     <div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-semibold text-mono">{{ $engine['label'] }}</h2><div class="mt-1 text-xs text-muted-foreground">{{ $engine['version'] ?: 'Sin versión detectada' }}@if($engine['port']) · Puerto {{ $engine['port'] }}@endif</div></div><span class="kt-badge {{ $engine['installed'] ? 'kt-badge-success' : 'kt-badge-outline' }}">{{ $engine['installed'] ? 'Instalado' : 'Disponible' }}</span></div>
     <p class="grow text-sm text-muted-foreground">{{ $engine['description'] }}</p>
     @if($engine['error'])<div class="rounded-lg bg-danger/10 p-3 text-xs text-danger break-words">{{ $engine['error'] }}</div>@endif
     @if($engine['installed'])<button class="kt-btn kt-btn-outline w-full" disabled>Disponible para Host</button>@else<form method="post" action="{{ route('admin.settings.database-engines.install', $slug) }}" onsubmit="return confirm('¿Instalar {{ $engine['label'] }} en este VPS?');">@csrf<button class="kt-btn kt-btn-primary w-full">Instalar {{ $engine['label'] }}</button></form>@endif
    </div></section>
   @endforeach
  </div>
  @php($dbGate = $engines['dbgate'])
  <div class="grid gap-3"><div><h2 class="text-lg font-semibold text-mono">Administrador visual</h2><p class="mt-1 text-sm text-muted-foreground">Herramientas para consultar y administrar los motores instalados. No almacenan datos ni sustituyen al servidor de base de datos.</p></div>
   <section class="kt-card"><div class="kt-card-content flex items-center justify-between gap-5 p-6">
    <div><div class="flex items-center gap-2"><h3 class="font-semibold text-mono">{{ $dbGate['label'] }}</h3><span class="kt-badge {{ $dbGate['installed'] ? 'kt-badge-success' : 'kt-badge-outline' }}">{{ $dbGate['installed'] ? 'Instalado' : 'Disponible' }}</span></div><p class="mt-2 text-sm text-muted-foreground">{{ $dbGate['description'] }} No es un motor: se conecta a MariaDB, PostgreSQL y MongoDB.</p>@if($dbGate['version'])<div class="mt-1 text-xs text-muted-foreground">Versión {{ $dbGate['version'] }}</div>@endif @if($dbGate['error'])<div class="mt-3 rounded-lg bg-danger/10 p-3 text-xs text-danger break-words">{{ $dbGate['error'] }}</div>@endif</div>
    <div class="shrink-0">@if($dbGate['installed'])<button class="kt-btn kt-btn-outline" disabled>Disponible para Host</button>@else<form method="post" action="{{ route('admin.settings.database-engines.install', 'dbgate') }}" onsubmit="return confirm('¿Instalar DbGate en este VPS?');">@csrf<button class="kt-btn kt-btn-primary">Instalar DbGate</button></form>@endif</div>
   </div></section>
  </div>
  <div class="rounded-xl border border-border bg-muted/30 p-5 text-sm text-muted-foreground">MariaDB es el motor predeterminado. PostgreSQL y MongoDB son opcionales. Redis no forma parte de esta sección.</div>
 </div></main>@include('layouts.partials.admin.footer')</div>
</div>
@endsection
