@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.view_customer_lang', [], session('locale')) }} - {{ $customer->name }}</title>
@endpush

<main class="min-h-screen bg-surface">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex justify-between items-center w-full px-8 py-4 sticky top-0 z-10">
        <div>
            <h1 class="text-xl font-bold text-on-surface">{{ $customer->name }}</h1>
            <p class="text-sm text-on-surface-variant">{{ $customer->phone ?: '—' }}</p>
        </div>
        <a href="{{ route('saloncustomer.index') }}" class="bg-white border border-outline-variant/20 px-4 py-2 rounded-lg text-sm font-semibold">
            Back
        </a>
    </header>

    <div class="px-8 py-8 space-y-6">
        <section class="bg-white rounded-xl p-5 editorial-shadow border border-outline-variant/15">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl bg-surface-container-low p-4">
                    <p class="text-xs uppercase tracking-wider text-on-surface-variant">Customer</p>
                    <p class="font-bold text-lg text-on-surface">{{ $customer->name }}</p>
                    <p class="text-sm text-on-surface-variant">{{ $customer->phone ?: '—' }}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                    <p class="text-xs uppercase tracking-wider text-on-surface-variant">Total bookings</p>
                    <p class="font-bold text-2xl text-on-surface">{{ $totals['bookings'] }}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                    <p class="text-xs uppercase tracking-wider text-on-surface-variant">Total payment received</p>
                    <p class="font-bold text-2xl text-green-700">{{ number_format((float) $totals['paid'], 3) }}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                    <p class="text-xs uppercase tracking-wider text-on-surface-variant">Total remaining</p>
                    <p class="font-bold text-2xl text-amber-700">{{ number_format((float) $totals['remaining'], 3) }}</p>
                </div>
            </div>
            @if(!empty($customer->notes))
                <div class="mt-4 border-t border-outline-variant/10 pt-3">
                    <p class="text-xs uppercase tracking-wider text-on-surface-variant">Notes</p>
                    <p class="text-sm text-on-surface mt-1">{{ $customer->notes }}</p>
                </div>
            @endif
        </section>

        <section class="bg-white rounded-xl editorial-shadow border border-outline-variant/15 overflow-hidden">
            <div class="px-5 pt-5 border-b border-outline-variant/10">
                <div class="flex items-center gap-2">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'bookings', 'payments_page' => null]) }}"
                        class="px-4 py-2 rounded-t-lg text-sm font-semibold {{ $activeTab === 'bookings' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface-variant' }}">
                        Bookings
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'payments', 'bookings_page' => null]) }}"
                        class="px-4 py-2 rounded-t-lg text-sm font-semibold {{ $activeTab === 'payments' ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface-variant' }}">
                        Payment history
                    </a>
                </div>
            </div>

            @if($activeTab === 'bookings')
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low">
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_booking_no', [], session('locale')) }}</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_staff', [], session('locale')) }}</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.view_bookings_team', [], session('locale')) }}</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Date & Time</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">Payment details</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.view_bookings_status', [], session('locale')) }}</th>
                                <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface">
                            @forelse($bookings as $index => $booking)
                                <tr>
                                    <td class="px-4 py-5 text-sm">{{ $bookings->firstItem() + $index }}</td>
                                    <td class="px-4 py-5 text-sm font-semibold">{{ $booking->booking_no }}</td>
                                    <td class="px-4 py-5 text-sm">{{ \App\Models\SaloonBooking::formatBookingStaffNames($booking, $staffById ?? collect()) }}</td>
                                    <td class="px-4 py-5 text-sm">{{ $booking->salonTeam?->displayName(session('locale')) ?? '—' }}</td>
                                    <td class="px-4 py-5 text-sm">
                                        <div>{{ optional($booking->booking_date)->format('Y-m-d') ?: '—' }}</div>
                                        <div class="text-xs text-on-surface-variant">{{ $booking->bookingTimeRangeDisplay() }}</div>
                                    </td>
                                    <td class="px-4 py-5 text-sm">
                                        <div class="text-xs">{{ trans('messages.view_bookings_total', [], session('locale')) }}: <span class="font-semibold">{{ number_format((float) $booking->total_services_amount, 3) }}</span></div>
                                        <div class="text-xs">{{ trans('messages.view_bookings_paid', [], session('locale')) }}: <span class="font-semibold">{{ number_format((float) $booking->total_paid, 3) }}</span></div>
                                        <div class="text-xs">{{ trans('messages.view_bookings_remaining', [], session('locale')) }}: <span class="font-semibold">{{ number_format((float) $booking->total_remaining, 3) }}</span></div>
                                    </td>
                                    <td class="px-4 py-5 text-sm text-center">
                                        @if($booking->status === 'draft')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-900">{{ trans('messages.view_bookings_status_draft', [], session('locale')) }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-900">{{ trans('messages.view_bookings_status_confirmed', [], session('locale')) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        @php
                                            $services = data_get($booking->detail->first(), 'services_json', []);
                                        @endphp
                                        <button
                                            type="button"
                                            class="services-popup-btn icon-btn hover:text-primary"
                                            data-booking="{{ $booking->booking_no }}"
                                            data-services='@json($services)'>
                                            <span class="material-symbols-outlined text-[20px]">list_alt</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-on-surface-variant">No bookings found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-8 py-5 flex items-center justify-between border-t border-surface">
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
                            <a href="{{ $bookings->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-sm">chevron_left</span>
                            </a>
                        @endif
                        @foreach ($bookings->getUrlRange(1, $bookings->lastPage()) as $page => $url)
                            @if ($page == $bookings->currentPage())
                                <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if ($bookings->hasMorePages())
                            <a href="{{ $bookings->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </a>
                        @else
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low">
                                <th class="px-5 py-4 text-xs font-bold uppercase">Date</th>
                                <th class="px-5 py-4 text-xs font-bold uppercase">Booking #</th>
                                <th class="px-5 py-4 text-xs font-bold uppercase">Method</th>
                                <th class="px-5 py-4 text-xs font-bold uppercase">Account</th>
                                <th class="px-5 py-4 text-xs font-bold uppercase text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface">
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="px-5 py-4">{{ optional($payment->payment_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td class="px-5 py-4">{{ optional($payment->booking)->booking_no ?: '—' }}</td>
                                    <td class="px-5 py-4">{{ $payment->payment_method ?: '—' }}</td>
                                    <td class="px-5 py-4">{{ optional($payment->account)->account_name ?: '—' }}</td>
                                    <td class="px-5 py-4 text-right">{{ number_format((float) $payment->amount, 3) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-on-surface-variant">No payment history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-outline-variant/10">
                    {{ $payments->links() }}
                </div>
            @endif
        </section>
    </div>
</main>

<div id="servicesModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45">
    <div class="bg-white rounded-xl shadow-xl max-w-xl w-full border border-outline-variant/15">
        <div class="px-4 py-3 border-b border-outline-variant/10 flex items-center justify-between">
            <h4 id="servicesModalTitle" class="font-bold text-on-surface">Services</h4>
            <button type="button" id="servicesModalClose" class="w-8 h-8 rounded-lg border border-outline-variant/20 inline-flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div id="servicesModalBody" class="p-4 space-y-2 max-h-[60vh] overflow-auto"></div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('servicesModal');
        const closeBtn = document.getElementById('servicesModalClose');
        const titleEl = document.getElementById('servicesModalTitle');
        const bodyEl = document.getElementById('servicesModalBody');
        if (!modal || !closeBtn || !titleEl || !bodyEl) return;

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openModal(bookingNo, services) {
            titleEl.textContent = 'Services - ' + (bookingNo || '');
            if (!Array.isArray(services) || services.length === 0) {
                bodyEl.innerHTML = '<p class="text-sm text-on-surface-variant">No services found.</p>';
            } else {
                bodyEl.innerHTML = services.map(function (s, idx) {
                    const nm = (s && s.name) ? s.name : '—';
                    const pr = (s && s.price !== undefined && s.price !== null) ? Number(s.price).toFixed(3) : '0.000';
                    return '<div class="rounded-lg border border-outline-variant/15 p-3 flex items-center justify-between"><span class="text-sm"><strong>' + (idx + 1) + '.</strong> ' + nm + '</span><span class="text-sm font-semibold">' + pr + '</span></div>';
                }).join('');
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        document.querySelectorAll('.services-popup-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const bookingNo = btn.getAttribute('data-booking') || '';
                let services = [];
                try {
                    services = JSON.parse(btn.getAttribute('data-services') || '[]');
                } catch (e) {
                    services = [];
                }
                openModal(bookingNo, services);
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    })();
</script>

@include('layouts.salon_footer')
@endsection
