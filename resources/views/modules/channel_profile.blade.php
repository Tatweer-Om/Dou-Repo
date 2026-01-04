@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.channel_profile', [], session('locale')) ?? 'Channel Profile' }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="channelProfile()"
      x-init="init()">

  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
          @if(session('locale') == 'ar')
            {{ $channel->channel_name_ar ?? 'Channel Profile' }}
          @else
            {{ $channel->channel_name_en ?? 'Channel Profile' }}
          @endif
        </h2>
        <p class="text-gray-500 text-sm">
          @if(session('locale') == 'ar')
            {{ trans('messages.channel_name_ar', [], session('locale')) }}: {{ $channel->channel_name_ar ?? '-' }}
          @else
            {{ trans('messages.channel_name_en', [], session('locale')) }}: {{ $channel->channel_name_en ?? '-' }}
          @endif
        </p>
      </div>
      <div class="flex gap-2">
        <a href="{{ route('channel') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">arrow_back</span> {{ trans('messages.back', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4">
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_quantity_sent', [], session('locale')) ?? 'Total Quantity Sent' }}</p>
          <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ number_format($totalQuantitySent ?? 0) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
          inventory_2
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_transfers', [], session('locale')) ?? 'Total Transfers' }}</p>
          <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ number_format($totalTransfers) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
          local_shipping
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_sales', [], session('locale')) ?? 'Total Sales' }}</p>
          <h3 class="text-2xl font-extrabold text-green-600">{{ number_format($totalSales ?? 0, 2) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-green-100 text-green-600 rounded-full p-3">
          point_of_sale
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-cyan-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_orders', [], session('locale')) ?? 'Total Orders' }}</p>
          <h3 class="text-2xl font-extrabold text-blue-600">{{ number_format($totalOrders ?? 0) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-blue-100 text-blue-600 rounded-full p-3">
          receipt_long
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-orange-50 to-amber-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.items_sold', [], session('locale')) ?? 'Items Sold' }}</p>
          <h3 class="text-2xl font-extrabold text-orange-600">{{ number_format($totalItemsSold ?? 0) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-orange-100 text-orange-600 rounded-full p-3">
          shopping_cart
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.profit_earned', [], session('locale')) ?? 'Profit Earned' }}</p>
          <h3 class="text-2xl font-extrabold text-indigo-600">{{ number_format($totalProfit ?? 0, 2) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-indigo-100 text-indigo-600 rounded-full p-3">
          trending_up
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-teal-50 to-cyan-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.available_items', [], session('locale')) ?? 'Available Items' }}</p>
          <h3 class="text-2xl font-extrabold text-teal-600">{{ number_format($availableItems ?? 0) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-teal-100 text-teal-600 rounded-full p-3">
          check_circle
        </span>
      </div>
    </div>

    <!-- General Details -->
    <div class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4">{{ trans('messages.general_details', [], session('locale')) ?? 'General Details' }}</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.channel_name_en', [], session('locale')) ?? 'Channel Name (English)' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->channel_name_en ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.channel_name_ar', [], session('locale')) ?? 'Channel Name (Arabic)' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->channel_name_ar ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.status', [], session('locale')) ?? 'Status' }}</p>
          <p class="font-semibold text-gray-800">
            @if($channel->status_for_pos == 1)
              <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">{{ trans('messages.active', [], session('locale')) ?? 'Active' }}</span>
            @else
              <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">{{ trans('messages.inactive', [], session('locale')) ?? 'Inactive' }}</span>
            @endif
          </p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.added_by', [], session('locale')) ?? 'Added By' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->added_by ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.created_at', [], session('locale')) ?? 'Created At' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->created_at ? $channel->created_at->format('Y-m-d H:i:s') : '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.updated_at', [], session('locale')) ?? 'Updated At' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->updated_at ? $channel->updated_at->format('Y-m-d H:i:s') : '-' }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 border-b border-pink-100 overflow-x-auto no-scrollbar bg-white rounded-xl shadow-sm px-4">
      <button @click="tab='sales'"
              :class="tab==='sales' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">point_of_sale</span> {{ trans('messages.sales', [], session('locale')) ?? 'Sales' }}
      </button>
      <button @click="tab='transfers'"
              :class="tab==='transfers' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">local_shipping</span> {{ trans('messages.transfers', [], session('locale')) ?? 'Transfers' }}
      </button>
      <button @click="tab='transfer_items'"
              :class="tab==='transfer_items' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">inventory</span> {{ trans('messages.transfer_items', [], session('locale')) ?? 'Transfer Items' }}
      </button>
      <button @click="tab='item_status'"
              :class="tab==='item_status' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">check_circle</span> {{ trans('messages.item_status', [], session('locale')) ?? 'Item Status' }}
      </button>
    </div>

    <!-- SALES TAB -->
    <section x-show="tab==='sales'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="salesSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterSales()">
          <input x-model="salesDateFrom" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterSales()">
          <input x-model="salesDateTo" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterSales()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.order_no', [], session('locale')) ?? 'Order No' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.order_date', [], session('locale')) ?? 'Order Date' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.abaya_code', [], session('locale')) ?? 'Abaya Code' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.design_name', [], session('locale')) ?? 'Design Name' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) ?? 'Color' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) ?? 'Size' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.actions', [], session('locale')) ?? 'Actions' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredSales.length === 0">
                <tr>
                  <td colspan="8" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_sales_found', [], session('locale')) ?? 'No sales found' }}
                  </td>
                </tr>
              </template>
              <template x-for="sale in filteredSales" :key="sale.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 text-center font-semibold" x-text="sale.order_no || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.order_date || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.abaya_code || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.design_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.color_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.size_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="sale.quantity || 0"></td>
                  <td class="px-3 py-2 text-center">
                    <a :href="'/pos_bill?order_id=' + (sale.order_id || '')" 
                       target="_blank"
                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition text-xs font-semibold">
                      <span class="material-symbols-outlined text-base">receipt</span>
                      {{ trans('messages.receipt', [], session('locale')) ?? 'Receipt' }}
                    </a>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- TRANSFERS TAB -->
    <section x-show="tab==='transfers'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="transferSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterTransfers()">
          <input x-model="transferDateFrom" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransfers()">
          <input x-model="transferDateTo" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransfers()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1000px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.transfer_code', [], session('locale')) ?? 'Transfer Code' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.transfer_date', [], session('locale')) ?? 'Date' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.from', [], session('locale')) ?? 'From' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.to', [], session('locale')) ?? 'To' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredTransfers.length === 0">
                <tr>
                  <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_transfers_found', [], session('locale')) ?? 'No transfers found' }}
                  </td>
                </tr>
              </template>
              <template x-for="transfer in filteredTransfers" :key="transfer.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 text-center font-semibold" x-text="transfer.transfer_code"></td>
                  <td class="px-3 py-2 text-center" x-text="transfer.date"></td>
                  <td class="px-3 py-2 text-center" x-text="transfer.quantity || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="transfer.from || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="transfer.to || '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- TRANSFER ITEMS TAB -->
    <section x-show="tab==='transfer_items'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="itemSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterTransferItems()">
          <input x-model="itemDateFrom" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransferItems()">
          <input x-model="itemDateTo" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransferItems()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.transfer_code', [], session('locale')) ?? 'Transfer Code' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.transfer_date', [], session('locale')) ?? 'Transfer Date' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.abaya_code', [], session('locale')) ?? 'Abaya Code' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) ?? 'Color' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) ?? 'Size' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.from_location', [], session('locale')) ?? 'From Location' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.to_location', [], session('locale')) ?? 'To Location' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredTransferItems.length === 0">
                <tr>
                  <td colspan="8" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_transfer_items_found', [], session('locale')) ?? 'No transfer items found' }}
                  </td>
                </tr>
              </template>
              <template x-for="item in filteredTransferItems" :key="item.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 text-center font-semibold" x-text="item.transfer_code || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.transfer_date || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.abaya_code || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.color_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.size_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.quantity || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.from_location || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.to_location || '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ITEM STATUS TAB -->
    <section x-show="tab==='item_status'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="itemStatusSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterItemStatus()">
          <select x-model="itemStatusFilter" 
                  class="h-10 px-3 border border-pink-200 rounded-lg"
                  @change="filterItemStatus()">
            <option value="all">{{ trans('messages.all', [], session('locale')) ?? 'All' }}</option>
            <option value="available">{{ trans('messages.available', [], session('locale')) ?? 'Available' }}</option>
            <option value="sold">{{ trans('messages.sold', [], session('locale')) ?? 'Sold' }}</option>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.abaya_code', [], session('locale')) ?? 'Abaya Code' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.design_name', [], session('locale')) ?? 'Design Name' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) ?? 'Color' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) ?? 'Size' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.status', [], session('locale')) ?? 'Status' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredItemStatus.length === 0">
                <tr>
                  <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_items_found', [], session('locale')) ?? 'No items found' }}
                  </td>
                </tr>
              </template>
              <template x-for="item in filteredItemStatus" :key="item.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 text-center font-semibold" x-text="item.abaya_code || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.design_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.color_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.size_name || '-'"></td>
                  <td class="px-3 py-2 text-center" x-text="item.quantity || 0"></td>
                  <td class="px-3 py-2 text-center">
                    <span x-show="item.status === 'sold'" 
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                      {{ trans('messages.sold', [], session('locale')) ?? 'Sold' }}
                    </span>
                    <span x-show="item.status === 'available'" 
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                      {{ trans('messages.available', [], session('locale')) ?? 'Available' }}
                    </span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div>
</main>



@endsection

