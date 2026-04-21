@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.special_orders_income_report', [], session('locale')) ?: 'Special Orders Income Report' }}</title>
@endpush

<script>
window.specialOrdersIncomeReport = function() {
  return {
    dataUrl: '{{ route('reports.special_orders_income.data') }}',
    fromDate: '{{ date('Y-m-d') }}',
    toDate: '{{ date('Y-m-d') }}',
    items: [],
    totals: {},
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null,
    loaded: false,

    async loadReport(page = 1) {
      this.loading = true;
      this.error = null;
      const params = new URLSearchParams({ page: String(page), from_date: this.fromDate || '', to_date: this.toDate || '' });
      try {
        const res = await fetch(this.dataUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await res.json();
        if (!res.ok || !d.success) { this.error = d.message || 'Error loading data'; this.items = []; return; }
        this.items = d.orders || [];
        this.totals = d.totals || {};
        this.currentPage = Number(d.current_page) || 1;
        this.lastPage = Math.max(1, Number(d.last_page) || 1);
      } catch (e) { this.error = e.message || 'Error loading data'; this.items = []; }
      finally { this.loading = false; this.loaded = true; }
    },

    goToPage(page) { if (page < 1 || page > this.lastPage) return; this.loadReport(page); },

    get pageList() {
      const L = this.lastPage, C = this.currentPage;
      if (L <= 1) return [];
      if (L <= 7) return Array.from({ length: L }, (_, i) => i + 1);
      if (C <= 4) return [1, 2, 3, 4, 5, '...', L];
      if (C >= L - 3) return [1, '...', L - 4, L - 3, L - 2, L - 1, L];
      return [1, '...', C - 1, C, C + 1, '...', L];
    },

    formatNum(n) { return Number(n ?? 0).toFixed(3); },

    get exportPdfUrl() {
      let u = '{{ route('reports.special_orders_income.export_pdf') }}';
      if (this.fromDate) u += (u.includes('?') ? '&' : '?') + 'from_date=' + encodeURIComponent(this.fromDate);
      if (this.toDate) u += (u.includes('?') ? '&' : '?') + 'to_date=' + encodeURIComponent(this.toDate);
      return u;
    },
    get exportExcelUrl() {
      let u = '{{ route('reports.special_orders_income.export_excel') }}';
      if (this.fromDate) u += (u.includes('?') ? '&' : '?') + 'from_date=' + encodeURIComponent(this.fromDate);
      if (this.toDate) u += (u.includes('?') ? '&' : '?') + 'to_date=' + encodeURIComponent(this.toDate);
      return u;
    }
  };
};
</script>

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" x-data="specialOrdersIncomeReport()" x-init="loadReport(1)">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">{{ trans('messages.special_orders_income_report', [], session('locale')) ?: 'Special Orders Income Report' }}</h2>
            <div class="mt-3 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                <p class="text-blue-800 font-medium">@if(session('locale') == 'ar') هذه هي الطلبات التي تم تسليمها للعملاء @else These are the orders delivered to customers @endif</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-[var(--border-color)]">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') من تاريخ @else From Date @endif</label>
                        <input type="date" x-model="fromDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') إلى تاريخ @else To Date @endif</label>
                        <input type="date" x-model="toDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    </div>
                    <div class="flex items-end">
                        <button @click="loadReport(1)" :disabled="loading" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-accent transition flex items-center gap-2 disabled:opacity-70">
                            <span class="material-symbols-outlined" :class="loading ? 'animate-spin' : ''">search</span>
                            @if(session('locale') == 'ar') بحث @else Filter @endif
                        </button>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a :href="exportPdfUrl" target="_blank" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        {{ trans('messages.export_pdf', [], session('locale')) ?: 'Export PDF' }}
                    </a>
                    <a :href="exportExcelUrl" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <span class="material-symbols-outlined">file_download</span>
                        {{ trans('messages.export_excel', [], session('locale')) ?: 'Export Excel' }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" x-show="Object.keys(totals).length > 0" x-transition>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-[var(--border-color)]">
                <p class="text-sm text-gray-500 mb-1">@if(session('locale') == 'ar') إجمالي المبلغ @else Total Amount @endif</p>
                <p class="text-2xl font-bold text-[var(--text-primary)]" x-text="formatNum(totals.total_amount) + ' ر.ع'">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-red-200">
                <p class="text-sm text-red-500 mb-1">@if(session('locale') == 'ar') إجمالي الخصم @else Total Discount @endif</p>
                <p class="text-2xl font-bold text-red-600" x-text="formatNum(totals.total_discount) + ' ر.ع'">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-green-200">
                <p class="text-sm text-green-600 mb-1">@if(session('locale') == 'ar') المبلغ المدفوع @else Paid Amount @endif</p>
                <p class="text-2xl font-bold text-green-700" x-text="formatNum(totals.paid_amount) + ' ر.ع'">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-[var(--border-color)]">
                <p class="text-sm text-gray-500 mb-1">@if(session('locale') == 'ar') الربح @else Profit @endif</p>
                <p class="text-2xl font-bold text-[var(--text-primary)]" x-text="formatNum(totals.profit) + ' ر.ع'">0.000</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.order_number', [], session('locale')) ?: 'Order Number' }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') اسم العميل @else Customer Name @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') رقم العميل @else Customer Phone @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') المصدر @else Source @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') المبلغ الإجمالي @else Total Amount @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') الخصم @else Discount @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') المبلغ المدفوع @else Paid Amount @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') رسوم التوصيل @else Delivery Charges @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') الربح @else Profit @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') تاريخ الإنشاء @else Created At @endif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500"><span class="material-symbols-outlined animate-spin align-middle">refresh</span> @if(session('locale') == 'ar') جاري التحميل... @else Loading... @endif</td></tr>
                        </template>
                        <template x-if="!loading && error">
                            <tr><td colspan="10" class="px-4 py-8 text-center text-red-500" x-text="error"></td></tr>
                        </template>
                        <template x-if="!loading && !error && (!items || items.length === 0) && loaded">
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') لا توجد بيانات @else No data found @endif</td></tr>
                        </template>
                        <template x-if="!loading && !error && items && items.length > 0">
                            <template x-for="order in items" :key="order.id">
                                <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                    <td class="px-4 py-4 font-semibold whitespace-nowrap" x-text="order.order_no"></td>
                                    <td class="px-4 py-4 whitespace-nowrap" x-text="order.customer_name"></td>
                                    <td class="px-4 py-4 whitespace-nowrap" x-text="order.customer_phone"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="order.source === 'whatsapp' ? 'bg-green-100 text-green-800' : (order.source === 'walkin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800')" x-text="order.source_label || (order.source === 'whatsapp' ? 'WhatsApp' : 'Direct')"></span>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(order.total_amount)"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap font-semibold"
                                        :class="parseFloat(order.discount) > 0 ? 'text-red-600' : 'text-gray-400'"
                                        x-text="parseFloat(order.discount) > 0 ? '– ' + formatNum(order.discount) : '—'"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(order.paid_amount)"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(order.delivery_charges || 0)"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(order.profit)"></td>
                                    <td class="px-4 py-4 whitespace-nowrap" x-text="order.created_at"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!loading && !loaded && !error">
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') اختر التواريخ واضغط بحث لعرض التقرير @else Select dates and click Filter to view report @endif</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-center mt-6" x-show="lastPage > 1">
            <ul class="dress_pagination flex flex-wrap gap-2 justify-center items-center list-none p-0 m-0">
                <li><button @click.prevent="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 border rounded-lg text-sm transition disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-gray-200">«</button></li>
                <template x-for="(p, i) in pageList" :key="p === '...' ? 'e'+i : p">
                    <li>
                        <template x-if="p === '...'"><span class="px-2 py-1 text-gray-500">…</span></template>
                        <template x-if="p !== '...'"><button @click.prevent="goToPage(p)" :class="p === currentPage ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white hover:bg-gray-100'" class="px-4 py-1 border rounded-lg text-sm font-medium transition" x-text="p"></button></template>
                    </li>
                </template>
                <li><button @click.prevent="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-3 py-1 border rounded-lg text-sm transition disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-gray-200">»</button></li>
            </ul>
        </div>
    </div>
</main>

@include('layouts.footer')
@endsection
