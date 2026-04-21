@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.saloon', [], session('locale')) }}</title>
@endpush
@php $__bookingToday = now()->format('Y-m-d'); @endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    /* Saloon booking: Flatpickr aligned with primary */
    .salon-booking-fp.flatpickr-calendar {
        box-shadow: 0 18px 50px rgba(17, 24, 39, 0.14);
        border-radius: 16px;
        border: 1px solid rgba(215, 193, 195, 0.55);
    }
    .salon-booking-fp .flatpickr-months { border-radius: 16px 16px 0 0; padding: 4px 0 2px; }
    .salon-booking-fp .flatpickr-current-month { font-weight: 800; font-family: Manrope, Inter, sans-serif; }
    .salon-booking-fp .flatpickr-weekdays { padding-top: 4px; }
    .salon-booking-fp .flatpickr-day.selected,
    .salon-booking-fp .flatpickr-day.startRange,
    .salon-booking-fp .flatpickr-day.endRange {
        background: #8a4853;
        border-color: #8a4853;
        box-shadow: 0 2px 8px rgba(138, 72, 83, 0.35);
    }
    .salon-booking-fp .flatpickr-day:hover:not(.selected):not(.flatpickr-disabled) {
        background: rgba(138, 72, 83, 0.12);
        border-color: rgba(138, 72, 83, 0.25);
    }
    .salon-booking-fp .flatpickr-today:not(.selected) {
        border-color: #8a4853;
    }
    .salon-booking-time {
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
    }
</style>

