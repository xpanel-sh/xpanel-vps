@push('styles')
<style>
.xmail-app {
    display: grid;
    grid-template-columns: 180px minmax(270px, 350px) minmax(0, 1fr) 30px;
    gap: 6px;
    height: 100dvh;
    padding: 10px;
    background: #f4f4f5;
    color: #18181b;
    font-size: 12px;
    line-height: 1.3;
}
.xmail-app.is-sidebar-collapsed {
    grid-template-columns: 48px minmax(270px, 350px) minmax(0, 1fr) 30px;
}
.xmail-app.is-sidebar-collapsed .xmail-sidebar { padding-left: 2px; padding-right: 2px; align-items: center; }
.xmail-app.is-sidebar-collapsed .xmail-sidebar-text,
.xmail-app.is-sidebar-collapsed .xmail-sidebar-count,
.xmail-app.is-sidebar-collapsed .xmail-sidebar-section { display: none !important; }
.xmail-app.is-sidebar-collapsed .xmail-mail-section { display: block !important; width: 100%; padding-inline: 0 !important; text-align: center; }
.xmail-app.is-sidebar-collapsed .xmail-sidebar > .flex:first-child { justify-content: center; padding-bottom: 20px !important; }
.xmail-app.is-sidebar-collapsed .xmail-sidebar > .flex:first-child .size-11 { width: 36px !important; height: 36px !important; }
.xmail-app.is-sidebar-collapsed .xmail-compose { width: 42px; height: 42px; padding: 0 !important; box-shadow: none; }
.xmail-app.is-sidebar-collapsed .xmail-folder { width: 40px; height: 40px; justify-content: center; padding: 0 !important; border-radius: 10px; }
.xmail-app.is-sidebar-collapsed .xmail-folder i { font-size: 16px !important; }
.xmail-app.is-sidebar-collapsed .xmail-direct-stack,
.xmail-app.is-sidebar-collapsed .xmail-bottom-stack { align-items: center; width: 100%; padding-left: 0 !important; padding-right: 0 !important; }
.xmail-app.is-sidebar-collapsed .xmail-direct-person { justify-content: center; }
.xmail-app.is-sidebar-collapsed .xmail-sidebar-bottom { width: 100%; }
.xmail-panel { min-height: 0; border: 1px solid #dedfe3; background: #ffffff; box-shadow: 0 1px 2px rgba(15,23,42,0.05); }
.xmail-sidebar-profile { gap: 10px !important; padding: 6px 8px 16px !important; }
.xmail-sidebar { min-height: 0; background: #f4f4f5; padding: 2px 6px 0 6px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #d4d4d8 transparent; }
.xmail-app .text-xs { font-size: 10px !important; line-height: 1.2 !important; }
.xmail-app .text-sm { font-size: 11.5px !important; line-height: 1.3 !important; }
.xmail-app .text-base { font-size: 12.5px !important; line-height: 1.3 !important; }
.xmail-app .text-lg { font-size: 13.5px !important; line-height: 1.3 !important; }
.xmail-app .text-xl { font-size: 15px !important; line-height: 1.3 !important; }
.xmail-app .text-2xl { font-size: 18px !important; line-height: 1.25 !important; }
.xmail-folder { height: 32px; border-radius: 9px; color: #27272a; gap: 8px !important; padding-left: 10px !important; padding-right: 10px !important; }
.xmail-folder.is-active { background: #ffffff; color: #4f7cff; box-shadow: 0 1px 1px rgba(15,23,42,0.03); }
.xmail-compose { height: 36px; border-radius: 8px; background: #4f7cff; color: #ffffff; box-shadow: 0 8px 16px rgba(79,124,255,0.18); font-size: 13px !important; }
.xmail-list-panel, .xmail-reader { border-radius: 14px; overflow: hidden; }
.xmail-message { border-radius: 9px; cursor: pointer; gap: 10px !important; padding: 9px 10px !important; }
.xmail-message.is-active { background: #f0f0f2; box-shadow: inset 0 0 0 1px rgba(79,124,255,0.08); }
.xmail-message:hover { background: #f7f7f8; }
.xmail-message.is-active:hover { background: #f0f0f2; }
.xmail-message.is-unread .xmail-msg-from { font-weight: 700; }
.xmail-search { height: 38px; border-radius: 8px; border: 1px solid #dfe3ea; background: #ffffff; }
.xmail-category-wrap { position: relative; }
.xmail-category-menu { position: absolute; top: calc(100% + 6px); right: 0; z-index: 8; display: none; width: 150px; border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; padding: 6px; box-shadow: 0 12px 28px rgba(15,23,42,0.12); }
.xmail-category-menu.is-open { display: block; }
.xmail-category-option { display: flex; width: 100%; align-items: center; gap: 8px; border-radius: 8px; padding: 7px 8px; color: #52525b; text-align: left; }
.xmail-category-option:hover, .xmail-category-option.is-active { background: #f4f4f5; color: #18181b; }
.xmail-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid transparent; color: #6b7280; font-size: 13px; }
.xmail-icon-btn:hover { border-color: #e5e7eb; background: #f8fafc; color: #111827; }
.xmail-app.is-sidebar-collapsed #xmail_sidebar_toggle i { transform: rotate(180deg); }
.xmail-rail-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e5e7eb; background: #ffffff; color: #6b7280; box-shadow: 0 1px 5px rgba(15,23,42,0.08); font-size: 13px; }
.xmail-rail-btn i { font-size: 15px !important; }
.xmail-rail-btn.is-active { color: #4f7cff; border-color: rgba(79,124,255,0.28); background: #ffffff; }
.xmail-attachment { height: 36px; border-radius: 9px; border: 1px solid #e5e7eb; background: #ffffff; gap: 8px !important; padding-left: 10px !important; padding-right: 8px !important; }
.xmail-sidebar .size-11, .xmail-message .size-11 { width: 32px !important; height: 32px !important; }
.xmail-sidebar .size-8 { width: 26px !important; height: 26px !important; }
.xmail-reader .size-10 { width: 32px !important; height: 32px !important; }
.xmail-reader .size-9 { width: 30px !important; height: 30px !important; }
.xmail-reader .size-8 { width: 28px !important; height: 28px !important; }
.xmail-reader .size-5 { width: 18px !important; height: 18px !important; }
.xmail-app .kt-btn { min-height: 30px; padding-inline: 10px; font-size: 12px; border-radius: 8px; }
.xmail-app .kt-btn-sm { min-height: 28px; padding-inline: 8px; font-size: 11.5px; }
.xmail-reader-toolbar { height: 50px !important; padding-left: 14px !important; padding-right: 14px !important; gap: 8px !important; }
.xmail-reader-actions { min-width: 0; }
.xmail-message-card { padding: 24px 28px !important; }
.xmail-message-card p { max-width: 900px; }
.xmail-compose-box { display: none; border: 1px solid #e1e4ea; border-radius: 12px; background: #ffffff; box-shadow: 0 1px 2px rgba(15,23,42,0.04); overflow: hidden; }
.xmail-compose-box.is-open { display: block; }
.xmail-app.is-compose-open .xmail-reader-footer { display: none !important; }
.xmail-compose-row { min-height: 40px; display: flex; align-items: center; gap: 10px; padding: 7px 12px; border-bottom: 1px solid #e5e7eb; }
.xmail-recipient-chip { min-height: 28px; display: inline-flex; align-items: center; gap: 7px; border: 1px solid #e1e4ea; border-radius: 999px; padding: 0 10px 0 8px; background: #ffffff; }
.xmail-compose-area { min-height: 120px; width: 100%; resize: vertical; border: 0; outline: 0; padding: 12px; color: #18181b; font-size: 12.5px; line-height: 1.45; }
.xmail-compose-tools { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding: 8px 12px; border-top: 1px solid #eef0f4; }
.xmail-reader > .grow { scrollbar-width: thin; scrollbar-color: #d4d4d8 transparent; }
.xmail-thread-header { padding: 15px 18px !important; }
.xmail-reader-scroll { min-height: 0; }
.xmail-reader-footer { padding: 8px 18px !important; }
.xmail-sidebar .mt-7 { margin-top: 15px !important; }
.xmail-sidebar .mt-3 { margin-top: 10px !important; }
.xmail-sidebar .space-y-3 > :not([hidden]) ~ :not([hidden]) { margin-top: 8px !important; }
.xmail-list-panel > .flex:first-child { gap: 8px !important; padding: 10px !important; }
#xmail_message_list { padding-left: 12px !important; padding-right: 12px !important; padding-bottom: 12px !important; }
#xmail_message_list .space-y-2 > :not([hidden]) ~ :not([hidden]) { margin-top: 6px !important; }
.xmail-reader > .grow > .border-b { padding: 15px 18px !important; }
.xmail-reader h1 { font-weight: 500; letter-spacing: 0; }
.xmail-reader article { padding: 15px 18px !important; }
.xmail-reader article > .flex { gap: 14px !important; }
.xmail-reader .mt-8 { margin-top: 16px !important; }
.xmail-reader .mt-7 { margin-top: 14px !important; }
.xmail-reader .mt-9 { margin-top: 18px !important; }
.xmail-reader > .border-t { padding: 8px 18px !important; }
.xmail-rail { gap: 10px !important; padding-top: 2px !important; }
.xmail-reader-iframe { width: 100%; border: 0; min-height: 180px; display: block; }
.xmail-account-select { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; font-size: 12px; padding: 4px 8px; color: #18181b; cursor: pointer; max-width: 100%; }
.xmail-skeleton { background: linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 50%,#e5e7eb 75%); background-size: 200% 100%; animation: xmail-shimmer 1.2s ease-in-out infinite; border-radius: 6px; }
@keyframes xmail-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.xmail-toast { position: fixed; bottom: 18px; right: 18px; z-index: 999; display: flex; align-items: center; gap: 10px; background: #18181b; color: #fff; border-radius: 10px; padding: 10px 16px; font-size: 12.5px; box-shadow: 0 8px 24px rgba(15,23,42,0.18); opacity: 0; transform: translateY(8px); transition: opacity .2s, transform .2s; pointer-events: none; }
.xmail-toast.is-visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
.xmail-toast.is-error { background: #dc2626; }
.xmail-compose-new { position: fixed; bottom: 24px; right: 24px; z-index: 50; width: 520px; max-width: calc(100vw - 40px); border: 1px solid #e1e4ea; border-radius: 14px; background: #fff; box-shadow: 0 16px 48px rgba(15,23,42,0.16); display: none; flex-direction: column; }
.xmail-compose-new.is-open { display: flex; }
.xmail-compose-new-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 12.5px; font-weight: 600; color: #18181b; }
.xmail-to-tags { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; min-height: 32px; }
.xmail-tag { display: inline-flex; align-items: center; gap: 5px; background: #f0f0f2; border-radius: 999px; padding: 2px 8px 2px 8px; font-size: 11px; }
.xmail-tag button { color: #6b7280; }
.xmail-tag button:hover { color: #18181b; }
@media (max-width: 1280px) {
    .xmail-app { grid-template-columns: 170px minmax(260px, 340px) minmax(0, 1fr); }
    .xmail-app.is-sidebar-collapsed { grid-template-columns: 48px minmax(260px, 340px) minmax(0, 1fr); }
    .xmail-rail { display: none; }
}
@media (max-width: 1024px) {
    .xmail-app { grid-template-columns: 50px minmax(0, 1fr); overflow: auto; }
    .xmail-app.is-sidebar-collapsed { grid-template-columns: 50px minmax(0, 1fr); }
    .xmail-app .xmail-sidebar { padding-left: 2px; padding-right: 2px; align-items: center; }
    .xmail-app .xmail-sidebar-text, .xmail-app .xmail-sidebar-count, .xmail-app .xmail-sidebar-section { display: none !important; }
    .xmail-app .xmail-mail-section { display: block !important; width: 100%; padding-inline: 0 !important; text-align: center; }
    .xmail-app .xmail-compose { width: 42px; height: 42px; padding: 0 !important; box-shadow: none; }
    .xmail-app .xmail-folder { width: 40px; height: 40px; justify-content: center; padding: 0 !important; }
    .xmail-app .xmail-direct-person { justify-content: center; }
    .xmail-reader { position: fixed; z-index: 25; inset: 10px; display: flex; transform: translateX(110%); transition: transform 180ms ease; box-shadow: 0 16px 48px rgba(15,23,42,0.18); }
    .xmail-app.is-reader-open .xmail-reader { transform: translateX(0); }
}
@media (max-width: 760px) {
    body { overflow: auto; }
    .xmail-app { display: grid; grid-template-columns: minmax(0, 1fr); height: 100dvh; min-height: 0; overflow: hidden; }
    .xmail-sidebar { position: fixed; z-index: 30; inset: 12px auto 12px 12px; width: 280px; border-radius: 14px; border: 1px solid #dedfe3; box-shadow: 0 16px 48px rgba(15,23,42,0.18); transform: translateX(-110%); transition: transform 180ms ease; }
    .xmail-app.is-mobile-sidebar-open .xmail-sidebar { transform: translateX(0); }
    .xmail-app.is-sidebar-collapsed .xmail-sidebar { align-items: stretch; width: 280px; }
    .xmail-app .xmail-sidebar { align-items: stretch; }
    .xmail-app .xmail-sidebar-text, .xmail-app .xmail-sidebar-count, .xmail-app .xmail-sidebar-section,
    .xmail-app.is-sidebar-collapsed .xmail-sidebar-text, .xmail-app.is-sidebar-collapsed .xmail-sidebar-count, .xmail-app.is-sidebar-collapsed .xmail-sidebar-section { display: block !important; }
    .xmail-app .xmail-folder, .xmail-app.is-sidebar-collapsed .xmail-folder { width: 100%; justify-content: flex-start; padding-left: 11px !important; padding-right: 11px !important; }
    .xmail-app .xmail-compose, .xmail-app.is-sidebar-collapsed .xmail-compose { width: 100%; padding: 0 16px !important; }
    .xmail-app .xmail-direct-person, .xmail-app.is-sidebar-collapsed .xmail-direct-person { justify-content: flex-start; }
    .xmail-list-panel { min-height: 0; }
    .xmail-reader { inset: 10px; }
}
</style>
@endpush

@php
    use Illuminate\Support\Facades\Auth;
    $user         = Auth::guard('web')->user();
    $displayName  = $user?->name ?: $tenant->name;
    $displayEmail = $primaryAccount?->email ?: $user?->email;
    $avatar       = asset('assets/media/avatars/300-1.png');
@endphp

<div class="xmail-app" id="xmail_app">

    {{-- ── Sidebar ─────────────────────────────────────────────────────────────── --}}
    <aside class="xmail-sidebar flex flex-col min-w-0" aria-label="Mail navigation">

        <div class="xmail-sidebar-profile flex items-center">
            <img class="size-11 rounded-full object-cover" src="{{ $avatar }}" alt="{{ $displayName }}">
            <div class="xmail-sidebar-text min-w-0">
                <div class="text-lg font-semibold text-mono truncate">{{ $displayName }}</div>
                @if(count($accounts) > 1)
                    <select class="xmail-account-select xmail-sidebar-text mt-1 w-full" id="xmail_account_select">
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->email }}" @selected($acct->email === $displayEmail)>{{ $acct->email }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="text-sm text-secondary-foreground truncate">{{ $displayEmail }}</div>
                @endif
            </div>
        </div>

        <button class="xmail-compose inline-flex items-center justify-center gap-2 px-4 text-base font-semibold"
                type="button" id="xmail_new_mail" aria-label="New mail">
            <i class="ki-filled ki-notepad-edit text-lg"></i>
            <span class="xmail-sidebar-text">New Mail</span>
        </button>

        <div class="xmail-sidebar-section xmail-mail-section mt-7 text-sm text-secondary-foreground px-2">Mail</div>
        <nav class="mt-2 space-y-1" id="xmail_folder_nav" aria-label="Mail folders">
            {{-- filled by JS --}}
            <div class="xmail-skeleton h-8 w-full mb-1"></div>
            <div class="xmail-skeleton h-8 w-full mb-1"></div>
            <div class="xmail-skeleton h-8 w-full"></div>
        </nav>

        <div class="xmail-sidebar-bottom xmail-bottom-stack mt-auto space-y-1 pb-2">
            <a class="xmail-folder flex w-full items-center gap-3 px-4" href="{{ route('client.mail.index') }}" title="Cuentas de correo">
                <i class="ki-filled ki-setting-2 text-lg"></i>
                <span class="xmail-sidebar-text grow font-medium">Email Accounts</span>
            </a>
            <a class="xmail-folder flex w-full items-center gap-3 px-4" href="{{ route('client.dashboard') }}" title="Dashboard">
                <i class="ki-filled ki-home-3 text-lg"></i>
                <span class="xmail-sidebar-text grow font-medium">Dashboard</span>
            </a>
        </div>
    </aside>

    {{-- ── Message list ────────────────────────────────────────────────────────── --}}
    <section class="xmail-panel xmail-list-panel flex flex-col min-w-0" aria-label="Message list">
        <div class="xmail-list-toolbar flex items-center gap-3 p-4">
            <button class="xmail-icon-btn shrink-0" type="button" id="xmail_sidebar_toggle" title="Toggle sidebar">
                <i class="ki-filled ki-black-left"></i>
            </button>
            <label class="xmail-search flex grow items-center gap-3 px-4" aria-label="Search messages">
                <i class="ki-filled ki-magnifier text-xl text-secondary-foreground"></i>
                <input class="w-full border-0 bg-transparent text-base outline-none placeholder:text-secondary-foreground"
                       id="xmail_filter" type="search" placeholder="Search...">
            </label>
            <div class="xmail-category-wrap shrink-0">
                <button class="kt-btn kt-btn-ghost gap-2" type="button" id="xmail_category_toggle"
                        aria-haspopup="menu" aria-expanded="false">
                    <span id="xmail_category_label">All</span>
                    <i class="ki-filled ki-down text-xs"></i>
                </button>
                <div class="xmail-category-menu" id="xmail_category_menu" role="menu">
                    <button class="xmail-category-option is-active" type="button" data-category="all" role="menuitem">
                        <i class="ki-filled ki-category text-sm"></i> All mail
                    </button>
                    <button class="xmail-category-option" type="button" data-category="unread" role="menuitem">
                        <i class="ki-filled ki-notification-status text-sm"></i> Unread
                    </button>
                    <button class="xmail-category-option" type="button" data-category="flagged" role="menuitem">
                        <i class="ki-filled ki-star text-sm"></i> Starred
                    </button>
                </div>
            </div>
            <button class="xmail-icon-btn shrink-0" type="button" id="xmail_refresh" title="Refresh">
                <i class="ki-filled ki-arrows-circle"></i>
            </button>
        </div>

        <div class="grow overflow-y-auto px-4 pb-4" id="xmail_message_list">
            {{-- filled by JS --}}
            <div class="space-y-2" id="xmail_msg_inner">
                <div class="xmail-skeleton h-14 w-full mb-2"></div>
                <div class="xmail-skeleton h-14 w-full mb-2"></div>
                <div class="xmail-skeleton h-14 w-full"></div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-border px-4 py-2" id="xmail_pagination">
            <button class="kt-btn kt-btn-ghost kt-btn-sm gap-1" type="button" id="xmail_prev_page" disabled>
                <i class="ki-filled ki-left text-xs"></i> Prev
            </button>
            <span class="text-sm text-secondary-foreground" id="xmail_page_info"></span>
            <button class="kt-btn kt-btn-ghost kt-btn-sm gap-1" type="button" id="xmail_next_page" disabled>
                Next <i class="ki-filled ki-right text-xs"></i>
            </button>
        </div>
    </section>

    {{-- ── Reader ──────────────────────────────────────────────────────────────── --}}
    <main class="xmail-panel xmail-reader flex flex-col min-w-0" aria-label="Message detail">
        <div class="xmail-reader-toolbar flex items-center justify-between border-b border-border">
            <div class="flex items-center gap-2">
                <button class="xmail-icon-btn" type="button" id="xmail_reader_back" title="Back">
                    <i class="ki-filled ki-left"></i>
                </button>
                <span class="ms-1 rounded-lg bg-muted px-3 py-1 text-sm font-semibold text-mono" id="xmail_reader_folder_label">—</span>
            </div>
            <div class="xmail-reader-actions flex items-center gap-1">
                <button class="kt-btn kt-btn-light gap-2" type="button" id="xmail_btn_reply_all">
                    <i class="ki-filled ki-left"></i> Reply all
                </button>
                <button class="xmail-icon-btn" id="xmail_btn_flag" type="button" title="Star"><i class="ki-filled ki-star"></i></button>
                <button class="xmail-icon-btn" id="xmail_btn_move_trash" type="button" title="Move to Trash"><i class="ki-filled ki-arrows-circle"></i></button>
                <button class="xmail-icon-btn bg-red-50 text-red-500" id="xmail_btn_delete" type="button" title="Delete permanently"><i class="ki-filled ki-trash"></i></button>
            </div>
        </div>

        <div class="xmail-reader-scroll grow overflow-y-auto" id="xmail_reader_scroll">
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center h-full gap-4 py-20 text-secondary-foreground" id="xmail_reader_empty">
                <i class="ki-filled ki-sms text-5xl opacity-30"></i>
                <p class="text-sm">Select a message to read</p>
            </div>

            {{-- Message view (hidden until a message is loaded) --}}
            <div class="hidden" id="xmail_reader_content">
                <div class="xmail-thread-header border-b border-border">
                    <h1 class="text-2xl font-medium text-mono" id="xmail_reader_subject"></h1>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-white px-3 py-1.5 text-sm font-medium">
                            <span class="flex size-5 items-center justify-center rounded-full bg-blue-100 text-blue-600 font-semibold text-xs" id="xmail_reader_avatar_char">?</span>
                            <span id="xmail_reader_from_full"></span>
                        </span>
                    </div>
                    <div id="xmail_reader_attachments_wrap" class="hidden mt-4">
                        <h2 class="text-base font-semibold text-mono">Attachments</h2>
                        <div class="mt-2 grid gap-2 xl:grid-cols-2" id="xmail_reader_attachments"></div>
                    </div>
                </div>

                <article class="px-6 py-5">
                    <div class="flex items-start gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-border bg-white font-semibold text-blue-600" id="xmail_article_avatar">?</span>
                        <div class="min-w-0 grow">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-mono" id="xmail_article_from"></span>
                                    </div>
                                    <div class="mt-1 text-sm text-secondary-foreground" id="xmail_article_to_line"></div>
                                </div>
                                <span class="text-sm font-medium text-mono shrink-0" id="xmail_article_date"></span>
                            </div>

                            <div class="xmail-message-card mt-6 rounded-xl bg-muted">
                                <iframe id="xmail_reader_iframe" class="xmail-reader-iframe" sandbox="allow-same-origin" title="Message body"></iframe>
                            </div>

                            {{-- Inline reply / forward compose --}}
                            <div class="xmail-compose-box mt-6" id="xmail_compose_box">
                                <div class="xmail-compose-row">
                                    <span class="text-base font-semibold text-secondary-foreground">To:</span>
                                    <div class="xmail-to-tags grow" id="xmail_compose_to_tags"></div>
                                    <input class="border-0 bg-transparent text-base outline-none flex-1 min-w-24"
                                           id="xmail_compose_to_input" type="email" placeholder="Add recipient...">
                                    <div class="ms-auto flex items-center gap-3 text-base font-semibold text-secondary-foreground">
                                        <button class="hover:text-mono" type="button" id="xmail_compose_toggle_cc">Cc</button>
                                        <button class="hover:text-mono" type="button" id="xmail_compose_close" title="Close">
                                            <i class="ki-filled ki-cross text-xl"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="xmail-compose-row hidden" id="xmail_compose_cc_row">
                                    <span class="text-base font-semibold text-secondary-foreground">Cc:</span>
                                    <input class="border-0 bg-transparent text-base outline-none grow"
                                           id="xmail_compose_cc_input" type="email" placeholder="Add cc...">
                                </div>
                                <div class="xmail-compose-row">
                                    <span class="text-base font-semibold text-secondary-foreground">Subject:</span>
                                    <input class="w-full border-0 bg-transparent text-base outline-none" id="xmail_compose_subject" type="text" placeholder="Subject">
                                </div>
                                <textarea class="xmail-compose-area" id="xmail_compose_body" placeholder="Type your message..."></textarea>
                                <div class="xmail-compose-tools">
                                    <button class="kt-btn kt-btn-primary gap-2" type="button" id="xmail_compose_send">
                                        <i class="ki-filled ki-send"></i> Send
                                    </button>
                                    <span class="text-sm text-secondary-foreground ms-2" id="xmail_compose_status"></span>
                                    <button class="kt-btn kt-btn-light ms-auto" type="button" id="xmail_compose_close2">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <div class="xmail-reader-footer border-t border-border" id="xmail_reader_footer" style="display:none">
            <div class="flex flex-wrap items-center gap-2">
                <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="reply">
                    <i class="ki-filled ki-left"></i> Reply <span class="rounded border border-border px-2 text-xs">r</span>
                </button>
                <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="reply-all">
                    <i class="ki-filled ki-double-left"></i> Reply All
                </button>
                <button class="kt-btn kt-btn-light kt-btn-sm gap-2" type="button" data-compose-action="forward">
                    <i class="ki-filled ki-right"></i> Forward
                </button>
            </div>
        </div>
    </main>

    {{-- ── Right rail ──────────────────────────────────────────────────────────── --}}
    <aside class="xmail-rail flex flex-col items-center gap-5 pt-3">
        <button class="xmail-rail-btn is-active" type="button" title="Account"><i class="ki-filled ki-profile-circle text-xl"></i></button>
        <button class="xmail-rail-btn" type="button" title="Settings"><i class="ki-filled ki-setting-2 text-xl"></i></button>
        <button class="xmail-rail-btn" type="button" title="Contacts"><i class="ki-filled ki-people text-xl"></i></button>
    </aside>
</div>

{{-- ── New mail compose window ─────────────────────────────────────────────────── --}}
<div class="xmail-compose-new" id="xmail_new_compose">
    <div class="xmail-compose-new-header">
        <span>New Message</span>
        <button class="xmail-icon-btn" type="button" id="xmail_new_compose_close"><i class="ki-filled ki-cross"></i></button>
    </div>
    <div class="xmail-compose-row">
        <span class="text-base font-semibold text-secondary-foreground shrink-0">To:</span>
        <div class="xmail-to-tags grow" id="xmail_new_to_tags"></div>
        <input class="border-0 bg-transparent text-base outline-none flex-1 min-w-24"
               id="xmail_new_to_input" type="email" placeholder="Recipient email">
    </div>
    <div class="xmail-compose-row">
        <span class="text-base font-semibold text-secondary-foreground shrink-0">Subject:</span>
        <input class="w-full border-0 bg-transparent text-base outline-none" id="xmail_new_subject" type="text" placeholder="Subject">
    </div>
    <textarea class="xmail-compose-area" id="xmail_new_body" placeholder="Type your message..." style="min-height:150px"></textarea>
    <div class="xmail-compose-tools">
        <button class="kt-btn kt-btn-primary gap-2" type="button" id="xmail_new_send">
            <i class="ki-filled ki-send"></i> Send
        </button>
        <span class="text-sm text-secondary-foreground ms-2" id="xmail_new_status"></span>
        <button class="kt-btn kt-btn-light ms-auto" type="button" id="xmail_new_compose_close2">Discard</button>
    </div>
</div>

{{-- ── Toast ────────────────────────────────────────────────────────────────────── --}}
<div class="xmail-toast" id="xmail_toast"></div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Config ──────────────────────────────────────────────────────────────────
    const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
    const BASE   = {
        folders  : '{{ route("client.mail.api.folders") }}',
        messages : '{{ route("client.mail.api.messages") }}',
        message  : '{{ route("client.mail.api.message") }}',
        flag     : '{{ route("client.mail.api.flag") }}',
        move     : '{{ route("client.mail.api.move") }}',
        delete   : '{{ route("client.mail.api.delete") }}',
        send     : '{{ route("client.mail.api.send") }}',
        folderCreate : '{{ route("client.mail.api.folder.create") }}',
        folderDelete : '{{ route("client.mail.api.folder.delete") }}',
    };

    // ── State ───────────────────────────────────────────────────────────────────
    const state = {
        account  : '{{ $displayEmail }}',
        folder   : 'INBOX',
        page     : 1,
        perPage  : 25,
        total    : 0,
        message  : null, // {uid, folder, from, fromEmail, subject, date, inReplyTo, references}
        category : 'all',
        folders  : [],
    };

    // ── DOM refs ────────────────────────────────────────────────────────────────
    const mailApp         = document.getElementById('xmail_app');
    const folderNav       = document.getElementById('xmail_folder_nav');
    const msgInner        = document.getElementById('xmail_msg_inner');
    const filterInput     = document.getElementById('xmail_filter');
    const pageInfo        = document.getElementById('xmail_page_info');
    const prevBtn         = document.getElementById('xmail_prev_page');
    const nextBtn         = document.getElementById('xmail_next_page');
    const readerEmpty     = document.getElementById('xmail_reader_empty');
    const readerContent   = document.getElementById('xmail_reader_content');
    const readerFooter    = document.getElementById('xmail_reader_footer');
    const readerFolderLbl = document.getElementById('xmail_reader_folder_label');
    const iframe          = document.getElementById('xmail_reader_iframe');
    const toastEl         = document.getElementById('xmail_toast');
    const accountSelect   = document.getElementById('xmail_account_select');

    // ── Helpers ─────────────────────────────────────────────────────────────────
    function toast(msg, isError = false) {
        toastEl.textContent = msg;
        toastEl.classList.toggle('is-error', isError);
        toastEl.classList.add('is-visible');
        setTimeout(() => toastEl.classList.remove('is-visible'), 3200);
    }

    async function api(url, opts = {}) {
        const res = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers || {}) },
            ...opts,
        });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            throw new Error(body.error || body.message || `HTTP ${res.status}`);
        }
        return res.json();
    }

    function get(url, params = {}) {
        const u = new URL(url, location.href);
        Object.entries(params).forEach(([k, v]) => v !== undefined && u.searchParams.set(k, v));
        return api(u.toString(), { method: 'GET' });
    }

    function post(url, data) {
        return api(url, { method: 'POST', body: JSON.stringify(data) });
    }

    function avatarChar(name) {
        return (name || '?').trim().charAt(0).toUpperCase();
    }

    function folderIcon(name) {
        const n = (name || '').toLowerCase();
        if (n === 'inbox')   return 'ki-sms';
        if (n === 'sent')    return 'ki-send';
        if (n.includes('draft'))  return 'ki-notepad';
        if (n.includes('trash'))  return 'ki-trash';
        if (n.includes('spam') || n.includes('junk')) return 'ki-information-2';
        if (n.includes('archive')) return 'ki-archive';
        return 'ki-folder';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        const now = new Date();
        const diff = (now - d) / 1000;
        if (diff < 86400 && d.getDate() === now.getDate()) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        if (diff < 7 * 86400) {
            return d.toLocaleDateString([], { weekday: 'short' });
        }
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    // ── Folder loading ───────────────────────────────────────────────────────────
    async function loadFolders() {
        folderNav.innerHTML = `<div class="xmail-skeleton h-8 w-full mb-1"></div><div class="xmail-skeleton h-8 w-full mb-1"></div><div class="xmail-skeleton h-8 w-full"></div>`;
        try {
            const data = await get(BASE.folders, { account: state.account });
            state.folders = data.folders || [];
            renderFolderNav();
        } catch (e) {
            folderNav.innerHTML = `<p class="text-sm text-red-500 px-2 py-2">${e.message}</p>`;
        }
    }

    function renderFolderNav() {
        folderNav.innerHTML = '';
        state.folders.forEach(f => {
            const btn = document.createElement('button');
            btn.className = `xmail-folder ${f.name === state.folder ? 'is-active' : ''} flex w-full items-center gap-3 px-4 text-start`;
            btn.type = 'button';
            btn.dataset.folder = f.name;
            btn.innerHTML = `
                <i class="ki-filled ${f.icon || folderIcon(f.name)} text-lg"></i>
                <span class="xmail-sidebar-text grow font-medium">${escapeHtml(f.name)}</span>
                ${f.unseen > 0 ? `<span class="xmail-sidebar-count text-sm text-secondary-foreground">${f.unseen}</span>` : ''}
            `;
            btn.addEventListener('click', () => selectFolder(f.name));
            folderNav.appendChild(btn);
        });
    }

    function selectFolder(name) {
        state.folder = name;
        state.page = 1;
        state.message = null;
        readerFolderLbl.textContent = name;
        document.querySelectorAll('#xmail_folder_nav .xmail-folder').forEach(b => {
            b.classList.toggle('is-active', b.dataset.folder === name);
        });
        showReaderEmpty();
        loadMessages();
    }

    // ── Message list ─────────────────────────────────────────────────────────────
    async function loadMessages() {
        msgInner.innerHTML = `<div class="xmail-skeleton h-14 w-full mb-2"></div><div class="xmail-skeleton h-14 w-full mb-2"></div><div class="xmail-skeleton h-14 w-full"></div>`;
        prevBtn.disabled = true; nextBtn.disabled = true; pageInfo.textContent = '';
        try {
            const data = await get(BASE.messages, {
                account  : state.account,
                folder   : state.folder,
                page     : state.page,
                per_page : state.perPage,
            });
            state.total = data.total || 0;
            renderMessages(data.messages || []);
            renderPagination();
        } catch (e) {
            msgInner.innerHTML = `<p class="text-sm text-red-500 py-4 text-center">${escapeHtml(e.message)}</p>`;
            toast(e.message, true);
        }
    }

    function renderMessages(messages) {
        if (!messages.length) {
            msgInner.innerHTML = '<p class="text-sm text-secondary-foreground py-8 text-center">No messages in this folder.</p>';
            return;
        }

        let filtered = filterMessages(messages);
        msgInner.innerHTML = '';
        const list = document.createElement('div');
        list.className = 'space-y-2';

        filtered.forEach(m => {
            const btn = document.createElement('button');
            btn.className = `xmail-message ${!m.seen ? 'is-unread' : ''} flex w-full items-center gap-4 px-3 py-4 text-start`;
            btn.type = 'button';
            btn.dataset.uid = m.uid;

            const ch = avatarChar(m.from || m.from_address || '?');
            btn.innerHTML = `
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white font-semibold text-blue-600">${escapeHtml(ch)}</span>
                <span class="min-w-0 grow">
                    <span class="flex items-center gap-2">
                        <span class="xmail-msg-from text-base truncate text-mono">${escapeHtml(m.from || m.from_address || '(no sender)')}</span>
                        ${!m.seen ? '<span class="size-2 shrink-0 rounded-full bg-blue-400"></span>' : ''}
                    </span>
                    <span class="mt-1 block truncate text-sm font-medium text-mono">${escapeHtml(m.subject || '(no subject)')}</span>
                    <span class="block truncate text-sm text-secondary-foreground">${escapeHtml(m.preview || '')}</span>
                </span>
                <span class="shrink-0 self-start text-xs text-mono">${formatDate(m.date)}</span>
            `;
            btn.addEventListener('click', () => openMessage(m));
            list.appendChild(btn);
        });

        msgInner.appendChild(list);
    }

    function filterMessages(messages) {
        if (state.category === 'unread') return messages.filter(m => !m.seen);
        if (state.category === 'flagged') return messages.filter(m => m.flagged);
        return messages;
    }

    function renderPagination() {
        const pages = Math.ceil(state.total / state.perPage) || 1;
        pageInfo.textContent = state.total > 0 ? `Page ${state.page} / ${pages} (${state.total})` : '';
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= pages;
    }

    // ── Message reader ───────────────────────────────────────────────────────────
    async function openMessage(summary) {
        showReaderLoading();
        try {
            const msg = await get(BASE.message, {
                account : state.account,
                folder  : state.folder,
                uid     : summary.uid,
            });
            state.message = { uid: summary.uid, folder: state.folder, ...msg };
            renderMessage(msg);

            // Mark unread badge gone in list
            const btn = msgInner.querySelector(`[data-uid="${summary.uid}"]`);
            btn?.classList.remove('is-unread');
            btn?.querySelector('.bg-blue-400')?.remove();

            // Refresh folder unseen count
            const folderData = state.folders.find(f => f.name === state.folder);
            if (folderData && !summary.seen) {
                folderData.unseen = Math.max(0, (folderData.unseen || 0) - 1);
                renderFolderNav();
            }
        } catch (e) {
            toast(e.message, true);
            showReaderEmpty();
        }

        if (mailApp && window.matchMedia('(max-width: 1024px)').matches) {
            mailApp.classList.add('is-reader-open');
        }
    }

    function renderMessage(msg) {
        readerEmpty.classList.add('hidden');
        readerContent.classList.remove('hidden');
        readerFooter.style.display = '';

        document.getElementById('xmail_reader_subject').textContent = msg.subject || '(no subject)';
        document.getElementById('xmail_reader_folder_label').textContent = state.folder;

        const fromName = msg.from || msg.from_address || '';
        const ch = avatarChar(fromName);
        document.getElementById('xmail_reader_avatar_char').textContent = ch;
        document.getElementById('xmail_article_avatar').textContent = ch;
        document.getElementById('xmail_reader_from_full').textContent = fromName + (msg.from_address && msg.from !== msg.from_address ? ` <${msg.from_address}>` : '');
        document.getElementById('xmail_article_from').textContent = fromName;
        document.getElementById('xmail_article_to_line').textContent = msg.to ? 'To: ' + (Array.isArray(msg.to) ? msg.to.join(', ') : msg.to) : '';
        document.getElementById('xmail_article_date').textContent = msg.date ? new Date(msg.date).toLocaleString() : '';

        // Render body in sandboxed iframe
        const body = msg.html || `<pre style="font-family:inherit;white-space:pre-wrap">${escapeHtml(msg.text || '')}</pre>`;
        const doc = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:system-ui,sans-serif;font-size:13px;line-height:1.6;color:#18181b;padding:20px 24px;margin:0}a{color:#4f7cff}img{max-width:100%}</style></head><body>${body}</body></html>`;
        iframe.srcdoc = doc;
        iframe.onload = () => {
            try { iframe.style.height = iframe.contentDocument.body.scrollHeight + 40 + 'px'; } catch {}
        };

        // Attachments
        const attsWrap = document.getElementById('xmail_reader_attachments_wrap');
        const attsDiv  = document.getElementById('xmail_reader_attachments');
        if (msg.attachments && msg.attachments.length) {
            attsWrap.classList.remove('hidden');
            attsDiv.innerHTML = msg.attachments.map(a => `
                <div class="xmail-attachment flex items-center gap-3 px-4">
                    <i class="ki-filled ki-folder text-xl text-pink-500"></i>
                    <span class="min-w-0 grow truncate text-sm font-medium text-mono">${escapeHtml(a.filename || 'attachment')}</span>
                    <span class="text-sm text-secondary-foreground">${formatBytes(a.size)}</span>
                </div>
            `).join('');
        } else {
            attsWrap.classList.add('hidden');
        }

        // Flag button state
        const flagBtn = document.getElementById('xmail_btn_flag');
        flagBtn.classList.toggle('text-yellow-400', !!msg.flagged);
        flagBtn.title = msg.flagged ? 'Unstar' : 'Star';

        closeCompose();
    }

    function showReaderEmpty() {
        readerEmpty.classList.remove('hidden');
        readerContent.classList.add('hidden');
        readerFooter.style.display = 'none';
        closeCompose();
    }

    function showReaderLoading() {
        readerEmpty.innerHTML = `<i class="ki-filled ki-arrows-circle text-4xl opacity-30 animate-spin"></i><p class="text-sm">Loading…</p>`;
        readerEmpty.classList.remove('hidden');
        readerContent.classList.add('hidden');
        readerFooter.style.display = 'none';
    }

    // ── Flag / Move / Delete ─────────────────────────────────────────────────────
    async function toggleFlag() {
        if (!state.message) return;
        const newVal = !state.message.flagged;
        try {
            await post(BASE.flag, { account: state.account, folder: state.message.folder, uid: state.message.uid, flag: 'flagged', set: newVal });
            state.message.flagged = newVal;
            const flagBtn = document.getElementById('xmail_btn_flag');
            flagBtn.classList.toggle('text-yellow-400', newVal);
            flagBtn.title = newVal ? 'Unstar' : 'Star';
            toast(newVal ? 'Starred' : 'Unstarred');
        } catch (e) { toast(e.message, true); }
    }

    async function moveToTrash() {
        if (!state.message) return;
        const trashFolder = state.folders.find(f => f.name.toLowerCase().includes('trash'))?.name || 'Trash';
        try {
            await post(BASE.move, { account: state.account, folder: state.message.folder, uid: state.message.uid, target_folder: trashFolder });
            toast('Moved to Trash');
            showReaderEmpty();
            loadMessages();
        } catch (e) { toast(e.message, true); }
    }

    async function deleteMessage() {
        if (!state.message) return;
        if (!confirm('Permanently delete this message?')) return;
        try {
            await post(BASE.delete, { account: state.account, folder: state.message.folder, uid: state.message.uid });
            toast('Deleted');
            showReaderEmpty();
            loadMessages();
        } catch (e) { toast(e.message, true); }
    }

    // ── Compose ──────────────────────────────────────────────────────────────────
    const recipientManagers = {};

    function makeRecipientManager(tagsEl, inputEl) {
        const recipients = [];
        function render() {
            tagsEl.innerHTML = recipients.map((r, i) => `
                <span class="xmail-tag">${escapeHtml(r)}<button type="button" data-idx="${i}" aria-label="Remove"><i class="ki-filled ki-cross text-xs"></i></button></span>
            `).join('');
            tagsEl.querySelectorAll('button[data-idx]').forEach(b => {
                b.addEventListener('click', () => { recipients.splice(+b.dataset.idx, 1); render(); });
            });
        }
        function add(email) {
            email = email.trim();
            if (email && !recipients.includes(email)) { recipients.push(email); render(); }
        }
        inputEl.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
                e.preventDefault();
                add(inputEl.value);
                inputEl.value = '';
            }
        });
        inputEl.addEventListener('blur', () => { if (inputEl.value.trim()) { add(inputEl.value); inputEl.value = ''; } });
        return { add, getAll: () => [...recipients], clear: () => { recipients.length = 0; render(); } };
    }

    const inlineRecipients = makeRecipientManager(
        document.getElementById('xmail_compose_to_tags'),
        document.getElementById('xmail_compose_to_input')
    );
    const newRecipients = makeRecipientManager(
        document.getElementById('xmail_new_to_tags'),
        document.getElementById('xmail_new_to_input')
    );

    function openCompose(mode = 'reply') {
        const composeBox = document.getElementById('xmail_compose_box');
        const subject    = document.getElementById('xmail_compose_subject');
        const body       = document.getElementById('xmail_compose_body');
        const msg        = state.message;

        inlineRecipients.clear();
        subject.value = '';
        body.value = '';
        document.getElementById('xmail_compose_status').textContent = '';

        if (msg) {
            const replyTo = msg.from_address || msg.from || '';
            const prefix = mode === 'forward' ? 'Fwd: ' : 'Re: ';
            subject.value = (msg.subject || '').startsWith(prefix) ? (msg.subject || '') : prefix + (msg.subject || '');

            if (mode !== 'forward') {
                if (mode === 'reply-all') {
                    (msg.to || []).forEach(a => a !== state.account && inlineRecipients.add(a));
                }
                if (replyTo && replyTo !== state.account) inlineRecipients.add(replyTo);
            }

            const quoted = msg.text
                ? `\n\n--- Original message from ${replyTo} ---\n${msg.text}`
                : '';
            if (mode === 'forward') body.value = quoted;
        }

        composeBox.classList.add('is-open');
        mailApp?.classList.add('is-compose-open');
        composeBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => body.focus(), 180);
    }

    function closeCompose() {
        document.getElementById('xmail_compose_box').classList.remove('is-open');
        mailApp?.classList.remove('is-compose-open');
    }

    async function sendInlineCompose() {
        const toList = inlineRecipients.getAll();
        const toInput = document.getElementById('xmail_compose_to_input').value.trim();
        if (toInput) toList.push(toInput);

        const subject = document.getElementById('xmail_compose_subject').value.trim();
        const text    = document.getElementById('xmail_compose_body').value.trim();
        const statusEl = document.getElementById('xmail_compose_status');

        if (!toList.length) { toast('Please add at least one recipient', true); return; }
        if (!subject) { toast('Subject is required', true); return; }

        statusEl.textContent = 'Sending…';
        const payload = {
            to: toList,
            cc: document.getElementById('xmail_compose_cc_input').value.trim()
                ? document.getElementById('xmail_compose_cc_input').value.split(',').map(s => s.trim()).filter(Boolean) : [],
            subject, text,
            in_reply_to: state.message?.message_id || '',
            references:  state.message?.references  || '',
        };

        try {
            await post(BASE.send, payload);
            toast('Message sent');
            statusEl.textContent = '';
            closeCompose();
        } catch (e) {
            statusEl.textContent = '';
            toast(e.message, true);
        }
    }

    async function sendNewCompose() {
        const toList = newRecipients.getAll();
        const toInput = document.getElementById('xmail_new_to_input').value.trim();
        if (toInput) toList.push(toInput);

        const subject = document.getElementById('xmail_new_subject').value.trim();
        const text    = document.getElementById('xmail_new_body').value.trim();
        const statusEl = document.getElementById('xmail_new_status');

        if (!toList.length) { toast('Please add at least one recipient', true); return; }
        if (!subject) { toast('Subject is required', true); return; }

        statusEl.textContent = 'Sending…';
        try {
            await post(BASE.send, { to: toList, subject, text, cc: [], bcc: [] });
            toast('Message sent');
            statusEl.textContent = '';
            document.getElementById('xmail_new_compose').classList.remove('is-open');
            newRecipients.clear();
            document.getElementById('xmail_new_subject').value = '';
            document.getElementById('xmail_new_body').value = '';
        } catch (e) {
            statusEl.textContent = '';
            toast(e.message, true);
        }
    }

    // ── Utility ──────────────────────────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function formatBytes(n) {
        if (!n) return '';
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n/1024).toFixed(1) + ' KB';
        return (n/1048576).toFixed(1) + ' MB';
    }

    // ── Event wiring ─────────────────────────────────────────────────────────────

    // Sidebar toggle
    document.getElementById('xmail_sidebar_toggle')?.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 760px)').matches) {
            mailApp.classList.toggle('is-mobile-sidebar-open');
        } else {
            mailApp.classList.toggle('is-sidebar-collapsed');
            localStorage.setItem('xmail-sidebar-collapsed', mailApp.classList.contains('is-sidebar-collapsed') ? '1' : '0');
        }
    });

    // Restore collapse state
    if (localStorage.getItem('xmail-sidebar-collapsed') === '1') mailApp.classList.add('is-sidebar-collapsed');

    // Close mobile sidebar on outside click
    document.addEventListener('click', e => {
        const sidebar = mailApp?.querySelector('.xmail-sidebar');
        if (mailApp?.classList.contains('is-mobile-sidebar-open') && sidebar && !sidebar.contains(e.target) && !document.getElementById('xmail_sidebar_toggle')?.contains(e.target)) {
            mailApp.classList.remove('is-mobile-sidebar-open');
        }
    });

    // Reader back on mobile
    document.getElementById('xmail_reader_back')?.addEventListener('click', () => {
        mailApp?.classList.remove('is-reader-open');
    });

    // Pagination
    prevBtn.addEventListener('click', () => { if (state.page > 1) { state.page--; loadMessages(); } });
    nextBtn.addEventListener('click', () => { const pages = Math.ceil(state.total / state.perPage); if (state.page < pages) { state.page++; loadMessages(); } });

    // Refresh
    document.getElementById('xmail_refresh')?.addEventListener('click', () => loadMessages());

    // Category filter
    document.querySelectorAll('[data-category]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-category]').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            state.category = btn.dataset.category;
            document.getElementById('xmail_category_label').textContent = btn.textContent.trim();
            document.getElementById('xmail_category_menu').classList.remove('is-open');
            loadMessages();
        });
    });

    document.getElementById('xmail_category_toggle')?.addEventListener('click', e => {
        e.stopPropagation();
        document.getElementById('xmail_category_menu').classList.toggle('is-open');
    });

    document.addEventListener('click', e => {
        const menu = document.getElementById('xmail_category_menu');
        if (menu?.classList.contains('is-open') && !menu.contains(e.target) && !document.getElementById('xmail_category_toggle')?.contains(e.target)) {
            menu.classList.remove('is-open');
        }
    });

    // Search filter (client-side on visible items)
    filterInput?.addEventListener('input', () => {
        const q = filterInput.value.trim().toLowerCase();
        msgInner.querySelectorAll('.xmail-message').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.classList.toggle('hidden', q !== '' && !text.includes(q));
        });
    });

    // Reader toolbar actions
    document.getElementById('xmail_btn_flag')?.addEventListener('click', toggleFlag);
    document.getElementById('xmail_btn_move_trash')?.addEventListener('click', moveToTrash);
    document.getElementById('xmail_btn_delete')?.addEventListener('click', deleteMessage);

    // Reply actions (footer)
    document.querySelectorAll('[data-compose-action]').forEach(btn => {
        btn.addEventListener('click', () => openCompose(btn.dataset.composeAction || 'reply'));
    });
    document.getElementById('xmail_btn_reply_all')?.addEventListener('click', () => openCompose('reply-all'));

    // Inline compose
    document.getElementById('xmail_compose_close')?.addEventListener('click', closeCompose);
    document.getElementById('xmail_compose_close2')?.addEventListener('click', closeCompose);
    document.getElementById('xmail_compose_send')?.addEventListener('click', sendInlineCompose);
    document.getElementById('xmail_compose_toggle_cc')?.addEventListener('click', () => {
        document.getElementById('xmail_compose_cc_row')?.classList.toggle('hidden');
    });

    // New mail
    document.getElementById('xmail_new_mail')?.addEventListener('click', () => {
        document.getElementById('xmail_new_compose').classList.add('is-open');
        document.getElementById('xmail_new_to_input').focus();
    });
    document.getElementById('xmail_new_compose_close')?.addEventListener('click', () => {
        document.getElementById('xmail_new_compose').classList.remove('is-open');
    });
    document.getElementById('xmail_new_compose_close2')?.addEventListener('click', () => {
        document.getElementById('xmail_new_compose').classList.remove('is-open');
    });
    document.getElementById('xmail_new_send')?.addEventListener('click', sendNewCompose);

    // Account switcher
    accountSelect?.addEventListener('change', () => {
        state.account = accountSelect.value;
        state.page = 1;
        state.message = null;
        state.folder = 'INBOX';
        showReaderEmpty();
        loadFolders().then(() => loadMessages());
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', e => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
        if (e.key === 'r') openCompose('reply');
        if (e.key === 'a') openCompose('reply-all');
        if (e.key === 'f') openCompose('forward');
        if (e.key === 'Escape') { closeCompose(); mailApp?.classList.remove('is-reader-open'); }
        if (e.key === 'n') { document.getElementById('xmail_new_compose').classList.add('is-open'); }
    });

    // ── Boot ─────────────────────────────────────────────────────────────────────
    if (state.account) {
        loadFolders().then(() => loadMessages());
    } else {
        folderNav.innerHTML = '<p class="text-sm text-secondary-foreground px-2 py-2">No mail account found.</p>';
        msgInner.innerHTML = '<p class="text-sm text-secondary-foreground py-8 text-center">Add a mail account first.</p>';
    }
})();
</script>
@endpush
