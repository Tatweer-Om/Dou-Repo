@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.perm_salon_dashboard', [], session('locale')) }} — Ethereal Salon</title>
@endpush

<style>
    .dashboard-no-scrollbar::-webkit-scrollbar { display: none; }
</style>

<main class="min-h-screen bg-surface">
    <header class="bg-[#f9f9f9]/80 dark:bg-slate-950/80 backdrop-blur-xl sticky top-0 z-40 flex justify-between items-center px-6 md:px-10 py-6 w-full max-w-[1600px] mx-auto font-['Manrope']">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#8a4853] md:hidden cursor-pointer">menu</span>
            <h1 class="text-2xl font-extrabold tracking-tight text-on-surface">Studio Overview</h1>
        </div>
        @if(in_array(16, array_map('intval', auth()->user()->permissions ?? [])))
        <a href="{{ route('saloon_booking_page') }}" class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-semibold text-sm hover:opacity-90 active:scale-95 transition-all shadow-sm inline-block text-center">
            + Add New Booking
        </a>
        @endif
    </header>

    <div class="p-6 md:p-10 space-y-12 max-w-[1600px] mx-auto w-full">
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">{{ trans('messages.dashboard_saloon_overall_note', [], session('locale')) }}</p>
                @if(in_array(28, array_map('intval', auth()->user()->permissions ?? [])))
                <a href="{{ route('saloon_income_expense_report') }}" class="text-sm font-semibold text-primary hover:underline">
                    {{ trans('messages.dashboard_saloon_open_income_report', [], session('locale')) }}
                </a>
                @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">{{ trans('messages.ie_report_received', [], session('locale')) }}</p>
                    <p class="mt-1 text-base font-semibold text-emerald-700">{{ number_format((float) $summary['received_amount'], 3) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">{{ trans('messages.ie_report_pending', [], session('locale')) }}</p>
                    <p class="mt-1 text-base font-semibold text-amber-700">{{ number_format((float) $summary['pending_amount'], 3) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">{{ trans('messages.ie_report_total', [], session('locale')) }}</p>
                    <p class="mt-1 text-base font-semibold">{{ number_format((float) $summary['total_amount'], 3) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">{{ trans('messages.ie_report_expense', [], session('locale')) }}</p>
                    <p class="mt-1 text-base font-semibold text-red-600">{{ number_format((float) $summary['expense_amount'], 3) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-outline-variant/20 p-4">
                    <p class="text-xs uppercase tracking-wide text-on-surface-variant font-bold">{{ trans('messages.ie_report_net_income', [], session('locale')) }}</p>
                    <p class="mt-1 text-base font-semibold {{ $summary['income_amount'] < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ number_format((float) $summary['income_amount'], 3) }}</p>
                </div>
            </div>
        </section>

        @include('saloon._dashboard_monthly_chart', $monthlyChart)

        @php
            $dashLoc = session('locale');
        @endphp
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-headline font-extrabold text-on-surface">{{ trans('messages.booking_management_schedule_title', [], $dashLoc) }}</h2>
                    <p class="text-sm text-on-surface-variant mt-1">{{ trans('messages.booking_management_date_label', [], $dashLoc) }}:
                        <span class="font-semibold text-on-surface">{{ \Carbon\Carbon::parse($dateStr)->locale($dashLoc === 'ar' ? 'ar' : 'en')->translatedFormat('l, j F Y') }}</span>
                    </p>
                    <p class="text-xs text-on-surface-variant/85 mt-1 max-w-2xl">{{ trans('messages.booking_management_schedule_hours', [], $dashLoc) }}</p>
                </div>
                @if(in_array(18, array_map('intval', auth()->user()->permissions ?? [])))
                <a href="{{ route('booking_management', array_filter(['date' => $dateStr, 'team' => $teamFilter])) }}"
                    class="text-sm font-semibold text-primary hover:underline shrink-0">
                    {{ trans('messages.booking_management_title', [], $dashLoc) }}
                </a>
                @endif
            </div>
            @include('saloon._booking_schedule_panel', [
                'scheduleFormAction' => route('saloon_dashboard'),
                'scheduleFormId' => 'dash-sched-filter-form',
                'scheduleDateInputId' => 'dash-sched-date',
                'scheduleTeamSelectId' => 'dash-sched-team',
                'showSectionHeading' => false,
                'showInnerScheduleHeading' => false,
                'showDayStatBoxes' => false,
                'loc' => $dashLoc,
            ])
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-headline font-bold text-lg">{{ trans('messages.dashboard_saloon_most_requested', [], $dashLoc) }}</h3>
                    @if(in_array(25, array_map('intval', auth()->user()->permissions ?? [])))
                        <a href="{{ route('salonservice.index') }}" class="text-xs font-semibold text-primary hover:underline shrink-0">{{ trans('messages.view_service_lang', [], $dashLoc) }}</a>
                    @endif
                </div>
                <div class="bg-white rounded-xl border border-outline-variant/15 overflow-hidden editorial-shadow">
                    @if(!empty($topSalonService))
                        <div class="relative min-h-[192px] flex flex-col justify-end p-6 sm:p-7 bg-gradient-to-br from-primary via-[#8a4853] to-secondary text-on-primary overflow-hidden group">
                            <div class="absolute -right-6 -top-6 text-white/[0.08] pointer-events-none transition-transform duration-500 group-hover:scale-105" aria-hidden="true">
                                <span class="material-symbols-outlined text-[180px]">spa</span>
                            </div>
                            <div class="relative z-[1]">
                                <p class="text-white/90 text-[11px] font-bold uppercase tracking-[0.2em] mb-2">{{ trans('messages.dashboard_saloon_top_service_label', [], $dashLoc) }}</p>
                                <h4 class="text-white font-headline text-xl sm:text-2xl font-extrabold leading-tight">{{ $topSalonService['name'] }}</h4>
                                <p class="mt-3 text-sm text-white/85 font-medium tabular-nums">
                                    {{ trans('messages.dashboard_saloon_top_service_times', ['count' => $topSalonService['times']], $dashLoc) }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="min-h-[192px] flex flex-col items-center justify-center p-8 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-5xl text-outline-variant/50 mb-3" aria-hidden="true">inventory_2</span>
                            <p class="text-sm font-medium max-w-xs leading-relaxed">{{ trans('messages.dashboard_saloon_top_service_empty', [], $dashLoc) }}</p>
                        </div>
                    @endif
                </div>
                <div class="bg-surface-container-low rounded-xl p-6">
                    <p class="text-sm font-semibold mb-4">{{ trans('messages.dashboard_saloon_bookings_per_team', [], $dashLoc) }}</p>
                    @php
                        $maxTeamBk = max(1, (int) ($teamBookingStats->max('bookings_count') ?? 1));
                    @endphp
                    @forelse($teamBookingStats as $ti => $teamRow)
                        <div class="{{ $ti > 0 ? 'mt-5 pt-5 border-t border-outline-variant/15' : '' }} space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-on-surface truncate">{{ $teamRow->displayName($dashLoc) }}</span>
                                <span class="text-sm font-bold tabular-nums shrink-0">{{ (int) $teamRow->bookings_count }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-surface-container-highest rounded-full overflow-hidden">
                                @php
                                    $pct = round(((int) $teamRow->bookings_count / $maxTeamBk) * 100);
                                @endphp
                                <div class="{{ $ti % 2 === 0 ? 'bg-primary' : 'bg-secondary' }} h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-on-surface-variant">{{ trans('messages.view_bookings_empty', [], $dashLoc) }}</p>
                    @endforelse
                </div>
            </div>
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-headline font-bold text-lg">{{ trans('messages.dashboard_saloon_schedule_for_day', [], $dashLoc) }}</h3>
                    @if(in_array(17, array_map('intval', auth()->user()->permissions ?? [])))
                    <a href="{{ route('view_bookings') }}" class="text-primary text-sm font-semibold hover:underline shrink-0">{{ trans('messages.view_bookings_title', [], $dashLoc) }}</a>
                    @else
                    <span class="text-primary text-sm font-semibold opacity-50 shrink-0">{{ trans('messages.view_bookings_title', [], $dashLoc) }}</span>
                    @endif
                </div>
                <div class="overflow-x-auto dashboard-no-scrollbar rounded-xl border border-outline-variant/10 bg-white">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead class="text-primary uppercase tracking-[0.05em] font-bold text-[10px] bg-surface-container-low/60">
                            <tr>
                                <th class="pb-4 pt-4 pl-4">{{ trans('messages.view_bookings_time', [], $dashLoc) }}</th>
                                <th class="pb-4 pt-4">{{ trans('messages.dashboard_saloon_client_and_service', [], $dashLoc) }}</th>
                                <th class="pb-4 pt-4">{{ trans('messages.view_bookings_team', [], $dashLoc) }}</th>
                                <th class="pb-4 pt-4">{{ trans('messages.dashboard_saloon_advance', [], $dashLoc) }}</th>
                                <th class="pb-4 pt-4 pr-4">{{ trans('messages.view_bookings_status', [], $dashLoc) }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-outline-variant/10">
                            @forelse($bookings as $b)
                                @php
                                    $teamName = $b->salonTeam?->displayName($dashLoc) ?? '—';
                                    $remaining = (float) $b->total_remaining;
                                    $paid = (float) $b->total_paid;
                                    $teamStyle = (($b->team_id ?? 0) % 2 === 0)
                                        ? 'bg-primary-container/10 text-primary'
                                        : 'bg-secondary-container/30 text-on-secondary-container';
                                @endphp
                                <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-surface-container-low/35' : 'bg-white' }} hover:bg-surface-container-low/80 transition-colors">
                                    <td class="py-4 pl-4 font-bold whitespace-nowrap align-top">{{ $b->bookingTimeRangeDisplay() }}</td>
                                    <td class="py-4 align-top">
                                        <div class="font-semibold">{{ $b->customer->name ?? '—' }}</div>
                                        <div class="text-xs text-on-surface-variant/70 mt-0.5 leading-snug">{{ $b->servicesLineSummary() }}</div>
                                    </td>
                                    <td class="py-4 align-top">
                                        @if($b->salonTeam)
                                            <span class="inline-flex px-2 py-1 {{ $teamStyle }} text-[10px] font-bold rounded uppercase">{{ $teamName }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 bg-surface-container-highest text-on-surface-variant text-[10px] font-bold rounded uppercase">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 font-medium tabular-nums align-top">
                                        {{ number_format($paid, 3) }}
                                        <span class="text-[10px] text-on-surface-variant font-semibold">{{ trans('messages.saloon_booking_currency_omr', [], $dashLoc) }}</span>
                                    </td>
                                    <td class="py-4 pr-4 align-top">
                                        @if($b->status === 'draft')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-900">{{ trans('messages.view_bookings_status_draft', [], $dashLoc) }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-900">{{ trans('messages.view_bookings_status_confirmed', [], $dashLoc) }}</span>
                                        @endif
                                        @if($remaining <= 0.0001 && $paid > 0)
                                            <div class="flex items-center gap-1.5 text-tertiary font-bold text-[10px] uppercase mt-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-tertiary shrink-0" aria-hidden="true"></span>
                                                {{ trans('messages.dashboard_saloon_paid_full', [], $dashLoc) }}
                                            </div>
                                        @elseif($remaining > 0.0001)
                                            <div class="text-[10px] text-amber-900 font-semibold mt-1.5">
                                                {{ trans('messages.dashboard_saloon_balance_due', [], $dashLoc) }}:
                                                {{ number_format($remaining, 3) }}
                                                <span class="text-on-surface-variant">{{ trans('messages.saloon_booking_currency_omr', [], $dashLoc) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 px-4 text-center text-on-surface-variant text-sm">
                                        {{ trans('messages.view_bookings_empty', [], $dashLoc) }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@include('custom_js.salon_dashboard_monthly_chart_js', ['chartData' => $monthlyChart['chartData']])
@include('layouts.salon_footer')
@endsection
