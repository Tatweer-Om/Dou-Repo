@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.mir_title', [], session('locale')) }}</title>
@endpush

<main class="min-h-screen bg-surface pt-10">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-4 w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex flex-wrap items-center gap-4 flex-1 min-w-0">
            <h1 class="text-lg font-headline font-extrabold text-on-surface shrink-0 inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[24px]">bar_chart</span>
                {{ trans('messages.mir_title', [], session('locale')) }}
            </h1>

            <form id="mir-filter-form" class="flex flex-wrap items-center gap-3 flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                    <label for="mir-month" class="text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant whitespace-nowrap">{{ trans('messages.mir_month', [], session('locale')) }}</label>
                    <input type="month" id="mir-month" name="month" value="{{ $monthInput }}"
                        class="rounded-full border border-outline-variant/20 bg-white px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                </div>
                <div class="flex items-center gap-1.5">
                    <label for="mir-team" class="text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant whitespace-nowrap">{{ trans('messages.mir_team', [], session('locale')) }}</label>
                    <select id="mir-team" name="team"
                        class="rounded-full border border-outline-variant/20 bg-white px-4 py-2 text-sm text-on-surface focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10 min-w-[140px]">
                        <option value="">{{ trans('messages.mir_all_teams', [], session('locale')) }}</option>
                        @foreach($salonTeams as $t)
                            <option value="{{ $t->id }}" @selected($teamFilter === (string) $t->id)>{{ $t->displayName(session('locale')) }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 rounded-full border border-outline-variant/20 bg-white px-3 py-2 text-xs font-bold text-on-surface-variant">
                    <input type="checkbox" id="mir-weekend-only" name="weekend_only" value="1" @checked(!empty($weekendOnly)) class="rounded border-outline-variant/30 text-primary focus:ring-primary/30">
                    Weekend (Thu/Fri/Sat)
                </label>
                <button type="submit"
                    class="shrink-0 rounded-full bg-gradient-to-br from-primary to-primary-container text-white px-5 py-2 text-sm font-bold transition-transform active:scale-95 inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                    {{ trans('messages.mir_apply', [], session('locale')) }}
                </button>
                <a id="mir-export-excel-btn"
                    href="{{ route('saloon_monthly_income_report.export_excel', request()->query()) }}"
                    class="shrink-0 rounded-full bg-emerald-600 text-white px-5 py-2 text-sm font-bold hover:bg-emerald-700 inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export Excel
                </a>
            </form>
        </div>
    </header>

    <div class="px-4 sm:px-8 py-6 sm:py-10 space-y-6">

        {{-- Summary Cards --}}
        <section class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4" id="mir-summary-cards">
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_bookings', [], session('locale')) }}</p>
                <p class="mt-1.5 text-2xl sm:text-3xl font-headline font-extrabold text-on-surface tabular-nums" id="mir-stat-bookings">{{ $summaryTotals['bookings'] }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary-container">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_amount', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-on-surface tabular-nums" id="mir-stat-amount">{{ number_format($summaryTotals['amount'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-emerald-500">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_paid', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-emerald-700 tabular-nums" id="mir-stat-paid">{{ number_format($summaryTotals['paid'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-red-500">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.mir_total_remaining', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-red-600 tabular-nums" id="mir-stat-remaining">{{ number_format($summaryTotals['remaining'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
            <div id="mir-weekend-income-card" class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-indigo-500 {{ !empty($weekendOnly) ? '' : 'hidden' }}">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Weekend Income</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-indigo-700 tabular-nums" id="mir-stat-weekend-income">{{ number_format((float)($summaryTotals['weekend_income'] ?? 0), 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
        </section>

        {{-- Chart Section --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.mir_chart_title', [], session('locale')) }}</h2>
                    <span id="mir-chart-month-label" class="text-xs font-bold text-on-surface-variant">{{ $monthLabel }}</span>
                </div>
                <div class="relative" style="height:280px; max-height:36vh;">
                    <canvas id="mirChart"></canvas>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 mt-4 text-[10px] font-bold uppercase tracking-wide">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#8a4853]"></span>{{ trans('messages.mir_legend_amount', [], session('locale')) }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#059669]"></span>{{ trans('messages.mir_legend_paid', [], session('locale')) }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-[#dc2626]"></span>{{ trans('messages.mir_legend_remaining', [], session('locale')) }}</span>
                </div>
            </div>
        </section>

        {{-- Bookings Table --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="px-5 sm:px-7 pt-5 sm:pt-7 pb-3">
                <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.mir_table_title', [], session('locale')) }}</h2>
            </div>
            <div id="mir-table-container">
                @include('saloon._monthly_report_table', ['bookings' => $bookings, 'staffById' => $staffById])
            </div>
        </section>
    </div>
</main>

@include('layouts.salon_footer')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@include('custom_js.salon_monthly_report_js')
@endsection
