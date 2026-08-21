@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin Global</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Dominios Globales</h1>
            <p class="mt-2 text-gray-400">Vista central de dominios registrados por todos los clientes.</p>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Sitio</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">DNS</th>
                            <th class="px-6 py-4">SSL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($domains as $domain)
                            <tr>
                                <td class="px-6 py-4 font-semibold text-white">{{ $domain->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $domain->tenant?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $domain->site?->domain ?? 'Sin asociar' }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ ucfirst($domain->type) }}</td>
                                <td class="px-6 py-4 text-yellow-300">{{ strtoupper($domain->dns_status) }}</td>
                                <td class="px-6 py-4 text-yellow-300">{{ strtoupper($domain->ssl_status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No hay dominios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $domains->links() }}
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.admin.footer')
        </div>
    </div>
@endsection
