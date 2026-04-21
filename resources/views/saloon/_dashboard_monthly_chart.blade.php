@php
    $dashMcLoc = session('locale');
    $permIdsMc = array_map('intval', auth()->user()->permissions ?? []);
@endphp
<section class="space-y-5" id="dash-monthly-income-section" aria-labelledby="dash-mir-section-title">
    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
            <h2 id="dash-mir-section-title" class="text-lg font-headline font-extrabold text-on-surface inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[26px]">bar_chart</span>
                {{ trans('messages.mir_title', [], $dashMcLoc) }}
            </h2>
            <p class="text-xs sm:text-sm text-on-surface-variant max-w-2xl leading-relaxed">
                {{ trans('messages.dashboard_saloon_monthly_chart_hint', [], $dashMcLoc) }}
            </p>
        </div>
        @if(in_array(27, $permIdsMc))
            <a href="{{ route('saloon_monthly_income_report') }}"
                class="text-sm font-semibold text-primary hover:underline shrink-0 inline-flex items-center gap-1.5 rounded-full border border-primary/25 px-4 py-2 bg-primary/5 hover:bg-primary/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]">table_chart</span>
                {{ trans('messages.mir_menu', [], $dashMcLoc) }}
            </a>
        @endif
    </div>

    <div class="bg-surface-container-lowest dark:bg-[#1a1c1c] rounded-2xl editorial-shadow border border-outline-variant/15 overflow-hidden">
        <div class="h-1 w-full bg-gradient-to-r from-primary via-[#a6606b] to-secondary"></div>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6">
            <form id="dash-mir-filter-form" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <label for="dash-mir-month" class="text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant whitespace-nowrap">{{ trans('messages.mir_month', [], $dashMcLoc) }}</label>
                    <input type="month" id="dash-mir-month" name="mir_month" value="{{ $monthInput }}"
                        class="rounded-full border border-outline-variant/20 bg-white dark:bg-slate-900 px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                </div>
                <div class="flex items-center gap-1.5">
                    <label for="dash-mir-team" class="text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant whitespace-nowrap">{{ trans('messages.mir_team', [], $dashMcLoc) }}</label>
                    <select id="dash-mir-team" name="mir_team"
                        class="rounded-full border border-outline-variant/20 bg-white dark:bg-slate-900 px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10 min-w-[140px]">
                        <option value="">{{ trans('messages.mir_all_teams', [], $dashMcLoc) }}</option>
                        @foreach($salonTeams as $t)
                            <option value="{{ $t->id }}" @selected($teamFilter === (string) $t->id)>{{ $t->displayName($dashMcLoc) }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 rounded-full border border-outline-variant/20 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-bold text-on-surface-variant cursor-pointer select-none">
                    <input type="checkbox" id="dash-mir-weekend-only" name="mir_weekend_only" value="1" @checked(!empty($weekendOnly))
                        class="rounded border-outline-variant/30 text-primary focus:ring-primary/30">
                    {{ trans('messages.mir_weekend', [], $dashMcLoc) }}
                </label>
                <button type="submit"
                    class="shrink-0 rounded-full bg-gradient-to-br from-primary to-primary-container text-white px-5 py-2 text-sm font-bold transition-transform active:scale-95 inline-flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                    {{ trans('messages.mir_apply', [], $dashMcLoc) }}
                </button>
            </form>

            <p id="dash-mir-chart-error" class="text-sm text-red-600 hidden" role="alert"></p>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4" id="dash-mir-summary-cards">
                <div class="bg-white/80 dark:bg-slate-900/60 rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_bookings', [], $dashMcLoc) }}</p>
                    <p class="mt-1.5 text-2xl sm:text-3xl font-headline font-extrabold text-on-surface tabular-nums" id="dash-mir-stat-bookings">{{ $summaryTotals['bookings'] }}</p>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/60 rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary-container">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_amount', [], $dashMcLoc) }}</p>
                    <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-on-surface tabular-nums" id="dash-mir-stat-amount">{{ number_format($summaryTotals['amount'], 3) }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], $dashMcLoc) }}</p>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/60 rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-emerald-500">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_paid', [], $dashMcLoc) }}</p>
                    <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-emerald-700 tabular-nums" id="dash-mir-stat-paid">{{ number_format($summaryTotals['paid'], 3) }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], $dashMcLoc) }}</p>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/60 rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-red-500">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_remaining', [], $dashMcLoc) }}</p>
                    <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-red-600 tabular-nums" id="dash-mir-stat-remaining">{{ number_format($summaryTotals['remaining'], 3) }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], $dashMcLoc) }}</p>
                </div>
                <div id="dash-mir-weekend-income-card" class="bg-white/80 dark:bg-slate-900/60 rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-indigo-500 {{ !empty($weekendOnly) ? '' : 'hidden' }}">
                    <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_weekend_income_title', [], $dashMcLoc) }}</p>
                    <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-indigo-700 tabular-nums" id="dash-mir-stat-weekend-income">{{ number_format((float)($summaryTotals['weekend_income'] ?? 0), 3) }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], $dashMcLoc) }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-gradient-to-b from-white to-surface-container-low/30 dark:from-slate-900/80 dark:to-slate-950/40 border border-outline-variant/10 p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.mir_chart_title', [], $dashMcLoc) }}</h3>
                    <span id="dash-mir-chart-month-label" class="text-xs font-bold text-on-surface-variant tabular-nums">{{ $monthLabel }}</span>
                </div>
                <div class="relative min-h-[240px]" style="height:300px; max-height:42vh;">
                    <canvas id="dashMirChart" aria-label="{{ trans('messages.mir_chart_title', [], $dashMcLoc) }}"></canvas>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 mt-4 text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#8a4853]" aria-hidden="true"></span>{{ trans('messages.mir_legend_amount', [], $dashMcLoc) }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#059669]" aria-hidden="true"></span>{{ trans('messages.mir_legend_paid', [], $dashMcLoc) }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#dc2626]" aria-hidden="true"></span>{{ trans('messages.mir_legend_remaining', [], $dashMcLoc) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
