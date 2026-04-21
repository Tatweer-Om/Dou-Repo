@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.staff_profile_title', [], session('locale')) }} — {{ $staff->name }}</title>
@endpush

<main class="min-h-screen bg-surface pt-10">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-4 w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <!-- <a href="{{ route('salonstaff.index') }}"
                class="shrink-0 rounded-full border border-outline-variant/20 bg-white dark:bg-surface-container-low px-5 py-2.5 text-sm font-bold text-on-surface hover:bg-surface-container transition-colors inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                {{ trans('messages.view_staff_lang', [], session('locale')) }}
            </a> -->
        </div>
    </header>

    <div class="px-4 sm:px-8 py-6 sm:py-10 space-y-6">

        {{-- Staff Details Card --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-primary via-primary-container to-primary"></div>
            <div class="p-5 sm:p-7">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="shrink-0">
                        @if($staff->staff_image)
                            <img src="{{ asset('uploads/staff_files/' . $staff->staff_image) }}" alt="{{ $staff->name }}"
                                class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-2 border-primary/20 shadow-md">
                        @else
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-primary/15 to-primary-container/20 border-2 border-primary/15 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-[40px]">person</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl sm:text-2xl font-headline font-extrabold text-on-surface">{{ $staff->name }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-on-surface-variant">
                            @if($staff->phone)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">phone</span>
                                    {{ $staff->phone }}
                                </span>
                            @endif
                            @if($staff->email)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">mail</span>
                                    {{ $staff->email }}
                                </span>
                            @endif
                            @if($staff->salonTeam)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">groups</span>
                                    {{ $staff->salonTeam->displayName(session('locale')) }}
                                </span>
                            @endif
                        </div>
                        @if($staff->address)
                            <p class="mt-1.5 text-xs text-on-surface-variant/80 inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                {{ $staff->address }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Stats Cards --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.staff_profile_total_bookings', [], session('locale')) }}</p>
                <p class="mt-1.5 text-2xl sm:text-3xl font-headline font-extrabold text-on-surface tabular-nums">{{ $stats['totalBookings'] }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-primary-container">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.staff_profile_total_amount', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-on-surface tabular-nums">{{ number_format($stats['totalAmount'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-emerald-500">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.staff_profile_total_paid', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-emerald-700 tabular-nums">{{ number_format($stats['totalPaid'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl editorial-shadow p-4 sm:p-5 border-l-4 border-red-500">
                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">{{ trans('messages.staff_profile_total_remaining', [], session('locale')) }}</p>
                <p class="mt-1.5 text-xl sm:text-2xl font-headline font-extrabold text-red-600 tabular-nums">{{ number_format($stats['totalRemaining'], 3) }}</p>
                <p class="text-[10px] text-on-surface-variant mt-0.5">{{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</p>
            </div>
        </section>

        {{-- Calendar Availability Section --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="p-5 sm:p-7">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.staff_profile_calendar_title', [], session('locale')) }}</h2>
                        <p class="text-[10px] text-on-surface-variant mt-1 leading-relaxed max-w-xl">{{ trans('messages.staff_profile_calendar_hint', [], session('locale')) }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" id="spAvailPrev" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-outline-variant/20 bg-white text-on-surface hover:bg-surface-container-low">
                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                        </button>
                        <span id="spAvailLabel" class="text-[11px] sm:text-xs font-extrabold text-on-surface min-w-[10rem] sm:min-w-[14rem] text-center tabular-nums px-1"></span>
                        <button type="button" id="spAvailNext" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-outline-variant/20 bg-white text-on-surface hover:bg-surface-container-low">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 mb-3 text-[10px] font-extrabold uppercase tracking-wide text-slate-800">
                    <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-red-900 bg-red-600 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_full', [], session('locale')) }}</span>
                    <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-amber-900 bg-amber-500 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_partial', [], session('locale')) }}</span>
                    <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-slate-600 bg-slate-300 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_free', [], session('locale')) }}</span>
                </div>
                <div id="spAvailCalendar" class="w-full"></div>
            </div>
        </section>

        {{-- Day Detail Modal --}}
        <div id="spDayModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45 backdrop-blur-[2px]" aria-hidden="true">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col border border-outline-variant/15">
                <div class="flex items-start justify-between gap-3 px-5 py-3.5 border-b border-outline-variant/10 bg-surface-container-lowest">
                    <div>
                        <h4 id="spDayModalTitle" class="text-sm font-headline font-extrabold text-on-surface"></h4>
                        <p id="spDayModalSub" class="text-[11px] text-on-surface-variant mt-0.5"></p>
                    </div>
                    <button type="button" id="spDayModalClose" class="shrink-0 w-9 h-9 rounded-xl border border-outline-variant/15 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-[22px]">close</span>
                    </button>
                </div>
                <div class="overflow-y-auto p-5 space-y-5">
                    {{-- Timeline --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-2">{{ trans('messages.saloon_booking_availability_timeline', [], session('locale')) }}</p>
                        <div class="relative rounded-xl border-2 border-slate-400 bg-slate-100 p-2 overflow-x-auto">
                            <div id="spDayTimelineLabels" class="relative h-5 mb-1 text-[9px] text-slate-800 font-extrabold"></div>
                            <div id="spDayTimelineBar" class="relative h-10 rounded-lg overflow-hidden"></div>
                        </div>
                    </div>
                    {{-- Bookings summary for the day --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-2">{{ trans('messages.staff_profile_day_bookings', [], session('locale')) }}</p>
                        <div id="spDayBookingsTable" class="overflow-x-auto rounded-xl border border-outline-variant/15"></div>
                    </div>
                    {{-- Slots grid --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-2">{{ trans('messages.saloon_booking_availability_slots', [], session('locale')) }}</p>
                        <div id="spDaySlotsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 text-[10px]"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bookings Table --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="px-5 sm:px-7 pt-5 sm:pt-7 pb-3">
                <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.staff_profile_all_bookings', [], session('locale')) }}</h2>
            </div>
            <div class="overflow-x-auto" id="sp-data-table">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_booking_no', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_customer', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_phone', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_team', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_date', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_time', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.view_bookings_total', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.view_bookings_paid', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.view_bookings_remaining', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.view_bookings_status', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface">
                        @forelse($bookings as $index => $b)
                            <tr data-id="{{ $b->id }}" class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-4 py-5 text-sm">{{ $bookings->firstItem() + $index }}</td>
                                <td class="px-4 py-5 text-sm font-semibold">{{ $b->booking_no }}</td>
                                <td class="px-4 py-5 text-sm">{{ $b->customer->name ?? '—' }}</td>
                                <td class="px-4 py-5 text-sm">{{ $b->customer->phone ?? '—' }}</td>
                                <td class="px-4 py-5 text-sm">{{ $b->salonTeam?->displayName(session('locale')) ?? '—' }}</td>
                                <td class="px-4 py-5 text-sm">{{ $b->booking_date ? $b->booking_date->format('Y-m-d') : '—' }}</td>
                                <td class="px-4 py-5 text-sm whitespace-nowrap">{{ $b->bookingTimeRangeDisplay() }}</td>
                                <td class="px-4 py-5 text-sm text-right font-medium">{{ number_format((float) $b->total_services_amount, 3) }}</td>
                                <td class="px-4 py-5 text-sm text-right">{{ number_format((float) $b->total_paid, 3) }}</td>
                                <td class="px-4 py-5 text-sm text-right">{{ number_format((float) $b->total_remaining, 3) }}</td>
                                <td class="px-4 py-5 text-sm text-center">
                                    @if($b->status === 'draft')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-900">{{ trans('messages.view_bookings_status_draft', [], session('locale')) }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-900">{{ trans('messages.view_bookings_status_confirmed', [], session('locale')) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-5 text-center whitespace-nowrap">
                                    <button type="button" class="sp-view-services-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_services', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">list_alt</span>
                                    </button>
                                    <button type="button" class="sp-view-payments-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_payments', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">payments</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-6 py-6 text-center text-on-surface-variant">
                                    {{ trans('messages.view_bookings_empty', [], session('locale')) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-surface" id="sp-data-pagination">
                <span class="text-xs text-on-surface-variant font-medium">
                    {{ trans('messages.view_bookings_showing', [], session('locale')) }}
                    {{ $bookings->firstItem() ?? 0 }} {{ trans('messages.view_bookings_to', [], session('locale')) }} {{ $bookings->lastItem() ?? 0 }}
                    {{ trans('messages.view_bookings_of', [], session('locale')) }} {{ $bookings->total() }}
                    {{ trans('messages.view_bookings_records', [], session('locale')) }}
                </span>

                <div class="flex items-center gap-2">
                    @if ($bookings->onFirstPage())
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $bookings->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant sp-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($bookings->getUrlRange(1, $bookings->lastPage()) as $page => $url)
                        @if ($page == $bookings->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant sp-page-link">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($bookings->hasMorePages())
                        <a href="{{ $bookings->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant sp-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </span>
                    @endif
                </div>
            </div>
        </section>
    </div>
</main>

@include('layouts.salon_footer')
@include('custom_js.salon_staff_profile_js')
@endsection
