{{-- Same pattern as layouts/header: language dropdown + logout (custom.js wires #langBtn / #langMenu / #langFlag) --}}
@php
    $barLoc = session('locale', 'en');
@endphp
@if(auth()->check())
<header class="sticky top-0 z-50 flex flex-wrap items-center justify-between gap-3 px-4 md:px-8 py-3 bg-[#f9f9f9]/95 dark:bg-slate-950/90 backdrop-blur-xl border-b border-outline-variant/15 shrink-0 shadow-sm">
    <div class="flex items-center gap-3 min-w-0">
        <div class="hidden sm:flex h-9 w-9 shrink-0 rounded-full bg-primary/15 items-center justify-center border border-primary/20">
            <span class="material-symbols-outlined text-primary text-[20px]">spa</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant/90 leading-tight">{{ trans('messages.perm_salon_dashboard', [], $barLoc) }}</p>
            <p class="text-sm font-headline font-bold text-on-surface truncate">{{ auth()->user()->user_name ?? trans('messages.user_default', [], $barLoc) }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <div class="relative">
            <button type="button" id="langBtn"
                class="h-10 min-w-[2.5rem] px-2 rounded-full bg-primary/10 dark:bg-primary/20 inline-flex items-center justify-center gap-1.5 hover:bg-primary/15 transition-colors border border-outline-variant/20"
                aria-haspopup="true" aria-expanded="false"
                title="{{ trans('messages.arabic', [], $barLoc) }} / {{ trans('messages.english', [], $barLoc) }}">
                <span id="langFlag" class="text-lg leading-none" data-locale="{{ $barLoc }}">{{ $barLoc === 'en' ? '🇬🇧' : '🇴🇲' }}</span>
                <span class="material-symbols-outlined text-on-surface-variant text-[18px] hidden sm:inline" aria-hidden="true">expand_more</span>
            </button>
            <div id="langMenu"
                class="dropdown absolute top-full mt-2 end-0 w-44 bg-white dark:bg-slate-900 border border-outline-variant/20 rounded-xl shadow-lg z-[60] py-1">
                <button type="button" data-lang="ar"
                    class="w-full flex items-center gap-2 px-3 py-2.5 text-sm text-start hover:bg-surface-container-low dark:hover:bg-slate-800 {{ $barLoc === 'ar' ? 'bg-surface-container-low/80' : '' }}">
                    <span class="text-base shrink-0">🇴🇲</span> {{ trans('messages.arabic', [], $barLoc) }}
                </button>
                <button type="button" data-lang="en"
                    class="w-full flex items-center gap-2 px-3 py-2.5 text-sm text-start hover:bg-surface-container-low dark:hover:bg-slate-800 {{ $barLoc === 'en' ? 'bg-surface-container-low/80' : '' }}">
                    <span class="text-base shrink-0">🇬🇧</span> {{ trans('messages.english', [], $barLoc) }}
                </button>
            </div>
        </div>
        <button type="button" onclick="logout()"
            class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-full bg-primary text-white text-sm font-bold shadow-sm hover:opacity-95 active:scale-[0.98] transition-all">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="hidden sm:inline">{{ trans('messages.logout_title', [], $barLoc) }}</span>
        </button>
    </div>
</header>
@endif