<main class="min-w-0 min-h-[calc(100vh-88px)] px-3 sm:px-5 py-6 sm:py-8">
    <div class="w-full max-w-screen-2xl mx-auto">
        <div class="text-center mb-5">
            <p class="text-primary font-headline tracking-[0.2em] text-[10px] font-extrabold uppercase">{{ trans('messages.saloon_booking_heading', [], session('locale')) }}</p>
        </div>

        <div class="rounded-[24px] border border-outline-variant/20 bg-white/95 shadow-[0_14px_45px_rgba(17,24,39,0.08)] overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-primary via-primary-container to-primary"></div>
            <form id="bookingForm" class="p-4 sm:p-5 space-y-4" novalidate>
                @csrf
                <input id="selectedCustomerId" type="hidden" value="">

                <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.saloon_booking_customer', [], session('locale')) }}</h3>
                        <span class="text-[10px] uppercase tracking-[0.16em] text-on-surface-variant">{{ trans('messages.saloon_booking_customer_search_label', [], session('locale')) }}</span>
                    </div>
                    <div class="space-y-2">
                        <label for="customerSearch" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_search_customer', [], session('locale')) }}</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input id="customerSearch" type="text" autocomplete="off" placeholder="{{ trans('messages.saloon_booking_customer_search_placeholder', [], session('locale')) }}" class="w-full rounded-xl border border-outline-variant/15 bg-white py-2.5 pl-10 pr-3 text-sm text-on-surface placeholder:text-outline/60 focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                            <div id="customerResults" class="hidden absolute z-20 mt-1 w-full rounded-xl border border-outline-variant/20 bg-white shadow-lg max-h-44 overflow-auto"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
                        <input id="selectedCustomerName" name="customer_name" type="text" placeholder="{{ trans('messages.saloon_booking_customer_name_placeholder', [], session('locale')) }}" class="w-full rounded-xl border border-outline-variant/15 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                        <input id="selectedCustomerPhone" name="customer_phone" type="tel" placeholder="{{ trans('messages.saloon_booking_customer_phone_placeholder', [], session('locale')) }}" class="w-full rounded-xl border border-outline-variant/15 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                    </div>
                </section>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                        <h3 class="text-sm font-headline font-extrabold text-on-surface mb-3">{{ trans('messages.saloon_booking_staff', [], session('locale')) }}</h3>
                        <span class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block mb-1">{{ trans('messages.saloon_booking_salon_staff', [], session('locale')) }}</span>
                        <div id="staffMultiRoot" class="relative">
                            <button type="button" id="staffMultiTrigger" aria-expanded="false" aria-haspopup="listbox"
                                class="staff-multi-trigger min-h-[3rem] w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2 text-left flex flex-wrap items-center gap-2 gap-y-2 hover:border-outline-variant/25 transition-colors focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10 [&[aria-expanded='true']_.staff-multi-chevron]:rotate-180">
                                <div id="staffMultiChips" class="flex flex-wrap gap-1.5 flex-1 items-center min-w-0">
                                    <span id="staffMultiPlaceholder" class="text-sm text-on-surface-variant/75">{{ trans('messages.saloon_booking_staff_trigger_placeholder', [], session('locale')) }}</span>
                                </div>
                                <span class="material-symbols-outlined text-on-surface-variant shrink-0 text-[22px] staff-multi-chevron transition-transform duration-200">expand_more</span>
                            </button>
                            <div id="staffMultiPanel" class="hidden absolute left-0 right-0 top-[calc(100%+6px)] z-40 rounded-xl border border-outline-variant/20 bg-white shadow-[0_16px_48px_rgba(17,24,39,0.14)] overflow-hidden ring-1 ring-black/[0.03]">
                                <div class="p-2.5 border-b border-outline-variant/10 bg-surface-container-lowest/80">
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px] pointer-events-none">search</span>
                                        <input type="text" id="staffMultiSearch" autocomplete="off" enterkeyhint="search"
                                            placeholder="{{ trans('messages.saloon_booking_staff_search_placeholder', [], session('locale')) }}"
                                            class="w-full rounded-lg border border-outline-variant/15 bg-white py-2.5 pl-10 pr-3 text-sm text-on-surface placeholder:text-outline/55 focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10" />
                                    </div>
                                </div>
                                <ul id="staffMultiList" role="listbox" aria-multiselectable="true" class="max-h-[min(14rem,45vh)] overflow-y-auto py-1.5 overscroll-contain"></ul>
                                <div class="px-3 py-2 border-t border-outline-variant/10 bg-surface-container-lowest/50 flex justify-end">
                                    <button type="button" id="staffMultiClear" class="text-xs font-bold text-on-surface-variant hover:text-primary uppercase tracking-wide">{{ trans('messages.saloon_booking_staff_clear_all', [], session('locale')) }}</button>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-on-surface-variant mt-1.5 leading-relaxed">{{ trans('messages.saloon_booking_staff_multi_hint', [], session('locale')) }}</p>
                    </section>

                    <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                        <h3 class="text-sm font-headline font-extrabold text-on-surface mb-3">{{ trans('messages.saloon_booking_schedule', [], session('locale')) }}</h3>
                        <div class="space-y-3">
                            <div class="space-y-1">
                                <label for="bookingDate" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_date', [], session('locale')) }}</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">calendar_month</span>
                                    <input id="bookingDate" name="booking_date" type="text" readonly="readonly" autocomplete="off" value="{{ $__bookingToday }}"
                                        class="w-full cursor-pointer rounded-xl border border-outline-variant/15 bg-white py-2.5 pl-11 pr-10 text-sm font-semibold tabular-nums text-on-surface focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 text-[20px]">expand_more</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-on-surface-variant leading-relaxed">{{ trans('messages.saloon_booking_time_type_hint', [], session('locale')) }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label for="bookingTimeFrom" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_time_from', [], session('locale')) }}</label>
                                    <input id="bookingTimeFrom" name="booking_time_from" type="time" step="60" value="13:00" inputmode="numeric"
                                        class="salon-booking-time w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                                </div>
                                <div class="space-y-1">
                                    <label for="bookingTimeTo" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_time_to', [], session('locale')) }}</label>
                                    <input id="bookingTimeTo" name="booking_time_to" type="time" step="60" value="15:30" inputmode="numeric"
                                        class="salon-booking-time w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <section id="staffAvailabilitySection" class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.saloon_booking_availability_title', [], session('locale')) }}</h3>
                            <p class="text-[10px] text-on-surface-variant mt-1 leading-relaxed max-w-xl">{{ trans('messages.saloon_booking_availability_hint', [], session('locale')) }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" id="availMonthPrev" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-outline-variant/20 bg-white text-on-surface hover:bg-surface-container-low" aria-label="{{ trans('messages.saloon_booking_availability_prev', [], session('locale')) }}">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </button>
                            <span id="availMonthLabel" class="text-[11px] sm:text-xs font-extrabold text-on-surface min-w-[10rem] sm:min-w-[14rem] text-center tabular-nums px-1"></span>
                            <button type="button" id="availMonthNext" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-outline-variant/20 bg-white text-on-surface hover:bg-surface-container-low" aria-label="{{ trans('messages.saloon_booking_availability_next', [], session('locale')) }}">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mb-3 text-[10px] font-extrabold uppercase tracking-wide text-slate-800">
                        <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-red-900 bg-red-600 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_full', [], session('locale')) }}</span>
                        <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-amber-900 bg-amber-500 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_partial', [], session('locale')) }}</span>
                        <span class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded border-2 border-slate-600 bg-slate-300 shadow-sm shrink-0"></span>{{ trans('messages.saloon_booking_availability_legend_free', [], session('locale')) }}</span>
                    </div>
                    <p id="staffAvailabilityPlaceholder" class="text-xs text-on-surface-variant py-4 text-center rounded-xl border border-dashed border-outline-variant/25 bg-white/60">{{ trans('messages.saloon_booking_availability_select_staff', [], session('locale')) }}</p>
                    <div id="staffAvailabilityCalendars" class="hidden w-full space-y-3"></div>
                </section>

                <div id="availDayModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/45 backdrop-blur-[2px]" aria-hidden="true">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col border border-outline-variant/15">
                        <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-outline-variant/10 bg-surface-container-lowest">
                            <div>
                                <h4 id="availDayModalTitle" class="text-sm font-headline font-extrabold text-on-surface"></h4>
                                <p id="availDayModalSub" class="text-[11px] text-on-surface-variant mt-0.5"></p>
                            </div>
                            <button type="button" id="availDayModalClose" class="shrink-0 w-9 h-9 rounded-xl border border-outline-variant/15 flex items-center justify-center text-on-surface-variant hover:bg-surface-container-low">
                                <span class="material-symbols-outlined text-[22px]">close</span>
                            </button>
                        </div>
                        <div class="overflow-y-auto p-4 space-y-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-2">{{ trans('messages.saloon_booking_availability_timeline', [], session('locale')) }}</p>
                                <div class="relative rounded-xl border-2 border-slate-400 bg-slate-100 p-2 overflow-x-auto">
                                    <div id="availDayTimelineLabels" class="relative h-5 mb-1 text-[9px] text-slate-800 font-extrabold"></div>
                                    <div id="availDayTimelineBar" class="relative h-10 rounded-lg overflow-hidden"></div>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant mb-2">{{ trans('messages.saloon_booking_availability_slots', [], session('locale')) }}</p>
                                <div id="availDaySlotsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 text-[10px]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-headline font-extrabold text-on-surface">{{ trans('messages.saloon_booking_services', [], session('locale')) }}</h3>
                        <button id="addServiceBtn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-outline-variant/20 bg-white px-2.5 py-1.5 text-xs font-bold text-primary hover:bg-primary/5">
                            <span class="material-symbols-outlined text-[16px]">add</span>{{ trans('messages.saloon_booking_add', [], session('locale')) }}
                        </button>
                    </div>

                    <div id="servicesWrapper" class="space-y-2"></div>

                    <div class="mt-3 rounded-xl border border-primary/15 bg-primary/5 p-2.5 text-xs text-on-surface">
                        <div class="flex items-center justify-between">
                            <span>{{ trans('messages.saloon_booking_total_services', [], session('locale')) }}</span>
                            <span id="servicesTotalText" class="font-bold">0.000 {{ trans('messages.saloon_booking_currency_omr', [], session('locale')) }}</span>
                        </div>
                    </div>
                </section>

              

                <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                    <h3 class="text-sm font-headline font-extrabold text-on-surface mb-2">{{ trans('messages.saloon_booking_notes_section', [], session('locale')) }}</h3>
                    <textarea id="specialNotes" name="special_notes" rows="3" placeholder="{{ trans('messages.saloon_booking_notes_placeholder', [], session('locale')) }}" class="w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10"></textarea>
                </section>

                <section class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-3 sm:p-4">
                    <h3 class="text-sm font-headline font-extrabold text-on-surface mb-3">{{ trans('messages.saloon_booking_payment', [], session('locale')) }}</h3>
                    <p class="text-[11px] text-on-surface-variant mb-3">{{ trans('messages.saloon_booking_payment_help', [], session('locale')) }}</p>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
                        <div class="lg:col-span-4 space-y-1">
                            <label for="paymentAccountSelect" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_payment_method', [], session('locale')) }}</label>
                            <select id="paymentAccountSelect" name="payment_account_id" class="w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10">
                                <option value="">{{ trans('messages.saloon_booking_no_payment', [], session('locale')) }}</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }}{{ $account->account_no ? ' — ' . $account->account_no : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-4 space-y-1">
                            <label for="bookingTotalAmount" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_total_amount', [], session('locale')) }}</label>
                            <input id="bookingTotalAmount" type="text" readonly tabindex="-1" class="w-full rounded-xl border border-outline-variant/15 bg-surface-container-low px-3 py-2.5 text-sm font-semibold text-on-surface" value="{{ trans('messages.saloon_booking_amount_placeholder', [], session('locale')) }}">
                        </div>
                        <div class="lg:col-span-4 space-y-1">
                            <label for="paymentPaidAmount" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_paid_amount', [], session('locale')) }}</label>
                            <input id="paymentPaidAmount" type="number" step="0.001" min="0" value="0" class="w-full rounded-xl border border-outline-variant/15 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-primary/30 focus:ring-2 focus:ring-primary/10" placeholder="{{ trans('messages.saloon_booking_amount_placeholder', [], session('locale')) }}">
                        </div>
                    </div>
                    <div class="mt-3 space-y-1 max-w-xs">
                        <label for="paymentRemainingAmount" class="text-[11px] font-bold tracking-[0.14em] uppercase text-on-surface-variant block">{{ trans('messages.saloon_booking_remaining_amount', [], session('locale')) }}</label>
                        <input id="paymentRemainingAmount" type="text" readonly tabindex="-1" class="w-full rounded-xl border border-primary/15 bg-primary/5 px-3 py-2.5 text-sm font-bold text-primary" value="{{ trans('messages.saloon_booking_amount_placeholder', [], session('locale')) }}">
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="submit" id="confirmBookingBtn" class="w-full sm:w-auto sm:min-w-[180px] inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-primary to-primary-container px-5 py-2.5 text-sm font-bold text-on-primary">
                        {{ trans('messages.saloon_booking_confirm', [], session('locale')) }}
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </button>
                    <button type="button" id="saveDraftBtn" class="w-full sm:w-auto sm:min-w-[130px] rounded-xl border border-outline-variant/15 bg-white px-5 py-2.5 text-sm font-bold text-on-surface">{{ trans('messages.saloon_booking_save_draft', [], session('locale')) }}</button>
                </div>
            </form>
        </div>
    </div>
</main>
@php
    $__sbLoc = session('locale');
    $saloonBookingI18n = [
        'omr' => trans('messages.saloon_booking_currency_omr', [], $__sbLoc),
        'searchService' => trans('messages.saloon_booking_search_service', [], $__sbLoc),
        'price' => trans('messages.saloon_booking_price', [], $__sbLoc),
        'amountPlaceholder' => trans('messages.saloon_booking_amount_placeholder', [], $__sbLoc),
        'noMatch' => trans('messages.saloon_booking_no_match', [], $__sbLoc),
        'errAddService' => trans('messages.saloon_booking_err_add_service', [], $__sbLoc),
        'errPaidNegative' => trans('messages.saloon_booking_err_paid_negative', [], $__sbLoc),
        'errPaidExceeds' => trans('messages.saloon_booking_err_paid_exceeds_total', [], $__sbLoc),
        'errSave' => trans('messages.saloon_booking_err_save', [], $__sbLoc),
        'successSaved' => trans('messages.saloon_booking_success_saved', [], $__sbLoc),
        'draftSaved' => trans('messages.saloon_booking_draft_saved', [], $__sbLoc),
        'errUnexpected' => trans('messages.saloon_booking_err_unexpected', [], $__sbLoc),
        'staffRemoveAria' => trans('messages.saloon_booking_staff_remove_aria', [], $__sbLoc),
        'availabilityLoadError' => trans('messages.saloon_booking_availability_load_error', [], $__sbLoc),
        'slotFreeLabel' => trans('messages.booking_management_slot_free', [], $__sbLoc),
        'availabilityDayTitle' => trans('messages.saloon_booking_availability_day_title', [], $__sbLoc),
        'slotConflictTitle' => trans('messages.saloon_booking_slot_conflict_title', [], $__sbLoc),
        'slotConflictIntro' => trans('messages.saloon_booking_slot_conflict_intro', [], $__sbLoc),
        'slotSuggestedLabel' => trans('messages.saloon_booking_slot_suggested_label', [], $__sbLoc),
        'slotNoAlternative' => trans('messages.saloon_booking_slot_no_alternative', [], $__sbLoc),
        'slotUseSuggested' => trans('messages.saloon_booking_slot_use_suggested', [], $__sbLoc),
        'slotChangeManually' => trans('messages.saloon_booking_slot_change_manually', [], $__sbLoc),
        'slotClose' => trans('messages.saloon_booking_slot_close', [], $__sbLoc),
        'errSlotCheck' => trans('messages.saloon_booking_err_slot_check', [], $__sbLoc),
        'errTimeOrder' => trans('messages.saloon_booking_err_time_order', [], $__sbLoc),
        'errTimeInvalid' => trans('messages.saloon_booking_err_time_invalid', [], $__sbLoc),
    ];
@endphp
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
@if(session('locale') === 'ar')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ar.js"></script>
@endif
<script>
    (function () {
        const saloonBookingI18n = @json($saloonBookingI18n);
        const staffMembers = @json($staff ?? []);
        let getSelectedStaffIds = function () {
            return [];
        };
        const bookingForm = document.getElementById("bookingForm");
        const selectedCustomerId = document.getElementById("selectedCustomerId");
        const services = @json($services ?? []);
        const servicesWrapper = document.getElementById("servicesWrapper");
        const addServiceBtn = document.getElementById("addServiceBtn");
        const servicesTotalText = document.getElementById("servicesTotalText");
        const customerSearch = document.getElementById("customerSearch");
        const customerResults = document.getElementById("customerResults");
        const selectedCustomerName = document.getElementById("selectedCustomerName");
        const selectedCustomerPhone = document.getElementById("selectedCustomerPhone");
        const paymentAccountSelect = document.getElementById("paymentAccountSelect");
        const bookingTotalAmountInput = document.getElementById("bookingTotalAmount");
        const paymentPaidAmountInput = document.getElementById("paymentPaidAmount");
        const paymentRemainingAmountInput = document.getElementById("paymentRemainingAmount");
        const saveDraftBtn = document.getElementById("saveDraftBtn");
        const confirmBookingBtn = document.getElementById("confirmBookingBtn");
        const slotCheckUrl = @json(route('saloon_bookings.availability.check'));
        const viewBookingsUrl = @json(route('view_bookings'));
        let bookingSubmitLocked = false;
        const salonBookingLocale = @json(session('locale', 'en'));

        /** Normalize to HH:mm for Laravel date_format:H:i */
        function normalizeTimeHM(raw) {
            if (raw == null || raw === "") {
                return "";
            }
            const s = String(raw).trim();
            const m = s.match(/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/);
            if (!m) {
                return "";
            }
            const h = parseInt(m[1], 10);
            const mi = parseInt(m[2], 10);
            if (!Number.isFinite(h) || !Number.isFinite(mi) || h < 0 || h > 23 || mi < 0 || mi > 59) {
                return "";
            }
            return String(h).padStart(2, "0") + ":" + String(mi).padStart(2, "0");
        }

        function timeToMinutesHM(raw) {
            const n = normalizeTimeHM(raw);
            if (!n) {
                return null;
            }
            const parts = n.split(":");
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function ensureTimeInputValue(inputEl, value) {
            if (!inputEl || !value) {
                return;
            }
            const n = normalizeTimeHM(value);
            if (n) {
                inputEl.value = n;
            }
        }

        if (typeof flatpickr !== "undefined") {
            const bookingDateInput = document.getElementById("bookingDate");
            if (bookingDateInput) {
                const fpOpts = {
                    dateFormat: "Y-m-d",
                    disableMobile: true,
                    monthSelectorType: "static",
                    onReady: function (selectedDates, dateStr, instance) {
                        if (instance && instance.calendarContainer) {
                            instance.calendarContainer.classList.add("salon-booking-fp");
                        }
                    },
                };
                if (salonBookingLocale === "ar" && flatpickr.l10ns && flatpickr.l10ns.ar) {
                    fpOpts.locale = flatpickr.l10ns.ar;
                }
                if (bookingDateInput.value) {
                    fpOpts.defaultDate = bookingDateInput.value;
                }
                flatpickr(bookingDateInput, fpOpts);
            }
        }

        (function wireBookingTimeBlur() {
            const tf = document.getElementById("bookingTimeFrom");
            const tt = document.getElementById("bookingTimeTo");
            function onTimeBlur(ev) {
                const el = ev.target;
                const n = normalizeTimeHM(el.value);
                if (n) {
                    el.value = n;
                }
            }
            if (tf) {
                tf.addEventListener("blur", onTimeBlur);
            }
            if (tt) {
                tt.addEventListener("blur", onTimeBlur);
            }
        })();

        function escapeBookingHtml(s) {
            return String(s || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }

        function setBookingActionButtonsBusy(busy) {
            if (confirmBookingBtn) {
                confirmBookingBtn.disabled = busy;
                confirmBookingBtn.setAttribute("aria-busy", busy ? "true" : "false");
                confirmBookingBtn.style.opacity = busy ? "0.72" : "";
                confirmBookingBtn.style.pointerEvents = busy ? "none" : "";
            }
            if (saveDraftBtn) {
                saveDraftBtn.disabled = busy;
                saveDraftBtn.setAttribute("aria-busy", busy ? "true" : "false");
                saveDraftBtn.style.opacity = busy ? "0.72" : "";
                saveDraftBtn.style.pointerEvents = busy ? "none" : "";
            }
        }

        let swalBookingLoadPromise = null;
        function ensureSwalLoaded() {
            if (typeof Swal !== "undefined" && Swal.fire) {
                return Promise.resolve(true);
            }
            if (!swalBookingLoadPromise) {
                swalBookingLoadPromise = new Promise(function (resolve) {
                    const s = document.createElement("script");
                    s.src = "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js";
                    s.async = true;
                    s.setAttribute("data-swal-booking", "1");
                    s.onload = function () {
                        resolve(typeof Swal !== "undefined" && !!Swal.fire);
                    };
                    s.onerror = function () {
                        resolve(false);
                    };
                    document.head.appendChild(s);
                });
            }
            return swalBookingLoadPromise;
        }

        async function showSlotConflictDialog(checkData) {
            const swalOk = await ensureSwalLoaded();
            const conflicts = checkData.conflicts || [];
            const conflictsHtml = conflicts
                .map(function (c) {
                    return (
                        '<div class="text-sm text-left">' +
                        escapeBookingHtml("#" + (c.booking_no || "") + " — " + (c.time_label || "")) +
                        "</div>"
                    );
                })
                .join("");
            let suggestedBlock = "";
            if (checkData.suggested_time_from && checkData.suggested_time_to) {
                suggestedBlock =
                    '<p class="mt-3 text-left text-sm"><strong>' +
                    escapeBookingHtml(saloonBookingI18n.slotSuggestedLabel) +
                    "</strong> " +
                    escapeBookingHtml(checkData.suggested_time_from + " – " + checkData.suggested_time_to) +
                    "</p>";
            } else {
                suggestedBlock =
                    '<p class="mt-3 text-left text-sm text-gray-600">' +
                    escapeBookingHtml(saloonBookingI18n.slotNoAlternative) +
                    "</p>";
            }
            const html =
                '<div class="text-left text-sm">' +
                escapeBookingHtml(saloonBookingI18n.slotConflictIntro) +
                '<div class="mt-2 space-y-1">' +
                conflictsHtml +
                "</div>" +
                suggestedBlock +
                "</div>";

            const hasSuggest = !!(checkData.suggested_time_from && checkData.suggested_time_to);
            let result = { isConfirmed: false };
            if (swalOk && typeof Swal !== "undefined") {
                result = await Swal.fire({
                    icon: "warning",
                    title: saloonBookingI18n.slotConflictTitle,
                    html: html,
                    showCancelButton: hasSuggest,
                    confirmButtonText: hasSuggest ? saloonBookingI18n.slotUseSuggested : saloonBookingI18n.slotClose,
                    cancelButtonText: saloonBookingI18n.slotChangeManually,
                });
            } else {
                const plain =
                    (checkData.conflicts || [])
                        .map(function (c) {
                            return "#" + (c.booking_no || "") + " — " + (c.time_label || "");
                        })
                        .join("\n") +
                    (hasSuggest
                        ? "\n" + saloonBookingI18n.slotSuggestedLabel + " " + checkData.suggested_time_from + " – " + checkData.suggested_time_to
                        : "\n" + saloonBookingI18n.slotNoAlternative);
                window.alert(saloonBookingI18n.slotConflictTitle + "\n\n" + plain);
            }
            if (hasSuggest && result.isConfirmed) {
                const tf = document.getElementById("bookingTimeFrom");
                const tt = document.getElementById("bookingTimeTo");
                ensureTimeInputValue(tf, checkData.suggested_time_from);
                ensureTimeInputValue(tt, checkData.suggested_time_to);
            }
        }

        (function initStaffMultiSelect() {
            const root = document.getElementById("staffMultiRoot");
            const trigger = document.getElementById("staffMultiTrigger");
            const panel = document.getElementById("staffMultiPanel");
            const searchInput = document.getElementById("staffMultiSearch");
            const listEl = document.getElementById("staffMultiList");
            const chipsContainer = document.getElementById("staffMultiChips");
            const placeholder = document.getElementById("staffMultiPlaceholder");
            const clearBtn = document.getElementById("staffMultiClear");
            if (!root || !trigger || !panel || !searchInput || !listEl || !chipsContainer) {
                return;
            }

            const selectedOrder = [];

            function getNameById(id) {
                const m = staffMembers.find(function (x) {
                    return Number(x.id) === id;
                });
                return m ? (m.name || "") : "";
            }

            function renderChips() {
                chipsContainer.querySelectorAll(".staff-chip").forEach(function (el) {
                    el.remove();
                });
                if (placeholder) {
                    placeholder.classList.toggle("hidden", selectedOrder.length > 0);
                }
                selectedOrder.forEach(function (id) {
                    const name = getNameById(id);
                    const chip = document.createElement("span");
                    chip.className =
                        "staff-chip inline-flex items-center gap-0.5 max-w-full pl-2 pr-1 py-1 rounded-lg bg-gradient-to-br from-primary/12 to-primary/8 border border-primary/20 text-xs font-bold text-primary shadow-sm";
                    const nameSpan = document.createElement("span");
                    nameSpan.className = "truncate max-w-[10rem] sm:max-w-[14rem]";
                    nameSpan.textContent = name;
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className =
                        "staff-chip-remove shrink-0 flex items-center justify-center p-0.5 rounded-md hover:bg-primary/15 text-primary";
                    btn.dataset.id = String(id);
                    btn.setAttribute("aria-label", saloonBookingI18n.staffRemoveAria);
                    const icon = document.createElement("span");
                    icon.className = "material-symbols-outlined text-[16px] leading-none";
                    icon.textContent = "close";
                    btn.appendChild(icon);
                    chip.appendChild(nameSpan);
                    chip.appendChild(btn);
                    chipsContainer.appendChild(chip);
                });
            }

            function renderList() {
                const term = (searchInput.value || "").trim().toLowerCase();
                const filtered = staffMembers.filter(function (s) {
                    return (s.name || "").toLowerCase().includes(term);
                });
                listEl.innerHTML = "";
                if (!filtered.length) {
                    const li = document.createElement("li");
                    li.className = "px-3 py-3.5 text-sm text-on-surface-variant text-center";
                    li.textContent = saloonBookingI18n.noMatch;
                    listEl.appendChild(li);
                    return;
                }
                filtered.forEach(function (s) {
                    const id = Number(s.id);
                    const sel = selectedOrder.indexOf(id) >= 0;
                    const li = document.createElement("li");
                    li.setAttribute("role", "option");
                    li.setAttribute("aria-selected", sel ? "true" : "false");
                    li.dataset.id = String(id);
                    li.className =
                        "staff-multi-option flex items-center gap-2.5 px-3 py-2.5 mx-1.5 rounded-xl cursor-pointer text-sm transition-colors " +
                        (sel
                            ? "bg-primary/10 text-primary font-bold ring-1 ring-primary/15"
                            : "hover:bg-surface-container-low text-on-surface");
                    const optIcon = document.createElement("span");
                    optIcon.className =
                        "material-symbols-outlined text-[22px] shrink-0 leading-none " +
                        (sel ? "text-primary" : "text-outline/45");
                    optIcon.textContent = sel ? "check_box" : "check_box_outline_blank";
                    const nameSpan = document.createElement("span");
                    nameSpan.className = "truncate font-medium";
                    nameSpan.textContent = s.name || "";
                    li.appendChild(optIcon);
                    li.appendChild(nameSpan);
                    listEl.appendChild(li);
                });
            }

            function toggleId(id) {
                const i = selectedOrder.indexOf(id);
                if (i >= 0) {
                    selectedOrder.splice(i, 1);
                } else {
                    selectedOrder.push(id);
                }
                renderChips();
                renderList();
                document.dispatchEvent(new CustomEvent("saloon-booking-staff-changed"));
            }

            function openPanel() {
                panel.classList.remove("hidden");
                trigger.setAttribute("aria-expanded", "true");
                searchInput.value = "";
                renderList();
                window.setTimeout(function () {
                    searchInput.focus();
                }, 10);
            }

            function closePanel() {
                panel.classList.add("hidden");
                trigger.setAttribute("aria-expanded", "false");
            }

            function panelIsOpen() {
                return !panel.classList.contains("hidden");
            }

            trigger.addEventListener("click", function (e) {
                if (e.target.closest(".staff-chip-remove")) {
                    return;
                }
                e.preventDefault();
                if (panelIsOpen()) {
                    closePanel();
                } else {
                    openPanel();
                }
            });

            chipsContainer.addEventListener("click", function (e) {
                const rm = e.target.closest(".staff-chip-remove");
                if (!rm) {
                    return;
                }
                e.stopPropagation();
                const id = parseInt(rm.getAttribute("data-id"), 10);
                if (Number.isFinite(id)) {
                    toggleId(id);
                }
            });

            listEl.addEventListener("click", function (e) {
                const row = e.target.closest(".staff-multi-option");
                if (!row || row.dataset.id === undefined) {
                    return;
                }
                const id = parseInt(row.dataset.id, 10);
                if (Number.isFinite(id)) {
                    toggleId(id);
                }
            });

            searchInput.addEventListener("input", renderList);
            searchInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                }
            });

            if (clearBtn) {
                clearBtn.addEventListener("click", function (e) {
                    e.stopPropagation();
                    selectedOrder.length = 0;
                    renderChips();
                    renderList();
                    document.dispatchEvent(new CustomEvent("saloon-booking-staff-changed"));
                });
            }

            document.addEventListener("click", function (e) {
                if (!root.contains(e.target)) {
                    closePanel();
                }
            });

            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape" && panelIsOpen()) {
                    closePanel();
                }
            });

            getSelectedStaffIds = function () {
                return selectedOrder.filter(function (id) {
                    return Number.isFinite(id) && id > 0;
                });
            };

            renderChips();
        })();

        function toNumber(v) {
            const n = parseFloat(v);
            return Number.isFinite(n) ? n : 0;
        }

        function format3(v) {
            return toNumber(v).toFixed(3);
        }

        function createServiceRow(initialService) {
            const row = document.createElement("div");
            row.className = "service-row rounded-xl border border-outline-variant/15 bg-white p-2.5";
            row.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                    <div class="sm:col-span-6 relative">
                        <input type="hidden" class="service-id" value="">
                        <input type="text" class="service-search w-full rounded-lg border border-outline-variant/15 bg-surface-container-low px-2.5 py-2 text-xs" placeholder="">
                        <div class="service-results hidden absolute z-20 mt-1 w-full rounded-lg border border-outline-variant/20 bg-white shadow-lg max-h-36 overflow-auto"></div>
                    </div>
                    <div class="sm:col-span-3">
                        <input type="number" step="0.001" min="0" class="service-price w-full rounded-lg border border-outline-variant/15 bg-surface-container-low px-2.5 py-2 text-xs" placeholder="">
                    </div>
                    <div class="sm:col-span-2">
                        <input type="text" readonly class="service-price-readonly w-full rounded-lg border border-primary/15 bg-primary/5 px-2.5 py-2 text-xs font-bold text-primary" value="">
                    </div>
                    <div class="sm:col-span-1">
                        <button type="button" class="remove-service w-full rounded-lg border border-red-200 text-red-500 px-2 py-2 text-xs hover:bg-red-50">x</button>
                    </div>
                </div>
            `;

            const serviceIdInput = row.querySelector(".service-id");
            const searchInput = row.querySelector(".service-search");
            const results = row.querySelector(".service-results");
            const priceInput = row.querySelector(".service-price");
            const readonlyPrice = row.querySelector(".service-price-readonly");
            const removeBtn = row.querySelector(".remove-service");

            searchInput.placeholder = saloonBookingI18n.searchService;
            priceInput.placeholder = saloonBookingI18n.price;
            readonlyPrice.value = saloonBookingI18n.amountPlaceholder;

            if (initialService) {
                serviceIdInput.value = initialService.id || "";
                searchInput.value = initialService.name || "";
                priceInput.value = format3(initialService.price || 0);
                readonlyPrice.value = format3(initialService.price || 0);
            }

            searchInput.addEventListener("input", function () {
                const term = searchInput.value.trim().toLowerCase();
                serviceIdInput.value = "";

                if (!term) {
                    results.innerHTML = "";
                    results.classList.add("hidden");
                    return;
                }

                const currentRowSelectedId = parseInt(serviceIdInput.value || "0", 10);
                const selectedIds = new Set(
                    Array.from(document.querySelectorAll(".service-row .service-id"))
                        .map(function (el) {
                            return parseInt(el.value || "0", 10);
                        })
                        .filter(function (id) {
                            return Number.isFinite(id) && id > 0 && id !== currentRowSelectedId;
                        })
                );
                const matched = services.filter(function (s) {
                    const id = parseInt(s.id, 10);
                    if (!String(s.name || "").toLowerCase().includes(term)) {
                        return false;
                    }
                    if (Number.isFinite(id) && id > 0 && selectedIds.has(id)) {
                        return false;
                    }
                    return true;
                });
                results.innerHTML = matched.map(s =>
                    `<button type="button" class="service-option w-full text-left px-2.5 py-2 text-xs hover:bg-surface-container-low" data-id="${s.id}" data-name="${s.name}" data-price="${s.price}">${s.name} <span class="text-on-surface-variant">(${format3(s.price)})</span></button>`
                ).join("");
                results.classList.toggle("hidden", matched.length === 0);
            });

            results.addEventListener("click", function (e) {
                const btn = e.target.closest(".service-option");
                if (!btn) {
                    return;
                }
                const selectedServiceId = parseInt(btn.dataset.id || "0", 10);
                const duplicateExists = Array.from(document.querySelectorAll(".service-row")).some(function (rowEl) {
                    if (rowEl === row) {
                        return false;
                    }
                    const idInput = rowEl.querySelector(".service-id");
                    const idVal = parseInt((idInput && idInput.value) || "0", 10);
                    return Number.isFinite(idVal) && idVal > 0 && idVal === selectedServiceId;
                });
                if (duplicateExists) {
                    toastr.error("The same service cannot be selected twice.");
                    results.classList.add("hidden");
                    return;
                }
                serviceIdInput.value = btn.dataset.id;
                searchInput.value = btn.dataset.name;
                priceInput.value = format3(btn.dataset.price);
                readonlyPrice.value = format3(btn.dataset.price);
                results.classList.add("hidden");
                updateServicesTotal();
            });

            priceInput.addEventListener("input", function () {
                readonlyPrice.value = format3(priceInput.value);
                updateServicesTotal();
            });

            removeBtn.addEventListener("click", function () {
                row.remove();
                updateServicesTotal();
            });

            return row;
        }

        function getServicesTotal() {
            return Array.from(document.querySelectorAll(".service-price"))
                .reduce((sum, input) => sum + toNumber(input.value), 0);
        }

        function updateServicesTotal() {
            const total = getServicesTotal();
            servicesTotalText.textContent = `${format3(total)} ${saloonBookingI18n.omr}`;
            if (paymentPaidAmountInput) {
                const paid = toNumber(paymentPaidAmountInput.value);
                if (paid < 0) {
                    paymentPaidAmountInput.value = "0";
                } else if (paid > total) {
                    paymentPaidAmountInput.value = format3(total);
                }
            }
            updatePaymentSummary();
        }

        function clampPaidAmountField() {
            if (!paymentPaidAmountInput) {
                return;
            }
            const total = getServicesTotal();
            let paid = toNumber(paymentPaidAmountInput.value);
            if (paid < 0) {
                paid = 0;
            }
            if (paid > total) {
                paid = total;
            }
            paymentPaidAmountInput.value = format3(paid);
            updatePaymentSummary();
        }

        function updatePaymentSummary() {
            const total = getServicesTotal();
            if (bookingTotalAmountInput) {
                bookingTotalAmountInput.value = format3(total);
            }
            const paid = paymentPaidAmountInput ? toNumber(paymentPaidAmountInput.value) : 0;
            const remaining = Math.max(total - paid, 0);
            if (paymentRemainingAmountInput) {
                paymentRemainingAmountInput.value = format3(remaining);
            }
        }

        async function renderCustomerResults(term) {
            const q = term.trim();
            if (!q) {
                customerResults.classList.add("hidden");
                customerResults.innerHTML = "";
                return;
            }

            const response = await fetch(`{{ route('saloon_bookings.customers.search') }}?q=${encodeURIComponent(q)}`);
            const items = await response.json();

            customerResults.innerHTML = items.map(c =>
                `<button type="button" class="customer-option w-full text-left px-3 py-2 hover:bg-surface-container-low" data-id="${c.id}" data-name="${c.name || ''}" data-phone="${c.phone || ''}"><p class="text-xs font-bold text-on-surface">${c.name || ''}</p><p class="text-[11px] text-on-surface-variant">${c.phone || ''}</p></button>`
            ).join("");

            if (!items.length) {
                customerResults.innerHTML = `<p class="px-3 py-2 text-xs text-on-surface-variant">${saloonBookingI18n.noMatch}</p>`;
            }
            customerResults.classList.remove("hidden");
        }

        function getSelectedAccountId() {
            if (!paymentAccountSelect || !paymentAccountSelect.value) {
                return null;
            }
            return paymentAccountSelect.value;
        }

        function collectServices() {
            return Array.from(document.querySelectorAll(".service-row"))
                .map(function (row) {
                    const serviceId = row.querySelector(".service-id").value;
                    const name = row.querySelector(".service-search").value.trim();
                    const price = toNumber(row.querySelector(".service-price").value);
                    if (!name || price <= 0) {
                        return null;
                    }
                    return {
                        service_id: serviceId ? parseInt(serviceId, 10) : null,
                        name: name,
                        price: parseFloat(format3(price))
                    };
                })
                .filter(Boolean);
        }

        function validateRequiredFields() {
            if (!selectedCustomerName.value.trim()) {
                toastr.error("Customer name is required.");
                return false;
            }
            if (!selectedCustomerPhone.value.trim()) {
                toastr.error("Customer phone is required.");
                return false;
            }
            const staffIds = getSelectedStaffIds();
            if (!staffIds.length) {
                toastr.error("Please select at least one staff member.");
                return false;
            }
            const bookingDateEl = document.getElementById("bookingDate");
            const bookingTimeFromEl = document.getElementById("bookingTimeFrom");
            const bookingTimeToEl = document.getElementById("bookingTimeTo");
            if (!bookingDateEl || !bookingDateEl.value) {
                toastr.error("Booking date is required.");
                return false;
            }
            if (!bookingTimeFromEl || !String(bookingTimeFromEl.value || "").trim()) {
                toastr.error("Booking time from is required.");
                return false;
            }
            if (!bookingTimeToEl || !String(bookingTimeToEl.value || "").trim()) {
                toastr.error("Booking time to is required.");
                return false;
            }
            const tfNorm = normalizeTimeHM(bookingTimeFromEl.value);
            const ttNorm = normalizeTimeHM(bookingTimeToEl.value);
            if (!tfNorm || !ttNorm) {
                toastr.error(saloonBookingI18n.errTimeInvalid);
                return false;
            }
            bookingTimeFromEl.value = tfNorm;
            bookingTimeToEl.value = ttNorm;
            const fromM = timeToMinutesHM(tfNorm);
            const toM = timeToMinutesHM(ttNorm);
            if (fromM === null || toM === null || toM < fromM) {
                toastr.error(saloonBookingI18n.errTimeOrder);
                return false;
            }
            return true;
        }

        function collectPaymentFields() {
            const accountId = getSelectedAccountId();
            const amount = paymentPaidAmountInput ? toNumber(paymentPaidAmountInput.value) : 0;
            return {
                account_id: accountId ? parseInt(accountId, 10) : null,
                payment_amount: amount > 0 ? parseFloat(format3(amount)) : null,
                total_amount: parseFloat(format3(getServicesTotal())),
                remaining_amount: parseFloat(format3(Math.max(getServicesTotal() - amount, 0)))
            };
        }

        addServiceBtn.addEventListener("click", function () {
            servicesWrapper.appendChild(createServiceRow());
        });

        customerSearch.addEventListener("input", function () {
            renderCustomerResults(customerSearch.value);
        });

        selectedCustomerName.addEventListener("input", function () {
            selectedCustomerId.value = "";
        });
        selectedCustomerPhone.addEventListener("input", function () {
            selectedCustomerId.value = "";
        });

        customerResults.addEventListener("click", function (e) {
            const item = e.target.closest(".customer-option");
            if (!item) {
                return;
            }
            selectedCustomerId.value = item.dataset.id || "";
            selectedCustomerName.value = item.dataset.name || "";
            selectedCustomerPhone.value = item.dataset.phone || "";
            customerSearch.value = `${item.dataset.name || ""} - ${item.dataset.phone || ""}`;
            customerResults.classList.add("hidden");
        });

        document.addEventListener("click", function (e) {
            if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
                customerResults.classList.add("hidden");
            }
        });

        if (paymentAccountSelect) {
            paymentAccountSelect.addEventListener("change", updatePaymentSummary);
        }
        if (paymentPaidAmountInput) {
            paymentPaidAmountInput.addEventListener("input", updatePaymentSummary);
            paymentPaidAmountInput.addEventListener("blur", clampPaidAmountField);
        }

        async function submitBooking(status) {
            if (bookingSubmitLocked) {
                return;
            }

            const servicesPayload = collectServices();
            if (!servicesPayload.length) {
                toastr.error(saloonBookingI18n.errAddService);
                return;
            }
            const distinctServiceKeys = new Set();
            for (let i = 0; i < servicesPayload.length; i += 1) {
                const item = servicesPayload[i];
                const key = item.service_id
                    ? "id:" + String(item.service_id)
                    : "name:" + String(item.name || "").trim().toLowerCase();
                if (distinctServiceKeys.has(key)) {
                    toastr.error("The same service cannot be selected twice.");
                    return;
                }
                distinctServiceKeys.add(key);
            }
            if (!validateRequiredFields()) {
                return;
            }

            const bookingTotal = getServicesTotal();
            const paidRaw = paymentPaidAmountInput ? toNumber(paymentPaidAmountInput.value) : 0;
            if (paidRaw < 0) {
                toastr.error(saloonBookingI18n.errPaidNegative);
                return;
            }
            if (paidRaw > bookingTotal) {
                toastr.error(saloonBookingI18n.errPaidExceeds);
                return;
            }

            const staffIds = getSelectedStaffIds();
            const pay = collectPaymentFields();
            const bookingDateEl = document.getElementById("bookingDate");
            const bookingTimeFromEl = document.getElementById("bookingTimeFrom");
            const bookingTimeToEl = document.getElementById("bookingTimeTo");
            const bookingDateVal = bookingDateEl && bookingDateEl.value ? bookingDateEl.value : null;
            const timeFromVal =
                bookingTimeFromEl && bookingTimeFromEl.value ? normalizeTimeHM(bookingTimeFromEl.value) : null;
            const timeToVal = bookingTimeToEl && bookingTimeToEl.value ? normalizeTimeHM(bookingTimeToEl.value) : null;
            const shouldCheckSlot =
                staffIds.length > 0 && bookingDateVal && timeFromVal && timeToVal;

            bookingSubmitLocked = true;
            setBookingActionButtonsBusy(true);

            try {
                if (shouldCheckSlot) {
                    let checkRes;
                    try {
                        checkRes = await fetch(slotCheckUrl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            },
                            body: JSON.stringify({
                                staff_ids: staffIds,
                                booking_date: bookingDateVal,
                                booking_time_from: timeFromVal,
                                booking_time_to: timeToVal,
                            }),
                        });
                    } catch (netErr) {
                        toastr.error(saloonBookingI18n.errSlotCheck);
                        return;
                    }

                    let checkData = {};
                    try {
                        checkData = await checkRes.json();
                    } catch (parseErr) {
                        toastr.error(saloonBookingI18n.errSlotCheck);
                        return;
                    }

                    if (!checkRes.ok) {
                        const firstVal =
                            checkData.errors && Object.keys(checkData.errors).length
                                ? Object.values(checkData.errors)[0][0]
                                : null;
                        const msg = firstVal || checkData.message || saloonBookingI18n.errSlotCheck;
                        toastr.error(msg);
                        return;
                    }

                    if (!checkData.available) {
                        setBookingActionButtonsBusy(false);
                        bookingSubmitLocked = false;
                        await showSlotConflictDialog(checkData);
                        return;
                    }
                }

                const payload = {
                    status: status,
                    customer_id: selectedCustomerId.value || null,
                    customer_name: selectedCustomerName.value.trim(),
                    customer_phone: selectedCustomerPhone.value.trim(),
                    staff_ids: staffIds.length ? staffIds : null,
                    booking_date: bookingDateVal,
                    booking_time_from: timeFromVal,
                    booking_time_to: timeToVal,
                    special_notes: document.getElementById("specialNotes").value || null,
                    services: servicesPayload,
                    account_id: pay.account_id,
                    payment_amount: pay.payment_amount,
                    total_amount: pay.total_amount,
                    remaining_amount: pay.remaining_amount,
                };

                const response = await fetch("{{ route('saloon_bookings.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    },
                    body: JSON.stringify(payload),
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (parseErr) {
                    toastr.error(saloonBookingI18n.errUnexpected);
                    return;
                }

                if (!response.ok || !result.success) {
                    const firstError = result.errors
                        ? Object.values(result.errors)[0][0]
                        : result.message || saloonBookingI18n.errSave;
                    toastr.error(firstError);
                    return;
                }

                const okMsg =
                    status === "draft"
                        ? saloonBookingI18n.draftSaved
                        : saloonBookingI18n.successSaved.replace(":number", result.booking.booking_no);
                toastr.success(okMsg);
                setTimeout(function () {
                    window.location.href = viewBookingsUrl;
                }, 250);
            } catch (error) {
                toastr.error(saloonBookingI18n.errUnexpected);
            } finally {
                bookingSubmitLocked = false;
                setBookingActionButtonsBusy(false);
            }
        }

        bookingForm.addEventListener("submit", function (e) {
            e.preventDefault();
            submitBooking("confirmed");
        });

        if (saveDraftBtn) {
            saveDraftBtn.addEventListener("click", function () {
                submitBooking("draft");
            });
        }

        (function initStaffAvailabilityCalendars() {
            const availRangeUrl = @json(route('saloon_bookings.availability.range'));
            const availDayUrl = @json(route('saloon_bookings.availability.day'));
            const appLocale = @json(session('locale', 'en'));
            const RANGE_DAYS = 31;

            const rangeAnchor = new Date();
            rangeAnchor.setHours(0, 0, 0, 0);

            const availEls = {
                prev: document.getElementById("availMonthPrev"),
                next: document.getElementById("availMonthNext"),
                label: document.getElementById("availMonthLabel"),
                placeholder: document.getElementById("staffAvailabilityPlaceholder"),
                wrap: document.getElementById("staffAvailabilityCalendars"),
                modal: document.getElementById("availDayModal"),
                modalClose: document.getElementById("availDayModalClose"),
                modalTitle: document.getElementById("availDayModalTitle"),
                modalSub: document.getElementById("availDayModalSub"),
                timelineLabels: document.getElementById("availDayTimelineLabels"),
                timelineBar: document.getElementById("availDayTimelineBar"),
                slotsGrid: document.getElementById("availDaySlotsGrid"),
            };

            function localYmd(d) {
                return (
                    d.getFullYear() +
                    "-" +
                    String(d.getMonth() + 1).padStart(2, "0") +
                    "-" +
                    String(d.getDate()).padStart(2, "0")
                );
            }

            function shiftRange(deltaDays) {
                rangeAnchor.setDate(rangeAnchor.getDate() + deltaDays);
            }

            function cellClass(level) {
                if (level === "full") {
                    return "bg-red-600 text-white border-2 border-red-900 shadow-md hover:bg-red-700";
                }
                if (level === "partial") {
                    return "bg-amber-500 text-gray-900 border-2 border-amber-900 shadow-md hover:bg-amber-600";
                }
                return "bg-slate-300 text-slate-900 border-2 border-slate-600 shadow-sm hover:bg-slate-400";
            }

            function escapeAttr(s) {
                return String(s || "")
                    .replace(/&/g, "&amp;")
                    .replace(/"/g, "&quot;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");
            }

            function buildRangeTable(data) {
                const dates = data.dates || [];
                const staff = data.staff || [];
                const loc = appLocale === "ar" ? "ar" : "en-US";
                const todayStr = localYmd(new Date());
                const cols = dates.length;
                if (cols === 0) {
                    return "";
                }

                const gridTemplate = "5.75rem repeat(" + cols + ", minmax(2rem, 1fr))";

                let html = '<div class="overflow-x-auto rounded-xl border-2 border-slate-400 bg-white p-2 sm:p-3 shadow-md">';
                html +=
                    '<div class="min-w-[720px]" style="display:grid;grid-template-columns:' +
                    gridTemplate +
                    ';gap:5px;align-items:stretch">';

                html += '<div class="text-[9px] font-extrabold text-slate-700 uppercase tracking-tight self-end pb-1 pr-1"></div>';
                dates.forEach(function (ds) {
                    const parts = ds.split("-");
                    const dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    const wd = dt.toLocaleDateString(loc, { weekday: "short" });
                    const dn = parseInt(parts[2], 10);
                    const mon = dt.toLocaleDateString(loc, { month: "short" });
                    html +=
                        '<div class="text-center text-[8px] font-extrabold text-slate-800 leading-tight py-1 px-0.5 bg-slate-200 rounded-md border-2 border-slate-400">' +
                        escapeAttr(wd) +
                        '<br><span class="text-[11px] tabular-nums">' +
                        dn +
                        '</span><br><span class="text-[7px] opacity-90 uppercase">' +
                        escapeAttr(mon) +
                        "</span></div>";
                });

                staff.forEach(function (s) {
                    const daysMap = s.days || {};
                    html +=
                        '<div class="text-xs font-extrabold text-slate-900 truncate py-2 pr-1 border-r-2 border-slate-300 self-center" title="' +
                        escapeAttr(s.name) +
                        '">' +
                        escapeAttr(s.name) +
                        "</div>";
                    dates.forEach(function (ds) {
                        const info = daysMap[ds] || { level: "free", booking_count: 0, booked_slot_count: 0, total_slots: 15 };
                        const level = info.level || "free";
                        const cnt = info.booking_count || 0;
                        const cls = cellClass(level);
                        const todayRing = ds === todayStr ? " ring-2 ring-violet-600 ring-offset-2" : "";
                        const titleAttr =
                            ds +
                            " · " +
                            cnt +
                            " bookings · " +
                            (info.booked_slot_count || 0) +
                            "/" +
                            (info.total_slots || 15) +
                            " h";
                        html +=
                            '<button type="button" class="avail-day-cell min-h-[2.85rem] rounded-md flex flex-col items-center justify-center p-0.5 leading-tight transition-transform active:scale-95 ' +
                            cls +
                            todayRing +
                            '" data-staff-id="' +
                            s.id +
                            '" data-date="' +
                            ds +
                            '" data-staff-name="' +
                            escapeAttr(s.name) +
                            '" title="' +
                            escapeAttr(titleAttr) +
                            '">';
                        html += '<span class="text-[11px] font-extrabold tabular-nums">' + parseInt(ds.split("-")[2], 10) + "</span>";
                        if (cnt > 0) {
                            html += '<span class="text-[8px] font-extrabold leading-none mt-0.5">' + cnt + "</span>";
                        }
                        html += "</button>";
                    });
                });

                html += "</div></div>";
                return html;
            }

            async function fetchAvailabilityRange() {
                if (!availEls.wrap || !availEls.placeholder) {
                    return;
                }
                const ids = getSelectedStaffIds();
                const effectiveIds = [];
                if (Array.isArray(ids) && ids.length > 0) {
                    ids.forEach(function (id) {
                        const sid = parseInt(id, 10);
                        if (Number.isFinite(sid) && sid > 0) {
                            effectiveIds.push(sid);
                        }
                    });
                } else if (Array.isArray(staffMembers)) {
                    staffMembers.forEach(function (s) {
                        const sid = parseInt(s && s.id, 10);
                        if (Number.isFinite(sid) && sid > 0) {
                            effectiveIds.push(sid);
                        }
                    });
                }

                if (effectiveIds.length === 0) {
                    availEls.placeholder.classList.remove("hidden");
                    availEls.wrap.classList.add("hidden");
                    availEls.wrap.innerHTML = "";
                    if (availEls.label) {
                        availEls.label.textContent = "";
                    }
                    return;
                }

                const params = new URLSearchParams();
                effectiveIds.forEach(function (id) {
                    params.append("staff_ids[]", id);
                });
                params.set("start", localYmd(rangeAnchor));
                params.set("days", String(RANGE_DAYS));

                try {
                    const res = await fetch(availRangeUrl + "?" + params.toString(), {
                        headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                    });
                    if (!res.ok) {
                        throw new Error("bad");
                    }
                    const data = await res.json();
                    if (availEls.label) {
                        availEls.label.textContent = data.range_label || "";
                    }
                    availEls.placeholder.classList.add("hidden");
                    availEls.wrap.classList.remove("hidden");
                    availEls.wrap.innerHTML = buildRangeTable(data);
                } catch (err) {
                    toastr.error(saloonBookingI18n.availabilityLoadError);
                }
            }

            function closeAvailModal() {
                if (!availEls.modal) {
                    return;
                }
                availEls.modal.classList.add("hidden");
                availEls.modal.classList.remove("flex");
                availEls.modal.setAttribute("aria-hidden", "true");
            }

            async function openAvailDay(staffId, staffName, dateStr) {
                if (!availEls.modal || !availEls.timelineLabels || !availEls.timelineBar || !availEls.slotsGrid) {
                    return;
                }
                const url = availDayUrl + "?staff_id=" + encodeURIComponent(String(staffId)) + "&date=" + encodeURIComponent(dateStr);
                try {
                    const res = await fetch(url, {
                        headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                    });
                    if (!res.ok) {
                        throw new Error("bad");
                    }
                    const data = await res.json();
                    availEls.modalTitle.textContent = staffName;
                    availEls.modalSub.textContent = data.date + " · " + saloonBookingI18n.availabilityDayTitle;

                    let labelsHtml = '<div class="relative h-full w-full" style="min-width:100%">';
                    (data.hour_markers || []).forEach(function (h) {
                        const st = h.anchor_end ? "right:0;transform:translateX(-50%)" : "left:" + h.left_pct + "%;transform:translateX(-50%)";
                        labelsHtml += '<span class="absolute top-0 whitespace-nowrap text-[8px]" style="' + st + '">' + escapeAttr(h.label) + "</span>";
                    });
                    labelsHtml += "</div>";
                    availEls.timelineLabels.innerHTML = labelsHtml;

                    let barInner = "";
                    (data.segments || []).forEach(function (seg) {
                        const bg = seg.status === "draft" ? "#c2410c" : "#5b21b6";
                        const tip = escapeAttr(seg.booking_no + " · " + seg.time_label + " · " + seg.customer);
                        const wPct = Math.max(parseFloat(seg.width_pct) || 0, 0.85);
                        barInner +=
                            '<div class="absolute top-0.5 bottom-0.5 rounded shadow-md border-2 border-white text-[7px] text-white font-extrabold flex items-center justify-center px-0.5 overflow-hidden leading-none" style="left:' +
                            seg.left_pct +
                            "%;width:" +
                            wPct +
                            "%;min-width:6px;background:" +
                            bg +
                            '" title="' +
                            tip +
                            '">' +
                            escapeAttr(seg.booking_no) +
                            "</div>";
                    });
                    availEls.timelineBar.innerHTML =
                        '<div class="relative w-full h-full min-w-[520px] bg-slate-200 rounded-lg border-2 border-slate-400">' + barInner + "</div>";

                    let slotsHtml = "";
                    (data.slots || []).forEach(function (slot) {
                        const booked = slot.booked;
                        const border = booked ? "border-2 border-violet-800" : "border-2 border-emerald-700";
                        const bg = booked ? "bg-violet-200" : "bg-emerald-100";
                        let block = '<div class="font-extrabold text-[9px] text-slate-900 truncate">' + escapeAttr(slot.label) + "</div>";
                        if (booked && slot.bookings && slot.bookings.length) {
                            slot.bookings.forEach(function (b) {
                                block +=
                                    '<div class="mt-0.5 text-[8px] font-semibold text-violet-950 truncate">' +
                                    escapeAttr(b.booking_no + " · " + b.time_label + " · " + b.customer) +
                                    "</div>";
                            });
                        } else {
                            block +=
                                '<div class="text-[8px] text-emerald-800 font-extrabold mt-0.5">' +
                                escapeAttr(saloonBookingI18n.slotFreeLabel) +
                                "</div>";
                        }
                        slotsHtml += '<div class="rounded-md p-1.5 ' + border + " " + bg + '">' + block + "</div>";
                    });
                    availEls.slotsGrid.innerHTML = slotsHtml;

                    availEls.modal.classList.remove("hidden");
                    availEls.modal.classList.add("flex");
                    availEls.modal.setAttribute("aria-hidden", "false");
                } catch (err) {
                    toastr.error(saloonBookingI18n.availabilityLoadError);
                }
            }

            if (availEls.prev) {
                availEls.prev.addEventListener("click", function () {
                    shiftRange(-RANGE_DAYS);
                    fetchAvailabilityRange();
                });
            }
            if (availEls.next) {
                availEls.next.addEventListener("click", function () {
                    shiftRange(RANGE_DAYS);
                    fetchAvailabilityRange();
                });
            }

            document.addEventListener("click", function (e) {
                const cell = e.target.closest(".avail-day-cell");
                if (!cell) {
                    return;
                }
                const sid = parseInt(cell.getAttribute("data-staff-id"), 10);
                const ds = cell.getAttribute("data-date");
                const sn = cell.getAttribute("data-staff-name") || "";
                if (Number.isFinite(sid) && ds) {
                    openAvailDay(sid, sn, ds);
                }
            });

            if (availEls.modalClose) {
                availEls.modalClose.addEventListener("click", closeAvailModal);
            }
            if (availEls.modal) {
                availEls.modal.addEventListener("click", function (e) {
                    if (e.target === availEls.modal) {
                        closeAvailModal();
                    }
                });
            }

            document.addEventListener("saloon-booking-staff-changed", fetchAvailabilityRange);

            fetchAvailabilityRange();
        })();

        servicesWrapper.appendChild(createServiceRow(services[0] || null));
        updateServicesTotal();
        updatePaymentSummary();
    })();
</script>


@include('layouts.salon_footer')
@endsection