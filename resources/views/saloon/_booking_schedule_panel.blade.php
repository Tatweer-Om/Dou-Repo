{{--
  Shared: date + team filter, day stats, unscheduled banner, staff timeline calendar.
  Required: $scheduleFormAction, $scheduleFormId, $scheduleDateInputId, $scheduleTeamSelectId
           $dateStr, $teamFilter, $salonTeams, $dayTotals, $unscheduled, $staffTimeline
  Optional: $showSectionHeading, $showInnerScheduleHeading (default true), $showDayStatBoxes (default true), $loc
--}}
@php
    $loc = $loc ?? session('locale');
    $showSectionHeading = $showSectionHeading ?? false;
    $showInnerScheduleHeading = $showInnerScheduleHeading ?? true;
    $showDayStatBoxes = $showDayStatBoxes ?? true;
@endphp

@if($showSectionHeading)
<div class="mb-4">
    <h2 class="text-lg font-headline font-extrabold text-on-surface">{{ trans('messages.booking_management_schedule_title', [], $loc) }}</h2>
    <p class="text-sm text-on-surface-variant mt-1">{{ trans('messages.booking_management_date_label', [], $loc) }}:
        <span class="font-semibold text-on-surface">{{ \Carbon\Carbon::parse($dateStr)->locale($loc === 'ar' ? 'ar' : 'en')->translatedFormat('l, j F Y') }}</span>
    </p>
</div>
@endif

