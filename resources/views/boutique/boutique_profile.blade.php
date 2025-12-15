@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.boutique_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="boutiqueProfile()"
      x-init="initCharts()">

  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $boutique->boutique_name ?? trans('messages.boutique_profile_name', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">{{ trans('messages.shelf_no', [], session('locale')) }}: {{ $boutique->shelf_no ?? '-' }} • {{ $boutique->boutique_address ?? '' }}</p>
      </div>
      <div class="flex gap-2">
        <a href="/edit_boutique/{{ $boutique->id }}" class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">edit</span> {{ trans('messages.edit', [], session('locale')) }}
        </a>
        <button @click="deleteBoutique()" class="px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">delete</span> {{ trans('messages.delete', [], session('locale')) }}
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 border-b border-pink-100 overflow-x-auto no-scrollbar bg-white rounded-xl shadow-sm px-4">
      <button @click="tab='overview'"
              :class="tab==='overview' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">home</span> {{ trans('messages.overview', [], session('locale')) }}
      </button>
      <button @click="tab='sales'"
              :class="tab==='sales' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">sell</span> {{ trans('messages.sales', [], session('locale')) }}
      </button>
      <button @click="tab='shipments'"
              :class="tab==='shipments' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">local_shipping</span> {{ trans('messages.shipment_record', [], session('locale')) }}
      </button>
      <button @click="tab='invoices'"
              :class="tab==='invoices' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">receipt_long</span> {{ trans('messages.invoices_and_payments', [], session('locale')) }}
      </button>
      <button @click="tab='income'"
              :class="tab==='income' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">account_balance</span> {{ trans('messages.income_report', [], session('locale')) }}
      </button>
    </div>
    <!-- OVERVIEW -->
    <section x-show="tab==='overview'" x-transition>
      <!-- كلمات عليا -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.total_sales', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ number_format($totalSales, 3) }} ر.ع</h3>
          </div>
<span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
    payments
</span>        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.number_of_shipments', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ $numberOfShipments }}</h3>
          </div>
          <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">local_shipping</span>
        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.abayas_sent', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ $totalAbayas }}</h3>
          </div>
          <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">inventory_2</span>
        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.unpaid_invoices', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-red-500">{{ $unpaidInvoicesCount ?? 0 }}</h3>
          </div>
          <span class="material-symbols-outlined bg-red-100 text-red-500 rounded-full p-3">warning</span>
        </div>
      </div>
<br>

      <!-- بطاقات مصغّرة تحليلية -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.best_selling_color', [], session('locale')) }}</p>
            <h4 class="font-bold text-gray-800">{{ $bestSellingColor }}</h4>
          </div>
          <span class="inline-block w-6 h-6 rounded-full border" style="background:#000"></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.most_requested_size', [], session('locale')) }}</p>
            <h4 class="font-bold text-gray-800">{{ $mostRequestedSize }}</h4>
          </div>
          <span class="material-symbols-outlined text-gray-500">straighten</span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.monthly_profit', [], session('locale')) }}</p>
            <h4 class="font-bold text-[var(--primary-color)]">{{ number_format($monthlyProfit, 3) }} ر.ع</h4>
          </div>
          <span class="material-symbols-outlined text-[var(--primary-color)]">trending_up</span>
        </div>
      </div>
<br>

      <!-- Line Chart داخل نظرة عامة -->
      <div class="bg-white rounded-2xl border border-pink-100 shadow-sm p-6">
        <h3 class="font-bold text-[var(--primary-color)] mb-3">{{ trans('messages.sales_last_6_months', [], session('locale')) }}</h3>
        <canvas id="salesChart" height="120"></canvas>
      </div>
    </section>

    <!-- SALES -->
    <section x-show="tab==='sales'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="salesSearch" type="text" placeholder="{{ trans('messages.search', [], session('locale')) }}"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterSales()">
          <input x-model="dateFromSales" type="date" class="h-10 px-2 border border-pink-200 rounded-lg" @change="filterSales()">
          <input x-model="dateToSales" type="date" class="h-10 px-2 border border-pink-200 rounded-lg" @change="filterSales()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1300px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.items_sent', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total_amount_sent', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.sold_amount', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.profit', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in filteredSales" :key="row.transfer_code">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 font-semibold" x-text="row.transfer_code"></td>
                  <td class="px-3 py-2" x-text="row.date"></td>
                  <td class="px-3 py-2" x-text="row.items_sent"></td>
                  <td class="px-3 py-2" x-text="formatCurrency(row.total_amount_sent)"></td>
                  <td class="px-3 py-2" x-text="formatCurrency(row.sold_amount)"></td>
                  <td class="px-3 py-2 font-semibold" :class="row.profit >= 0 ? 'text-green-600' : 'text-red-600'" x-text="formatCurrency(row.profit)"></td>
                  <td class="px-3 py-2 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                          :class="row.status === 'fully_paid' ? 'bg-green-100 text-green-700' : row.status === 'partially_paid' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                          x-text="getStatusText(row.status)"></span>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button @click="openSaleDetails(row)"
                            class="px-3 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-[var(--primary-color)] text-xs font-semibold">
                      {{ trans('messages.view_details', [], session('locale')) }}
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- SHIPMENTS -->
    <section x-show="tab==='shipments'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="shipmentSearch" type="text" placeholder="{{ trans('messages.search', [], session('locale')) }}"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterShipments()">
          <input x-model="dateFromSh" type="date" class="h-10 px-2 border border-pink-200 rounded-lg" @change="filterShipments()">
          <input x-model="dateToSh" type="date" class="h-10 px-2 border border-pink-200 rounded-lg" @change="filterShipments()">
          <button @click="filterShipments()" class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white text-sm">{{ trans('messages.filter', [], session('locale')) }}</button>
          <button class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 text-sm hover:bg-purple-200">
            <span class="material-symbols-outlined text-base align-middle">download</span> Excel
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1000px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.order_number', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.shipment_date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total_items', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total_amount', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in filteredShipments" :key="row.transfer_code">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 font-semibold" x-text="row.transfer_code"></td>
                  <td class="px-3 py-2" x-text="row.date"></td>
                  <td class="px-3 py-2" x-text="row.total_items"></td>
                  <td class="px-3 py-2" x-text="formatCurrency(row.total_amount)"></td>
                  <td class="px-3 py-2 text-center">
                    <button @click="openShipmentDetails(row)"
                            class="px-3 py-1 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs font-semibold">
                      {{ trans('messages.view_details', [], session('locale')) }}
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- INVOICES -->
    <section x-show="tab==='invoices'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        @if(isset($unpaidInvoices) && $unpaidInvoices->count() > 0)
        <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-lg">
          <p class="text-sm text-red-700 font-semibold">
            <span class="material-symbols-outlined align-middle text-base">warning</span>
            {{ trans('messages.unpaid_invoices', [], session('locale')) }}: {{ $unpaidInvoices->count() }}
          </p>
        </div>
        @endif
        @if(isset($allInvoices) && $allInvoices->count() > 0)
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.month', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.amount', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.payment_date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($allInvoices as $invoice)
              <tr class="border-t hover:bg-pink-50/60">
                <td class="px-3 py-2 font-semibold">{{ $invoice->month }}</td>
                <td class="px-3 py-2">{{ $invoice->total_amount ?? $boutique->monthly_rent }} ر.ع</td>
                <td class="px-3 py-2">{{ $invoice->payment_date ? date('Y-m-d', strtotime($invoice->payment_date)) : '-' }}</td>
                <td class="px-3 py-2 text-center">
                  @if($invoice->status == '4')
                  <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                    <span class="material-symbols-outlined text-xs">check_circle</span>
                    {{ trans('messages.paid', [], session('locale')) }}
                  </span>
                  @else
                  <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                    <span class="material-symbols-outlined text-xs">cancel</span>
                    {{ trans('messages.unpaid', [], session('locale')) }}
                  </span>
                  @endif
                </td>
                <td class="px-3 py-2 text-center">
                  @if($invoice->status == '5')
                  <button @click="openPaymentModal({{ $invoice->id }}, '{{ $invoice->month }}', {{ $invoice->total_amount ?? $boutique->monthly_rent }})"
                          class="px-3 py-1 rounded-lg bg-[var(--primary-color)] hover:bg-pink-700 text-white text-xs font-semibold">
                    {{ trans('messages.pay', [], session('locale')) }}
                  </button>
                  @else
                  <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-semibold">{{ trans('messages.paid', [], session('locale')) }}</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-12">
          <span class="material-symbols-outlined text-6xl text-gray-300 mb-4 block">receipt_long</span>
          <p class="text-gray-500 text-lg font-semibold">{{ trans('messages.no_invoices_found', [], session('locale')) }}</p>
        </div>
        @endif
      </div>
    </section>

    <!-- INCOME REPORT -->
    <section x-show="tab==='income'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.complete_income_report', [], session('locale')) }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Total Items Sent -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-blue-600 text-3xl">inventory_2</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items_sent', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ number_format($incomeReport['total_items_sent'] ?? 0, 0) }}</h3>
          </div>
          
          <!-- Total Items Pulled -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 shadow-md border border-orange-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-orange-600 text-3xl">remove_shopping_cart</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items_pulled', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-orange-600">{{ number_format($incomeReport['total_items_pulled'] ?? 0, 0) }}</h3>
          </div>
          
          <!-- Total Sellable -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-purple-600 text-3xl">shopping_bag</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_sellable', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ number_format($incomeReport['total_sellable'] ?? 0, 0) }}</h3>
          </div>
          
          <!-- Total Sold -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 shadow-md border border-green-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-green-600 text-3xl">check_circle</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_sold', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-green-600">{{ number_format($incomeReport['total_sold'] ?? 0, 0) }}</h3>
          </div>
          
          <!-- Total Profit -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 shadow-md border border-emerald-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-emerald-600 text-3xl">trending_up</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_profit', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-emerald-600">{{ number_format($incomeReport['total_profit'] ?? 0, 3) }} ر.ع</h3>
          </div>
          
          <!-- Total Price Sent -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-pink-50 to-pink-100 shadow-md border border-pink-200">
            <div class="flex items-center justify-between mb-3">
              <span class="material-symbols-outlined text-[var(--primary-color)] text-3xl">payments</span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_price_sent', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-[var(--primary-color)]">{{ number_format($incomeReport['total_price_sent'] ?? 0, 3) }} ر.ع</h3>
          </div>
        </div>
        
        <!-- Summary Table -->
        <div class="mt-8 bg-gray-50 rounded-2xl p-6 border border-gray-200">
          <h4 class="text-lg font-bold text-gray-800 mb-4">{{ trans('messages.summary', [], session('locale')) }}</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-lg border border-gray-200">
              <p class="text-sm text-gray-600 mb-2">{{ trans('messages.items_available', [], session('locale')) }}</p>
              <p class="text-2xl font-bold text-[var(--primary-color)]">
                {{ number_format(($incomeReport['total_items_sent'] ?? 0) - ($incomeReport['total_items_pulled'] ?? 0) - ($incomeReport['total_sold'] ?? 0), 0) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                ({{ trans('messages.sent', [], session('locale')) }} - {{ trans('messages.pulled', [], session('locale')) }} - {{ trans('messages.sold', [], session('locale')) }})
              </p>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200">
              <p class="text-sm text-gray-600 mb-2">{{ trans('messages.profit_margin', [], session('locale')) }}</p>
              <p class="text-2xl font-bold text-emerald-600">
                @if(($incomeReport['total_price_sent'] ?? 0) > 0)
                  {{ number_format((($incomeReport['total_profit'] ?? 0) / ($incomeReport['total_price_sent'] ?? 1)) * 100, 2) }}%
                @else
                  0%
                @endif
              </p>
              <p class="text-xs text-gray-500 mt-1">
                ({{ trans('messages.profit', [], session('locale')) }} / {{ trans('messages.total_price_sent', [], session('locale')) }})
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- MODALS -->
    <!-- Sales Details Modal -->
    <div x-show="showSaleModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-6 space-y-3 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
          <div>
            <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.transfer_details', [], session('locale')) }} - <span x-text="currentSale.transfer_code"></span></h3>
            <p class="text-sm text-gray-600 mt-1">{{ trans('messages.date', [], session('locale')) }}: <span x-text="currentSale.date"></span></p>
          </div>
          <button @click="showSaleModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="detailSearch" type="text" placeholder="{{ trans('messages.search', [], session('locale')) }}"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]">
          <input x-model="detailDateFrom" type="date" class="h-10 px-2 border border-pink-200 rounded-lg">
          <input x-model="detailDateTo" type="date" class="h-10 px-2 border border-pink-200 rounded-lg">
        </div>
        <table class="w-full text-sm">
          <thead class="bg-pink-50 text-gray-700">
            <tr>
              <th class="p-2 text-right">{{ trans('messages.code', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.color', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.size', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.price', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="item in filteredDetails" :key="item.code + (item.size || '') + (item.color || '')">
              <tr class="border-t">
                <td class="p-2" x-text="item.code"></td>
                <td class="p-2" x-text="item.color || '-'"></td>
                <td class="p-2" x-text="item.size || '-'"></td>
                <td class="p-2" x-text="item.quantity"></td>
                <td class="p-2" x-text="formatCurrency(item.price)"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.pay_invoice', [], session('locale')) }} - <span x-text="currentPaymentInvoice.month"></span></h3>
          <button @click="showPaymentModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.amount', [], session('locale')) }}</label>
            <input type="number" step="0.001" x-model="paymentAmount" 
                   class="w-full h-10 px-3 border border-pink-200 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)]"
                   :placeholder="currentPaymentInvoice.amount">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.payment_date', [], session('locale')) }}</label>
            <input type="date" x-model="paymentDate" 
                   class="w-full h-10 px-3 border border-pink-200 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)]">
          </div>
          <div class="flex gap-3 pt-3">
            <button @click="savePayment()" 
                    class="flex-1 px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white font-semibold hover:bg-pink-700">
              {{ trans('messages.save_payments', [], session('locale')) }}
            </button>
            <button @click="showPaymentModal=false" 
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200">
              {{ trans('messages.cancel', [], session('locale')) }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Shipment Details Modal -->
    <div x-show="showShipModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-6 space-y-3 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
          <div>
            <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.shipment_details', [], session('locale')) }} - <span x-text="currentShipment.transfer_code"></span></h3>
            <p class="text-sm text-gray-600 mt-1">{{ trans('messages.transfer_date', [], session('locale')) }}: <span x-text="currentShipment.date"></span></p>
          </div>
          <button @click="showShipModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-pink-50 text-gray-700">
            <tr>
              <th class="p-2 text-right">{{ trans('messages.code', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.color', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.size', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.price', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="item in currentShipment.items" :key="item.code + (item.size || '') + (item.color || '')">
              <tr class="border-t">
                <td class="p-2" x-text="item.code"></td>
                <td class="p-2" x-text="item.color || '-'"></td>
                <td class="p-2" x-text="item.size || '-'"></td>
                <td class="p-2" x-text="item.quantity"></td>
                <td class="p-2" x-text="formatCurrency(item.price)"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.pay_invoice', [], session('locale')) }} - <span x-text="currentPaymentInvoice.month"></span></h3>
          <button @click="showPaymentModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.amount', [], session('locale')) }}</label>
            <input type="number" step="0.001" x-model="paymentAmount" 
                   class="w-full h-10 px-3 border border-pink-200 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)]"
                   :placeholder="currentPaymentInvoice.amount">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.payment_date', [], session('locale')) }}</label>
            <input type="date" x-model="paymentDate" 
                   class="w-full h-10 px-3 border border-pink-200 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)]">
          </div>
          <div class="flex gap-3 pt-3">
            <button @click="savePayment()" 
                    class="flex-1 px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white font-semibold hover:bg-pink-700">
              {{ trans('messages.save_payments', [], session('locale')) }}
            </button>
            <button @click="showPaymentModal=false" 
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200">
              {{ trans('messages.cancel', [], session('locale')) }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function boutiqueProfile() {
  return {
    tab: 'overview',
    boutiqueId: {{ $boutique->id }},
    
    // Delete boutique function
    deleteBoutique() {
      Swal.fire({
        title: '{{ trans("messages.confirm_delete_title", [], session("locale")) }}',
        text: '{{ trans("messages.confirm_delete_text", [], session("locale")) }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '{{ trans("messages.yes_delete", [], session("locale")) }}',
        cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: '/boutique/' + this.boutiqueId,
            method: 'DELETE',
            data: {
              _token: '{{ csrf_token() }}'
            },
            success: (data) => {
              Swal.fire(
                '{{ trans("messages.deleted_success", [], session("locale")) }}',
                '{{ trans("messages.deleted_success_text", [], session("locale")) }}',
                'success'
              ).then(() => {
                window.location.href = '/boutique_list';
              });
            },
            error: () => {
              Swal.fire(
                '{{ trans("messages.delete_error", [], session("locale")) }}',
                '{{ trans("messages.delete_error_text", [], session("locale")) }}',
                'error'
              );
            }
          });
        }
      });
    },

    // SALES
    salesSearch: '',
    dateFromSales: '',
    dateToSales: '',
    sales: @json($salesByTransfer ?? []),
    filteredSales: [],
    currentSale: {transfer_code:'', date:'', items:[]},
    showSaleModal: false,
    detailSearch: '',
    detailDateFrom: '',
    detailDateTo: '',
    get filteredDetails() {
      if (!this.currentSale.items) return [];
      const q = this.detailSearch.toLowerCase();
      return this.currentSale.items.filter(item => {
        const matchQ = !q || 
          (item.code && item.code.toLowerCase().includes(q)) ||
          (item.color && item.color.toLowerCase().includes(q)) ||
          (item.size && item.size.toLowerCase().includes(q));
        return matchQ;
      });
    },
    formatCurrency(n) {
      const v = Number(n || 0);
      return v.toLocaleString('ar-EG', {minimumFractionDigits: 3, maximumFractionDigits: 3}) + ' ر.ع';
    },
    getStatusText(status) {
      if (status === 'fully_paid') return '{{ trans('messages.fully_paid', [], session('locale')) }}';
      if (status === 'partially_paid') return '{{ trans('messages.partially_paid', [], session('locale')) }}';
      return '{{ trans('messages.not_paid', [], session('locale')) }}';
    },
    filterSales() {
      const q = this.salesSearch.toLowerCase();
      const from = this.dateFromSales ? new Date(this.dateFromSales) : null;
      const to   = this.dateToSales ? new Date(this.dateToSales) : null;
      this.filteredSales = this.sales.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || (r.transfer_code && r.transfer_code.toLowerCase().includes(q));
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openSaleDetails(row) {
      this.currentSale = {
        transfer_code: row.transfer_code,
        date: row.date,
        items: row.items || []
      };
      this.detailSearch = '';
      this.detailDateFrom = '';
      this.detailDateTo = '';
      this.showSaleModal = true;
    },

    // SHIPMENTS
    shipmentSearch: '',
    dateFromSh: '',
    dateToSh: '',
    shipments: @json($shipmentsData ?? []),
    filteredShipments: [],
    currentShipment: {transfer_code:'', date:'', items:[]},
    showShipModal: false,
    filterShipments() {
      const q = this.shipmentSearch.toLowerCase();
      const from = this.dateFromSh ? new Date(this.dateFromSh) : null;
      const to   = this.dateToSh ? new Date(this.dateToSh) : null;
      this.filteredShipments = this.shipments.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || (r.transfer_code && r.transfer_code.toLowerCase().includes(q));
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openShipmentDetails(row) {
      this.currentShipment = {
        transfer_code: row.transfer_code,
        date: row.date,
        items: row.items || []
      };
      this.showShipModal = true;
    },

    // INVOICES
    showPaymentModal: false,
    currentPaymentInvoice: {id: null, month: '', amount: 0},
    paymentAmount: '',
    paymentDate: '',
    openPaymentModal(invoiceId, month, amount) {
      this.currentPaymentInvoice = {
        id: invoiceId,
        month: month,
        amount: amount
      };
      this.paymentAmount = amount;
      this.paymentDate = new Date().toISOString().split('T')[0]; // Today's date
      this.showPaymentModal = true;
    },
    async savePayment() {
      if (!this.paymentAmount || !this.paymentDate) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.warning', [], session('locale')) }}',
            text: '{{ trans('messages.please_enter_amount_and_date', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.please_enter_amount_and_date', [], session('locale')) }}');
        }
        return;
      }
      
      try {
        const response = await fetch('{{ route('update_invoice_payment') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            invoices: [{
              id: this.currentPaymentInvoice.id,
              total_amount: this.paymentAmount,
              payment_date: this.paymentDate
            }]
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          // Close modal first
          this.showPaymentModal = false;
          
          // Show success message and reload after it closes
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.success', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.payment_updated_successfully', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false,
              allowOutsideClick: false,
              allowEscapeKey: false
            }).then(() => {
              location.reload(); // Reload to show updated invoice status
            });
          } else {
            alert(data.message || '{{ trans('messages.payment_updated_successfully', [], session('locale')) }}');
            setTimeout(() => {
              location.reload();
            }, 100);
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.error_updating_payments', [], session('locale')) }}'
            });
          } else {
            alert(data.message || '{{ trans('messages.error_updating_payments', [], session('locale')) }}');
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.error_updating_payments', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.error_updating_payments', [], session('locale')) }}');
        }
      }
    },

    // CHART
    initCharts() {
      // default filtered
      this.filterSales();
      this.filterShipments();

      const ctx = document.getElementById('salesChart');
      if (!ctx) return;
      
      // Get sales data from PHP
      const salesData = @json($salesData);
      const monthTranslationKeys = @json($monthNames);
      
      // Translate month names
      const monthLabels = monthTranslationKeys.map(key => {
        const translations = {
          'january': '{{ trans("messages.january", [], session("locale")) }}',
          'february': '{{ trans("messages.february", [], session("locale")) }}',
          'march': '{{ trans("messages.march", [], session("locale")) }}',
          'april': '{{ trans("messages.april", [], session("locale")) }}',
          'may': '{{ trans("messages.may", [], session("locale")) }}',
          'june': '{{ trans("messages.june", [], session("locale")) }}',
          'july': '{{ trans("messages.july", [], session("locale")) }}',
          'august': '{{ trans("messages.august", [], session("locale")) }}',
          'september': '{{ trans("messages.september", [], session("locale")) }}',
          'october': '{{ trans("messages.october", [], session("locale")) }}',
          'november': '{{ trans("messages.november", [], session("locale")) }}',
          'december': '{{ trans("messages.december", [], session("locale")) }}'
        };
        return translations[key] || key;
      });
      
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: monthLabels,
          datasets: [{
            label: '{{ trans('messages.total_sales_currency', [], session('locale')) }}',
            data: salesData,
            borderColor: '#d63384',
            backgroundColor: 'rgba(214, 51, 132, 0.15)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          plugins:{legend:{display:false}},
          scales:{y:{beginAtZero:true,ticks:{color:'#666'}},x:{ticks:{color:'#666'}}}
        }
      });
    }
  }
}
</script>

@include('layouts.footer')
@endsection