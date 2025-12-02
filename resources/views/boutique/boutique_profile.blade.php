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
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.boutique_profile_name', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">{{ trans('messages.boutique_profile_shelf_location', [], session('locale')) }}</p>
      </div>
      <div class="flex gap-2">
        <button class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">edit</span> {{ trans('messages.edit', [], session('locale')) }}
        </button>
        <button class="px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition font-semibold">
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
    </div>

    <!-- OVERVIEW -->
    <section x-show="tab==='overview'" x-transition>
      <!-- كلمات عليا -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.total_sales', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">1,540 ر.ع</h3>
          </div>
          <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">attach_money</span>
        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.number_of_shipments', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">8</h3>
          </div>
          <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">local_shipping</span>
        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.abayas_sent', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">210</h3>
          </div>
          <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">inventory_2</span>
        </div>
        <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ trans('messages.unpaid_invoices', [], session('locale')) }}</p>
            <h3 class="text-2xl font-extrabold text-red-500">2</h3>
          </div>
          <span class="material-symbols-outlined bg-red-100 text-red-500 rounded-full p-3">warning</span>
        </div>
      </div>

      <!-- بطاقات مصغّرة تحليلية -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.best_selling_color', [], session('locale')) }}</p>
            <h4 class="font-bold text-gray-800">أسود</h4>
          </div>
          <span class="inline-block w-6 h-6 rounded-full border" style="background:#000"></span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.most_requested_size', [], session('locale')) }}</p>
            <h4 class="font-bold text-gray-800">M</h4>
          </div>
          <span class="material-symbols-outlined text-gray-500">straighten</span>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-pink-100 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ trans('messages.monthly_profit', [], session('locale')) }}</p>
            <h4 class="font-bold text-[var(--primary-color)]">430 ر.ع</h4>
          </div>
          <span class="material-symbols-outlined text-[var(--primary-color)]">trending_up</span>
        </div>
      </div>

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
          <input x-model="salesSearch" type="text" placeholder="{{ trans('messages.search_by_code_color_size', [], session('locale')) }}"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]">
          <input x-model="dateFromSales" type="date" class="h-10 px-2 border border-pink-200 rounded-lg">
          <input x-model="dateToSales" type="date" class="h-10 px-2 border border-pink-200 rounded-lg">
          <button @click="filterSales()" class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white text-sm">{{ trans('messages.filter', [], session('locale')) }}</button>
          <button class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 text-sm hover:bg-purple-200">
            <span class="material-symbols-outlined text-base align-middle">download</span> Excel
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[860px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.invoice_number', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.number_of_items', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in filteredSales" :key="row.invoice">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2" x-text="row.date"></td>
                  <td class="px-3 py-2" x-text="row.invoice"></td>
                  <td class="px-3 py-2" x-text="row.items"></td>
                  <td class="px-3 py-2" x-text="row.total + ' ر.ع'"></td>
                  <td class="px-3 py-2 text-center">
                    <span class="font-bold" :class="row.status==='{{ trans('messages.paid', [], session('locale')) }}' ? 'text-green-600' : 'text-yellow-600'" x-text="row.status"></span>
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
          <input x-model="shipmentSearch" type="text" placeholder="{{ trans('messages.search_by_order_status', [], session('locale')) }}"
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
          <table class="w-full text-sm min-w-[860px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.order_number', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.shipment_date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total_items', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in filteredShipments" :key="row.order_no">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2" x-text="row.order_no"></td>
                  <td class="px-3 py-2" x-text="row.date"></td>
                  <td class="px-3 py-2" x-text="row.qty"></td>
                  <td class="px-3 py-2">
                    <span class="font-bold" :class="row.status==='{{ trans('messages.done', [], session('locale')) }}' ? 'text-green-600' : 'text-yellow-600'" x-text="row.status"></span>
                  </td>
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
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[760px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.invoice_number', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.amount', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in invoices" :key="row.no">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2" x-text="row.no"></td>
                  <td class="px-3 py-2" x-text="row.date"></td>
                  <td class="px-3 py-2" x-text="row.amount + ' ر.ع'"></td>
                  <td class="px-3 py-2 text-center">
                    <span class="font-bold" :class="row.paid ? 'text-green-600' : 'text-red-600'" x-text="row.paid ? '{{ trans('messages.paid', [], session('locale')) }}' : '{{ trans('messages.unpaid', [], session('locale')) }}'"></span>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <template x-if="!row.paid">
                      <button @click="openPay(row)"
                              class="px-4 py-1 rounded-full bg-[var(--primary-color)] text-white font-semibold text-xs hover:opacity-90">
                        💳 {{ trans('messages.pay', [], session('locale')) }}
                      </button>
                    </template>
                    <template x-if="row.paid">
                      <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">{{ trans('messages.paid', [], session('locale')) }}</span>
                    </template>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- MODALS -->
    <!-- Sales Details Modal -->
    <div x-show="showSaleModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-3">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.sales_details', [], session('locale')) }} - <span x-text="currentSale.invoice"></span></h3>
          <button @click="showSaleModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-pink-50 text-gray-700">
            <tr>
              <th class="p-2 text-right">{{ trans('messages.code', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.color', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.size', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="item in currentSale.details" :key="item.code + item.size + item.color">
              <tr>
                <td class="p-2" x-text="item.code"></td>
                <td class="p-2" x-text="item.color"></td>
                <td class="p-2" x-text="item.size"></td>
                <td class="p-2" x-text="item.qty"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Shipment Details Modal -->
    <div x-show="showShipModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-3">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.shipment_details', [], session('locale')) }} - <span x-text="currentShipment.order_no"></span></h3>
          <button @click="showShipModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-pink-50 text-gray-700">
            <tr>
              <th class="p-2 text-right">{{ trans('messages.color', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.size', [], session('locale')) }}</th>
              <th class="p-2 text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="item in currentShipment.details" :key="item.color + item.size">
              <tr>
                <td class="p-2" x-text="item.color"></td>
                <td class="p-2" x-text="item.size"></td>
                <td class="p-2" x-text="item.qty"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pay Modal -->
    <div x-show="showPayModal" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-3">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.pay_invoice', [], session('locale')) }} <span x-text="currentInvoice.no"></span></h3>
          <button @click="showPayModal=false" class="text-gray-500 hover:text-gray-700">✖</button>
        </div>
        <p class="font-semibold text-gray-700">{{ trans('messages.required_amount', [], session('locale')) }}: <span class="text-[var(--primary-color)] font-bold" x-text="currentInvoice.amount + ' ر.ع'"></span></p>
        <select class="w-full h-10 rounded-lg border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)]">
          <option>{{ trans('messages.cash', [], session('locale')) }}</option><option>{{ trans('messages.bank_transfer', [], session('locale')) }}</option><option>{{ trans('messages.card', [], session('locale')) }}</option>
        </select>
        <textarea rows="3" class="w-full rounded-lg border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] text-sm p-2" placeholder="{{ trans('messages.payment_notes', [], session('locale')) }}"></textarea>
        <div class="flex justify-end gap-2">
          <button @click="showPayModal=false" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold">{{ trans('messages.cancel', [], session('locale')) }}</button>
          <button @click="showPayModal=false" class="px-6 py-2 rounded-lg bg-[var(--primary-color)] text-white font-bold hover:opacity-90">{{ trans('messages.confirm_payment', [], session('locale')) }}</button>
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

    // SALES (dummy)
    salesSearch: '',
    dateFromSales: '',
    dateToSales: '',
    sales: [
      {date:'2025-11-01', invoice:'INV-101', items:18, total:450, status:'مدفوعة',
       details:[{code:'ABY101',color:'أسود',size:'M',qty:3},{code:'ABY101',color:'أسود',size:'L',qty:2}]},
      {date:'2025-11-02', invoice:'INV-102', items:12, total:320, status:'مدفوعة',
       details:[{code:'ABY103',color:'بيج',size:'S',qty:1},{code:'ABY103',color:'بيج',size:'M',qty:2}]},
      {date:'2025-11-03', invoice:'INV-103', items:10, total:300, status:'قيد المراجعة',
       details:[{code:'ABY104',color:'رمادي',size:'M',qty:2},{code:'ABY104',color:'رمادي',size:'L',qty:1}]},
      {date:'2025-11-04', invoice:'INV-104', items:15, total:520, status:'مدفوعة',
       details:[{code:'ABY105',color:'أسود',size:'M',qty:4},{code:'ABY106',color:'بيج',size:'L',qty:2}]},
      {date:'2025-11-05', invoice:'INV-105', items:11, total:360, status:'مدفوعة',
       details:[{code:'ABY107',color:'كحلي',size:'M',qty:3},{code:'ABY107',color:'كحلي',size:'S',qty:2}]},
    ],
    filteredSales: [],
    currentSale: {invoice:'', details:[]},
    showSaleModal: false,
    filterSales() {
      const q = this.salesSearch.toLowerCase();
      const from = this.dateFromSales ? new Date(this.dateFromSales) : null;
      const to   = this.dateToSales ? new Date(this.dateToSales) : null;
      this.filteredSales = this.sales.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || r.invoice.toLowerCase().includes(q);
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openSaleDetails(row) {
      this.currentSale = row;
      this.showSaleModal = true;
    },

    // SHIPMENTS (dummy)
    shipmentSearch: '',
    dateFromSh: '',
    dateToSh: '',
    shipments: [
      {order_no:'ORD-2025-01', date:'2025-11-01', qty:25, status:'تم',
       details:[{color:'أسود',size:'M',qty:5},{color:'أسود',size:'L',qty:3},{color:'بيج',size:'S',qty:2}]},
      {order_no:'ORD-2025-02', date:'2025-11-02', qty:18, status:'تم',
       details:[{color:'رمادي',size:'M',qty:4},{color:'أسود',size:'M',qty:2}]},
      {order_no:'ORD-2025-03', date:'2025-11-03', qty:22, status:'قيد الشحن',
       details:[{color:'بيج',size:'M',qty:5},{color:'أسود',size:'S',qty:2}]},
      {order_no:'ORD-2025-04', date:'2025-11-04', qty:12, status:'تم',
       details:[{color:'كحلي',size:'L',qty:3},{color:'رمادي',size:'M',qty:1}]},
      {order_no:'ORD-2025-05', date:'2025-11-05', qty:28, status:'تم',
       details:[{color:'أسود',size:'M',qty:6},{color:'أسود',size:'XL',qty:2}]},
    ],
    filteredShipments: [],
    currentShipment: {order_no:'', details:[]},
    showShipModal: false,
    filterShipments() {
      const q = this.shipmentSearch.toLowerCase();
      const from = this.dateFromSh ? new Date(this.dateFromSh) : null;
      const to   = this.dateToSh ? new Date(this.dateToSh) : null;
      this.filteredShipments = this.shipments.filter(r => {
        const d = new Date(r.date);
        const matchQ = !q || r.order_no.toLowerCase().includes(q) || r.status.toLowerCase().includes(q);
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },
    openShipmentDetails(row) {
      this.currentShipment = row;
      this.showShipModal = true;
    },

    // INVOICES (dummy)
    invoices: [
      {no:'INV-2025-01', date:'2025-11-01', amount:120, paid:true},
      {no:'INV-2025-02', date:'2025-11-08', amount:70,  paid:false},
      {no:'INV-2025-03', date:'2025-11-15', amount:85,  paid:true},
      {no:'INV-2025-04', date:'2025-11-22', amount:95,  paid:false},
      {no:'INV-2025-05', date:'2025-11-29', amount:110, paid:true},
    ],
    currentInvoice: {no:'', amount:0},
    showPayModal: false,
    openPay(row) {
      this.currentInvoice = row;
      this.showPayModal = true;
    },

    // CHART
    initCharts() {
      // default filtered
      this.filterSales();
      this.filterShipments();

      const ctx = document.getElementById('salesChart');
      if (!ctx) return;
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['{{ trans('messages.june', [], session('locale')) }}','{{ trans('messages.july', [], session('locale')) }}','{{ trans('messages.august', [], session('locale')) }}','{{ trans('messages.september', [], session('locale')) }}','{{ trans('messages.october', [], session('locale')) }}','{{ trans('messages.november', [], session('locale')) }}'],
          datasets: [{
            label: '{{ trans('messages.total_sales_currency', [], session('locale')) }}',
            data: [200, 320, 280, 450, 390, 500],
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