<form id="{{ $scheduleFormId }}" method="get" action="{{ $scheduleFormAction }}" class="flex flex-wrap items-end gap-4 mb-6">
    <div class="space-y-1">
        <label for="{{ $scheduleDateInputId }}" class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_filter_date', [], $loc) }}</label>
        <input type="date" name="date" id="{{ $scheduleDateInputId }}" value="{{ $dateStr }}"
            class="rounded-xl border border-outline-variant/20 bg-white dark:bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary/30">
    </div>
    <div class="space-y-1 min-w-[200px]">
        <label for="{{ $scheduleTeamSelectId }}" class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_filter_team', [], $loc) }}</label>
        <select name="team" id="{{ $scheduleTeamSelectId }}"
            class="w-full rounded-xl border border-outline-variant/20 bg-white dark:bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/15 focus:border-primary/30">
            <option value="">{{ trans('messages.booking_management_team_all', [], $loc) }}</option>
            @foreach($salonTeams ?? [] as $stOpt)
                <option value="{{ $stOpt->id }}" @selected((string) $teamFilter === (string) $stOpt->id)>{{ $stOpt->displayName($loc) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit"
        class="rounded-xl bg-primary text-on-primary px-6 py-2.5 text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
        {{ trans('messages.booking_management_apply', [], $loc) }}
    </button>
</form>

@if($showDayStatBoxes)
<section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-xl bg-surface-container-lowest editorial-shadow border border-outline-variant/10 p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_stat_bookings', [], $loc) }}</p>
        <p class="text-2xl font-headline font-extrabold text-primary mt-1">{{ $dayTotals['bookings'] }}</p>
    </div>
    <div class="rounded-xl bg-surface-container-lowest editorial-shadow border border-outline-variant/10 p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_stat_total', [], $loc) }}</p>
        <p class="text-2xl font-headline font-extrabold text-on-surface mt-1">{{ number_format($dayTotals['total'], 3) }} <span class="text-sm font-semibold text-on-surface-variant">{{ trans('messages.saloon_booking_currency_omr', [], $loc) }}</span></p>
    </div>
    <div class="rounded-xl bg-surface-container-lowest editorial-shadow border border-emerald-100 p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_stat_paid', [], $loc) }}</p>
        <p class="text-2xl font-headline font-extrabold text-emerald-800 mt-1">{{ number_format($dayTotals['paid'], 3) }} <span class="text-sm font-semibold text-emerald-700/80">{{ trans('messages.saloon_booking_currency_omr', [], $loc) }}</span></p>
    </div>
    <div class="rounded-xl bg-surface-container-lowest editorial-shadow border border-amber-100 p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ trans('messages.booking_management_stat_remaining', [], $loc) }}</p>
        <p class="text-2xl font-headline font-extrabold text-amber-900 mt-1">{{ number_format($dayTotals['remaining'], 3) }} <span class="text-sm font-semibold text-amber-800/80">{{ trans('messages.saloon_booking_currency_omr', [], $loc) }}</span></p>
    </div>
</section>
@endif

@if($unscheduled->isNotEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 flex items-start gap-3 mb-8">
        <span class="material-symbols-outlined text-amber-800 text-[22px] shrink-0">schedule</span>
        <div>
            <p class="text-sm font-bold text-amber-950">{{ trans('messages.booking_management_unscheduled_title', [], $loc) }}</p>
            <p class="text-xs text-amber-900/90 mt-0.5">{{ trans('messages.booking_management_unscheduled_hint', [], $loc) }} ({{ $unscheduled->count() }})</p>
        </div>
    </div>
@endif

@php
    $st = $staffTimeline ?? ['teams' => [], 'unassigned' => [], 'hour_markers' => [], 'total_minutes' => 900];
    $stTeams = $st['teams'] ?? [];
    $stUnassigned = $st['unassigned'] ?? [];
    $stHours = $st['hour_markers'] ?? [];
@endphp

<section class="bg-surface-container-lowest rounded-xl editorial-shadow border border-outline-variant/10 overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant/10 flex flex-wrap items-center justify-between gap-3 {{ !$showInnerScheduleHeading ? 'justify-end' : '' }}">
        @if($showInnerScheduleHeading)
        <div>
            <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.booking_management_schedule_title', [], $loc) }}</h2>
            <p class="text-xs text-on-surface-variant mt-0.5">{{ trans('messages.booking_management_schedule_hours', [], $loc) }}</p>
            <p class="text-[11px] text-on-surface-variant/80 mt-1">{{ trans('messages.booking_management_timeline_hint', [], $loc) }}</p>
        </div>
        @endif
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
            <span class="inline-flex items-center gap-2">
                <span class="w-8 h-3 rounded-sm bg-gradient-to-b from-emerald-50 to-emerald-100/80 border border-emerald-200/60"></span>
                {{ trans('messages.booking_management_slot_free', [], $loc) }}
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-8 h-3 rounded-sm bg-primary border border-primary/40"></span>
                {{ trans('messages.booking_management_slot_booked', [], $loc) }}
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-8 h-3 rounded-sm bg-amber-500 border border-amber-700/40"></span>
                {{ trans('messages.view_bookings_status_draft', [], $loc) }}
            </span>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if(count($stTeams) === 0 && count($stUnassigned) === 0)
            <p class="text-sm text-on-surface-variant text-center py-10">{{ trans('messages.booking_management_timeline_empty_staff', [], $loc) }}</p>
        @else
            <div class="overflow-x-auto pb-1 -mx-1 px-1">
                <div class="min-w-[56rem] space-y-1" dir="ltr">
                    <div class="flex items-end mb-2">
                        <div class="w-40 sm:w-44 shrink-0"></div>
                        <div class="flex-1 relative h-7 border-b border-outline-variant/20">
                            @foreach($stHours as $hm)
                                @if(!empty($hm['anchor_end']))
                                    <span class="absolute bottom-0 right-0 text-[10px] font-bold text-on-surface-variant whitespace-nowrap">{{ $hm['label'] }}</span>
                                @else
                                    <span class="absolute bottom-0 text-[10px] font-bold text-on-surface-variant -translate-x-1/2 whitespace-nowrap" style="left: {{ $hm['left_pct'] }}%">{{ $hm['label'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @foreach($stTeams as $teamBlock)
                        <div class="pt-4 first:pt-0">
                            <div class="flex items-center gap-2 mb-3 px-0.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-primary">{{ $teamBlock['label'] }}</span>
                                <span class="flex-1 h-px bg-gradient-to-r from-primary/25 to-transparent"></span>
                            </div>
                            @foreach($teamBlock['rows'] as $row)
                                @php $staffMember = $row['staff']; @endphp
                                <div class="flex items-stretch gap-0 border-b border-outline-variant/10 last:border-0 py-2.5">
                                    <div class="w-40 sm:w-44 shrink-0 pr-3 flex items-center" dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}">
                                        <p class="text-sm font-bold text-on-surface leading-snug">{{ $staffMember->name }}</p>
                                    </div>
                                    <div class="flex-1 min-w-0 py-0.5">
                                        <div class="relative h-[3.25rem] rounded-xl border border-emerald-200/50 bg-gradient-to-b from-emerald-50/95 to-emerald-100/40 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)] overflow-hidden"
                                            style="background-image: repeating-linear-gradient(90deg, transparent 0, transparent calc(6.666% - 1px), rgba(17,24,39,0.05) calc(6.666% - 1px), rgba(17,24,39,0.05) 6.666%);">
                                            @foreach($row['segments'] as $zi => $seg)
                                                @php $isDraft = ($seg['status'] ?? '') === 'draft'; @endphp
                                                <div class="absolute top-1 bottom-1 flex flex-col justify-center rounded-lg shadow-md border overflow-hidden px-1.5 {{ $isDraft ? 'bg-amber-500 border-amber-700/50 text-white' : 'bg-primary border-primary/30 text-on-primary' }}"
                                                    style="left: {{ $seg['left_pct'] }}%; width: {{ $seg['width_pct'] }}%; z-index: {{ 10 + (int) $zi }};"
                                                    title="{{ $seg['booking_no'] }} · {{ $seg['time_label'] }} · {{ $seg['customer'] }}">
                                                    <span class="text-[10px] font-extrabold leading-tight truncate tracking-tight">{{ $seg['booking_no'] }}</span>
                                                    <span class="text-[9px] font-semibold leading-tight truncate opacity-95">{{ $seg['time_label'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    @if(count($stUnassigned) > 0)
                        <div class="pt-6">
                            <div class="flex items-center gap-2 mb-3 px-0.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-amber-800">{{ trans('messages.booking_management_timeline_unassigned', [], $loc) }}</span>
                                <span class="flex-1 h-px bg-gradient-to-r from-amber-200 to-transparent"></span>
                            </div>
                            <div class="flex items-stretch gap-0 py-2.5">
                                <div class="w-40 sm:w-44 shrink-0 pr-3 flex items-center text-on-surface-variant text-xs font-semibold italic" dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}">
                                    —
                                </div>
                                <div class="flex-1 min-w-0 py-0.5">
                                    <div class="relative h-[3.25rem] rounded-xl border border-amber-200/60 bg-gradient-to-b from-amber-50/80 to-amber-100/30 overflow-hidden"
                                        style="background-image: repeating-linear-gradient(90deg, transparent 0, transparent calc(6.666% - 1px), rgba(180,83,9,0.06) calc(6.666% - 1px), rgba(180,83,9,0.06) 6.666%);">
                                        @foreach($stUnassigned as $zi => $seg)
                                            @php $isDraft = ($seg['status'] ?? '') === 'draft'; @endphp
                                            <div class="absolute top-1 bottom-1 flex flex-col justify-center rounded-lg shadow-md border overflow-hidden px-1.5 {{ $isDraft ? 'bg-amber-600 border-amber-800/50 text-white' : 'bg-slate-700 border-slate-900/40 text-white' }}"
                                                style="left: {{ $seg['left_pct'] }}%; width: {{ $seg['width_pct'] }}%; z-index: {{ 10 + (int) $zi }};"
                                                title="{{ $seg['booking_no'] }} · {{ $seg['time_label'] }} · {{ $seg['customer'] }}">
                                                <span class="text-[10px] font-extrabold leading-tight truncate">{{ $seg['booking_no'] }}</span>
                                                <span class="text-[9px] font-semibold leading-tight truncate opacity-95">{{ $seg['time_label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>

<script>
(function () {
    var form = document.getElementById(@json($scheduleFormId));
    if (!form) return;
    form.querySelectorAll('#{{ $scheduleDateInputId }}, #{{ $scheduleTeamSelectId }}').forEach(function (el) {
        el.addEventListener('change', function () {
            form.submit();
        });
    });
})();
</script>
