@extends('layouts.admin')

@section('content')
    @php
        $resources = $runtime['resources'] ?? [];
    @endphp

    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div>
                            <div class="text-sm font-semibold text-secondary-foreground">Broker privilegiado</div>
                            <h1 class="mt-1 text-2xl font-semibold text-mono">Operaciones de instancias Host</h1>
                            <p class="mt-2 max-w-3xl text-sm text-secondary-foreground">
                                Solicitudes verificadas que las instancias envían a XPanel VPS para aplicar cambios nativos en el servidor.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-5 xl:grid-cols-4">
                            @foreach([
                                ['label' => 'Bases', 'value' => $resources['databases'] ?? 0],
                                ['label' => 'Registros DNS', 'value' => $resources['dns_records'] ?? 0],
                                ['label' => 'Correos', 'value' => $resources['mail_accounts'] ?? 0],
                                ['label' => 'Operaciones', 'value' => $resources['operations'] ?? 0],
                            ] as $summary)
                                <div class="kt-card">
                                    <div class="kt-card-content p-5">
                                        <div class="text-sm text-secondary-foreground">{{ $summary['label'] }}</div>
                                        <div class="mt-2 text-3xl font-semibold text-mono">{{ $summary['value'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="kt-card kt-card-grid min-w-full">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">Historial del broker</h3>
                                <span class="text-xs text-secondary-foreground">Últimas 100 operaciones</span>
                            </div>
                            <div class="kt-card-table">
                                <div class="kt-scrollable-x-auto">
                                    <table class="kt-table kt-table-border min-w-[900px]">
                                        <thead>
                                            <tr>
                                                <th>Instancia</th>
                                                <th>Acción</th>
                                                <th>Estado</th>
                                                <th>Solicitud</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($operations as $operation)
                                                @php
                                                    $statusClass = match ($operation->status) {
                                                        'completed', 'success', 'applied' => 'kt-badge-success',
                                                        'failed', 'error', 'rejected' => 'kt-badge-destructive',
                                                        default => 'kt-badge-warning',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td class="font-medium text-mono">{{ $operation->instance?->panel_domain ?? '-' }}</td>
                                                    <td>{{ $operation->action }}</td>
                                                    <td>
                                                        <span class="kt-badge kt-badge-sm kt-badge-outline {{ $statusClass }}">
                                                            {{ $operation->status }}
                                                        </span>
                                                    </td>
                                                    <td class="font-mono text-xs">{{ $operation->request_id }}</td>
                                                    <td class="text-secondary-foreground">{{ $operation->created_at?->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                                @if($operation->error || $operation->output)
                                                    <tr>
                                                        <td colspan="5" class="text-xs {{ $operation->error ? 'text-destructive' : 'text-secondary-foreground' }}">
                                                            {{ $operation->error ?: $operation->output }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-10 text-center text-sm text-secondary-foreground">
                                                        Aún no hay operaciones registradas por las instancias.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
