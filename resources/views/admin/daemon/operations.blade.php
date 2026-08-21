@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <div class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Agente XPanel</div>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Operaciones del daemon</h1>
            <p class="mt-3 max-w-3xl text-gray-400">
                Historial operativo de solicitudes enviadas desde el panel hacia el agente local:
                sitios, DNS, correos, bases de datos y nameservers.
            </p>
        </div>
    </div>

    @if($error)
        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-5 text-sm text-red-100">
            No se pudo leer el historial del agente: {{ $error }}
        </div>
    @endif

    @if(!empty($runtime))
        @php
            $resources = $runtime['resources'] ?? [];
            $artifacts = $runtime['artifacts'] ?? [];
            $dnsZones = $artifacts['dns_zones'] ?? [];
            $mail = $artifacts['mail'] ?? [];
            $mailFiles = $mail['files'] ?? [];
        @endphp

        <div class="mb-8 grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Bases</div>
                <div class="mt-2 text-3xl font-black">{{ $resources['databases'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">DNS records</div>
                <div class="mt-2 text-3xl font-black">{{ $resources['dns_records'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Correos</div>
                <div class="mt-2 text-3xl font-black">{{ $resources['mail_accounts'] ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Operaciones</div>
                <div class="mt-2 text-3xl font-black">{{ $resources['operations'] ?? 0 }}</div>
            </div>
        </div>

        <div class="mb-8 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-sm font-bold text-white">Artefactos DNS</div>
                <div class="mt-3 text-sm text-gray-400">Zonas generadas: {{ $dnsZones['count'] ?? 0 }}</div>
                <div class="mt-2 break-all font-mono text-xs text-gray-500">{{ $dnsZones['path'] ?? '-' }}</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="text-sm font-bold text-white">Artefactos Mail</div>
                <div class="mt-3 grid gap-2 text-sm text-gray-400">
                    @foreach(['virtual_domains', 'virtual_mailboxes', 'virtual_quotas'] as $file)
                        <div class="flex items-center justify-between">
                            <span>{{ $file }}</span>
                            <span class="{{ !empty($mailFiles[$file]) ? 'text-emerald-300' : 'text-gray-600' }}">
                                {{ !empty($mailFiles[$file]) ? 'listo' : 'pendiente' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.03]">
        <div class="grid grid-cols-12 border-b border-white/10 px-5 py-3 text-xs font-bold uppercase tracking-widest text-gray-500">
            <div class="col-span-2">Tipo</div>
            <div class="col-span-2">Acción</div>
            <div class="col-span-2">Estado</div>
            <div class="col-span-3">Recurso</div>
            <div class="col-span-3">Fecha</div>
        </div>

        @forelse($operations as $operation)
            <div class="grid grid-cols-12 gap-3 border-b border-white/5 px-5 py-4 text-sm last:border-b-0">
                <div class="col-span-2 font-semibold text-white">{{ $operation['kind'] ?? '-' }}</div>
                <div class="col-span-2 text-gray-300">{{ $operation['action'] ?? '-' }}</div>
                <div class="col-span-2">
                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-200">
                        {{ $operation['status'] ?? '-' }}
                    </span>
                </div>
                <div class="col-span-3 break-all text-gray-300">{{ $operation['resource'] ?? '-' }}</div>
                <div class="col-span-3 text-gray-500">{{ $operation['created_at'] ?? '-' }}</div>
                @if(!empty($operation['message']))
                    <div class="col-span-12 rounded-2xl bg-black/30 px-4 py-3 text-xs text-gray-400">
                        {{ $operation['message'] }}
                    </div>
                @endif
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-500">
                Aún no hay operaciones registradas por el daemon.
            </div>
        @endforelse
    </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
