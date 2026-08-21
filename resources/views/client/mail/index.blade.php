@extends('layouts.client')

@section('content')
    <div class="flex grow rounded-b-xl bg-background border-x border-b border-input lg:mt-(--navbar-height) mx-5 lg:ms-(--sidebar-width) mb-5">
        <div class="flex flex-col grow kt-scrollable-y lg:[scrollbar-width:auto] pt-7 lg:[&amp;_.kt-container-fluid]:pe-4" id="scrollable_content">
            <main class="grow" role="content">
                <div class="kt-container-fluid">
                    <div class="grid gap-5 lg:gap-7.5">
<section class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-gray-500">Panel Cliente</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Correos</h1>
                <p class="mt-2 text-gray-400">Crea y gestiona cuentas de correo para tus dominios.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('client.mail.xmail') }}"
                    class="flex items-center gap-2 rounded-xl border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition hover:bg-white/5">
                    XMail
                </a>
                <a href="{{ route('client.mail.create') }}"
                    class="flex items-center gap-2 rounded-xl bg-gray-900 dark:bg-white px-5 py-3 text-sm font-bold text-white dark:text-black transition hover:bg-gray-700 dark:hover:bg-gray-200">
                    Crear correo
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="text-xs uppercase tracking-widest text-gray-500 border-b border-white/10">
                        <tr>
                            <th class="px-6 py-4">Correo</th>
                            <th class="px-6 py-4">Dominio</th>
                            <th class="px-6 py-4">Cuota</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($accounts as $account)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 font-semibold text-white">{{ $account->email }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $account->domain?->domain }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ number_format($account->quota_mb / 1024, 1) }} GB</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClass = str_contains($account->status, 'error')
                                            ? 'border-red-500/30 bg-red-500/10 text-red-300'
                                            : ($account->status === 'provisioning'
                                                ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300');
                                    @endphp
                                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ strtoupper($account->status) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div x-data="{ open: false }" class="inline-block text-left relative">
                                        <button @click="open = !open" type="button"
                                            class="text-sm font-semibold text-white/80 hover:text-white transition px-3 py-1.5 rounded-lg hover:bg-white/5">
                                            Gestionar ▾
                                        </button>
                                        <div x-show="open" @click.outside="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             class="absolute right-0 mt-2 w-80 rounded-2xl border border-white/10 bg-[#13141a] p-4 shadow-2xl z-10"
                                             style="display:none;">
                                            <form action="{{ route('client.mail.reset-password', $account) }}" method="POST" class="space-y-3">
                                                @csrf
                                                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Cambiar contraseña</p>
                                                <input type="password" name="password" required minlength="12"
                                                    autocomplete="new-password"
                                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none focus:border-white/20"
                                                    placeholder="Nueva contraseña (mín. 12 chars)">
                                                <button class="w-full rounded-xl bg-white/10 hover:bg-white/15 px-4 py-2 text-sm font-semibold text-white transition">
                                                    Actualizar contraseña
                                                </button>
                                            </form>
                                            <div class="mt-3 pt-3 border-t border-white/10">
                                                <x-confirm-modal
                                                    action="{{ route('client.mail.destroy', $account) }}"
                                                    title="Eliminar cuenta de correo"
                                                    message="Se eliminará '{{ $account->email }}' permanentemente."
                                                    btnText="Eliminar correo"
                                                    triggerClass="w-full text-left rounded-xl border border-red-500/20 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/10 transition block"
                                                    triggerText="Eliminar cuenta"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    No tienes cuentas de correo todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $accounts->links() }}
    </section>
                    </div>
                </div>
            </main>

            @include('layouts.partials.client.footer')
        </div>
    </div>
@endsection
