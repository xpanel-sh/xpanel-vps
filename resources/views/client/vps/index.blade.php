@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-(--header-height) m-5">
        <div class="flex flex-col grow kt-scrollable-y-auto lg:[--kt-scrollbar-width:auto] pt-5" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="kt-card">
                        <div class="kt-card-content p-6">
                            <span class="kt-badge kt-badge-outline kt-badge-primary">VPS</span>
                            <h1 class="mt-3 text-2xl font-semibold text-mono">Servidores VPS</h1>
                            <p class="mt-2 text-sm text-secondary-foreground">Vista preparada para gestionar servidores VPS del cliente.</p>
                        </div>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
