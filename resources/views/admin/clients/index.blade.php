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
                <h1 class="mt-2 text-3xl font-black tracking-tight">Clientes</h1>
                <p class="text-gray-400">Administra las cuentas de tus clientes y sus límites.</p>
            </div>
            <a href="{{ route('admin.clients.create') }}"
                class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-6 py-3 font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Cliente
            </a>
        </div>

        {{-- Buscador client-side --}}
        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"></path>
            </svg>
            <input x-model="search" type="text" placeholder="Buscar por nombre, dominio o email..."
                class="w-full rounded-xl border border-white/10 bg-white/[0.03] pl-11 pr-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:border-white/20">
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Empresa / Dominio</th>
                            <th class="px-6 py-4">Usuario Principal</th>
                            <th class="px-6 py-4">Plan</th>
                            <th class="px-6 py-4">Sitios</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($tenants as $tenant)
                            <tr class="hover:bg-white/[0.02] transition"
                                x-show="!search || '{{ strtolower($tenant->name . ' ' . $tenant->domain . ' ' . $tenant->user?->email) }}'.includes(search.toLowerCase())">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">{{ $tenant->name }}</div>
                                    <div class="text-sm text-gray-500 font-mono">{{ $tenant->domain }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="text-gray-300">{{ $tenant->user?->name ?? 'Sin asignar' }}</div>
                                    <div class="text-xs text-gray-500">{{ $tenant->user?->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">
                                    {{ $tenant->plan?->name ?? 'Sin plan' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-indigo-500/10 text-indigo-400 rounded-lg text-xs font-bold">
                                        {{ $tenant->sites->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase
                                        {{ $tenant->status === 'active'
                                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                                            : 'bg-red-500/10 text-red-400 border border-red-500/30' }}">
                                        {{ $tenant->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.clients.show', $tenant) }}"
                                        class="text-sm font-semibold text-indigo-400 hover:text-white transition">
                                        Ver detalle →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">No hay clientes registrados aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">
                {{ $tenants->links() }}
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
