@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.view_bookings_title', [], session('locale')) }}</title>
@endpush


<main class="min-h-screen bg-surface pt-10">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-4 w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex flex-wrap items-center gap-4 flex-1 min-w-0">
            <a href="{{ route('booking_management') }}"
                class="shrink-0 rounded-full border border-outline-variant/20 bg-white dark:bg-surface-container-low px-5 py-2.5 text-sm font-bold text-on-surface hover:bg-surface-container transition-colors inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">calendar_view_day</span>
                {{ trans('messages.booking_management_title', [], session('locale')) }}
            </a>
            <a href="{{ route('saloon_booking_page') }}"
                class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200 inline-block shrink-0">
                {{ trans('messages.saloon_booking_new_booking', [], session('locale')) }}
            </a>
            <form id="bookings-search-form" action="{{ route('view_bookings') }}" method="get" class="flex items-center gap-2 flex-1 min-w-[200px] max-w-xl">
                <div class="relative flex-1 flex items-center">
                    <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-[20px] pointer-events-none">search</span>
                    <input type="search" name="q" id="bookings-search-q" value="{{ $search ?? '' }}"
                        autocomplete="off"
                        placeholder="{{ trans('messages.view_bookings_search_placeholder', [], session('locale')) }}"
                        class="w-full rounded-full border border-outline-variant/20 bg-white dark:bg-surface-container-low pl-10 pr-4 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant/70 focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                </div>
                <button type="submit" class="shrink-0 rounded-full border border-outline-variant/20 bg-surface-container-low px-5 py-2.5 text-sm font-bold text-on-surface hover:bg-surface-container transition-colors">
                    {{ trans('messages.search', [], session('locale')) }}
                </button>
            </form>
        </div>
    </header>

    <div class="px-8 py-10 space-y-8">
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="overflow-x-auto" id="data-table">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_booking_no', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_customer', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_phone', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_staff', [], session('locale')) }}</th>
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
                                <td class="px-4 py-5 text-sm">{{ \App\Models\SaloonBooking::formatBookingStaffNames($b, $staffById ?? collect()) }}</td>
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
                                    <button type="button" class="view-services-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_services', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">list_alt</span>
                                    </button>
                                    <button type="button" class="view-payments-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_payments', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">payments</span>
                                    </button>
                                    @if((float) $b->total_remaining > 0)
                                        <button type="button"
                                            class="receive-payment-btn icon-btn hover:text-emerald-600"
                                            data-remaining="{{ number_format((float) $b->total_remaining, 3, '.', '') }}"
                                            data-total="{{ number_format((float) $b->total_services_amount, 3, '.', '') }}"
                                            title="Receive payment">
                                            <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                                        </button>
                                    @endif
                                    @if($b->status === 'draft')
                                        <button type="button" class="approve-booking-btn icon-btn hover:text-emerald-600" title="{{ trans('messages.view_bookings_approve', [], session('locale')) }}">
                                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                        </button>
                                        <button type="button" class="delete-booking-btn icon-btn hover:text-red-600" title="{{ trans('messages.view_bookings_delete', [], session('locale')) }}">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-6 text-center text-on-surface-variant">
                                    {{ trans('messages.view_bookings_empty', [], session('locale')) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-surface" id="data-pagination">
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
                        <a href="{{ $bookings->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($bookings->getUrlRange(1, $bookings->lastPage()) as $page => $url)
                        @if ($page == $bookings->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant dress-page-link">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($bookings->hasMorePages())
                        <a href="{{ $bookings->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
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
@include('custom_js.saloon_bookings_list_js')
@endsection
