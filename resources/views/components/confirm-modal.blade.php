@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Confirmar acción',
    'message' => '¿Estás seguro? Esta acción no se puede deshacer.',
    'btnText' => 'Eliminar',
    'triggerClass' => 'text-sm font-semibold text-red-400 dark:text-red-300 hover:text-red-600 dark:hover:text-red-100 transition',
    'triggerText' => 'Eliminar',
])
<div x-data="{ open: false }">
    <button type="button" @click="open = true" class="{{ $triggerClass }}">{{ $triggerText }}</button>

    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         @click.self="open = false" style="display:none;">
        <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#13141a] p-6 shadow-2xl">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-red-500/10">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">{{ $title }}</h3>
            <p class="text-sm text-white/60 mb-6">{{ $message }}</p>
            <div class="flex gap-3 justify-end">
                <button @click="open = false" type="button"
                    class="px-4 py-2 rounded-xl border border-white/10 text-sm text-white/70 hover:bg-white/5 transition">
                    Cancelar
                </button>
                <form action="{{ $action }}" method="POST" class="inline">
                    @csrf
                    @method($method)
                    <button type="submit"
                        class="px-4 py-2 rounded-xl bg-red-500/20 text-sm font-semibold text-red-300 hover:bg-red-500/30 transition">
                        {{ $btnText }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
