@extends('layouts.home')
@section('title', $home['company_name'].' — Hosting')
@section('content')
<div class="kt-container-fixed pt-7.5 lg:pt-12.5">
    <div class="kt-card"><div class="kt-card-content flex flex-col items-center text-center px-5 py-10 lg:py-16">
        <span class="kt-badge kt-badge-outline kt-badge-success mb-5"><i class="ki-filled ki-verify"></i>{{ $home['company_tagline'] }}</span>
        <h1 class="text-3xl lg:text-5xl font-semibold text-mono max-w-[850px]">{{ $home['hero_title'] }}</h1>
        <p class="text-base text-secondary-foreground max-w-[650px] mt-5 leading-7">{{ $home['hero_description'] }}</p>
        <div class="flex flex-wrap justify-center gap-3 mt-7"><a class="kt-btn kt-btn-primary" href="#planes"><i class="ki-filled ki-handcart"></i> Ver planes</a><a class="kt-btn kt-btn-outline" href="{{ route('client.login') }}">Ya soy cliente</a></div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 w-full mt-10">
            @foreach([['ki-shield-tick','SSL incluido','Certificados automáticos'],['ki-code','PHP-FPM','Runtime nativo'],['ki-data','Bases de datos','MariaDB administrado'],['ki-cloud','Panel completo','Todo en un solo lugar']] as [$icon,$title,$text])
                <div class="kt-card"><div class="kt-card-content flex items-center gap-3 p-5 text-start"><i class="ki-filled {{ $icon }} text-2xl text-primary"></i><div><div class="text-sm font-semibold text-mono">{{ $title }}</div><div class="text-xs text-secondary-foreground mt-1">{{ $text }}</div></div></div></div>
            @endforeach
        </div>
    </div></div>
</div>
<div class="kt-container-fixed py-10 lg:py-16" id="planes">
    <div class="flex flex-col items-center text-center mb-8"><span class="kt-badge kt-badge-outline kt-badge-primary">Planes de hosting</span><h2 class="text-2xl lg:text-3xl font-semibold text-mono mt-3">Elige el entorno adecuado para tu proyecto</h2><p class="text-secondary-foreground mt-2">Cada plan incluye acceso a tu propio panel XPanel Host.</p></div>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-7.5">
        @forelse($plans as $plan)
            <div class="kt-card {{ $loop->iteration === 2 ? 'border-primary' : '' }}"><div class="kt-card-content flex flex-col p-7.5">
                <div class="flex items-center justify-between gap-3"><h3 class="text-xl text-mono font-semibold">{{ $plan->name }}</h3>@if($loop->iteration === 2)<span class="kt-badge kt-badge-success kt-badge-outline">Recomendado</span>@endif</div>
                <p class="text-sm text-secondary-foreground min-h-12 mt-3">{{ $plan->description ?: 'Hosting administrado para publicar y crecer con tranquilidad.' }}</p>
                <div class="flex items-end gap-1.5 py-6"><span class="text-3xl text-mono font-semibold">{{ $home['currency_symbol'] }}{{ number_format((float) $plan->monthly_price, 2) }}</span><span class="text-secondary-foreground text-xs pb-1">por mes</span></div>
                <div class="grid gap-3 text-sm text-foreground grow">
                    <div class="flex items-center gap-2"><i class="ki-filled ki-check text-green-500"></i>{{ $plan->max_sites ?: 'Ilimitados' }} sitios web</div>
                    <div class="flex items-center gap-2"><i class="ki-filled ki-check text-green-500"></i>{{ $plan->max_databases ?: 'Ilimitadas' }} bases de datos</div>
                    <div class="flex items-center gap-2"><i class="ki-filled ki-check text-green-500"></i>{{ $plan->storage_mb >= 1024 ? number_format($plan->storage_mb / 1024, 0).' GB' : $plan->storage_mb.' MB' }} de almacenamiento</div>
                    <div class="flex items-center gap-2"><i class="ki-filled ki-check text-green-500"></i>{{ $plan->bandwidth_gb > 0 ? $plan->bandwidth_gb.' GB/mes de referencia (aviso; sin corte)' : 'Transferencia sin umbral de aviso definido' }}</div>
                    <div class="flex items-center gap-2"><i class="ki-filled ki-check text-green-500"></i>{{ $plan->email_accounts ?: 'Sin' }} cuentas de correo</div>
                </div>
                <a href="{{ route('client.login', ['plan' => $plan->slug]) }}" class="kt-btn {{ $loop->iteration === 2 ? 'kt-btn-primary' : 'kt-btn-outline' }} justify-center w-full mt-7">Elegir {{ $plan->name }}</a>
            </div></div>
        @empty
            <div class="kt-card md:col-span-2 xl:col-span-3"><div class="kt-card-content text-center py-10 text-secondary-foreground">Los planes estarán disponibles muy pronto.</div></div>
        @endforelse
    </div>
</div>
<div class="bg-muted/40 border-y border-border"><div class="kt-container-fixed py-10 lg:py-16"><div class="grid lg:grid-cols-2 gap-7.5 items-center"><div><span class="kt-badge kt-badge-outline">{{ $home['company_name'] }}</span><h2 class="text-2xl lg:text-3xl font-semibold text-mono mt-4">Infraestructura profesional con una experiencia sencilla.</h2><p class="text-secondary-foreground leading-7 mt-4">{{ $home['company_description'] }}</p><a class="kt-btn kt-btn-outline mt-6" href="{{ route('pages.show', 'about') }}">Conocer la empresa</a></div><div class="kt-card"><div class="kt-card-content grid grid-cols-2 gap-5 p-7.5">@foreach([['99.9%','Disponibilidad'],['24/7','Acceso al panel'],['1 panel','Toda la operación'],['SSL','Por cada sitio']] as [$value,$label])<div><div class="text-2xl font-semibold text-primary">{{ $value }}</div><div class="text-sm text-secondary-foreground mt-1">{{ $label }}</div></div>@endforeach</div></div></div></div></div>
@endsection
