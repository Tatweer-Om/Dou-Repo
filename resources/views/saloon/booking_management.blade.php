@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.booking_management_title', [], session('locale')) }}</title>
@endpush

@php
    $loc = session('locale');
@endphp

<main class="min-h-screen bg-surface pt-10">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] border-b border-outline-variant/10">
        <div class="px-8 py-5 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-primary mb-1">{{ trans('messages.booking_management_subtitle', [], $loc) }}</p>
                    <h1 class="text-2xl font-headline font-extrabold text-on-surface">{{ trans('messages.booking_management_title', [], $loc) }}</h1>
                    <p class="text-sm text-on-surface-variant mt-1">{{ trans('messages.booking_management_date_label', [], $loc) }}:
                        <span class="font-semibold text-on-surface">{{ \Carbon\Carbon::parse($dateStr)->locale($loc === 'ar' ? 'ar' : 'en')->translatedFormat('l, j F Y') }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('booking_management', ['date' => $dateStr, 'team' => $teamFilter]) }}"
                        class="inline-flex items-center gap-2 rounded-full border border-outline-variant/20 bg-white px-4 py-2 text-xs font-bold text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                        {{ trans('messages.refresh', [], $loc) }}
                    </a>
                    <a href="{{ route('view_bookings') }}"
                        class="inline-flex items-center gap-2 rounded-full border border-outline-variant/20 bg-white px-4 py-2 text-xs font-bold text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[18px]">list</span>
                        {{ trans('messages.view_bookings_title', [], $loc) }}
                    </a>
                    <a href="{{ route('saloon_booking_page') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-br from-primary to-primary-container px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:opacity-95 transition-opacity">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        {{ trans('messages.saloon_booking_new_booking', [], $loc) }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="px-8 py-10 space-y-8">
        @include('saloon._booking_schedule_panel', [
            'scheduleFormAction' => route('booking_management'),
            'scheduleFormId' => 'mgmt-filter-form',
            'scheduleDateInputId' => 'mgmt-date',
            'scheduleTeamSelectId' => 'mgmt-team',
            'showSectionHeading' => false,
            'loc' => $loc,
        ])

        {{-- Detail table --}}
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden border border-outline-variant/10">
            <div class="px-6 py-4 border-b border-outline-variant/10">
                <h2 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.booking_management_table_title', [], $loc) }}</h2>
                <p class="text-xs text-on-surface-variant mt-0.5">{{ trans('messages.booking_management_table_subtitle', [], $loc) }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1100px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">#</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_booking_no', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_time', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_customer', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_phone', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_staff', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary whitespace-nowrap">{{ trans('messages.view_bookings_team', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary min-w-[180px]">{{ trans('messages.booking_management_col_services', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary min-w-[200px]">{{ trans('messages.booking_management_col_payments', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary text-right whitespace-nowrap">{{ trans('messages.view_bookings_total', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary text-right whitespace-nowrap">{{ trans('messages.view_bookings_paid', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary text-right whitespace-nowrap">{{ trans('messages.view_bookings_remaining', [], $loc) }}</th>
                            <th class="px-4 py-4 text-[10px] font-bold uppercase tracking-[0.1em] text-primary text-center whitespace-nowrap">{{ trans('messages.view_bookings_status', [], $loc) }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($bookings as $index => $b)
                            @php
                                $detailRow = $b->detail->first();
                                $servicesList = $detailRow && is_array($detailRow->services_json) ? $detailRow->services_json : [];
                                $bookingTimeLabel = $b->bookingTimeRangeDisplay();
                            @endphp
                            <tr class="hover:bg-surface-container-low/60 transition-colors align-top">
                                <td class="px-4 py-4 text-xs text-on-surface-variant">{{ $index + 1 }}</td>
                                <td class="px-4 py-4 text-xs font-bold text-on-surface whitespace-nowrap">{{ $b->booking_no }}</td>
                                <td class="px-4 py-4 text-xs font-semibold">
                                    @if($bookingTimeLabel !== '—')
                                        <span class="whitespace-nowrap">{{ $bookingTimeLabel }}</span>
                                    @else
                                        <span class="text-amber-700 font-bold whitespace-nowrap">{{ trans('messages.booking_management_no_time', [], $loc) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-xs font-medium">{{ $b->customer->name ?? '—' }}</td>
                                <td class="px-4 py-4 text-xs whitespace-nowrap">{{ $b->customer->phone ?? '—' }}</td>
                                <td class="px-4 py-4 text-xs">{{ \App\Models\SaloonBooking::formatBookingStaffNames($b, $staffById ?? collect()) }}</td>
                                <td class="px-4 py-4 text-xs">{{ $b->salonTeam?->displayName($loc) ?? '—' }}</td>
                                <td class="px-4 py-4 text-[11px] text-on-surface">
                                    @forelse($servicesList as $svc)
                                        <div class="py-0.5 border-b border-outline-variant/5 last:border-0">
                                            <span class="font-semibold">{{ $svc['name'] ?? '—' }}</span>
                                            @if(isset($svc['price']))
                                                <span class="text-on-surface-variant"> · {{ number_format((float) $svc['price'], 3) }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-on-surface-variant">—</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-4 text-[11px] text-on-surface">
                                    @forelse($b->payments as $pay)
                                        <div class="py-0.5 border-b border-outline-variant/5 last:border-0">
                                            <span class="font-semibold">{{ $pay->payment_method }}</span>
                                            @if($pay->account)
                                                <span class="text-on-surface-variant"> · {{ $pay->account->account_name ?? '' }}</span>
                                            @endif
                                            <span class="block text-primary font-bold">{{ number_format((float) $pay->amount, 3) }} {{ trans('messages.saloon_booking_currency_omr', [], $loc) }}</span>
                                            @if($pay->payment_at)
                                                <span class="text-[10px] text-on-surface-variant">{{ $pay->payment_at->format('Y-m-d H:i') }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-on-surface-variant">{{ trans('messages.view_bookings_no_payments', [], $loc) }}</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-4 text-xs text-right font-bold whitespace-nowrap">{{ number_format((float) $b->total_services_amount, 3) }}</td>
                                <td class="px-4 py-4 text-xs text-right whitespace-nowrap">{{ number_format((float) $b->total_paid, 3) }}</td>
                                <td class="px-4 py-4 text-xs text-right whitespace-nowrap">{{ number_format((float) $b->total_remaining, 3) }}</td>
                                <td class="px-4 py-4 text-center">
                                    @if($b->status === 'draft')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-100 text-amber-900">{{ trans('messages.view_bookings_status_draft', [], $loc) }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-100 text-emerald-900">{{ trans('messages.view_bookings_status_confirmed', [], $loc) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    {{ trans('messages.booking_management_empty', [], $loc) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

@include('layouts.salon_footer')
@endsection
