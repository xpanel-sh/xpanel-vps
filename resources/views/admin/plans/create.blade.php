@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="grid gap-5 lg:gap-7.5">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="font-medium text-lg text-mono">Crear plan de hosting</h1>
                <div class="flex items-center gap-1 text-sm">
                    <a class="text-secondary-foreground hover:text-primary" href="{{ route('admin.plans.index') }}">Planes</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-mono">Nuevo</span>
                </div>
            </div>
            <a href="{{ route('admin.plans.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Volver</a>
        </div>

        <form action="{{ route('admin.plans.store') }}" method="POST">
            @include('admin.plans._form')
        </form>
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
