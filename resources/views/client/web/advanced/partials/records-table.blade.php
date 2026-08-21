{{-- Records table partial — used by dns-zone-editor in all modes --}}
<div class="kt-card overflow-visible">
    <div class="kt-card-header min-h-14">
        <div>
            <h2 class="kt-card-title">Registros actuales</h2>
            <p class="text-xs text-secondary-foreground mt-0.5">{{ $domain }}</p>
        </div>
        <span class="kt-badge kt-badge-outline">{{ $records instanceof \Illuminate\Pagination\AbstractPaginator ? $records->total() : count($records) }}</span>
    </div>

    @if($records->isEmpty())
        <div class="flex flex-col items-center gap-3 py-14 text-center border-t border-border">
            <i class="ki-filled ki-setting-2 text-3xl text-secondary-foreground opacity-30"></i>
            <p class="text-sm text-secondary-foreground">Aún no hay registros DNS para este dominio.</p>
        </div>
    @else
        <div class="border-t border-border overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground w-20">Tipo</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Nombre</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Valor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-secondary-foreground w-24">TTL</th>
                        <th class="px-5 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        @php
                            $typeColors = [
                                'A'=>'bg-blue-500/10 text-blue-600 border-blue-500/20',
                                'AAAA'=>'bg-indigo-500/10 text-indigo-600 border-indigo-500/20',
                                'CNAME'=>'bg-purple-500/10 text-purple-600 border-purple-500/20',
                                'MX'=>'bg-amber-500/10 text-amber-600 border-amber-500/20',
                                'TXT'=>'bg-green-500/10 text-green-600 border-green-500/20',
                                'NS'=>'bg-sky-500/10 text-sky-600 border-sky-500/20',
                                'SRV'=>'bg-orange-500/10 text-orange-600 border-orange-500/20',
                                'CAA'=>'bg-rose-500/10 text-rose-600 border-rose-500/20',
                            ];
                            $tc = $typeColors[$record->type] ?? 'bg-muted text-secondary-foreground border-border';
                            $ttlMap = [300=>'5m',1800=>'30m',3600=>'1h',14400=>'4h',86400=>'24h'];
                        @endphp
                        <tr class="border-b border-border last:border-b-0 hover:bg-muted/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-bold {{ $tc }}">{{ $record->type }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <code class="rounded bg-muted px-2 py-0.5 text-xs font-mono text-mono">{{ $record->name }}</code>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <span class="block truncate font-mono text-xs text-secondary-foreground" title="{{ $record->value }}">{{ $record->value }}</span>
                                @if($record->priority !== null)
                                    <span class="text-xs text-secondary-foreground opacity-70">Pri: {{ $record->priority }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-secondary-foreground whitespace-nowrap">
                                {{ $ttlMap[$record->ttl] ?? $record->ttl.'s' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <x-confirm-modal
                                    action="{{ route('client.websites.dns-zone-editor.destroy', [$domain, $record]) }}"
                                    title="Eliminar registro DNS"
                                    message="Se eliminará el registro {{ $record->type }} '{{ $record->name }}'. Esta acción se sincroniza con el servidor DNS."
                                    btnText="Eliminar"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($records instanceof \Illuminate\Pagination\AbstractPaginator && $records->hasPages())
            <div class="px-5 py-4 border-t border-border">
                {{ $records->links() }}
            </div>
        @endif
    @endif
</div>
