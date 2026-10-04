@extends('layouts.admin')

@section('content')
<div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
    <main class="grow p-7">
        <div class="mb-6">
            <h1 class="text-3xl font-black">Instancias XPanel Host</h1>
            <p class="mt-2 text-gray-400">Entornos independientes administrados desde este servidor.</p>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="w-full min-w-[900px] text-left">
                <thead class="text-xs uppercase tracking-widest text-gray-500"><tr><th class="px-6 py-4">Cliente</th><th class="px-6 py-4">Dominio</th><th class="px-6 py-4">Versión</th><th class="px-6 py-4">PHP</th><th class="px-6 py-4">Estado</th><th class="px-6 py-4"></th></tr></thead>
                <tbody class="divide-y divide-white/10">
                @forelse($instances as $instance)
                    <tr>
                        <td class="px-6 py-4 font-semibold">{{ $instance->tenant->name }}</td>
                        <td class="px-6 py-4"><a class="text-primary hover:underline" href="{{ route('admin.instances.access', $instance) }}" target="_blank" rel="noopener">{{ $instance->panel_domain }}</a></td>
                        <td class="px-6 py-4 text-gray-400">{{ $instance->version ?? '—' }} / {{ $instance->update_channel }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $instance->php_version }}</td>
                        <td class="px-6 py-4"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ strtoupper($instance->status) }}</span></td>
                        <td class="px-6 py-4 text-right"><a class="text-sm font-bold hover:text-primary" href="{{ route('admin.clients.show', $instance->tenant) }}">Gestionar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Todavía no hay instancias.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $instances->links() }}</div>
    </main>
</div>
@endsection
