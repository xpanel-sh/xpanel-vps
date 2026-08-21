@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="space-y-6" x-data="{ search: '' }">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Sitios Globales</h1>
                <p class="text-gray-400">Vista global de todos los sitios alojados en el servidor.</p>
            </div>
        </div>

        {{-- Buscador client-side --}}
        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"></path>
            </svg>
            <input x-model="search" type="text" placeholder="Buscar por dominio o cliente..."
                class="w-full rounded-xl border border-white/10 bg-white/[0.03] pl-11 pr-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:border-white/20">
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">PHP</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4">Creado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($sites as $site)
                            <tr class="hover:bg-white/[0.02] transition"
                                x-show="!search || '{{ strtolower($site->domain . ' ' . ($site->tenant->name ?? '')) }}'.includes(search.toLowerCase())">
                                <td class="px-6 py-4 text-white font-semibold">{{ $site->domain }}</td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ $site->tenant?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold uppercase text-gray-400">{{ $site->project_type }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($site->php_version)
                                        <span class="px-2 py-1 bg-blue-500/10 text-blue-400 rounded-lg text-xs font-bold">{{ $site->php_version }}</span>
                                    @else
                                        <span class="text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($site->status === 'active')
                                        <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400">Activo</span>
                                    @elseif(str_contains($site->status, 'error'))
                                        <span class="rounded-full border border-red-500/30 bg-red-500/10 px-3 py-1 text-xs font-bold text-red-400">{{ ucfirst($site->status) }}</span>
                                    @else
                                        <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-400">{{ ucfirst($site->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $site->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.files.index', $site->domain) }}"
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-400 hover:text-white transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                        </svg>
                                        Archivos
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No hay sitios registrados en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">
                {{ $sites->links() }}
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
