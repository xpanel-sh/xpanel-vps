@extends('layouts.admin')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Admin</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Servidores Conectados</h1>
                <p class="text-gray-400">Administra servidores donde corre el agente XPanel.</p>
            </div>
            <a href="{{ route('admin.servers.create') }}"
                class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-6 py-3 font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
                Agregar Servidor
            </a>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Nombre</th>
                            <th class="px-6 py-4">IP</th>
                            <th class="px-6 py-4">Puerto</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($nodes as $node)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 text-white font-semibold">{{ $node->name }}</td>
                                <td class="px-6 py-4 text-gray-400 font-mono text-sm">{{ $node->ip_address }}</td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ $node->port }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold border
                                        {{ $node->is_active
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                                            : 'bg-red-500/10 text-red-400 border-red-500/30' }}">
                                        {{ $node->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.servers.toggle', $node) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="text-sm font-semibold text-indigo-400 hover:text-white transition">
                                            {{ $node->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">No hay servidores conectados registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-white/10">
                {{ $nodes->links() }}
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
