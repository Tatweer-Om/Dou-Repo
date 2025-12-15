@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.boutique_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6">
  <div class="w-full max-w-screen-xl mx-auto">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
        {{ trans('messages.manage_boutiques', [], session('locale')) }}
      </h2>
      <a href="{{ route('boutique') }}"
         class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
        <span class="material-symbols-outlined me-1 text-base">add_business</span>
        {{ trans('messages.add_boutique', [], session('locale')) }}
      </a>
    </div>

    <!-- Search -->
    <div class="bg-white border border-pink-100 rounded-2xl shadow-sm p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-3 items-center">
        <div class="flex-1 w-full relative">
          <input type="search" id="search_boutique"
                 placeholder="{{ trans('messages.search_boutique_placeholder', [], session('locale')) }}"
                 class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm px-3">
        </div>
        <button id="search_button" type="button"
                class="inline-flex items-center justify-center gap-1 rounded-xl px-5 h-11 text-sm text-white bg-[var(--primary-color)] hover:bg-pink-700 transition-all">
          <span class="material-symbols-outlined text-base">search</span>
          {{ trans('messages.search', [], session('locale')) }}
        </button>
      </div>
    </div>

    <!-- Table (Desktop & Tablet) -->
    <div class="hidden md:block rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition">
      <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
          <tr>
            <th class="px-3 py-3 text-right font-bold">#</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.boutique_name', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.shelf_no', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.monthly_rent', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.rent_date', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.address', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-right font-bold">{{ trans('messages.rent_invoice_status', [], session('locale')) }}</th>
            <th class="px-3 py-3 text-center font-bold">{{ trans('messages.actions', [], session('locale')) }}</th>
          </tr>
        </thead>
        <tbody id="desktop_boutique_body"></tbody>
      </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden grid grid-cols-1 gap-4" id="mobile_boutique_cards"></div>

    <!-- Pagination -->
    <ul id="pagination" class="flex gap-2 justify-center mt-4"></ul>

    <!-- Rent Invoice History Modal -->
    <div id="rent_invoice_modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-900" id="rent_invoice_modal_title">{{ trans('messages.rent_invoice_history', [], session('locale')) }}</h3>
                <button id="close_rent_invoice_modal" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
            <input type="hidden" id="rent_invoice_boutique_id" value="">
            <div class="p-6 overflow-y-auto flex-1">
                <div id="rent_invoice_loading" class="text-center py-8">
                    <span class="material-symbols-outlined text-4xl animate-spin text-[var(--primary-color)]">refresh</span>
                    <p class="mt-2 text-gray-600">{{ trans('messages.loading', [], session('locale')) }}...</p>
                </div>
                <div id="rent_invoice_content" class="hidden">
                    <div class="mb-4 p-3 bg-pink-50 rounded-lg border border-pink-100">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">{{ trans('messages.boutique', [], session('locale')) }}:</span>
                            <span id="rent_invoice_boutique_name"></span>
                        </p>
                        <p class="text-sm text-gray-700 mt-1">
                            <span class="font-semibold">{{ trans('messages.monthly_rent', [], session('locale')) }}:</span>
                            <span id="rent_invoice_monthly_rent"></span> ر.ع
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-right font-bold">{{ trans('messages.month', [], session('locale')) }}</th>
                                    <th class="px-4 py-3 text-center font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                                    <th class="px-4 py-3 text-right font-bold">{{ trans('messages.amount', [], session('locale')) }}</th>
                                    <th class="px-4 py-3 text-right font-bold">{{ trans('messages.payment_date', [], session('locale')) }}</th>
                                </tr>
                            </thead>
                            <tbody id="rent_invoice_table_body">
                                <!-- Invoice rows will be populated here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button id="save_invoice_payments_btn" class="px-6 py-2 bg-[var(--primary-color)] hover:bg-pink-700 text-white font-bold rounded-lg transition flex items-center gap-2">
                            <span class="material-symbols-outlined">save</span>
                            {{ trans('messages.save_payments', [], session('locale')) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</main>

@include('layouts.footer')
@endsection
