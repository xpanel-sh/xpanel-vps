@php
    $isAdminFiles = ($scope ?? 'admin') === 'admin';
    $filesTitle = $isAdminFiles ? 'Archivos [Admin]' : 'Archivos';
    $filesBaseUrl = $isAdminFiles ? url('/admin/files/api') : url('/client/files/api');
    $filesDomain = $domain ?? null;
    $filesBackRoute = $isAdminFiles
        ? route('admin.sites.index')
        : ($filesDomain ? route('client.website.file-manager.entry', ['domain' => $filesDomain]) : route('client.websites.index'));
    $filesSites = ($sites ?? collect())->map(fn ($site) => [
        'domain' => $site->domain,
        'tenant' => $site->tenant->name ?? null,
        'url' => $isAdminFiles ? route('admin.files.index', $site->domain) : route('client.website.file-manager.ikode', ['domain' => $site->domain]),
    ])->values();
@endphp

@push('styles')
    <link href="{{ asset('assets/files/editor.css') }}" rel="stylesheet" />
    <style>
        .xpanel-file-shell {
            height: 100%;
            min-height: 620px;
            grid-template-rows: 46px minmax(0, 1fr);
        }
        .xpanel-file-shell .ikode_editor_left {
            flex: 0 0 300px;
            min-width: 240px;
            width: auto;
            display: flex;
            flex-direction: column;
        }
        .xpanel-file-shell .ikode_editor_left .ikode_left_mode_panel {
            flex: 1 1 auto;
            min-height: 0;
        }
        .xpanel-file-shell .ikode_editor_right { flex: 0 0 310px; min-width: 260px; width: auto; }
        .xpanel-file-shell .ikode_editor_center {
            flex: 1 1 auto;
            min-width: 360px;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .xpanel-file-shell .ikode_editor_split {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .xpanel-file-shell .ikode_editor_codepane { flex: 1 1 auto; min-height: 0; }
        .xpanel-file-shell .ikode_editor_bottom { flex: 0 0 220px; min-height: 120px; }
        .xpanel-file-shell .ikode_editor_right.is-start-hidden { display: none; }
        .xpanel-file-shell .ikode_tabs_actions {
            height: 35px;
            padding: 4px 6px;
            gap: 5px;
            align-items: center;
            flex: 0 0 auto;
        }
        .xpanel-file-shell .ikode_tabs_action_btn {
            width: 26px;
            height: 26px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            line-height: 1;
            flex: 0 0 auto;
        }
        .xpanel-file-shell .ikode_tabs_action_btn:hover,
        .xpanel-file-shell .ikode_tabs_action_btn.is-active {
            background: rgba(59, 130, 246, .18);
        }
        .xpanel-file-shell .xpanel-editor-group-tabs {
            flex: 0 0 35px;
            min-width: 0;
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid hsl(var(--border));
            background: hsl(var(--background));
        }
        .xpanel-file-shell .xpanel-editor-groups {
            width: 100%;
            height: 100%;
            min-width: 0;
            min-height: 0;
            display: flex;
            overflow: hidden;
        }
        .xpanel-file-shell .xpanel-editor-group {
            flex: 1 1 0;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: hsl(var(--background));
        }
        .xpanel-file-shell .xpanel-editor-group-close {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: var(--muted-foreground);
        }
        .xpanel-file-shell .xpanel-editor-group-close:hover {
            background: hsl(var(--muted));
            color: hsl(var(--foreground));
        }
        .xpanel-file-shell .xpanel-editor-group-body {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
        }
        .xpanel-file-shell .xpanel-editor-group-body .ikode_monaco {
            width: 100%;
            height: 100%;
            min-width: 0;
        }
        .xpanel-file-shell.xpanel-fullscreen {
            position: fixed;
            inset: 10px;
            z-index: 80;
            height: calc(100dvh - 20px);
            min-height: 0;
            border-radius: 10px;
        }
        .xpanel-file-shell .xpanel-site-select {
            width: min(240px, 28vw);
            height: 28px;
            font-size: 12px;
            padding-top: 0;
            padding-bottom: 0;
        }
        .xpanel-file-row {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 28px;
            padding: 4px 8px;
            border-radius: 8px;
            color: var(--muted-foreground);
            cursor: pointer;
            user-select: none;
        }
        .xpanel-file-tree {
            padding: 4px;
            min-height: 100%;
        }
        .xpanel-file-row:hover,
        .xpanel-file-row.active {
            background: hsl(var(--muted));
            color: hsl(var(--foreground));
        }
        .xpanel-file-row.drop-target {
            outline: 1px dashed hsl(var(--primary));
            background: hsl(var(--primary) / 0.08);
        }
        .xpanel-file-row.xpanel-file-row-muted {
            color: var(--muted-foreground);
            cursor: default;
            opacity: .75;
        }
        .xpanel-file-row .xpanel-file-toggle {
            width: 14px;
            height: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted-foreground);
            flex: 0 0 auto;
        }
        .xpanel-file-row .xpanel-file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            font-size: 12px;
        }
        .xpanel-file-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 28px;
            padding: 4px 8px;
            border-radius: 8px;
            background: hsl(var(--muted));
        }
        .xpanel-file-inline input {
            min-width: 0;
            flex: 1;
            height: 24px;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            background: hsl(var(--background));
            padding: 0 7px;
            font-size: 12px;
            outline: none;
        }
        .xpanel-file-rename-input {
            min-width: 0;
            flex: 1;
            height: 24px;
            border: 1px solid hsl(var(--primary));
            border-radius: 6px;
            background: hsl(var(--background));
            color: hsl(var(--foreground));
            padding: 0 7px;
            font-size: 12px;
            outline: none;
        }
        .xpanel-file-row.is-renaming {
            background: hsl(var(--muted));
            cursor: default;
        }
        .xpanel-file-row .xpanel-file-size {
            font-size: 10px;
            color: var(--muted-foreground);
        }
        .xpanel-file-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid hsl(var(--border));
            font-size: 12px;
        }
        .xpanel-file-drop {
            border: 1px dashed hsl(var(--border));
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            color: var(--muted-foreground);
            font-size: 12px;
        }
        .xpanel-file-drop.dragover {
            border-color: hsl(var(--primary));
            color: hsl(var(--primary));
            background: hsl(var(--primary) / 0.08);
        }
        #xpanel_file_list.dragover {
            outline: 1px dashed hsl(var(--primary));
            outline-offset: -6px;
            background: hsl(var(--primary) / 0.05);
        }
        .xpanel-file-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            color: var(--muted-foreground);
            text-align: center;
            background: hsl(var(--background));
            z-index: 5;
        }
        .xpanel-preview-message {
            position: absolute;
            inset: 0;
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
            background: hsl(var(--background));
        }
        .xpanel-preview-message-inner {
            display: grid;
            gap: 10px;
            justify-items: center;
            max-width: 420px;
        }
        .xpanel-file-shell .ikode_tab_close {
            display: inline-flex;
        }
        .xpanel-file-progress-wrap {
            display: grid;
            gap: 3px;
            padding: 5px 10px;
            border-bottom: 1px solid hsl(var(--border));
        }
        .xpanel-file-progress-wrap[hidden] {
            display: none;
        }
        .xpanel-file-progress-wrap progress {
            width: 100%;
            height: 6px;
            accent-color: hsl(var(--primary));
        }
        .xpanel-file-progress-label {
            font-size: 10px;
            color: var(--muted-foreground);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .xpanel-terminal-workspace {
            height: 100%;
            min-height: 0;
            display: flex;
            background: hsl(var(--background));
        }
        .xpanel-terminal-sidebar {
            flex: 0 0 30%;
            min-width: 150px;
            max-width: 360px;
            border-right: 1px solid hsl(var(--border));
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .xpanel-terminal-sidebar-head {
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 0 8px 0 10px;
            border-bottom: 1px solid hsl(var(--border));
            font-size: 11px;
            font-weight: 700;
            color: var(--muted-foreground);
        }
        .xpanel-terminal-actions {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .xpanel-terminal-action {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            color: hsl(var(--foreground));
        }
        .xpanel-terminal-action:hover { background: hsl(var(--muted)); }
        .xpanel-terminal-list {
            flex: 1;
            min-height: 0;
            overflow: auto;
            padding: 6px;
        }
        .xpanel-terminal-item {
            position: relative;
            width: 100%;
            display: grid;
            grid-template-columns: 8px 18px 1fr auto;
            align-items: center;
            gap: 7px;
            min-height: 30px;
            padding: 5px 7px;
            border: 1px solid transparent;
            border-radius: 6px;
            color: hsl(var(--foreground));
            text-align: left;
            font-size: 12px;
        }
        .xpanel-terminal-item:hover,
        .xpanel-terminal-item.active { background: hsl(var(--muted)); }
        .xpanel-terminal-item.active {
            border-color: hsl(var(--primary) / 0.55);
            box-shadow: inset 3px 0 0 hsl(var(--primary));
        }
        .xpanel-terminal-item.active .xpanel-terminal-active-dot {
            opacity: 1;
        }
        .xpanel-terminal-active-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: hsl(var(--primary));
            opacity: 0;
        }
        .xpanel-terminal-badge {
            color: var(--muted-foreground);
            font-size: 10px;
        }
        .xpanel-terminal-main {
            flex: 1;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .xpanel-terminal-output {
            flex: 1;
            min-height: 0;
            overflow: auto;
            padding: 10px;
            font-family: Consolas, 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.55;
            white-space: pre-wrap;
        }
        .xpanel-terminal-inputbar {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 6px 10px;
            border-top: 1px solid hsl(var(--border));
            font-family: Consolas, 'Courier New', monospace;
            font-size: 12px;
        }
        .xpanel-terminal-input {
            flex: 1;
            min-width: 0;
            background: transparent;
            outline: none;
            color: hsl(var(--foreground));
        }
        .ikode_right_view {
            min-height: 0;
            overflow: auto;
        }
        .xpanel-setting-control {
            display: grid;
            gap: 6px;
            font-size: 12px;
        }
        .xpanel-setting-control span {
            color: var(--muted-foreground);
        }
        .xpanel-setting-control select,
        .xpanel-setting-control input[type="number"] {
            width: 100%;
            height: 32px;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            background: hsl(var(--background));
            padding: 0 9px;
            color: hsl(var(--foreground));
        }
        .xpanel-right-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .xpanel-right-kpi {
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            padding: 8px;
            background: hsl(var(--muted) / 0.35);
        }
        .xpanel-right-kpi span {
            display: block;
            font-size: 10px;
            color: var(--muted-foreground);
        }
        .xpanel-right-kpi strong {
            display: block;
            margin-top: 2px;
            font-size: 13px;
            color: hsl(var(--foreground));
            overflow-wrap: anywhere;
        }
        .xpanel-agent-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            border: 1px solid hsl(var(--border));
            border-radius: 7px;
            background: hsl(var(--muted) / 0.3);
            padding: 10px;
            text-align: left;
            transition: border-color .15s ease, background .15s ease;
        }
        .xpanel-agent-card:hover {
            border-color: hsl(var(--primary) / 0.45);
            background: hsl(var(--primary) / 0.06);
        }
        .xpanel-agent-card span:first-child {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .xpanel-agent-card strong,
        .xpanel-agent-card small {
            display: block;
        }
        .xpanel-agent-card strong {
            font-size: 13px;
            color: hsl(var(--foreground));
        }
        .xpanel-agent-card small {
            margin-top: 1px;
            font-size: 11px;
            color: var(--muted-foreground);
        }
        .xpanel-search-wrap {
            position: relative;
            width: min(520px, 46vw);
            min-width: 260px;
        }
        .xpanel-search-wrap .ikode_quick_btn {
            width: 100%;
            max-width: none;
            justify-content: flex-start;
        }
        .xpanel-search-scope {
            display: inline-flex;
            align-items: center;
            max-width: 170px;
            color: hsl(var(--primary));
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .xpanel-search-button-text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--muted-foreground);
            font-size: 13px;
        }
        .xpanel-search-popover {
            position: absolute;
            top: -4px;
            left: 50%;
            right: auto;
            z-index: 60;
            width: min(760px, calc(100vw - 48px));
            transform: translateX(-50%);
            border: 1px solid hsl(var(--border));
            border-radius: 8px;
            background: var(--background);
            box-shadow: 0 18px 50px rgb(0 0 0 / 0.28);
            overflow: hidden;
        }
        .xpanel-search-input-row {
            display: grid;
            grid-template-columns: minmax(150px, 220px) minmax(0, 1fr);
            gap: 8px;
            padding: 8px;
            border-bottom: 1px solid hsl(var(--border));
        }
        .xpanel-search-scope-select {
            min-width: 0;
            height: 34px;
        }
        .xpanel-search-field {
            display: flex;
            align-items: center;
            min-width: 0;
            height: 34px;
            gap: 8px;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            padding: 0 10px;
            background: hsl(var(--background));
        }
        .xpanel-search-field input {
            flex: 1;
            min-width: 0;
            height: 100%;
            background: transparent;
            outline: none;
            color: hsl(var(--foreground));
            font-size: 13px;
        }
        .xpanel-search-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px;
            border-bottom: 1px solid hsl(var(--border));
        }
        .xpanel-search-toggle {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 24px;
            padding: 0 7px;
            border: 1px solid hsl(var(--border));
            border-radius: 6px;
            font-size: 11px;
            color: var(--muted-foreground);
        }
        .xpanel-search-toggle.active {
            color: hsl(var(--primary));
            background: hsl(var(--primary) / 0.08);
            border-color: hsl(var(--primary) / 0.4);
        }
        .xpanel-search-results {
            max-height: min(48vh, 420px);
            overflow: auto;
            padding: 6px;
        }
        .xpanel-search-result {
            width: 100%;
            display: grid;
            grid-template-columns: 18px 1fr auto;
            gap: 8px;
            align-items: start;
            padding: 7px;
            border-radius: 7px;
            text-align: left;
        }
        .xpanel-search-result:hover { background: hsl(var(--muted)); }
        .xpanel-search-result-title {
            font-size: 12px;
            font-weight: 600;
            color: hsl(var(--foreground));
            overflow-wrap: anywhere;
        }
        .xpanel-search-result-path,
        .xpanel-search-result-preview {
            margin-top: 2px;
            font-size: 11px;
            color: var(--muted-foreground);
            overflow-wrap: anywhere;
        }
        .xpanel-search-result-kind {
            font-size: 10px;
            color: var(--muted-foreground);
        }
        .xpanel-console-line {
            display: flex;
            gap: 10px;
            min-width: 0;
            padding: 2px 0;
        }
        .xpanel-console-time {
            color: var(--muted-foreground);
            flex: 0 0 auto;
        }
        .xpanel-console-text {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        @media (max-width: 900px) {
            .xpanel-file-shell { height: 78dvh; min-height: 560px; }
            .xpanel-file-shell .xpanel-site-select { width: 150px; }
            .xpanel-file-shell .ikode_editor_header_center { padding-inline: 6px; }
            .xpanel-search-wrap { width: min(460px, 62vw); }
            .xpanel-search-input-row { grid-template-columns: 1fr; }
        }
    </style>
@endpush


    <div class="ikode_editor_shell xpanel-file-shell bg-background border border-border" id="xpanel_file_shell">
        <div class="ikode_editor_header border-b border-border">
            <div class="ikode_editor_header_left">
                <div class="ikode_editor_actions">
                    <a href="{{ $filesBackRoute }}" class="ikode_header_btn" title="Volver">
                        <i class="ki-filled ki-left"></i>
                    </a>
                    <button class="ikode_header_btn ikode_header_btn_active" type="button" data-left-mode="explorer" title="Explorador">
                        <i class="ki-filled ki-folder"></i>
                    </button>
                    <button class="ikode_header_btn" type="button" data-left-mode="settings" title="Settings">
                        <i class="ki-filled ki-setting-2"></i>
                    </button>
                </div>
            </div>
            <div class="ikode_editor_header_center">
                <div class="flex items-center gap-3 min-w-0 w-full justify-center">
                    <div class="xpanel-search-wrap">
                        <button class="kt-input max-w-96 ikode_quick_btn" type="button" id="xpanel_quick_focus">
                            <i class="ki-outline ki-magnifier"></i>
                            <span class="xpanel-search-scope" id="xpanel_search_scope_label">{{ $filesDomain ?: 'www/' }} -</span>
                            <span class="xpanel-search-button-text" id="xpanel_search_button_text">Buscar archivo, carpeta o contenido</span>
                        </button>
                        <div class="xpanel-search-popover hidden" id="xpanel_search_popover">
                            <div class="xpanel-search-input-row">
                                <select id="xpanel_site_jump" class="kt-select xpanel-search-scope-select text-primary" title="Raiz del gestor">
                                    <option value="{{ $isAdminFiles ? route('admin.files.index') : route('client.website.file-manager.root') }}">
                                        {{ $isAdminFiles ? 'www/ todos los sitios' : 'www/ mis sitios' }}
                                    </option>
                                    @foreach($filesSites as $fileSite)
                                        <option value="{{ $fileSite['url'] }}" @selected($filesDomain === $fileSite['domain'])>
                                            {{ $fileSite['domain'] }}{{ $fileSite['tenant'] ? ' - ' . $fileSite['tenant'] : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <label class="xpanel-search-field">
                                    <i class="ki-outline ki-magnifier"></i>
                                    <input id="xpanel_file_filter" placeholder="Buscar archivo, carpeta o contenido" type="text" autocomplete="off">
                                </label>
                            </div>
                            <div class="xpanel-search-tools">
                                <div class="flex items-center gap-1">
                                    <button class="xpanel-search-toggle active" type="button" data-search-toggle="content" title="Buscar dentro de archivos">
                                        <i class="ki-filled ki-document"></i>
                                        Contenido
                                    </button>
                                    <button class="xpanel-search-toggle" type="button" data-search-toggle="case" title="Distinguir mayusculas">
                                        Aa
                                    </button>
                                </div>
                                <button class="xpanel-terminal-action" type="button" data-search-action="clear" title="Limpiar busqueda">
                                    <i class="ki-filled ki-cross"></i>
                                </button>
                            </div>
                            <div class="xpanel-search-results" id="xpanel_search_results">
                                <div class="p-3 text-xs text-secondary-foreground">Escribe al menos 2 caracteres para buscar.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ikode_editor_header_right">
                <button class="ikode_header_btn ikode_header_btn_active" type="button" data-layout-toggle="left" title="Panel izquierdo">
                    <i class="ki-filled ki-element-4"></i>
                </button>
                <button class="ikode_header_btn ikode_header_btn_active" type="button" data-layout-toggle="bottom" title="Consola inferior">
                    <i class="ki-filled ki-element-9"></i>
                </button>
                <button class="ikode_header_btn" type="button" data-layout-toggle="right" title="Panel de informacion">
                    <i class="ki-filled ki-element-3"></i>
                </button>
                <button class="ikode_header_btn" type="button" data-layout-action="fullscreen" title="Pantalla completa">
                    <i class="ki-filled ki-maximize"></i>
                </button>
            </div>
        </div>

        <div class="ikode_editor_main">
            <aside class="ikode_editor_left border-r border-border" id="xpanel_left_pane">
                <div class="ikode_left_split ikode_left_mode_panel" id="xpanel_left_split" data-left-panel="explorer">
                    <div class="ikode_editor_lefthead border-b border-border">
                        <span>EXPLORADOR</span>
                        <div class="ikode_left_actions">
                            <button class="ikode_left_action_btn" type="button" data-fm-action="new-file" title="Nuevo archivo">
                                <i class="ki-filled ki-file-up"></i>
                            </button>
                            <button class="ikode_left_action_btn" type="button" data-fm-action="new-folder" title="Nueva carpeta">
                                <i class="ki-filled ki-folder-up"></i>
                            </button>
                            <button class="ikode_left_action_btn" type="button" data-fm-action="refresh" title="Refrescar">
                                <i class="ki-filled ki-arrows-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="xpanel-file-progress-wrap" id="xpanel_file_progress_wrap" hidden>
                        <progress id="xpanel_file_progress" max="100" value="0">0%</progress>
                        <div class="xpanel-file-progress-label" id="xpanel_file_progress_label">Preparando...</div>
                    </div>

                    <div class="ikode_left_files" id="xpanel_left_files_pane"
                         ondragover="XPanelFM.dragOver(event)"
                         ondragleave="XPanelFM.dragLeave(event)"
                         ondrop="XPanelFM.drop(event)">
                        <div id="xpanel_file_list">
                            <div class="p-3 text-xs text-secondary-foreground">Cargando...</div>
                        </div>
                    </div>

                    <div class="ikode_left_bottom border-t border-border" id="xpanel_left_outline_pane">
                        <div class="ikode_left_bottom_tabs border-b border-border">
                            <button class="ikode_left_bottom_tab ikode_left_bottom_tab_active" type="button" data-outline-tab="outline">OUTLINE</button>
                            <button class="ikode_left_bottom_tab" type="button" data-outline-tab="timeline">TIMELINE</button>
                        </div>
                        <div class="ikode_left_bottom_body">
                            <div data-outline-view="outline">
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-folder"></i>
                                    <span class="min-w-0 truncate" id="xpanel_breadcrumb">/</span>
                                </div>
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-row-vertical"></i>
                                    <span id="xpanel_outline_count">0 elementos</span>
                                </div>
                                <div class="ikode_left_item">
                                    <i class="ki-filled ki-code"></i>
                                    <span id="xpanel_outline_file">Sin archivo abierto</span>
                                </div>
                            </div>
                            <div class="ikode_hidden" data-outline-view="timeline">
                                <div id="xpanel_timeline_list">
                                    <div class="ikode_left_item">
                                        <i class="ki-filled ki-time"></i>
                                        <span>Gestor listo.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ikode_left_mode_panel ikode_hidden" data-left-panel="settings" style="overflow: auto;">
                    <div class="ikode_simple_head border-b border-border">SETTINGS</div>
                    <div class="ikode_simple_list">
                        <div class="ikode_panel">
                            <div class="ikode_panel_title">Preferencias</div>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-eye"></i> Mostrar ocultos</span>
                                <span class="ikode_setting_check"><input type="checkbox" disabled></span>
                            </label>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-shield-tick"></i> Edicion segura</span>
                                <span class="ikode_setting_check"><input type="checkbox" checked disabled></span>
                            </label>
                        </div>

                        <div class="ikode_panel">
                            <div class="ikode_panel_title">Editor</div>
                            <label class="xpanel-setting-control">
                                <span>Tamano de texto</span>
                                <input type="number" min="11" max="24" step="1" id="xpanel_editor_font_size">
                            </label>
                            <label class="xpanel-setting-control mt-3">
                                <span>Fuente</span>
                                <select id="xpanel_editor_font_family">
                                    <option value="jetbrains">JetBrains / Fira Code</option>
                                    <option value="consolas">Consolas</option>
                                    <option value="system">Sistema monoespaciado</option>
                                </select>
                            </label>
                            <label class="xpanel-setting-control mt-3">
                                <span>Color</span>
                                <select id="xpanel_editor_theme">
                                    <option value="auto">Seguir tema del panel</option>
                                    <option value="dark">Oscuro</option>
                                    <option value="light">Claro</option>
                                </select>
                            </label>
                            <label class="ikode_setting_row mt-3">
                                <span class="ikode_setting_label"><i class="ki-filled ki-code"></i> Ajustar lineas</span>
                                <span class="ikode_setting_check"><input type="checkbox" id="xpanel_editor_word_wrap"></span>
                            </label>
                            <label class="ikode_setting_row">
                                <span class="ikode_setting_label"><i class="ki-filled ki-row-horizontal"></i> Minimap</span>
                                <span class="ikode_setting_check"><input type="checkbox" id="xpanel_editor_minimap"></span>
                            </label>
                            <button class="kt-btn kt-btn-outline w-full mt-3" type="button" data-settings-action="reset-editor">
                                <i class="ki-filled ki-arrows-circle"></i>
                                Restablecer editor
                            </button>
                        </div>


                    </div>
                </div>
            </aside>

            <section class="ikode_editor_center bg-background" id="xpanel_center_pane">
                <div class="ikode_editor_split">
                    <div class="ikode_editor_codepane border-b border-border" id="xpanel_code_pane">
                        <div id="xpanel_editor_groups" class="xpanel-editor-groups ikode_hidden">
                            <section id="xpanel_editor_group_main" class="xpanel-editor-group">
                                <div class="ikode_tabs xpanel-editor-group-tabs">
                                    <div class="flex min-w-0" id="xpanel_file_tabs" style="width: 100%; overflow-x: auto;"></div>
                                    <div class="ikode_tabs_actions">
                                        <button class="ikode_tabs_action_btn ikode_hidden" type="button" data-fm-action="duplicate-tab" title="Duplicar pestaña">
                                            <i class="ki-filled ki-copy"></i>
                                        </button>
                                        <button class="ikode_tabs_action_btn" type="button" data-fm-action="save" title="Guardar">
                                            <i class="ki-filled ki-check"></i>
                                        </button>
                                        <button class="ikode_tabs_action_btn" type="button" data-fm-action="download" title="Descargar">
                                            <i class="ki-filled ki-exit-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="xpanel-editor-group-body">
                                    <div id="xpanel_monaco_editor" class="ikode_monaco"></div>
                                </div>
                            </section>
                            <section id="xpanel_editor_group_clone" class="xpanel-editor-group ikode_hidden">
                                <div class="ikode_tabs xpanel-editor-group-tabs">
                                    <div class="flex min-w-0" id="xpanel_file_tabs_clone" style="width: 100%; overflow-x: auto;"></div>
                                    <div class="ikode_tabs_actions">
                                        <button class="ikode_tabs_action_btn" type="button" data-clone-action="save" title="Guardar">
                                            <i class="ki-filled ki-check"></i>
                                        </button>
                                        <button class="ikode_tabs_action_btn" type="button" data-clone-action="download" title="Descargar">
                                            <i class="ki-filled ki-exit-down"></i>
                                        </button>
                                        <button class="xpanel-editor-group-close" type="button" data-duplicate-close title="Cerrar duplicado">x</button>
                                    </div>
                                </div>
                                <div class="xpanel-editor-group-body">
                                    <div id="xpanel_monaco_clone" class="ikode_monaco"></div>
                                </div>
                            </section>
                        </div>
                        <div class="ikode_file_preview ikode_hidden" id="xpanel_file_preview"></div>
                        <div class="xpanel-file-empty" id="xpanel_empty_state">
                            <i class="ki-filled ki-code text-4xl text-muted-foreground"></i>
                            <div>
                                <div class="text-sm font-semibold text-mono">Selecciona un archivo</div>
                                <div class="text-xs text-secondary-foreground mt-1">Puedes editar texto, previsualizar imagenes y guardar con Ctrl+S.</div>
                            </div>
                        </div>
                    </div>

                    <div class="ikode_editor_bottom" id="xpanel_bottom_pane">
                        <div class="ikode_terminal_tabs border-b border-border">
                            <button class="ikode_terminal_tab" type="button" data-console-tab="problems">Problemas</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="output">Output</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="logs">Logs</button>
                            <button class="ikode_terminal_tab ikode_terminal_tab_active" type="button" data-console-tab="terminal">Terminal</button>
                            <button class="ikode_terminal_tab" type="button" data-console-tab="ports">Ports</button>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="problems">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">info</span><span class="xpanel-console-text">0 errores criticos detectados en el gestor.</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">hint</span><span class="xpanel-console-text">Las validaciones reales se conectaran al agente del sitio.</span></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="output">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">xpanel</span><span class="xpanel-console-text">Esperando tareas del sitio {{ $filesDomain ?: 'www/' }}.</span></div>
                            <div id="xpanel_output_log"></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="logs">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">logs</span><span class="xpanel-console-text">Esperando eventos del gestor.</span></div>
                            <div id="xpanel_logs_output"></div>
                        </div>
                        <div data-console-view="terminal">
                            <div class="xpanel-terminal-workspace">
                                <aside class="xpanel-terminal-sidebar" id="xpanel_terminal_sidebar">
                                    <div class="xpanel-terminal-sidebar-head">
                                        <span>TERMINALES</span>
                                        <div class="xpanel-terminal-actions">
                                            <button class="xpanel-terminal-action" type="button" data-terminal-action="new" title="Nueva terminal">
                                                <i class="ki-filled ki-plus"></i>
                                            </button>
                                            <button class="xpanel-terminal-action" type="button" data-terminal-action="remove" title="Eliminar terminal">
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="xpanel-terminal-list" id="xpanel_terminal_list"></div>
                                </aside>
                                <section class="xpanel-terminal-main" id="xpanel_terminal_main">
                                    <div class="xpanel-terminal-output" id="xpanel_terminal_output"></div>
                                    <form class="xpanel-terminal-inputbar" id="xpanel_terminal_form" autocomplete="off">
                                        <span class="ikode_prompt" id="xpanel_terminal_prompt">xpanel:/ $</span>
                                        <input class="xpanel-terminal-input" id="xpanel_terminal_input" type="text" spellcheck="false" placeholder="help, ls, cd, open, mkdir, touch, extract...">
                                    </form>
                                </section>
                            </div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="ports">
                            <div class="xpanel-console-line"><span class="xpanel-console-time">80</span><span class="xpanel-console-text">HTTP del sitio</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">443</span><span class="xpanel-console-text">HTTPS del sitio</span></div>
                            <div class="xpanel-console-line"><span class="xpanel-console-time">22</span><span class="xpanel-console-text">SSH administrado por el nodo</span></div>
                        </div>
                        <div class="ikode_terminal_body ikode_hidden" data-console-view="preview">
                            <div id="xpanel_inline_preview">Sin vista previa.</div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="ikode_editor_right border-l border-border ikode_hidden" id="xpanel_right_pane">
                <div class="ikode_left_bottom_tabs border-b border-border ikode_right_tabs">
                    <button class="ikode_left_bottom_tab ikode_left_bottom_tab_active ikode_right_tab" type="button" data-right-tab="info">Info</button>
                    <button class="ikode_left_bottom_tab ikode_right_tab" type="button" data-right-tab="agents">Agentes</button>
                </div>

                <div class="ikode_right_view" data-right-view="info">
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Elemento seleccionado</div>
                        <div id="xpanel_file_info" class="text-sm text-secondary-foreground">
                            No hay seleccion.
                        </div>
                    </div>
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Estado</div>
                        <div class="xpanel-right-kpis">
                            <div class="xpanel-right-kpi"><span>Vista</span><strong id="xpanel_info_preview">-</strong></div>
                            <div class="xpanel-right-kpi"><span>Extension</span><strong id="xpanel_info_ext">-</strong></div>
                            <div class="xpanel-right-kpi"><span>Pestanas</span><strong id="xpanel_info_tabs">0</strong></div>
                            <div class="xpanel-right-kpi"><span>Terminal</span><strong id="xpanel_info_terminal">Terminal 1</strong></div>
                        </div>
                    </div>
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Contexto</div>
                        <div class="grid gap-2 text-sm">
                            <div class="xpanel-file-meta"><span>Scope</span><strong>{{ $isAdminFiles ? 'Admin' : 'Cliente' }}</strong></div>
                            <div class="xpanel-file-meta"><span>Raiz</span><strong>{{ $filesDomain ?: 'www/' }}</strong></div>
                            <div class="xpanel-file-meta"><span>Sitios</span><strong>{{ $filesSites->count() }}</strong></div>
                        </div>
                    </div>
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Resumen del gestor</div>
                        <div class="grid gap-2 text-sm text-secondary-foreground">
                            <div class="xpanel-file-meta"><span>Directorio</span><strong id="xpanel_summary_path">/</strong></div>
                            <div class="xpanel-file-meta"><span>Elementos</span><strong id="xpanel_summary_count">0</strong></div>
                            <div class="xpanel-file-meta"><span>Carpetas</span><strong id="xpanel_summary_dirs">0</strong></div>
                            <div class="xpanel-file-meta"><span>Archivos</span><strong id="xpanel_summary_files">0</strong></div>
                            <div class="xpanel-file-meta"><span>Pestanas</span><strong id="xpanel_summary_tabs">0</strong></div>
                            <div class="xpanel-file-meta"><span>Activo</span><strong id="xpanel_summary_active" class="text-end break-all">-</strong></div>
                            <div class="xpanel-file-meta"><span>Editor</span><strong id="xpanel_summary_editor">-</strong></div>
                            <div class="xpanel-file-meta"><span>Paneles</span><strong id="xpanel_summary_layout">-</strong></div>
                            <div class="xpanel-file-meta"><span>Terminal</span><strong id="xpanel_summary_terminal">Terminal 1</strong></div>
                        </div>
                    </div>
                </div>

                <div class="ikode_right_view ikode_hidden" data-right-view="agents">
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Agentes</div>
                        <div class="grid gap-2">
                            <button class="xpanel-agent-card" type="button" data-agent="claude">
                                <span>
                                    <i class="ki-filled ki-message-programming"></i>
                                    <span><strong>Claude</strong><small>Proyecto actual</small></span>
                                </span>
                                <span class="kt-badge kt-badge-outline">Pronto</span>
                            </button>
                            <button class="xpanel-agent-card" type="button" data-agent="gpt">
                                <span>
                                    <i class="ki-filled ki-artificial-intelligence"></i>
                                    <span><strong>GPT</strong><small>Edicion asistida</small></span>
                                </span>
                                <span class="kt-badge kt-badge-outline">Pronto</span>
                            </button>
                            <button class="xpanel-agent-card" type="button" data-agent="local">
                                <span>
                                    <i class="ki-filled ki-setting-2"></i>
                                    <span><strong>Agente local</strong><small>Servidor del sitio</small></span>
                                </span>
                                <span class="kt-badge kt-badge-outline">Pronto</span>
                            </button>
                        </div>
                    </div>
                    <div class="ikode_panel">
                        <div class="ikode_panel_title">Contexto de agente</div>
                        <div class="grid gap-2 text-sm text-secondary-foreground">
                            <div class="xpanel-file-meta"><span>Raiz</span><strong>{{ $filesDomain ?: 'www/' }}</strong></div>
                            <div class="xpanel-file-meta"><span>Archivo</span><strong id="xpanel_agent_active" class="text-end break-all">-</strong></div>
                            <div class="xpanel-file-meta"><span>Modo</span><strong id="xpanel_agent_mode">Lectura</strong></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>


<div id="xpanel_ctx_menu" class="fixed hidden z-50 w-48 rounded-md border border-border bg-background shadow-2xl py-1 text-sm overflow-hidden">
    <button type="button" data-fm-action="new-file" class="w-full text-left px-4 py-2 hover:bg-muted">Nuevo archivo</button>
    <button type="button" data-fm-action="new-folder" class="w-full text-left px-4 py-2 hover:bg-muted">Nueva carpeta</button>
    <div class="border-t border-border my-1"></div>
    <button type="button" data-fm-action="open" class="w-full text-left px-4 py-2 hover:bg-muted">Abrir / editar</button>
    <button type="button" data-fm-action="extract" data-archive-only class="w-full text-left px-4 py-2 hover:bg-muted">Descomprimir aqui</button>
    <button type="button" data-fm-action="rename" class="w-full text-left px-4 py-2 hover:bg-muted">Renombrar</button>
    <button type="button" data-fm-action="download" class="w-full text-left px-4 py-2 hover:bg-muted">Descargar</button>
    <div class="border-t border-border my-1"></div>
    <button type="button" data-fm-action="delete" class="w-full text-left px-4 py-2 hover:bg-destructive/10 text-destructive">Eliminar</button>
</div>

<input type="file" id="xpanel_upload_input" class="hidden" multiple>

<div id="xpanel_input_modal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-background border border-border rounded-md p-6 shadow-2xl">
        <h3 id="xpanel_input_title" class="text-base font-semibold text-mono mb-4"></h3>
        <input id="xpanel_input_value" type="text" class="kt-input w-full mb-4">
        <div class="flex gap-2 justify-end">
            <button type="button" class="kt-btn kt-btn-outline" data-input-cancel>Cancelar</button>
            <button type="button" class="kt-btn kt-btn-primary" data-input-confirm>Confirmar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/files/split.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
    <script>
        window.XPANEL_FILE_MANAGER_CONFIG = {
            baseUrl: @json($filesBaseUrl),
            domain: @json($filesDomain),
            rootLabel: @json($filesDomain ?: 'www/'),
            scope: @json($isAdminFiles ? 'admin' : 'client'),
        };
    </script>
    <script>
        (() => {
            const config = window.XPANEL_FILE_MANAGER_CONFIG;
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const state = {
                currentPath: '/',
                entries: [],
                dirCache: {},
                expanded: new Set(['/']),
                loadingDirs: new Set(),
                selected: null,
                ctxEntry: null,
                ctxDirectory: '/',
                ctxFromBlank: false,
                draggedEntry: null,
                editor: null,
                cloneEditor: null,
                tabs: [],
                activeTab: null,
                cloneTabs: [],
                activeCloneTab: null,
                openPath: null,
                openName: null,
                isDirty: false,
                inputCallback: null,
                pendingCreate: null,
                terminals: [],
                activeTerminalId: null,
                terminalSeq: 1,
                searchTimer: null,
                searchAbort: null,
                searchResults: [],
                pendingRename: null,
            };

            const $ = (selector) => document.querySelector(selector);
            const $$ = (selector) => Array.from(document.querySelectorAll(selector));
            const domainParam = () => encodeURIComponent(config.domain || '');
            const storageKey = `xpanel:files:${config.scope}:${config.domain || 'www'}`;
            const defaultUiState = {
                layout: { left: true, right: false, bottom: true },
                split: { mainThree: [24, 52, 24], mainTwo: [28, 72], center: [68, 32], left: [68, 32], editor: [50, 50], terminal: [30, 70] },
                ui: { leftMode: 'explorer', rightTab: 'info', outlineTab: 'outline', consoleTab: 'terminal' },
                editor: { fontSize: 14, fontFamily: 'jetbrains', theme: 'auto', wordWrap: true, minimap: false },
                search: { includeContent: true, caseSensitive: false },
                terminal: {
                    active: 'terminal-1',
                    seq: 1,
                    sessions: [{ id: 'terminal-1', name: 'Terminal 1', cwd: '/' }],
                },
            };
            const clone = (value) => JSON.parse(JSON.stringify(value));
            const loadUiState = () => {
                try {
                    const raw = localStorage.getItem(storageKey);
                    if (!raw) return clone(defaultUiState);
                    const parsed = JSON.parse(raw);
                    return {
                        layout: {
                            left: parsed?.layout?.left !== false,
                            right: parsed?.layout?.right === true,
                            bottom: parsed?.layout?.bottom !== false,
                        },
                        split: {
                            mainThree: Array.isArray(parsed?.split?.mainThree) ? parsed.split.mainThree : defaultUiState.split.mainThree,
                            mainTwo: Array.isArray(parsed?.split?.mainTwo) ? parsed.split.mainTwo : defaultUiState.split.mainTwo,
                            center: Array.isArray(parsed?.split?.center) ? parsed.split.center : defaultUiState.split.center,
                            left: Array.isArray(parsed?.split?.left) ? parsed.split.left : defaultUiState.split.left,
                            editor: Array.isArray(parsed?.split?.editor) ? parsed.split.editor : defaultUiState.split.editor,
                            terminal: Array.isArray(parsed?.split?.terminal) ? parsed.split.terminal : defaultUiState.split.terminal,
                        },
                        ui: {
                            leftMode: parsed?.ui?.leftMode || defaultUiState.ui.leftMode,
                            rightTab: ['info', 'agents'].includes(parsed?.ui?.rightTab) ? parsed.ui.rightTab : defaultUiState.ui.rightTab,
                            outlineTab: parsed?.ui?.outlineTab || defaultUiState.ui.outlineTab,
                            consoleTab: parsed?.ui?.consoleTab || defaultUiState.ui.consoleTab,
                        },
                        editor: {
                            fontSize: Number(parsed?.editor?.fontSize || defaultUiState.editor.fontSize),
                            fontFamily: parsed?.editor?.fontFamily || defaultUiState.editor.fontFamily,
                            theme: parsed?.editor?.theme || defaultUiState.editor.theme,
                            wordWrap: parsed?.editor?.wordWrap !== false,
                            minimap: parsed?.editor?.minimap === true,
                        },
                        search: {
                            includeContent: parsed?.search?.includeContent !== false,
                            caseSensitive: parsed?.search?.caseSensitive === true,
                        },
                        terminal: {
                            active: parsed?.terminal?.active || defaultUiState.terminal.active,
                            seq: Number(parsed?.terminal?.seq || defaultUiState.terminal.seq),
                            sessions: Array.isArray(parsed?.terminal?.sessions) && parsed.terminal.sessions.length
                                ? parsed.terminal.sessions.map((session, index) => ({
                                    id: session.id || `terminal-${index + 1}`,
                                    name: session.name || `Terminal ${index + 1}`,
                                    cwd: session.cwd || '/',
                                }))
                                : clone(defaultUiState.terminal.sessions),
                        },
                    };
                } catch (error) {
                    return clone(defaultUiState);
                }
            };
            const uiState = loadUiState();
            const persistUiState = () => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(uiState));
                } catch (error) {
                    console.warn('No se pudo guardar estado del gestor', error);
                }
            };
            const editorFontFamilies = {
                jetbrains: "'JetBrains Mono','Fira Code','Consolas',monospace",
                consolas: "'Consolas','Courier New',monospace",
                system: "ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace",
            };
            state.terminals = uiState.terminal.sessions.map((session, index) => ({
                ...session,
                history: [],
                commandHistory: [],
                commandIndex: 0,
            }));
            state.terminalSeq = Math.max(uiState.terminal.seq || 1, state.terminals.length);
            state.activeTerminalId = state.terminals.some((terminal) => terminal.id === uiState.terminal.active)
                ? uiState.terminal.active
                : state.terminals[0]?.id;

            const activeTerminal = () => state.terminals.find((terminal) => terminal.id === state.activeTerminalId) || state.terminals[0] || null;
            const persistTerminals = () => {
                uiState.terminal.active = state.activeTerminalId;
                uiState.terminal.seq = state.terminalSeq;
                uiState.terminal.sessions = state.terminals.map(({ id, name, cwd }) => ({ id, name, cwd }));
                persistUiState();
                updateSummary();
            };
            const resolveMonacoTheme = () => {
                if (uiState.editor.theme === 'dark') return 'vs-dark';
                if (uiState.editor.theme === 'light') return 'vs';
                return document.documentElement.classList.contains('dark') ? 'vs-dark' : 'vs';
            };
            const editorOptions = () => ({
                fontSize: Math.max(11, Math.min(24, Number(uiState.editor.fontSize) || 14)),
                minimap: { enabled: uiState.editor.minimap === true },
                wordWrap: uiState.editor.wordWrap === false ? 'off' : 'on',
                automaticLayout: true,
                scrollBeyondLastLine: false,
                fontFamily: editorFontFamilies[uiState.editor.fontFamily] || editorFontFamilies.jetbrains,
            });
            const applyEditorSettings = () => {
                state.editor?.updateOptions(editorOptions());
                state.cloneEditor?.updateOptions(editorOptions());
                if (window.monaco?.editor) monaco.editor.setTheme(resolveMonacoTheme());
                updateSummary();
            };
            const hydrateSettings = () => {
                const fontSize = $('#xpanel_editor_font_size');
                const fontFamily = $('#xpanel_editor_font_family');
                const theme = $('#xpanel_editor_theme');
                const wordWrap = $('#xpanel_editor_word_wrap');
                const minimap = $('#xpanel_editor_minimap');
                if (fontSize) fontSize.value = uiState.editor.fontSize;
                if (fontFamily) fontFamily.value = uiState.editor.fontFamily;
                if (theme) theme.value = uiState.editor.theme;
                if (wordWrap) wordWrap.checked = uiState.editor.wordWrap !== false;
                if (minimap) minimap.checked = uiState.editor.minimap === true;
            };
            const bindSettings = () => {
                $('#xpanel_editor_font_size')?.addEventListener('input', (event) => {
                    uiState.editor.fontSize = Number(event.target.value) || 14;
                    persistUiState();
                    applyEditorSettings();
                });
                $('#xpanel_editor_font_family')?.addEventListener('change', (event) => {
                    uiState.editor.fontFamily = event.target.value;
                    persistUiState();
                    applyEditorSettings();
                });
                $('#xpanel_editor_theme')?.addEventListener('change', (event) => {
                    uiState.editor.theme = event.target.value;
                    persistUiState();
                    applyEditorSettings();
                });
                $('#xpanel_editor_word_wrap')?.addEventListener('change', (event) => {
                    uiState.editor.wordWrap = event.target.checked;
                    persistUiState();
                    applyEditorSettings();
                });
                $('#xpanel_editor_minimap')?.addEventListener('change', (event) => {
                    uiState.editor.minimap = event.target.checked;
                    persistUiState();
                    applyEditorSettings();
                });
                $('[data-settings-action="reset-editor"]')?.addEventListener('click', () => {
                    uiState.editor = clone(defaultUiState.editor);
                    hydrateSettings();
                    persistUiState();
                    applyEditorSettings();
                    toast('Editor restablecido');
                });
            };

            const log = (message) => {
                const safeMessage = escapeHtml(String(message));
                const timeline = $('#xpanel_timeline_list');
                const output = $('#xpanel_output_log');
                const logs = $('#xpanel_logs_output');
                const line = document.createElement('div');
                line.className = 'xpanel-console-line';
                line.innerHTML = `<span class="xpanel-console-time">${new Date().toLocaleTimeString()}</span><span class="xpanel-console-text">${safeMessage}</span>`;
                logs?.prepend(line);
                if (output) {
                    const out = document.createElement('div');
                    out.className = 'xpanel-console-line';
                    out.innerHTML = `<span class="xpanel-console-time">${new Date().toLocaleTimeString()}</span><span class="xpanel-console-text">${safeMessage}</span>`;
                    output.prepend(out);
                }
                if (timeline) {
                    const item = document.createElement('div');
                    item.className = 'ikode_left_item';
                    item.innerHTML = `<i class="ki-filled ki-time"></i><span>${safeMessage}</span>`;
                    timeline.prepend(item);
                }
            };

            const toast = (message, type = 'success') => {
                const el = document.createElement('div');
                el.className = `fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-md text-sm font-semibold shadow-2xl ${type === 'error' ? 'bg-destructive/15 border border-destructive/30 text-destructive' : 'bg-green-500/15 border border-green-500/30 text-green-500'}`;
                el.textContent = message;
                document.body.appendChild(el);
                setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 250); }, 2600);
            };

            const showProgress = (label = 'Procesando...', value = 0) => {
                $('#xpanel_file_progress_wrap').hidden = false;
                $('#xpanel_file_progress').value = Math.max(0, Math.min(100, value));
                $('#xpanel_file_progress').textContent = `${Math.round(value)}%`;
                $('#xpanel_file_progress_label').textContent = label;
            };
            const setProgress = (value, label = null) => {
                $('#xpanel_file_progress').value = Math.max(0, Math.min(100, value));
                $('#xpanel_file_progress').textContent = `${Math.round(value)}%`;
                if (label) $('#xpanel_file_progress_label').textContent = label;
            };
            const hideProgress = (delay = 450) => {
                setTimeout(() => {
                    $('#xpanel_file_progress_wrap').hidden = true;
                    $('#xpanel_file_progress').value = 0;
                }, delay);
            };

            const api = async (method, endpoint, body = null, signal = null) => {
                const options = { method, headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } };
                if (signal) options.signal = signal;
                if (body instanceof FormData) {
                    options.body = body;
                } else if (body) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }

                const response = await fetch(config.baseUrl + endpoint, options);
                const contentType = response.headers.get('Content-Type') || '';
                if (!response.ok) {
                    throw new Error(await response.text() || `HTTP ${response.status}`);
                }

                return contentType.includes('application/json') ? response.json() : response;
            };

            const ext = (name) => (name.includes('.') ? name.split('.').pop() : '').toLowerCase();
            const isImage = (name) => /^(png|jpg|jpeg|gif|svg|webp|ico|bmp)$/i.test(ext(name));
            const isVideo = (name) => /^(mp4|webm|ogg|mov|m4v)$/i.test(ext(name));
            const isPdf = (name) => ext(name) === 'pdf';
            const isArchive = (name) => /^(zip|jar|zipx|rar|7z|tar|gz|tgz)$/i.test(ext(name));
            const isExtractable = (name) => /^(zip|jar)$/i.test(ext(name));
            const codeExtensions = new Set(['php', 'js', 'ts', 'jsx', 'tsx', 'html', 'htm', 'css', 'scss', 'json', 'yml', 'yaml', 'py', 'sh', 'bash', 'md', 'xml', 'sql', 'txt', 'env', 'gitignore', 'htaccess', 'ini', 'conf', 'log']);
            const isCode = (name) => codeExtensions.has(ext(name)) || !ext(name);
            const previewKind = (name) => {
                if (isImage(name)) return 'image';
                if (isVideo(name)) return 'video';
                if (isPdf(name)) return 'pdf';
                if (isCode(name)) return 'code';
                return 'unsupported';
            };
            const language = (name) => ({
                php: 'php', js: 'javascript', ts: 'typescript', jsx: 'javascript', tsx: 'typescript',
                html: 'html', htm: 'html', css: 'css', scss: 'css', json: 'json', yml: 'yaml',
                yaml: 'yaml', py: 'python', sh: 'shell', bash: 'shell', md: 'markdown', xml: 'xml',
                sql: 'sql', txt: 'plaintext', env: 'plaintext', gitignore: 'plaintext', htaccess: 'ini',
            })[ext(name)] || 'plaintext';
            const escapeHtml = (value = '') => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const basename = (path = '/') => path.split('/').filter(Boolean).pop() || '/';
            const dirname = (path = '/') => {
                const parts = path.split('/').filter(Boolean);
                parts.pop();
                return parts.length ? `/${parts.join('/')}` : '/';
            };
            const normalizePath = (path = '/') => {
                const parts = String(path || '/').replace(/\\/g, '/').split('/');
                const clean = [];
                parts.forEach((part) => {
                    if (!part || part === '.') return;
                    if (part === '..') {
                        clean.pop();
                        return;
                    }
                    clean.push(part);
                });
                return clean.length ? `/${clean.join('/')}` : '/';
            };
            const isGlobalSitesRoot = () => !config.domain;
            const virtualSiteRoot = (path = state.currentPath) => {
                if (!isGlobalSitesRoot()) return '/';
                const first = normalizePath(path).split('/').filter(Boolean)[0];
                return first ? `/${first}` : '/';
            };
            const currentVirtualSiteRoot = () => {
                const source = state.ctxEntry?.path || state.selected?.path || state.currentPath || '/';
                return virtualSiteRoot(source);
            };
            const requireConcreteSiteTarget = (target = currentVirtualSiteRoot()) => {
                if (!isGlobalSitesRoot() || target !== '/') return target;
                toast('Selecciona o entra a un dominio antes de crear o mover archivos.', 'error');
                throw new Error('Selecciona un dominio');
            };
            const downloadUrl = (path, inline = false) => `${config.baseUrl}/download?domain=${domainParam()}&path=${encodeURIComponent(path)}${inline ? '&inline=1' : ''}`;

            const icon = (entry) => {
                if (entry.is_dir) return 'ki-folder';
                if (isImage(entry.name)) return 'ki-picture';
                if (isVideo(entry.name)) return 'ki-youtube';
                if (isPdf(entry.name)) return 'ki-document';
                if (isArchive(entry.name)) return 'ki-archive';
                return ({
                    php: 'ki-code', js: 'ki-js', ts: 'ki-code', html: 'ki-html', css: 'ki-css',
                    json: 'ki-code', md: 'ki-document', sql: 'ki-data', sh: 'ki-setting-2',
                    zip: 'ki-archive', rar: 'ki-archive', gz: 'ki-archive',
                })[ext(entry.name)] || 'ki-document';
            };

            const size = (bytes = 0) => {
                if (bytes < 1024) return `${bytes} B`;
                if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
                return `${(bytes / 1048576).toFixed(1)} MB`;
            };

            const pathJoin = (base, name) => normalizePath(`${base.replace(/\/$/, '')}/${name}`);

            const renderInfo = (entry = state.selected) => {
                const box = $('#xpanel_file_info');
                const preview = $('#xpanel_info_preview');
                const extBox = $('#xpanel_info_ext');
                if (!entry) {
                    box.textContent = 'No hay seleccion.';
                    if (preview) preview.textContent = '-';
                    if (extBox) extBox.textContent = '-';
                    updateSummary();
                    return;
                }

                const html = `
                    <div class="grid gap-1">
                        <div class="xpanel-file-meta"><span>Nombre</span><strong class="text-end break-all">${entry.name}</strong></div>
                        <div class="xpanel-file-meta"><span>Tipo</span><strong>${entry.is_dir ? 'Carpeta' : 'Archivo'}</strong></div>
                        <div class="xpanel-file-meta"><span>Tamano</span><strong>${entry.is_dir ? '-' : size(entry.size || 0)}</strong></div>
                        <div class="xpanel-file-meta"><span>Ruta</span><strong class="text-end break-all">${entry.path}</strong></div>
                        <div class="xpanel-file-meta"><span>Permisos</span><strong>${entry.mode || '-'}</strong></div>
                    </div>
                `;
                box.innerHTML = html;
                if (preview) preview.textContent = entry.is_dir ? 'Arbol' : previewKind(entry.name);
                if (extBox) extBox.textContent = entry.is_dir ? '-' : (ext(entry.name) || 'sin ext');
                updateSummary();
            };

            const activeTab = () => state.tabs.find((tab) => tab.path === state.activeTab) || null;
            const entriesFor = (path = '/') => (state.dirCache[path || '/'] || []).slice().sort((a, b) => {
                if (a.is_dir !== b.is_dir) return a.is_dir ? -1 : 1;
                return a.name.localeCompare(b.name);
            });
            const getEntry = (path) => {
                for (const entries of Object.values(state.dirCache)) {
                    const found = (entries || []).find((entry) => entry.path === path);
                    if (found) return found;
                }
                return null;
            };
            const setCurrentPath = (path = '/') => {
                state.currentPath = path || '/';
                renderBreadcrumb();
                updateSummary();
            };
            const renderBreadcrumb = () => {
                $('#xpanel_breadcrumb').textContent = state.currentPath;
            };
            const updateSummary = () => {
                const entries = entriesFor(state.currentPath);
                const dirCount = entries.filter((entry) => entry.is_dir).length;
                const fileCount = entries.length - dirCount;
                $('#xpanel_summary_path').textContent = state.currentPath;
                $('#xpanel_summary_count').textContent = entries.length;
                $('#xpanel_summary_dirs').textContent = dirCount;
                $('#xpanel_summary_files').textContent = fileCount;
                $('#xpanel_outline_count').textContent = `${entries.length} elemento(s)`;
                $('#xpanel_summary_tabs').textContent = state.tabs.length;
                $('#xpanel_summary_active').textContent = activeTab()?.name || '-';
                $('#xpanel_summary_editor').textContent = `${uiState.editor.fontSize}px / ${uiState.editor.wordWrap ? 'wrap' : 'nowrap'}`;
                $('#xpanel_summary_layout').textContent = [
                    uiState.layout.left ? 'izq' : null,
                    uiState.layout.bottom ? 'terminal' : null,
                    uiState.layout.right ? 'info' : null,
                ].filter(Boolean).join(' + ') || 'editor';
                const terminal = activeTerminal();
                $('#xpanel_summary_terminal').textContent = terminal?.name || '-';
                $('#xpanel_info_tabs').textContent = state.tabs.length;
                $('#xpanel_info_terminal').textContent = terminal?.name || '-';
                const agentActive = $('#xpanel_agent_active');
                if (agentActive) agentActive.textContent = activeTab()?.name || state.selected?.name || '-';
            };
            const closeDuplicatePane = () => {
                destroyEditorSplit();
                $('#xpanel_file_shell').classList.remove('xpanel-editor-duplicated');
                $('#xpanel_editor_group_clone').classList.add('ikode_hidden');
                state.cloneTabs = [];
                state.activeCloneTab = null;
                state.cloneEditor?.setModel(null);
                $$('[data-fm-action="duplicate-tab"]').forEach((button) => button.classList.remove('is-active'));
                layoutEditor();
            };
            const syncEditorActions = () => {
                const tab = activeTab();
                $$('[data-fm-action="duplicate-tab"]').forEach((button) => {
                    button.classList.toggle('ikode_hidden', !tab || tab.kind !== 'code');
                });
            };
            const cloneTab = () => state.tabs.find((tab) => tab.path === state.activeCloneTab) || null;
            const cloneTabList = () => {
                state.cloneTabs = state.cloneTabs.filter((path) => state.tabs.some((tab) => tab.path === path && tab.kind === 'code'));
                if (state.activeCloneTab && !state.cloneTabs.includes(state.activeCloneTab)) {
                    state.activeCloneTab = state.cloneTabs[0] || null;
                }
                return state.cloneTabs
                    .map((path) => state.tabs.find((tab) => tab.path === path))
                    .filter(Boolean);
            };
            const renderTabsInto = (tabs, tabList = state.tabs, activePath = state.activeTab, group = 'main') => {
                if (!tabs) return;
                tabs.innerHTML = tabList.map((tab) => `
                    <button class="ikode_tab ${tab.path === activePath ? 'ikode_tab_active' : ''}" type="button" data-tab-path="${escapeHtml(tab.path)}">
                        <i class="ki-filled ${icon({ name: tab.name, is_dir: false })}"></i>
                        <span>${escapeHtml(tab.name)}</span>
                        ${tab.isDirty ? '<span class="text-warning">*</span>' : ''}
                        <span class="ikode_tab_close" data-tab-close="${escapeHtml(tab.path)}">x</span>
                    </button>
                `).join('');
                tabs.querySelectorAll('[data-tab-path]').forEach((tabButton) => {
                    tabButton.addEventListener('click', (event) => {
                        if (event.target.closest('[data-tab-close]')) return;
                        if (group === 'clone') {
                            activateCloneTab(tabButton.dataset.tabPath);
                            return;
                        }
                        activateTab(tabButton.dataset.tabPath);
                    });
                });
                tabs.querySelectorAll('[data-tab-close]').forEach((closeButton) => {
                    closeButton.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (group === 'clone') {
                            closeCloneTab(closeButton.dataset.tabClose);
                            return;
                        }
                        closeTab(closeButton.dataset.tabClose);
                    });
                });
            };
            const renderTabs = () => {
                renderTabsInto($('#xpanel_file_tabs'), state.tabs, state.activeTab, 'main');
                renderTabsInto($('#xpanel_file_tabs_clone'), cloneTabList(), state.activeCloneTab, 'clone');
                syncEditorActions();
            };
            const showEmptyEditor = () => {
                state.openPath = null;
                state.openName = null;
                state.isDirty = false;
                $('#xpanel_empty_state').classList.remove('ikode_hidden');
                $('#xpanel_file_preview').classList.add('ikode_hidden');
                $('#xpanel_editor_groups').classList.add('ikode_hidden');
                state.editor?.setModel(null);
                closeDuplicatePane();
                syncEditorActions();
            };
            const renderPreview = (tab) => {
                const preview = $('#xpanel_file_preview');
                const safeName = escapeHtml(tab.name);
                preview.classList.remove('ikode_hidden');
                if (tab.kind === 'image') {
                    preview.innerHTML = `<img src="${downloadUrl(tab.path, true)}" alt="${safeName}" style="max-width:100%;max-height:100%;object-fit:contain;">`;
                    $('#xpanel_inline_preview').innerHTML = `Imagen abierta: <span class="font-mono">${safeName}</span>`;
                    return;
                }
                if (tab.kind === 'video') {
                    preview.innerHTML = `<video src="${downloadUrl(tab.path, true)}" controls style="width:100%;height:100%;object-fit:contain;background:#000;"></video>`;
                    $('#xpanel_inline_preview').innerHTML = `Video abierto: <span class="font-mono">${safeName}</span>`;
                    return;
                }
                if (tab.kind === 'pdf') {
                    preview.innerHTML = `<iframe src="${downloadUrl(tab.path, true)}" style="width:100%;height:100%;border:0;background:#111;"></iframe>`;
                    $('#xpanel_inline_preview').innerHTML = `PDF abierto: <span class="font-mono">${safeName}</span>`;
                    return;
                }
                preview.innerHTML = `
                    <div class="xpanel-preview-message">
                        <div class="xpanel-preview-message-inner">
                            <i class="ki-filled ki-document text-5xl text-muted-foreground"></i>
                            <div class="text-base font-semibold text-mono break-all">${safeName}</div>
                            <div class="text-sm text-secondary-foreground">Este formato no soporta vista previa en el navegador.</div>
                            <a class="kt-btn kt-btn-outline" href="${downloadUrl(tab.path)}" target="_blank" rel="noopener">
                                <i class="ki-filled ki-exit-down"></i>
                                Descargar archivo
                            </a>
                        </div>
                    </div>
                `;
                $('#xpanel_inline_preview').innerHTML = `Vista previa no soportada: <span class="font-mono">${safeName}</span>`;
            };
            const showActiveTab = () => {
                const tab = activeTab();
                if (!tab) {
                    showEmptyEditor();
                    return;
                }
                state.openPath = tab.path;
                state.openName = tab.name;
                state.isDirty = !!tab.isDirty;
                $('#xpanel_empty_state').classList.add('ikode_hidden');
                $('#xpanel_outline_file').textContent = tab.name;
                renderTabs();
                if (tab.kind === 'code') {
                    $('#xpanel_file_preview').classList.add('ikode_hidden');
                    $('#xpanel_editor_groups').classList.remove('ikode_hidden');
                    state.editor.setModel(tab.model);
                    if ($('#xpanel_file_shell').classList.contains('xpanel-editor-duplicated')) {
                        state.cloneEditor?.setModel(cloneTab()?.model || null);
                        $('#xpanel_editor_group_clone').classList.remove('ikode_hidden');
                        buildEditorSplit();
                    }
                    $('#xpanel_inline_preview').textContent = 'Este archivo esta en modo editor.';
                    layoutEditor();
                } else {
                    $('#xpanel_editor_groups').classList.add('ikode_hidden');
                    state.editor?.setModel(null);
                    closeDuplicatePane();
                    renderPreview(tab);
                }
                syncEditorActions();
            };
            const activateTab = (path) => {
                state.activeTab = path;
                renderTabs();
                showActiveTab();
            };
            const activateCloneTab = (path) => {
                if (!state.cloneTabs.includes(path)) return;
                const tab = state.tabs.find((item) => item.path === path);
                if (!tab || tab.kind !== 'code' || !tab.model) {
                    closeCloneTab(path);
                    return;
                }
                state.activeCloneTab = path;
                state.cloneEditor?.setModel(tab.model);
                renderTabs();
                layoutEditor();
            };
            const closeCloneTab = (path) => {
                const index = state.cloneTabs.indexOf(path);
                if (index < 0) return;
                const wasActive = state.activeCloneTab === path;
                state.cloneTabs.splice(index, 1);
                if (!state.cloneTabs.length) {
                    closeDuplicatePane();
                    renderTabs();
                    return;
                }
                if (wasActive) {
                    const next = state.cloneTabs[index] || state.cloneTabs[index - 1] || state.cloneTabs[0];
                    state.activeCloneTab = next || null;
                    state.cloneEditor?.setModel(cloneTab()?.model || null);
                }
                renderTabs();
                layoutEditor();
            };
            const closeTab = (path) => {
                const index = state.tabs.findIndex((tab) => tab.path === path);
                if (index < 0) return;
                const tab = state.tabs[index];
                if (tab.isDirty && !confirm(`Cerrar "${tab.name}" sin guardar?`)) return;
                const wasActive = state.activeTab === path;
                closeCloneTab(path);
                if (wasActive) {
                    state.editor?.setModel(null);
                }
                tab.model?.dispose?.();
                state.tabs.splice(index, 1);
                if (wasActive) {
                    const next = state.tabs[index] || state.tabs[index - 1] || null;
                    state.activeTab = next?.path || null;
                }
                renderTabs();
                showActiveTab();
            };
            const renderInlineCreate = (parentPath, depth) => {
                if (!state.pendingCreate || state.pendingCreate.parentPath !== parentPath) return '';
                const iconClass = state.pendingCreate.type === 'folder' ? 'ki-folder-up' : 'ki-file-up';
                const placeholder = state.pendingCreate.type === 'folder' ? 'nueva-carpeta' : 'nuevo-archivo.txt';
                return `
                    <div class="xpanel-file-inline" style="padding-left:${8 + depth * 14}px">
                        <span class="xpanel-file-toggle"></span>
                        <i class="ki-filled ${iconClass}"></i>
                        <input data-inline-create="true" placeholder="${placeholder}" autocomplete="off">
                    </div>
                `;
            };
            const renderLoadingRow = (depth) => `
                <div class="xpanel-file-row xpanel-file-row-muted" style="padding-left:${8 + depth * 14}px">
                    <span class="xpanel-file-toggle"></span>
                    <i class="ki-filled ki-arrows-circle"></i>
                    <span class="xpanel-file-name">Cargando...</span>
                </div>
            `;
            const renderRenameRow = (entry, depth, expanded = false) => {
                const toggle = entry.is_dir ? (expanded ? 'ki-down' : 'ki-right') : '';
                return `
                    <div class="xpanel-file-row is-renaming"
                         style="padding-left:${8 + depth * 14}px"
                         data-path="${escapeHtml(entry.path)}"
                         data-dir="${entry.is_dir ? '1' : '0'}">
                        <span class="xpanel-file-toggle">${entry.is_dir ? `<i class="ki-filled ${toggle}"></i>` : ''}</span>
                        <i class="ki-filled ${icon(entry)}"></i>
                        <input class="xpanel-file-rename-input" data-inline-rename="true" value="${escapeHtml(entry.name)}" autocomplete="off">
                    </div>
                `;
            };
            const renderDirectoryRows = (parentPath = '/', depth = 0) => {
                const filter = ($('#xpanel_file_filter').value || '').toLowerCase();
                const entries = entriesFor(parentPath).filter((entry) => !filter || entry.name.toLowerCase().includes(filter) || entry.is_dir);
                let html = renderInlineCreate(parentPath, depth);
                html += entries.map((entry) => {
                    const expanded = state.expanded.has(entry.path);
                    const selected = state.selected?.path === entry.path;
                    const toggle = entry.is_dir ? (expanded ? 'ki-down' : 'ki-right') : '';
                    const childRows = entry.is_dir && expanded
                        ? (state.loadingDirs.has(entry.path) ? renderLoadingRow(depth + 1) : renderDirectoryRows(entry.path, depth + 1))
                        : '';
                    if (state.pendingRename?.path === entry.path) {
                        return `${renderRenameRow(entry, depth, expanded)}${childRows}`;
                    }
                    return `
                        <div class="xpanel-file-row ${selected ? 'active' : ''}"
                             style="padding-left:${8 + depth * 14}px"
                             data-path="${escapeHtml(entry.path)}"
                             data-dir="${entry.is_dir ? '1' : '0'}"
                             draggable="true">
                            <span class="xpanel-file-toggle">${entry.is_dir ? `<i class="ki-filled ${toggle}"></i>` : ''}</span>
                            <i class="ki-filled ${icon(entry)}"></i>
                            <span class="xpanel-file-name ${entry.is_dir ? 'font-semibold text-mono' : ''}">${escapeHtml(entry.name)}</span>
                            ${entry.is_dir ? '' : `<span class="xpanel-file-size">${size(entry.size || 0)}</span>`}
                        </div>
                        ${childRows}
                    `;
                }).join('');
                return html;
            };
            const focusPendingInput = () => {
                const input = $('[data-inline-create], [data-inline-rename]');
                if (!input) return;
                setTimeout(() => {
                    input.focus();
                    input.select();
                }, 20);
            };
            const renderTree = () => {
                const list = $('#xpanel_file_list');
                const rootEntries = entriesFor('/');
                if (!rootEntries.length && !state.pendingCreate) {
                    list.innerHTML = `
                        <div class="xpanel-file-drop m-3" id="xpanel_drop_hint">
                            <i class="ki-filled ki-file-up text-lg"></i>
                            <div>Arrastra archivos aqui para subirlos</div>
                            <div class="mt-1 text-[11px] opacity-70">Tambien puedes crear archivos o carpetas desde arriba.</div>
                        </div>
                    `;
                    return;
                }
                list.innerHTML = `<div class="xpanel-file-tree">${renderDirectoryRows('/', 0)}</div>`;
                attachTreeEvents();
                focusPendingInput();
            };
            const draggedEntryFromEvent = (event) => {
                if (state.draggedEntry) return state.draggedEntry;
                const path = event.dataTransfer?.getData('application/x-xpanel-path') || event.dataTransfer?.getData('text/plain');
                return path ? getEntry(path) : null;
            };
            const canMoveEntryTo = (entry, targetDir) => {
                if (!entry || !targetDir || targetDir === dirname(entry.path)) return false;
                return !(entry.is_dir && (targetDir === entry.path || targetDir.startsWith(`${entry.path}/`)));
            };
            const attachTreeEvents = () => {
                $$('.xpanel-file-row').forEach((row) => {
                    const entry = getEntry(row.dataset.path);
                    if (!entry) return;
                    row.addEventListener('click', async (event) => {
                        if (event.target.closest('[data-inline-rename]')) return;
                        if (entry.is_dir) {
                            select(entry);
                            await toggleDirectory(entry.path);
                            return;
                        }
                        await open(entry);
                    });
                    row.addEventListener('contextmenu', (event) => {
                        event.stopPropagation();
                        context(event, entry);
                    });
                    row.addEventListener('dragstart', (event) => {
                        state.draggedEntry = entry;
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('application/x-xpanel-path', entry.path);
                        event.dataTransfer.setData('text/plain', entry.path);
                    });
                    row.addEventListener('dragend', () => {
                        state.draggedEntry = null;
                        $$('.xpanel-file-row.drop-target').forEach((item) => item.classList.remove('drop-target'));
                    });
                    row.addEventListener('dragover', (event) => {
                        if (!entry.is_dir) return;
                        const hasFiles = Array.from(event.dataTransfer?.types || []).includes('Files');
                        if (!hasFiles && !canMoveEntryTo(state.draggedEntry, entry.path)) return;
                        event.preventDefault();
                        event.stopPropagation();
                        event.dataTransfer.dropEffect = hasFiles ? 'copy' : 'move';
                        row.classList.add('drop-target');
                    });
                    row.addEventListener('dragleave', () => row.classList.remove('drop-target'));
                    row.addEventListener('drop', async (event) => {
                        if (!entry.is_dir) return;
                        event.preventDefault();
                        event.stopPropagation();
                        row.classList.remove('drop-target');
                        const dragged = draggedEntryFromEvent(event);
                        if (dragged) {
                            await moveEntry(dragged, entry.path);
                            state.draggedEntry = null;
                        } else {
                            await upload(Array.from(event.dataTransfer.files || []), entry.path);
                        }
                    });
                });
                const inlineInput = $('[data-inline-create]');
                if (inlineInput) {
                    let finished = false;
                    const finish = async (cancel = false) => {
                        if (finished) return;
                        finished = true;
                        if (cancel) {
                            cancelInlineCreate();
                            return;
                        }
                        try {
                            await commitInlineCreate(inlineInput.value);
                        } catch (error) {
                            state.pendingCreate = null;
                            renderTree();
                            toast(error.message, 'error');
                        }
                    };
                    inlineInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            finish(false);
                        }
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            finish(true);
                        }
                    });
                    inlineInput.addEventListener('blur', () => finish(false));
                }
                const renameInput = $('[data-inline-rename]');
                if (renameInput) {
                    let finished = false;
                    const finish = async (cancel = false) => {
                        if (finished) return;
                        finished = true;
                        if (cancel) {
                            cancelInlineRename();
                            return;
                        }
                        try {
                            await commitInlineRename(renameInput.value);
                        } catch (error) {
                            state.pendingRename = null;
                            renderTree();
                            toast(error.message, 'error');
                        }
                    };
                    renameInput.addEventListener('click', (event) => event.stopPropagation());
                    renameInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            finish(false);
                        }
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            finish(true);
                        }
                    });
                    renameInput.addEventListener('blur', () => finish(false));
                }
            };
            const renderList = renderTree;
            const searchLabel = (result) => result.kind === 'content' ? `Linea ${result.line || 1}` : (result.is_dir ? 'Carpeta' : 'Nombre');
            const syncSearchButton = () => {
                const query = ($('#xpanel_file_filter')?.value || '').trim();
                const label = $('#xpanel_search_button_text');
                if (label) label.textContent = query || 'Buscar archivo, carpeta o contenido';
            };
            const openSearchLauncher = () => {
                $('#xpanel_search_popover')?.classList.remove('hidden');
                syncSearchButton();
                renderSearchResults();
                setTimeout(() => $('#xpanel_file_filter')?.focus(), 30);
            };
            const closeSearchLauncher = () => {
                $('#xpanel_search_popover')?.classList.add('hidden');
                syncSearchButton();
            };
            const renderSearchResults = (payload = null, loading = false) => {
                const popover = $('#xpanel_search_popover');
                const target = $('#xpanel_search_results');
                const query = ($('#xpanel_file_filter').value || '').trim();
                if (!popover || !target) return;
                syncSearchButton();
                if (!query) {
                    target.innerHTML = '<div class="p-3 text-xs text-secondary-foreground">Escribe al menos 2 caracteres para buscar.</div>';
                    return;
                }
                if (query.length < 2) {
                    target.innerHTML = '<div class="p-3 text-xs text-secondary-foreground">Escribe al menos 2 caracteres para buscar.</div>';
                    return;
                }
                if (loading) {
                    target.innerHTML = '<div class="p-3 text-xs text-secondary-foreground">Buscando en archivos...</div>';
                    return;
                }
                const results = payload?.results || state.searchResults || [];
                if (!results.length) {
                    target.innerHTML = '<div class="p-3 text-xs text-secondary-foreground">Sin coincidencias.</div>';
                    return;
                }
                target.innerHTML = `
                    ${payload?.truncated ? '<div class="px-2 py-1 text-[11px] text-warning">Resultados limitados. Refina la busqueda.</div>' : ''}
                    ${results.map((result, index) => `
                        <button class="xpanel-search-result" type="button" data-search-index="${index}">
                            <i class="ki-filled ${icon({ name: result.name, is_dir: result.is_dir })}"></i>
                            <span>
                                <span class="xpanel-search-result-title">${escapeHtml(result.name)}</span>
                                <span class="xpanel-search-result-path">${escapeHtml(result.path || '/')}</span>
                                ${result.preview ? `<span class="xpanel-search-result-preview">${escapeHtml(result.preview)}</span>` : ''}
                            </span>
                            <span class="xpanel-search-result-kind">${escapeHtml(searchLabel(result))}</span>
                        </button>
                    `).join('')}
                `;
            };
            const performSearch = async () => {
                const query = ($('#xpanel_file_filter').value || '').trim();
                syncSearchButton();
                renderList();
                if (state.searchAbort) state.searchAbort.abort();
                if (query.length < 2) {
                    state.searchResults = [];
                    renderSearchResults();
                    return;
                }
                state.searchAbort = new AbortController();
                renderSearchResults(null, true);
                try {
                    const payload = await api('POST', '/search', {
                        domain: config.domain,
                        path: state.currentPath || '/',
                        query,
                        include_content: uiState.search.includeContent,
                        case_sensitive: uiState.search.caseSensitive,
                    }, state.searchAbort.signal);
                    state.searchResults = payload.results || [];
                    renderSearchResults(payload);
                    log(`Busqueda: "${query}" (${state.searchResults.length})`);
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    $('#xpanel_search_results').innerHTML = `<div class="p-3 text-xs text-destructive">${escapeHtml(error.message)}</div>`;
                }
            };
            const queueSearch = () => {
                clearTimeout(state.searchTimer);
                state.searchTimer = setTimeout(performSearch, 320);
            };
            const revealEditorLine = (line = 1, column = 1) => {
                const safeLine = Math.max(1, Number(line) || 1);
                const safeColumn = Math.max(1, Number(column) || 1);
                setTimeout(() => {
                    state.editor?.setPosition({ lineNumber: safeLine, column: safeColumn });
                    state.editor?.revealLineInCenter(safeLine);
                    state.editor?.focus();
                }, 120);
            };
            const openSearchResult = async (index) => {
                const result = state.searchResults[index];
                if (!result) return;
                $('#xpanel_search_popover')?.classList.add('hidden');
                if (result.is_dir) {
                    state.expanded.add(result.path);
                    await loadDirectory(result.path);
                    select({ ...result, is_dir: true });
                    return;
                }
                const parent = dirname(result.path);
                state.expanded.add(parent);
                await ensureDirectory(parent);
                const entry = getEntry(result.path) || { name: result.name, path: result.path, is_dir: false, size: 0 };
                await open(entry);
                if (result.kind === 'content') revealEditorLine(result.line, result.column);
            };
            const ensureDirectory = async (path = '/') => {
                if (state.dirCache[path]) return state.dirCache[path];
                await loadDirectory(path, { render: false, setCurrent: false });
                return state.dirCache[path] || [];
            };
            const loadDirectory = async (path = '/', options = {}) => {
                const targetPath = path || '/';
                const shouldRender = options.render !== false;
                const shouldSetCurrent = options.setCurrent !== false;
                if (shouldSetCurrent) setCurrentPath(targetPath);
                if (shouldRender && !state.dirCache[targetPath]) {
                    $('#xpanel_file_list').innerHTML = '<div class="p-4 text-xs text-secondary-foreground">Cargando...</div>';
                }
                try {
                    const payload = await api('GET', `/list?domain=${domainParam()}&path=${encodeURIComponent(targetPath)}`);
                    state.dirCache[targetPath] = payload.entries || [];
                    if (targetPath === state.currentPath) state.entries = state.dirCache[targetPath];
                    updateSummary();
                    if (shouldRender) renderTree();
                    log(`Directorio cargado: ${targetPath}`);
                    return state.dirCache[targetPath];
                } catch (error) {
                    if (shouldRender) $('#xpanel_file_list').innerHTML = `<div class="p-4 text-xs text-destructive">${escapeHtml(error.message)}</div>`;
                    log(`Error: ${error.message}`);
                    throw error;
                }
            };
            const select = (entry) => {
                state.selected = entry;
                if (entry) setCurrentPath(entry.is_dir ? entry.path : dirname(entry.path));
                renderInfo(entry);
                renderTree();
            };
            const toggleDirectory = async (path) => {
                if (state.expanded.has(path)) {
                    state.expanded.delete(path);
                    renderTree();
                    return;
                }
                state.expanded.add(path);
                if (!state.dirCache[path]) {
                    state.loadingDirs.add(path);
                    renderTree();
                    try {
                        await loadDirectory(path, { render: false, setCurrent: false });
                    } finally {
                        state.loadingDirs.delete(path);
                    }
                }
                renderTree();
            };
            const open = async (entry = state.selected) => {
                if (!entry) return;
                if (entry.is_dir) {
                    select(entry);
                    await toggleDirectory(entry.path);
                    return;
                }
                select(entry);
                const existing = state.tabs.find((tab) => tab.path === entry.path);
                if (existing) {
                    activateTab(existing.path);
                    return;
                }
                const kind = previewKind(entry.name);
                const tab = { path: entry.path, name: entry.name, kind, entry, model: null, isDirty: false };
                state.tabs.push(tab);
                if (kind === 'code') {
                    try {
                        const payload = await api('GET', `/read?domain=${domainParam()}&path=${encodeURIComponent(entry.path)}`);
                        tab.model = monaco.editor.createModel(payload.content || '', language(entry.name));
                        tab.model.onDidChangeContent(() => {
                            if (!tab.isDirty) {
                                tab.isDirty = true;
                                if (state.activeTab === tab.path) state.isDirty = true;
                                renderTabs();
                            }
                        });
                    } catch (error) {
                        state.tabs = state.tabs.filter((item) => item.path !== tab.path);
                        toast(error.message, 'error');
                        log(`Error al abrir: ${error.message}`);
                        return;
                    }
                }
                activateTab(entry.path);
                log(`Archivo abierto: ${entry.path}`);
            };
            const save = async (tab = activeTab()) => {
                if (!tab || tab.kind !== 'code' || !tab.model) return;
                try {
                    await api('POST', '/write', { domain: config.domain, path: tab.path, content: tab.model.getValue() });
                    tab.isDirty = false;
                    state.isDirty = false;
                    renderTabs();
                    toast('Archivo guardado');
                    log(`Guardado: ${tab.path}`);
                } catch (error) {
                    toast(error.message, 'error');
                }
            };
            const download = (tab = activeTab()) => {
                const path = tab?.path || (state.selected?.is_dir ? null : state.selected?.path);
                if (!path) return;
                window.open(downloadUrl(path));
            };
            const cloneAction = async (name) => {
                const tab = cloneTab();
                if (name === 'save') await save(tab);
                if (name === 'download') download(tab);
            };

            const promptInput = (title, value, callback) => {
                state.inputCallback = callback;
                $('#xpanel_input_title').textContent = title;
                $('#xpanel_input_value').value = value;
                $('#xpanel_input_modal').classList.remove('hidden');
                $('#xpanel_input_modal').classList.add('flex');
                setTimeout(() => $('#xpanel_input_value').focus(), 30);
            };

            const closeInput = () => {
                $('#xpanel_input_modal').classList.add('hidden');
                $('#xpanel_input_modal').classList.remove('flex');
                state.inputCallback = null;
            };

            const uniqueName = (parentPath, type, preferred = '') => {
                const fallback = type === 'folder' ? 'nueva-carpeta' : 'nuevo-archivo.txt';
                const initial = (preferred || '').trim() || fallback;
                const names = new Set(entriesFor(parentPath).map((entry) => entry.name.toLowerCase()));
                if (!names.has(initial.toLowerCase())) return initial;
                const dot = type === 'file' ? initial.lastIndexOf('.') : -1;
                const stem = dot > 0 ? initial.slice(0, dot) : initial;
                const suffix = dot > 0 ? initial.slice(dot) : '';
                for (let index = 1; index < 500; index++) {
                    const candidate = `${stem}-${index}${suffix}`;
                    if (!names.has(candidate.toLowerCase())) return candidate;
                }
                return `${stem}-${Date.now()}${suffix}`;
            };
            const targetDirectory = (fromContextMenu = false) => {
                if (fromContextMenu) {
                    if (state.ctxEntry?.is_dir) return state.ctxEntry.path;
                    if (state.ctxEntry) return dirname(state.ctxEntry.path);
                    if (state.ctxFromBlank) {
                        const contextTarget = state.ctxDirectory || state.currentPath || '/';
                        return requireConcreteSiteTarget(contextTarget);
                    }
                }

                const current = state.currentPath || '/';
                return requireConcreteSiteTarget(current);
            };
            const startInlineCreate = async (type, fromContextMenu = false) => {
                const parentPath = targetDirectory(fromContextMenu);
                state.pendingRename = null;
                state.pendingCreate = { type, parentPath };
                state.expanded.add(parentPath);
                await ensureDirectory(parentPath);
                renderTree();
            };
            const commitInlineCreate = async (value = '') => {
                if (!state.pendingCreate) return;
                const pending = state.pendingCreate;
                const name = uniqueName(pending.parentPath, pending.type, value);
                const newPath = pathJoin(pending.parentPath, name);
                state.pendingCreate = null;
                if (pending.type === 'folder') {
                    await api('POST', '/mkdir', { domain: config.domain, path: newPath });
                    state.expanded.add(newPath);
                    await loadDirectory(pending.parentPath);
                    state.dirCache[newPath] = [];
                    toast('Carpeta creada');
                    log(`Carpeta creada: ${newPath}`);
                } else {
                    await api('POST', '/create', {
                        domain: config.domain,
                        path: pending.parentPath,
                        name,
                        type: 'file',
                    });
                    await loadDirectory(pending.parentPath);
                    const entry = getEntry(newPath) || { name, path: newPath, is_dir: false, size: 0 };
                    toast('Archivo creado');
                    await open(entry);
                }
            };
            const cancelInlineCreate = () => {
                state.pendingCreate = null;
                renderTree();
            };
            const newFile = (fromContextMenu = false) => startInlineCreate('file', fromContextMenu);
            const newFolder = (fromContextMenu = false) => startInlineCreate('folder', fromContextMenu);

            const startInlineRename = () => {
                const entry = state.selected || state.ctxEntry;
                if (!entry) return;
                state.pendingCreate = null;
                state.pendingRename = { path: entry.path, name: entry.name };
                state.expanded.add(dirname(entry.path));
                renderTree();
            };
            const commitInlineRename = async (value = '') => {
                if (!state.pendingRename) return;
                const pending = state.pendingRename;
                const entry = getEntry(pending.path) || state.selected || state.ctxEntry;
                if (!entry) {
                    state.pendingRename = null;
                    renderTree();
                    return;
                }
                const parent = dirname(entry.path);
                const name = String(value || '').trim() || entry.name;
                if (name.includes('/') || name.includes('\\')) {
                    throw new Error('El nombre no puede contener barras');
                }
                if (name === entry.name) {
                    cancelInlineRename();
                    return;
                }
                if (entriesFor(parent).some((item) => item.path !== entry.path && item.name.toLowerCase() === name.toLowerCase())) {
                    throw new Error('Ya existe un elemento con ese nombre');
                }

                const oldPath = entry.path;
                const newPath = pathJoin(parent, name);
                state.pendingRename = null;
                await api('POST', '/rename', { domain: config.domain, old_path: oldPath, new_path: newPath });
                updateOpenPaths(oldPath, newPath);
                clearCachedBranch(oldPath);
                await loadDirectory(parent, { render: false, setCurrent: false });
                if (entry.is_dir && state.expanded.has(oldPath)) {
                    state.expanded.delete(oldPath);
                    state.expanded.add(newPath);
                }
                if (state.selected?.path === oldPath || state.selected?.path?.startsWith(`${oldPath}/`)) {
                    const selectedPath = newPath + state.selected.path.slice(oldPath.length);
                    state.selected = { ...state.selected, path: selectedPath, name: basename(selectedPath) };
                    renderInfo(state.selected);
                }
                if (state.currentPath === oldPath || state.currentPath.startsWith(`${oldPath}/`)) {
                    setCurrentPath(newPath + state.currentPath.slice(oldPath.length));
                }
                renderTree();
                toast('Renombrado');
                log(`Renombrado: ${oldPath} -> ${newPath}`);
            };
            const cancelInlineRename = () => {
                state.pendingRename = null;
                renderTree();
            };

            const remove = async () => {
                const entry = state.selected || state.ctxEntry;
                if (!entry || !confirm(`Eliminar "${entry.name}"?`)) return;
                await api('POST', '/delete', { domain: config.domain, path: entry.path });
                state.selected = null;
                closeTabsUnder(entry.path);
                clearCachedBranch(entry.path);
                await loadDirectory(dirname(entry.path));
                renderInfo(null);
                toast('Eliminado');
            };

            const uploadOne = (file, targetPath, index, total) => new Promise((resolve, reject) => {
                const form = new FormData();
                form.append('domain', config.domain || '');
                form.append('path', targetPath);
                form.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', `${config.baseUrl}/upload`);
                xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) return;
                    const filePercent = event.loaded / event.total;
                    const totalPercent = ((index + filePercent) / total) * 100;
                    setProgress(totalPercent, `Subiendo ${index + 1}/${total}: ${file.name} (${Math.round(totalPercent)}%)`);
                };
                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(xhr.responseText ? JSON.parse(xhr.responseText) : {});
                        return;
                    }
                    reject(new Error(xhr.responseText || `HTTP ${xhr.status}`));
                };
                xhr.onerror = () => reject(new Error('Error de red al subir archivo'));
                xhr.send(form);
            });

            const upload = async (files, targetPath = state.currentPath) => {
                if (!files.length) return;
                showProgress(`Subiendo ${files.length} archivo(s)...`, 0);
                try {
                    for (let index = 0; index < files.length; index++) {
                        await uploadOne(files[index], targetPath, index, files.length);
                    }
                    setProgress(100, 'Subida completada');
                    await loadDirectory(targetPath);
                    toast(`${files.length} archivo(s) subido(s)`);
                } catch (error) {
                    toast(error.message, 'error');
                    log(`Error al subir: ${error.message}`);
                    throw error;
                } finally {
                    hideProgress();
                }
            };

            const extractArchive = async (entry = state.selected || state.ctxEntry) => {
                if (!entry || entry.is_dir) return;
                if (!isExtractable(entry.name)) {
                    toast('Por ahora solo se puede descomprimir ZIP/JAR', 'error');
                    return;
                }
                const parent = dirname(entry.path);
                showProgress(`Descomprimiendo ${entry.name}...`, 10);
                try {
                    let response = await api('POST', '/extract', { domain: config.domain, path: entry.path });
                    if (response.status === 'conflict') {
                        const preview = response.conflicts.slice(0, 10).join('\n');
                        const more = response.conflict_count > response.conflicts.length ? `\n... y ${response.conflict_count - response.conflicts.length} mas` : '';
                        hideProgress();
                        const confirmed = confirm(`Esto reemplazara ${response.conflict_count} archivo(s) existente(s):\n\n${preview}${more}\n\nContinuar?`);
                        if (!confirmed) {
                            log(`Descompresion cancelada (${response.conflict_count} archivo(s) en conflicto): ${entry.path}`);
                            return;
                        }
                        showProgress(`Descomprimiendo ${entry.name}...`, 10);
                        response = await api('POST', '/extract', { domain: config.domain, path: entry.path, overwrite: true });
                    }
                    setProgress(100, `Descompresion completada (${response.count || 0} archivo(s))`);
                    state.expanded.add(parent);
                    clearCachedBranch(parent);
                    await loadDirectory(parent);
                    toast('Archivo descomprimido');
                    log(`Descomprimido: ${entry.path}`);
                } finally {
                    hideProgress(700);
                }
            };

            const updateOpenPaths = (oldPath, newPath) => {
                state.tabs.forEach((tab) => {
                    if (tab.path === oldPath || tab.path.startsWith(`${oldPath}/`)) {
                        tab.path = newPath + tab.path.slice(oldPath.length);
                        tab.name = basename(tab.path);
                    }
                });
                if (state.activeTab === oldPath || state.activeTab?.startsWith(`${oldPath}/`)) {
                    state.activeTab = newPath + state.activeTab.slice(oldPath.length);
                }
                state.cloneTabs = state.cloneTabs.map((path) => {
                    if (path === oldPath || path.startsWith(`${oldPath}/`)) return newPath + path.slice(oldPath.length);
                    return path;
                });
                if (state.activeCloneTab === oldPath || state.activeCloneTab?.startsWith(`${oldPath}/`)) {
                    state.activeCloneTab = newPath + state.activeCloneTab.slice(oldPath.length);
                    state.cloneEditor?.setModel(cloneTab()?.model || null);
                }
                renderTabs();
            };
            const closeTabsUnder = (path) => {
                state.cloneTabs = state.cloneTabs.filter((tabPath) => !(tabPath === path || tabPath.startsWith(`${path}/`)));
                if (state.activeCloneTab === path || state.activeCloneTab?.startsWith(`${path}/`)) {
                    state.activeCloneTab = state.cloneTabs[0] || null;
                    state.cloneEditor?.setModel(cloneTab()?.model || null);
                }
                if (!state.cloneTabs.length && $('#xpanel_file_shell').classList.contains('xpanel-editor-duplicated')) {
                    closeDuplicatePane();
                }
                state.tabs = state.tabs.filter((tab) => {
                    const match = tab.path === path || tab.path.startsWith(`${path}/`);
                    if (match) tab.model?.dispose?.();
                    return !match;
                });
                if (state.activeTab === path || state.activeTab?.startsWith(`${path}/`)) {
                    state.activeTab = state.tabs[0]?.path || null;
                    showActiveTab();
                }
                renderTabs();
            };
            const clearCachedBranch = (path) => {
                Object.keys(state.dirCache).forEach((key) => {
                    if (key === path || key.startsWith(`${path}/`)) delete state.dirCache[key];
                });
            };
            const moveEntry = async (entry, targetDir) => {
                if (!entry || !targetDir || targetDir === dirname(entry.path)) return;
                if (entry.is_dir && (targetDir === entry.path || targetDir.startsWith(`${entry.path}/`))) {
                    toast('No puedes mover una carpeta dentro de si misma', 'error');
                    return;
                }
                const newPath = pathJoin(targetDir, entry.name);
                if (!state.dirCache[targetDir]) {
                    await loadDirectory(targetDir, { render: false, setCurrent: false });
                }
                if (entriesFor(targetDir).some((item) => item.path !== entry.path && item.name.toLowerCase() === entry.name.toLowerCase())) {
                    toast('Ya existe un elemento con ese nombre en destino', 'error');
                    return;
                }
                const oldPath = entry.path;
                const oldParent = dirname(oldPath);
                await api('POST', '/rename', { domain: config.domain, old_path: entry.path, new_path: newPath });
                updateOpenPaths(entry.path, newPath);
                clearCachedBranch(entry.path);
                state.expanded.add(targetDir);
                if (state.selected?.path === oldPath || state.selected?.path?.startsWith(`${oldPath}/`)) {
                    const selectedPath = newPath + state.selected.path.slice(oldPath.length);
                    state.selected = { ...state.selected, path: selectedPath, name: basename(selectedPath) };
                    renderInfo(state.selected);
                }
                if (state.currentPath === oldPath || state.currentPath.startsWith(`${oldPath}/`)) {
                    setCurrentPath(newPath + state.currentPath.slice(oldPath.length));
                }
                if (state.expanded.has(oldPath)) {
                    state.expanded.delete(oldPath);
                    state.expanded.add(newPath);
                }
                await loadDirectory(oldParent, { render: false, setCurrent: false });
                await loadDirectory(targetDir, { render: false, setCurrent: false });
                renderTree();
                toast('Elemento movido');
                log(`Movido: ${oldPath} -> ${newPath}`);
            };
            const duplicateTab = () => {
                const tab = activeTab();
                if (!tab || tab.kind !== 'code' || !tab.model) {
                    toast('Duplica solo archivos de codigo', 'error');
                    return;
                }

                const shell = $('#xpanel_file_shell');
                const cloneHost = $('#xpanel_monaco_clone');
                const cloneGroup = $('#xpanel_editor_group_clone');
                shell.classList.add('xpanel-editor-duplicated');
                cloneGroup.classList.remove('ikode_hidden');
                $$('[data-fm-action="duplicate-tab"]').forEach((button) => button.classList.add('is-active'));

                if (!state.cloneEditor) {
                    state.cloneEditor = monaco.editor.create(cloneHost, {
                        value: '',
                        language: 'plaintext',
                        theme: resolveMonacoTheme(),
                        ...editorOptions(),
                    });
                }
                if (!state.cloneTabs.includes(tab.path)) state.cloneTabs.push(tab.path);
                state.activeCloneTab = tab.path;
                state.cloneEditor.setModel(tab.model);
                renderTabs();
                buildEditorSplit();
                log(`Pestana duplicada: ${tab.path}`);

                layoutEditor();
                window.setTimeout(layoutEditor, 60);
            };

            const context = (event, entry = null) => {
                event.preventDefault();
                if (!entry) {
                    const row = event.target.closest('[data-path]');
                    entry = row ? getEntry(row.dataset.path) : null;
                }
                state.ctxEntry = entry;
                state.ctxDirectory = entry?.is_dir ? entry.path : (entry ? dirname(entry.path) : (state.currentPath || '/'));
                state.ctxFromBlank = !entry;
                if (entry) select(entry);
                $$('[data-archive-only]').forEach((item) => {
                    item.classList.toggle('hidden', !entry || entry.is_dir || !isExtractable(entry.name));
                });
                const menu = $('#xpanel_ctx_menu');
                menu.style.left = `${event.clientX}px`;
                menu.style.top = `${event.clientY}px`;
                menu.classList.remove('hidden');
            };

            const action = async (name, fromContextMenu = false) => {
                try {
                    if (name === 'open') await open(state.selected || state.ctxEntry);
                    if (name === 'save') await save();
                    if (name === 'download') download();
                    if (name === 'duplicate-tab') duplicateTab();
                    if (name === 'new-file') await newFile(fromContextMenu);
                    if (name === 'new-folder') await newFolder(fromContextMenu);
                    if (name === 'extract') await extractArchive(state.selected || state.ctxEntry);
                    if (name === 'refresh') await loadDirectory(state.currentPath);
                    if (name === 'rename') startInlineRename();
                    if (name === 'delete') await remove();
                } catch (error) {
                    toast(error.message, 'error');
                    log(`Error: ${error.message}`);
                } finally {
                    $('#xpanel_ctx_menu').classList.add('hidden');
                    state.ctxEntry = null;
                    state.ctxDirectory = '/';
                    state.ctxFromBlank = false;
                }
            };

            let mainSplit = null;
            let bottomSplit = null;
            let leftSplit = null;
            let editorSplit = null;
            let terminalSplit = null;

            const layoutEditor = () => {
                window.requestAnimationFrame(() => {
                    state.editor?.layout();
                    state.cloneEditor?.layout();
                });
            };

            const splitVisible = (selector) => {
                const el = $(selector);
                return el && !el.classList.contains('ikode_hidden');
            };

            const resetSplitStyles = () => {
                ['#xpanel_left_pane', '#xpanel_center_pane', '#xpanel_right_pane', '#xpanel_code_pane', '#xpanel_bottom_pane', '#xpanel_left_files_pane', '#xpanel_left_outline_pane', '#xpanel_editor_group_main', '#xpanel_editor_group_clone', '#xpanel_monaco_editor', '#xpanel_monaco_clone', '#xpanel_terminal_sidebar', '#xpanel_terminal_main'].forEach((selector) => {
                    const el = $(selector);
                    if (!el) return;
                    el.style.width = '';
                    el.style.height = '';
                    el.style.flexBasis = '';
                });
            };

            const saveSplitState = () => {
                if (mainSplit) {
                    const sizes = mainSplit.getSizes();
                    if (splitVisible('#xpanel_right_pane') && splitVisible('#xpanel_left_pane') && sizes.length === 3) {
                        uiState.split.mainThree = sizes;
                    } else if (!splitVisible('#xpanel_right_pane') && splitVisible('#xpanel_left_pane') && sizes.length === 2) {
                        uiState.split.mainTwo = sizes;
                    }
                }
                if (bottomSplit) uiState.split.center = bottomSplit.getSizes();
                if (leftSplit) uiState.split.left = leftSplit.getSizes();
                if (editorSplit) uiState.split.editor = editorSplit.getSizes();
                if (terminalSplit) uiState.split.terminal = terminalSplit.getSizes();
                persistUiState();
            };

            const splitElementStyle = (dimension, size, gutterSize) => ({
                flexBasis: `calc(${size}% - ${gutterSize}px)`,
            });

            const splitGutterStyle = (dimension, gutterSize) => ({
                flexBasis: `${gutterSize}px`,
                flexShrink: '0',
                flexGrow: '0',
            });

            const buildMainSplit = () => {
                if (mainSplit) {
                    mainSplit.destroy();
                    mainSplit = null;
                }

                const panes = ['#xpanel_left_pane', '#xpanel_center_pane', '#xpanel_right_pane'].filter(splitVisible);
                if (!window.Split || panes.length < 2 || window.matchMedia('(max-width: 860px)').matches) {
                    return;
                }

                const sizes = panes.length === 3
                    ? uiState.split.mainThree
                    : (panes.includes('#xpanel_left_pane') ? uiState.split.mainTwo : [76, 24]);
                const minByPane = {
                    '#xpanel_left_pane': 240,
                    '#xpanel_center_pane': 360,
                    '#xpanel_right_pane': 260,
                };

                mainSplit = Split(panes, {
                    sizes,
                    minSize: panes.map((selector) => minByPane[selector]),
                    gutterSize: 3,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDrag: layoutEditor,
                    onDragEnd: () => {
                        saveSplitState();
                        layoutEditor();
                    },
                });
            };

            const buildBottomSplit = () => {
                if (bottomSplit) {
                    bottomSplit.destroy();
                    bottomSplit = null;
                }

                if (!window.Split || !splitVisible('#xpanel_bottom_pane')) {
                    $('#xpanel_code_pane').style.flexBasis = '';
                    $('#xpanel_bottom_pane').style.flexBasis = '';
                    return;
                }

                bottomSplit = Split(['#xpanel_code_pane', '#xpanel_bottom_pane'], {
                    direction: 'vertical',
                    sizes: uiState.split.center,
                    minSize: [240, 120],
                    gutterSize: 3,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDrag: layoutEditor,
                    onDragEnd: () => {
                        saveSplitState();
                        layoutEditor();
                    },
                });
            };

            const buildLeftSplit = () => {
                if (leftSplit) {
                    leftSplit.destroy();
                    leftSplit = null;
                }

                if (!window.Split || !splitVisible('#xpanel_left_pane') || uiState.ui.leftMode !== 'explorer') {
                    return;
                }

                leftSplit = Split(['#xpanel_left_files_pane', '#xpanel_left_outline_pane'], {
                    direction: 'vertical',
                    sizes: uiState.split.left,
                    minSize: [120, 110],
                    gutterSize: 3,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDragEnd: saveSplitState,
                });
            };

            const destroyEditorSplit = () => {
                if (editorSplit) {
                    editorSplit.destroy();
                    editorSplit = null;
                }
            };

            const buildEditorSplit = () => {
                destroyEditorSplit();
                if (!$('#xpanel_file_shell').classList.contains('xpanel-editor-duplicated')) return;
                const editorGroup = $('#xpanel_editor_group_main');
                const cloneGroup = $('#xpanel_editor_group_clone');
                editorGroup.style.flexBasis = `${uiState.split.editor[0] || 50}%`;
                cloneGroup.style.flexBasis = `${uiState.split.editor[1] || 50}%`;
                if (!window.Split) return;
                try {
                    editorSplit = Split(['#xpanel_editor_group_main', '#xpanel_editor_group_clone'], {
                        sizes: uiState.split.editor,
                        minSize: [0, 0],
                        gutterSize: 3,
                        elementStyle: splitElementStyle,
                        gutterStyle: splitGutterStyle,
                        snapOffset: 0,
                        onDrag: layoutEditor,
                        onDragEnd: () => {
                            saveSplitState();
                            layoutEditor();
                        },
                    });
                } catch (error) {
                    editorSplit = null;
                    console.warn('No se pudo iniciar el split del editor duplicado.', error);
                }
            };
            const buildTerminalSplit = () => {
                if (terminalSplit) {
                    terminalSplit.destroy();
                    terminalSplit = null;
                }

                if (!window.Split || uiState.ui.consoleTab !== 'terminal' || !splitVisible('#xpanel_bottom_pane') || window.matchMedia('(max-width: 760px)').matches) {
                    return;
                }

                terminalSplit = Split(['#xpanel_terminal_sidebar', '#xpanel_terminal_main'], {
                    sizes: uiState.split.terminal,
                    minSize: [150, 260],
                    gutterSize: 3,
                    elementStyle: splitElementStyle,
                    gutterStyle: splitGutterStyle,
                    snapOffset: 0,
                    onDragEnd: saveSplitState,
                });
            };

            const rebuildLayout = () => {
                resetSplitStyles();
                buildMainSplit();
                buildBottomSplit();
                buildLeftSplit();
                buildEditorSplit();
                buildTerminalSplit();
                layoutEditor();
            };

            const syncLayoutButtons = () => {
                $$('[data-layout-toggle]').forEach((button) => {
                    const pane = button.dataset.layoutToggle;
                    const target = pane === 'bottom' ? '#xpanel_bottom_pane' : `#xpanel_${pane}_pane`;
                    button.classList.toggle('ikode_header_btn_active', splitVisible(target));
                });
            };

            const toggleLayoutPane = (pane) => {
                const target = pane === 'bottom' ? '#xpanel_bottom_pane' : `#xpanel_${pane}_pane`;
                const el = $(target);
                if (!el) return;

                uiState.layout[pane] = !uiState.layout[pane];
                el.classList.toggle('ikode_hidden', !uiState.layout[pane]);
                if (pane === 'bottom') {
                    $('#xpanel_code_pane').classList.toggle('border-b', uiState.layout[pane]);
                }
                persistUiState();
                syncLayoutButtons();
                rebuildLayout();
            };

            const toggleFullscreen = (button) => {
                $('#xpanel_file_shell').classList.toggle('xpanel-fullscreen');
                button.classList.toggle('ikode_header_btn_active', $('#xpanel_file_shell').classList.contains('xpanel-fullscreen'));
                rebuildLayout();
            };

            const switchLeftMode = (mode) => {
                uiState.ui.leftMode = mode;
                $$('[data-left-mode]').forEach((button) => {
                    button.classList.toggle('ikode_header_btn_active', button.dataset.leftMode === mode);
                });
                $$('[data-left-panel]').forEach((panel) => {
                    panel.classList.toggle('ikode_hidden', panel.dataset.leftPanel !== mode);
                });
                persistUiState();
                rebuildLayout();
            };

            const switchOutlineTab = (tab) => {
                uiState.ui.outlineTab = tab;
                $$('[data-outline-tab]').forEach((button) => {
                    button.classList.toggle('ikode_left_bottom_tab_active', button.dataset.outlineTab === tab);
                });
                $$('[data-outline-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.outlineView !== tab);
                });
                persistUiState();
            };

            const switchConsoleTab = (tab) => {
                uiState.ui.consoleTab = tab;
                $$('[data-console-tab]').forEach((button) => {
                    button.classList.toggle('ikode_terminal_tab_active', button.dataset.consoleTab === tab);
                });
                $$('[data-console-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.consoleView !== tab);
                });
                persistUiState();
                if (tab === 'terminal') rebuildLayout();
            };

            const switchRightTab = (tab) => {
                const nextTab = tab === 'summary' ? 'info' : (['info', 'agents'].includes(tab) ? tab : 'info');
                uiState.ui.rightTab = nextTab;
                $$('[data-right-tab]').forEach((button) => {
                    button.classList.toggle('ikode_left_bottom_tab_active', button.dataset.rightTab === nextTab);
                });
                $$('[data-right-view]').forEach((view) => {
                    view.classList.toggle('ikode_hidden', view.dataset.rightView !== nextTab);
                });
                persistUiState();
            };

            const terminalPrompt = (terminal = activeTerminal()) => `xpanel:${terminal?.cwd || '/'} $`;
            const terminalWrite = (terminal, text, kind = 'output') => {
                if (!terminal) return;
                terminal.history.push({ kind, text: String(text) });
                if (terminal.history.length > 250) terminal.history.shift();
                if (terminal.id === state.activeTerminalId) renderTerminalOutput();
            };
            const renderTerminalList = () => {
                const list = $('#xpanel_terminal_list');
                if (!list) return;
                list.innerHTML = state.terminals.map((terminal) => `
                    <button class="xpanel-terminal-item ${terminal.id === state.activeTerminalId ? 'active' : ''}" type="button" data-terminal-id="${terminal.id}">
                        <span class="xpanel-terminal-active-dot"></span>
                        <i class="ki-filled ki-screen"></i>
                        <span>${escapeHtml(terminal.name)}</span>
                        <span class="xpanel-terminal-badge">${escapeHtml(terminal.cwd || '/')}</span>
                    </button>
                `).join('');
            };
            const renderTerminalOutput = () => {
                const terminal = activeTerminal();
                const output = $('#xpanel_terminal_output');
                if (!terminal || !output) return;
                $('#xpanel_terminal_prompt').textContent = terminalPrompt(terminal);
                output.innerHTML = terminal.history.map((item) => {
                    if (item.kind === 'command') {
                        return `<div><span class="ikode_prompt">${escapeHtml(item.prompt || terminalPrompt(terminal))}</span> ${escapeHtml(item.text)}</div>`;
                    }
                    const color = item.kind === 'error' ? 'text-destructive' : (item.kind === 'system' ? 'text-secondary-foreground' : '');
                    return `<div class="${color}">${escapeHtml(item.text)}</div>`;
                }).join('');
                output.scrollTop = output.scrollHeight;
                renderTerminalList();
                updateSummary();
            };
            const switchTerminalSession = (id) => {
                if (!state.terminals.some((terminal) => terminal.id === id)) return;
                state.activeTerminalId = id;
                const terminal = activeTerminal();
                if (terminal) terminal.commandIndex = terminal.commandHistory.length;
                persistTerminals();
                renderTerminalOutput();
                setTimeout(() => $('#xpanel_terminal_input')?.focus(), 20);
            };
            const createTerminal = () => {
                state.terminalSeq += 1;
                const terminal = {
                    id: `terminal-${Date.now()}`,
                    name: `Terminal ${state.terminalSeq}`,
                    cwd: state.currentPath || '/',
                    history: [],
                    commandHistory: [],
                    commandIndex: 0,
                };
                state.terminals.push(terminal);
                switchTerminalSession(terminal.id);
            };
            const resetSingleTerminal = () => {
                state.terminalSeq = 1;
                const terminal = {
                    id: `terminal-${Date.now()}`,
                    name: 'Terminal 1',
                    cwd: state.currentPath || '/',
                    history: [],
                    commandHistory: [],
                    commandIndex: 0,
                };
                state.terminals = [terminal];
                state.activeTerminalId = terminal.id;
                persistTerminals();
                renderTerminalOutput();
            };
            const removeActiveTerminal = () => {
                const index = state.terminals.findIndex((terminal) => terminal.id === state.activeTerminalId);
                if (index < 0) return;
                if (state.terminals.length <= 1) {
                    resetSingleTerminal();
                    return;
                }
                state.terminals.splice(index, 1);
                const next = state.terminals[Math.max(0, index - 1)] || state.terminals[0];
                state.activeTerminalId = next?.id || null;
                persistTerminals();
                renderTerminalOutput();
            };
            const terminalAction = (action) => {
                if (action === 'new') createTerminal();
                if (action === 'remove') removeActiveTerminal();
            };
            const resolveTerminalPath = (terminal, value = '') => {
                const raw = String(value || '').trim();
                if (!raw) return terminal?.cwd || '/';
                return normalizePath(raw.startsWith('/') ? raw : pathJoin(terminal?.cwd || '/', raw));
            };
            const commandHelp = [
                'Comandos: help, pwd, ls [ruta], cd <carpeta>, open <archivo>, mkdir <nombre>, touch <archivo>, extract <zip>, refresh, clear.',
                'Esta terminal opera sobre el gestor de archivos; una shell real del servidor requiere PTY/WebSocket seguro.',
            ];
            const executeTerminalCommand = async (command) => {
                const terminal = activeTerminal();
                if (!terminal || !command.trim()) return;
                const raw = command.trim();
                terminal.history.push({ kind: 'command', text: raw, prompt: terminalPrompt(terminal) });
                terminal.commandHistory.push(raw);
                terminal.commandIndex = terminal.commandHistory.length;
                renderTerminalOutput();

                const [name = '', ...rest] = raw.split(/\s+/);
                const arg = rest.join(' ');
                try {
                    if (name === 'help') {
                        commandHelp.forEach((line) => terminalWrite(terminal, line, 'system'));
                        return;
                    }
                    if (name === 'clear') {
                        terminal.history = [];
                        renderTerminalOutput();
                        return;
                    }
                    if (name === 'pwd') {
                        terminalWrite(terminal, terminal.cwd || '/');
                        return;
                    }
                    if (name === 'refresh') {
                        clearCachedBranch(terminal.cwd || '/');
                        await loadDirectory(terminal.cwd || '/');
                        terminalWrite(terminal, `Actualizado ${terminal.cwd || '/'}`);
                        return;
                    }
                    if (name === 'ls') {
                        const path = resolveTerminalPath(terminal, arg || terminal.cwd);
                        const entries = await ensureDirectory(path);
                        terminalWrite(terminal, entries.length ? entries.map((entry) => `${entry.is_dir ? '[dir] ' : '      '}${entry.name}`).join('\n') : '(vacio)');
                        return;
                    }
                    if (name === 'cd') {
                        const path = resolveTerminalPath(terminal, arg || '/');
                        await ensureDirectory(path);
                        terminal.cwd = path;
                        state.expanded.add(path);
                        await loadDirectory(path);
                        persistTerminals();
                        terminalWrite(terminal, `Directorio actual: ${path}`);
                        return;
                    }
                    if (name === 'open') {
                        const path = resolveTerminalPath(terminal, arg);
                        const parent = dirname(path);
                        await ensureDirectory(parent);
                        const entry = getEntry(path);
                        if (!entry || entry.is_dir) throw new Error('Archivo no encontrado');
                        await open(entry);
                        terminalWrite(terminal, `Abierto ${path}`);
                        return;
                    }
                    if (name === 'mkdir') {
                        const target = resolveTerminalPath(terminal, arg || uniqueName(terminal.cwd || '/', 'folder'));
                        await api('POST', '/mkdir', { domain: config.domain, path: target });
                        clearCachedBranch(dirname(target));
                        await loadDirectory(dirname(target));
                        terminalWrite(terminal, `Carpeta creada: ${target}`);
                        return;
                    }
                    if (name === 'touch') {
                        const target = resolveTerminalPath(terminal, arg || uniqueName(terminal.cwd || '/', 'file'));
                        await api('POST', '/write', { domain: config.domain, path: target, content: '' });
                        clearCachedBranch(dirname(target));
                        await loadDirectory(dirname(target));
                        terminalWrite(terminal, `Archivo creado: ${target}`);
                        return;
                    }
                    if (name === 'extract') {
                        const path = resolveTerminalPath(terminal, arg);
                        await ensureDirectory(dirname(path));
                        const entry = getEntry(path) || { path, name: basename(path), is_dir: false };
                        await extractArchive(entry);
                        terminalWrite(terminal, `Descomprimido: ${path}`);
                        return;
                    }
                    terminalWrite(terminal, `Comando no reconocido: ${name}. Escribe help.`, 'error');
                } catch (error) {
                    terminalWrite(terminal, error.message, 'error');
                    log(`Terminal: ${error.message}`);
                }
            };

            const applyStoredLayout = () => {
                $('#xpanel_left_pane').classList.toggle('ikode_hidden', !uiState.layout.left);
                $('#xpanel_right_pane').classList.toggle('ikode_hidden', !uiState.layout.right);
                $('#xpanel_bottom_pane').classList.toggle('ikode_hidden', !uiState.layout.bottom);
                $('#xpanel_code_pane').classList.toggle('border-b', uiState.layout.bottom);
                syncLayoutButtons();
                switchLeftMode(uiState.ui.leftMode);
                switchOutlineTab(uiState.ui.outlineTab);
                switchConsoleTab(uiState.ui.consoleTab);
                switchRightTab(uiState.ui.rightTab);
                renderTerminalList();
                switchTerminalSession(state.activeTerminalId);
            };

            window.XPanelFM = {
                dragOver(event) {
                    event.preventDefault();
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.add('dragover');
                },
                dragLeave() {
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.remove('dragover');
                },
                async drop(event) {
                    event.preventDefault();
                    ($('#xpanel_drop_hint') || $('#xpanel_file_list')).classList.remove('dragover');
                    const target = requireConcreteSiteTarget();
                    const dragged = draggedEntryFromEvent(event);
                    if (dragged) {
                        await moveEntry(dragged, target);
                        state.draggedEntry = null;
                        return;
                    }
                    await upload(Array.from(event.dataTransfer.files || []), target);
                },
            };

            $$('[data-fm-action]').forEach((button) => button.addEventListener('click', () => {
                action(button.dataset.fmAction, Boolean(button.closest('#xpanel_ctx_menu')));
            }));
            $$('[data-left-mode]').forEach((button) => button.addEventListener('click', () => switchLeftMode(button.dataset.leftMode)));
            $$('[data-layout-toggle]').forEach((button) => button.addEventListener('click', () => toggleLayoutPane(button.dataset.layoutToggle)));
            $$('[data-layout-action="fullscreen"]').forEach((button) => button.addEventListener('click', () => toggleFullscreen(button)));
            $$('[data-outline-tab]').forEach((button) => button.addEventListener('click', () => switchOutlineTab(button.dataset.outlineTab)));
            $$('[data-console-tab]').forEach((button) => button.addEventListener('click', () => switchConsoleTab(button.dataset.consoleTab)));
            $$('[data-right-tab]').forEach((button) => button.addEventListener('click', () => switchRightTab(button.dataset.rightTab)));
            $$('[data-terminal-action]').forEach((button) => button.addEventListener('click', () => terminalAction(button.dataset.terminalAction)));
            $$('[data-clone-action]').forEach((button) => button.addEventListener('click', () => cloneAction(button.dataset.cloneAction)));
            $$('[data-duplicate-close]').forEach((button) => button.addEventListener('click', closeDuplicatePane));
            $('#xpanel_terminal_list')?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-terminal-id]');
                if (button) switchTerminalSession(button.dataset.terminalId);
            });
            $('#xpanel_terminal_form')?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const input = $('#xpanel_terminal_input');
                const command = input.value;
                input.value = '';
                await executeTerminalCommand(command);
            });
            $('#xpanel_terminal_input')?.addEventListener('keydown', (event) => {
                const terminal = activeTerminal();
                if (!terminal) return;
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    terminal.commandIndex = Math.max(0, terminal.commandIndex - 1);
                    event.target.value = terminal.commandHistory[terminal.commandIndex] || '';
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    terminal.commandIndex = Math.min(terminal.commandHistory.length, terminal.commandIndex + 1);
                    event.target.value = terminal.commandHistory[terminal.commandIndex] || '';
                }
            });
            hydrateSettings();
            bindSettings();

            $('#xpanel_site_jump')?.addEventListener('change', (event) => {
                window.location.href = event.target.value;
            });
            $('#xpanel_quick_focus')?.addEventListener('click', openSearchLauncher);
            $('#xpanel_file_filter').addEventListener('input', queueSearch);
            $('#xpanel_file_filter').addEventListener('focus', renderSearchResults);
            $('#xpanel_search_results')?.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-search-index]');
                if (!button) return;
                try {
                    await openSearchResult(Number(button.dataset.searchIndex));
                } catch (error) {
                    toast(error.message, 'error');
                }
            });
            $$('[data-search-toggle]').forEach((button) => {
                const key = button.dataset.searchToggle;
                const active = key === 'content' ? uiState.search.includeContent : uiState.search.caseSensitive;
                button.classList.toggle('active', active);
                button.addEventListener('click', () => {
                    if (key === 'content') uiState.search.includeContent = !uiState.search.includeContent;
                    if (key === 'case') uiState.search.caseSensitive = !uiState.search.caseSensitive;
                    button.classList.toggle('active', key === 'content' ? uiState.search.includeContent : uiState.search.caseSensitive);
                    persistUiState();
                    queueSearch();
                });
            });
            $('[data-search-action="clear"]')?.addEventListener('click', () => {
                $('#xpanel_file_filter').value = '';
                state.searchResults = [];
                syncSearchButton();
                renderList();
                renderSearchResults();
                $('#xpanel_file_filter')?.focus();
            });
            $('#xpanel_upload_input').addEventListener('change', (event) => upload(Array.from(event.target.files || [])));
            $('#xpanel_left_files_pane').addEventListener('contextmenu', (event) => context(event));
            $('[data-input-cancel]').addEventListener('click', closeInput);
            $('[data-input-confirm]').addEventListener('click', async () => {
                const value = $('#xpanel_input_value').value.trim();
                const callback = state.inputCallback;
                closeInput();
                if (value && callback) {
                    try { await callback(value); } catch (error) { toast(error.message, 'error'); }
                }
            });
            document.addEventListener('click', (event) => {
                if (!event.target.closest('#xpanel_ctx_menu')) $('#xpanel_ctx_menu').classList.add('hidden');
                if (!event.target.closest('.xpanel-search-wrap')) closeSearchLauncher();
            });
            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    save();
                }
                if (event.key === 'Escape') {
                    closeSearchLauncher();
                }
            });

            require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs' } });
            require(['vs/editor/editor.main'], () => {
                state.editor = monaco.editor.create($('#xpanel_monaco_editor'), {
                    value: '',
                    language: 'plaintext',
                    theme: resolveMonacoTheme(),
                    ...editorOptions(),
                });
                $('#xpanel_editor_groups').classList.add('ikode_hidden');
                $('#xpanel_editor_group_clone').classList.add('ikode_hidden');
                new MutationObserver(() => {
                    monaco.editor.setTheme(resolveMonacoTheme());
                }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                applyEditorSettings();
                applyStoredLayout();
                loadDirectory('/');
            });

            applyStoredLayout();
            window.addEventListener('resize', rebuildLayout);
        })();
    </script>
@endpush
