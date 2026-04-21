<div class="overflow-x-auto" id="mir-data-table">
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
                        <button type="button" class="mir-view-services-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_services', [], session('locale')) }}">
                            <span class="material-symbols-outlined text-[20px]">list_alt</span>
                        </button>
                        <button type="button" class="mir-view-payments-btn icon-btn hover:text-primary" title="{{ trans('messages.view_bookings_payments', [], session('locale')) }}">
                            <span class="material-symbols-outlined text-[20px]">payments</span>
                        </button>
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

<div class="px-8 py-5 flex items-center justify-between border-t border-surface" id="mir-data-pagination">
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
            <a href="{{ $bookings->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant mir-page-link">
                <span class="material-symbols-outlined text-sm">chevron_left</span>
            </a>
        @endif

        @foreach ($bookings->getUrlRange(1, $bookings->lastPage()) as $page => $url)
            @if ($page == $bookings->currentPage())
                <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant mir-page-link">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        @if ($bookings->hasMorePages())
            <a href="{{ $bookings->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant mir-page-link">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        @else
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </span>
        @endif
    </div>
</div>
