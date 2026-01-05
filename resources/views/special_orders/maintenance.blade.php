@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.maintenance_status', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6" x-data="maintenanceApp" x-init="init()">

  <!-- 📊 Statistics Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Delivered to Tailor -->
    <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-orange-500">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm mb-1">{{ trans('messages.delivered_to_tailor', [], session('locale')) }}</p>
          <p class="text-3xl font-bold text-orange-600" x-text="statistics.delivered_to_tailor || 0"></p>
        </div>
        <div class="bg-orange-100 p-4 rounded-full">
          <span class="material-symbols-outlined text-orange-600 text-4xl">send</span>
        </div>
      </div>
    </div>

    <!-- Received from Tailor -->
    <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-blue-500">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm mb-1">{{ trans('messages.received_from_tailor', [], session('locale')) }}</p>
          <p class="text-3xl font-bold text-blue-600" x-text="statistics.received_from_tailor || 0"></p>
        </div>
        <div class="bg-blue-100 p-4 rounded-full">
          <span class="material-symbols-outlined text-blue-600 text-4xl">inventory_2</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
    <div class="flex gap-3 border-b border-gray-200 overflow-x-auto no-scrollbar">
      <button @click="activeTab = 'current'"
              :class="activeTab === 'current' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-4 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">list</span> {{ trans('messages.current_items', [], session('locale')) }}
      </button>
      <button @click="activeTab = 'history'"
              :class="activeTab === 'history' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-4 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">history</span> {{ trans('messages.repair_history', [], session('locale')) }}
      </button>
    </div>
  </div>

  <!-- Current Items Tab -->
  <div x-show="activeTab === 'current'" x-transition>
    <!-- 🔍 Search Delivered Orders -->
    <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
      <h3 class="text-lg font-semibold mb-3">{{ trans('messages.search_delivered_orders', [], session('locale')) ?: 'Search Delivered Orders' }}</h3>
      <div class="relative">
        <input type="text" 
               placeholder="{{ trans('messages.search_by_customer_order_code_phone', [], session('locale')) ?: 'Search by customer name, order number, abaya code, or phone...' }}"
               x-model="deliveredOrderSearch"
               @input.debounce.500ms="searchDeliveredOrders()"
               class="form-input w-full border-gray-300 rounded-xl px-4 py-2 shadow-sm focus:ring-primary">
        <div x-show="deliveredOrderSearch && deliveredOrderSearchResults.length > 0" 
             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
          <template x-for="order in deliveredOrderSearchResults" :key="order.id">
            <div @click="openOrderItemsModal(order)"
                 class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 last:border-b-0">
              <p class="font-semibold text-indigo-600" x-text="order.order_no"></p>
              <p class="text-sm text-gray-600" x-text="order.customer_name + ' - ' + order.customer_phone"></p>
              <p class="text-xs text-gray-500" x-text="order.items_count + ' {{ trans('messages.items', [], session('locale')) }}'"></p>
            </div>
          </template>
        </div>
        <div x-show="deliveredOrderSearch && deliveredOrderSearchResults.length === 0 && !searchingDeliveredOrders" 
             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-sm text-gray-500">
          {{ trans('messages.no_orders_found', [], session('locale')) ?: 'No orders found' }}
        </div>
      </div>
    </div>

    <!-- 🔍 Search Current Items -->
    <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <input type="text" 
               placeholder="{{ trans('messages.search_placeholder_maintenance', [], session('locale')) }}"
               x-model="search"
               class="form-input w-full md:w-72 border-gray-300 rounded-xl px-4 py-2 shadow-sm focus:ring-primary">
      </div>
    </div>

    <!-- 📋 Items List -->
  <div class="bg-white rounded-2xl shadow-md overflow-hidden">
    <div x-show="loading" class="p-8 text-center">
      <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
      <p class="mt-2 text-gray-500">{{ trans('messages.loading', [], session('locale')) }}</p>
    </div>

    <div x-show="!loading && filteredItems().length === 0" class="p-8 text-center text-gray-500">
      {{ trans('messages.no_items_found', [], session('locale')) }}
    </div>

    <!-- Desktop Table -->
    <table class="w-full text-sm hidden md:table" x-show="!loading && filteredItems().length > 0">
      <thead class="bg-gray-100 text-gray-700">
        <tr>
          <th class="py-3 px-4 text-left">{{ trans('messages.image', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.design_name', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.code', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.order_no', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.customer', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.customer_phone', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.status', [], session('locale')) }}</th>
          <th class="py-3 px-4 text-left">{{ trans('messages.actions', [], session('locale')) }}</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="item in paginatedItems()" :key="item.id">
          <tr class="border-t hover:bg-indigo-50 transition">
            <td class="py-3 px-4">
              <img :src="item.image" 
                   :alt="item.design_name"
                   class="w-16 h-16 object-cover rounded-lg">
            </td>
            <td class="py-3 px-4 font-semibold" x-text="item.design_name"></td>
            <td class="py-3 px-4 text-gray-600" x-text="item.abaya_code"></td>
            <td class="py-3 px-4">
              <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold" x-text="item.quantity || 1"></span>
            </td>
            <td class="py-3 px-4">
              <p class="font-semibold text-indigo-600" x-text="item.order_no || '—'"></p>
            </td>
            <td class="py-3 px-4">
              <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
            </td>
            <td class="py-3 px-4">
              <p class="text-gray-600" x-text="item.customer_phone || 'N/A'"></p>
            </td>
            <td class="py-3 px-4">
              <span x-show="item.maintenance_status" 
                    :class="getStatusBadgeClass(item.maintenance_status)" 
                    class="px-3 py-1 rounded-full text-xs font-semibold"
                    x-text="getStatusLabel(item.maintenance_status)"></span>
              <span x-show="!item.maintenance_status" 
                    class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                {{ trans('messages.not_in_maintenance', [], session('locale')) }}
              </span>
            </td>
            <td class="py-3 px-4">
              <div class="flex items-center gap-2">
                <button x-show="item.maintenance_status === 'delivered_to_tailor' && item.maintenance_notes"
                        @click="openNotesModal(item)"
                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                        title="{{ trans('messages.view_notes', [], session('locale')) }}">
                  <span class="material-symbols-outlined text-lg">note</span>
                </button>
                <button x-show="item.maintenance_status === 'delivered_to_tailor'"
                        @click="openActionModal(item)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                  {{ trans('messages.receive_from_tailor', [], session('locale')) }}
                </button>
                <button x-show="item.maintenance_status === 'received_from_tailor'"
                        @click="openDeliverModal(item)"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                  {{ trans('messages.deliver', [], session('locale')) ?: 'Deliver' }}
                </button>
                <button x-show="item.maintenance_status !== 'delivered_to_tailor' && item.maintenance_status !== 'received_from_tailor'"
                        @click="openActionModal(item)"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                  {{ trans('messages.send_to_tailor', [], session('locale')) }}
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y" x-show="!loading && filteredItems().length > 0">
      <template x-for="item in paginatedItems()" :key="item.id">
        <div class="p-4">
          <div class="flex gap-4">
            <img :src="item.image" 
                 :alt="item.design_name"
                 class="w-20 h-20 object-cover rounded-lg">
            <div class="flex-1">
              <h3 class="font-semibold text-lg" x-text="item.design_name || 'N/A'"></h3>
              <p class="text-sm text-gray-600" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + (item.abaya_code || 'N/A')"></p>
              <p class="text-sm mt-1">
                <span class="text-indigo-600 font-semibold" x-text="'{{ trans('messages.order_no', [], session('locale')) }}: ' + (item.order_no || '—')"></span>
                <span class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold" x-text="'{{ trans('messages.quantity', [], session('locale')) ?: 'Qty' }}: ' + (item.quantity || 1)"></span>
              </p>
              <p class="text-sm mt-1">
                <span class="font-medium" x-text="item.customer_name || 'N/A'"></span>
                <span class="text-gray-500" x-text="' - ' + (item.customer_phone || 'N/A')"></span>
              </p>
              <div class="flex gap-2 mt-2">
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </div>
              <div class="flex gap-2 mt-2">
                <button x-show="item.maintenance_status === 'delivered_to_tailor' && item.maintenance_notes"
                        @click="openNotesModal(item)"
                        class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition border border-indigo-200 flex-shrink-0"
                        title="{{ trans('messages.view_notes', [], session('locale')) }}">
                  <span class="material-symbols-outlined text-lg">note</span>
                </button>
                <button x-show="item.maintenance_status === 'delivered_to_tailor'"
                        @click="openActionModal(item)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex-1">
                  {{ trans('messages.receive_from_tailor', [], session('locale')) }}
                </button>
                <button x-show="item.maintenance_status === 'received_from_tailor'"
                        @click="openDeliverModal(item)"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex-1">
                  {{ trans('messages.deliver', [], session('locale')) ?: 'Deliver' }}
                </button>
                <button x-show="item.maintenance_status !== 'delivered_to_tailor' && item.maintenance_status !== 'received_from_tailor'"
                        @click="openActionModal(item)"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex-1">
                  {{ trans('messages.send_to_tailor', [], session('locale')) }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-6 px-4 pb-4" x-show="!loading && filteredItems().length > 0">
      <p class="text-sm text-gray-500">
        {{ trans('messages.showing', [], session('locale')) }}
        <span x-text="startItem()"></span> -
        <span x-text="endItem()"></span>
        {{ trans('messages.of', [], session('locale')) }}
        <span x-text="filteredItems().length"></span>
        {{ trans('messages.items', [], session('locale')) }}
      </p>

      <div class="flex items-center gap-2 justify-end">
        <button @click="prevPage()" 
                class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                :disabled="page === 1"
                :class="page === 1 ? 'opacity-50 cursor-not-allowed' : ''">
          {{ trans('messages.previous', [], session('locale')) }}
        </button>

        <template x-for="p in pageNumbers()" :key="p">
          <button @click="goToPage(p)"
                  :class="page === p 
                           ? 'px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm font-semibold' 
                           : 'px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm'">
            <span x-text="p"></span>
          </button>
        </template>

        <button @click="nextPage()" 
                class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                :disabled="page === totalPages()"
                :class="page === totalPages() ? 'opacity-50 cursor-not-allowed' : ''">
          {{ trans('messages.next', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>
  </div>

  <!-- Repair History Tab -->
  <div x-show="activeTab === 'history'" x-transition>
    <div class="bg-white rounded-2xl shadow-md overflow-hidden">
      <div x-show="loadingHistory" class="p-8 text-center">
        <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
        <p class="mt-2 text-gray-500">{{ trans('messages.loading_history', [], session('locale')) }}</p>
      </div>

      <div x-show="!loadingHistory && repairHistory.length === 0" class="p-8 text-center text-gray-500">
        {{ trans('messages.no_repair_history_found', [], session('locale')) }}
      </div>

      <!-- History Table -->
      <table class="w-full text-sm hidden md:table" x-show="!loadingHistory && repairHistory.length > 0">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="py-3 px-4 text-left">{{ trans('messages.order_no', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.design_name', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.code', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.customer', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.customer_phone', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.tailor', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.sent_date', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.received_date', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.delivery_charges', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.repair_cost', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.cost_bearer', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.notes', [], session('locale')) }}</th>
            <th class="py-3 px-4 text-left">{{ trans('messages.status', [], session('locale')) }}</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="item in paginatedHistory()" :key="item.id">
            <tr class="border-t hover:bg-indigo-50 transition">
              <td class="py-3 px-4 font-semibold text-indigo-600" x-text="item.order_no || '—'"></td>
              <td class="py-3 px-4 font-semibold" x-text="item.design_name || 'N/A'"></td>
              <td class="py-3 px-4 text-gray-600" x-text="item.abaya_code || 'N/A'"></td>
              <td class="py-3 px-4">
                <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
              </td>
              <td class="py-3 px-4">
                <p class="text-gray-600" x-text="item.customer_phone || 'N/A'"></p>
              </td>
              <td class="py-3 px-4">
                <p class="font-medium" x-text="item.tailor_name || 'N/A'"></p>
              </td>
              <td class="py-3 px-4 text-sm text-gray-600" x-text="item.sent_date || '—'"></td>
              <td class="py-3 px-4 text-sm text-gray-600" x-text="item.received_date || '—'"></td>
              <td class="py-3 px-4 text-sm font-semibold" x-text="item.delivery_charges ? item.delivery_charges + ' ر.ع' : '—'"></td>
              <td class="py-3 px-4 text-sm font-semibold" x-text="item.repair_cost ? item.repair_cost + ' ر.ع' : '—'"></td>
              <td class="py-3 px-4">
                <span x-show="item.cost_bearer" 
                      :class="item.cost_bearer === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                      class="px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="item.cost_bearer === 'customer' ? '{{ trans('messages.customer_bearer', [], session('locale')) }}' : '{{ trans('messages.company_bearer', [], session('locale')) }}'"></span>
                <span x-show="!item.cost_bearer" class="text-gray-400">—</span>
              </td>
              <td class="py-3 px-4 text-sm text-gray-700 max-w-xs">
                <div class="flex items-center gap-2">
                  <p class="truncate flex-1" :title="item.maintenance_notes || '—'" x-text="item.maintenance_notes ? (item.maintenance_notes.length > 30 ? item.maintenance_notes.substring(0, 30) + '...' : item.maintenance_notes) : '—'"></p>
                  <button x-show="item.maintenance_notes"
                          @click="openNotesModal(item)"
                          class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition flex-shrink-0"
                          title="{{ trans('messages.view_notes', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-base">visibility</span>
                  </button>
                </div>
              </td>
              <td class="py-3 px-4">
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- Mobile History Cards -->
      <div class="md:hidden divide-y" x-show="!loadingHistory && repairHistory.length > 0">
        <template x-for="item in paginatedHistory()" :key="item.id">
          <div class="p-4">
            <div class="space-y-2">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-semibold text-lg" x-text="item.design_name || 'N/A'"></h3>
                  <p class="text-sm text-gray-600" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + (item.abaya_code || 'N/A')"></p>
                  <p class="text-sm text-indigo-600 mt-1" x-text="'{{ trans('messages.order_no', [], session('locale')) }}: ' + (item.order_no || '—')"></p>
                </div>
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <p class="text-gray-600">{{ trans('messages.customer', [], session('locale')) }}:</p>
                  <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
                  <p class="text-gray-500" x-text="item.customer_phone || 'N/A'"></p>
                </div>
                <div>
                  <p class="text-gray-600">{{ trans('messages.tailor', [], session('locale')) }}:</p>
                  <p class="font-medium" x-text="item.tailor_name || 'N/A'"></p>
                </div>
                <div>
                  <p class="text-gray-600">{{ trans('messages.sent_date', [], session('locale')) }}:</p>
                  <p x-text="item.sent_date || '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">{{ trans('messages.received_date', [], session('locale')) }}:</p>
                  <p x-text="item.received_date || '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">{{ trans('messages.delivery_charges', [], session('locale')) }}:</p>
                  <p class="font-semibold" x-text="item.delivery_charges ? item.delivery_charges + ' ر.ع' : '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">{{ trans('messages.repair_cost', [], session('locale')) }}:</p>
                  <p class="font-semibold" x-text="item.repair_cost ? item.repair_cost + ' ر.ع' : '—'"></p>
                </div>
                <div class="col-span-2">
                  <p class="text-gray-600">{{ trans('messages.cost_bearer', [], session('locale')) }}:</p>
                  <span x-show="item.cost_bearer" 
                        :class="item.cost_bearer === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                        class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                        x-text="item.cost_bearer === 'customer' ? '{{ trans('messages.customer_bearer', [], session('locale')) }}' : '{{ trans('messages.company_bearer', [], session('locale')) }}'"></span>
                  <span x-show="!item.cost_bearer" class="text-gray-400">—</span>
                </div>
                <div class="col-span-2">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                      <p class="text-gray-600 font-medium">{{ trans('messages.notes', [], session('locale')) }}:</p>
                      <p class="text-sm text-gray-700 mt-1 break-words" x-text="item.maintenance_notes ? (item.maintenance_notes.length > 50 ? item.maintenance_notes.substring(0, 50) + '...' : item.maintenance_notes) : '—'"></p>
                    </div>
                    <button x-show="item.maintenance_notes && item.maintenance_notes.length > 50"
                            @click="openNotesModal(item)"
                            class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition flex-shrink-0 mt-5"
                            title="{{ trans('messages.view_notes', [], session('locale')) }}">
                      <span class="material-symbols-outlined text-base">visibility</span>
                    </button>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- History Pagination -->
      <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-6 px-4 pb-4" x-show="!loadingHistory && repairHistory.length > 0">
        <p class="text-sm text-gray-500">
          {{ trans('messages.showing', [], session('locale')) }}
          <span x-text="historyStartItem()"></span> -
          <span x-text="historyEndItem()"></span>
          {{ trans('messages.of', [], session('locale')) }}
          <span x-text="repairHistory.length"></span>
          {{ trans('messages.items', [], session('locale')) }}
        </p>

        <div class="flex items-center gap-2 justify-end">
          <button @click="prevHistoryPage()" 
                  class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                  :disabled="historyPage === 1"
                  :class="historyPage === 1 ? 'opacity-50 cursor-not-allowed' : ''">
            {{ trans('messages.previous', [], session('locale')) }}
          </button>

          <template x-for="p in historyPageNumbers()" :key="p">
            <button @click="goToHistoryPage(p)"
                    :class="historyPage === p 
                             ? 'px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm font-semibold' 
                             : 'px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm'">
              <span x-text="p"></span>
            </button>
          </template>

          <button @click="nextHistoryPage()" 
                  class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                  :disabled="historyPage === totalHistoryPages()"
                  :class="historyPage === totalHistoryPages() ? 'opacity-50 cursor-not-allowed' : ''">
            {{ trans('messages.next', [], session('locale')) }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔧 Modal: Send/Receive Action -->
  <div x-show="showActionModal" 
       x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
       @click.away="showActionModal = false">
    <div @click.stop
         x-transition.scale
         class="bg-white w-full max-w-md mx-2 md:mx-4 rounded-2xl shadow-2xl p-4 md:p-6 max-h-[90vh] overflow-y-auto">
      <h2 class="text-2xl font-bold mb-4" x-text="selectedItem.maintenance_status === 'delivered_to_tailor' ? '{{ trans('messages.receive_from_tailor', [], session('locale')) }}' : '{{ trans('messages.send_to_tailor', [], session('locale')) }}'"></h2>
      
      <div class="mb-4">
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.design', [], session('locale')) }}: ' + (selectedItem.design_name || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + (selectedItem.abaya_code || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-show="selectedItem.order_no" x-text="'{{ trans('messages.order_no', [], session('locale')) }}: ' + selectedItem.order_no"></p>
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.customer', [], session('locale')) }}: ' + (selectedItem.customer_name || 'N/A') + ' (' + (selectedItem.customer_phone || 'N/A') + ')'"></p>
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status !== 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.select_tailor', [], session('locale')) }}</label>
        <select x-model="selectedTailorId" 
                class="form-select w-full border-gray-300 rounded-lg">
          <option value="">{{ trans('messages.select_a_tailor', [], session('locale')) }}</option>
          <template x-for="tailor in tailors" :key="tailor.id">
            <option :value="tailor.id" x-text="tailor.name"></option>
          </template>
        </select>
      </div>

      <!-- Quantity Selector (only show when sending to tailor and quantity > 1) -->
      <div class="mb-4" x-show="selectedItem.maintenance_status !== 'delivered_to_tailor' && selectedItem.quantity > 1">
        <label class="block text-sm font-medium mb-2">
          {{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }} 
          <span class="text-gray-500 text-xs">({{ trans('messages.available', [], session('locale')) ?: 'Available' }}: <span x-text="selectedItem.available_quantity || selectedItem.quantity"></span>)</span>
        </label>
        <input type="number" 
               x-model="selectedQuantity"
               :min="1"
               :max="selectedItem.available_quantity || selectedItem.quantity"
               class="form-input w-full border-gray-300 rounded-lg">
        <p class="text-xs text-gray-500 mt-1">
          {{ trans('messages.select_quantity_to_send', [], session('locale')) ?: 'Select how many pieces to send for alteration' }}
        </p>
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status !== 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea x-model="maintenanceNotes" 
                  placeholder="{{ trans('messages.enter_notes', [], session('locale')) }}"
                  rows="4"
                  class="form-textarea w-full border-gray-300 rounded-lg resize-none"></textarea>
      </div>


      <div class="flex gap-3 justify-end">
        <button @click="showActionModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>
        <button @click="performAction()"
                :class="selectedItem.maintenance_status === 'delivered_to_tailor' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700'"
                class="px-4 py-2 text-white rounded-lg">
          <span x-text="selectedItem.maintenance_status === 'delivered_to_tailor' ? '{{ trans('messages.confirm_receive', [], session('locale')) }}' : '{{ trans('messages.send', [], session('locale')) }}'"></span>
        </button>
      </div>
    </div>
  </div>

  <!-- Notes Modal -->
  <div x-show="showNotesModal" 
       x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
       @click.away="showNotesModal = false">
    <div @click.stop
         x-transition.scale
         class="bg-white w-full max-w-lg mx-2 md:mx-4 rounded-2xl shadow-2xl p-4 md:p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl md:text-2xl font-bold">{{ trans('messages.notes', [], session('locale')) }}</h2>
        <button @click="showNotesModal = false"
                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      
      <div class="mb-4">
        <p class="text-sm text-gray-600 mb-2">
          <span class="font-medium">{{ trans('messages.design_name', [], session('locale')) }}: </span>
          <span x-text="selectedNotesItem.design_name || 'N/A'"></span>
        </p>
        <p class="text-sm text-gray-600 mb-2">
          <span class="font-medium">{{ trans('messages.code', [], session('locale')) }}: </span>
          <span x-text="selectedNotesItem.abaya_code || 'N/A'"></span>
        </p>
        <p class="text-sm text-gray-600 mb-2" x-show="selectedNotesItem.order_no">
          <span class="font-medium">{{ trans('messages.order_no', [], session('locale')) }}: </span>
          <span x-text="selectedNotesItem.order_no"></span>
        </p>
      </div>

      <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <p class="text-sm text-gray-600 mb-2 font-medium">{{ trans('messages.notes', [], session('locale')) }}:</p>
        <p class="text-gray-800 whitespace-pre-wrap break-words" x-text="selectedNotesItem.maintenance_notes || '—'"></p>
        <template x-if="selectedNotesItem.delivery_charges || selectedNotesItem.repair_cost">
          <div class="mt-3 pt-3 border-t border-gray-300">
            <p class="text-sm text-gray-600 mb-1 font-medium">{{ trans('messages.cost_details', [], session('locale')) }}:</p>
            <p class="text-sm text-gray-700" x-show="selectedNotesItem.delivery_charges">
              {{ trans('messages.delivery_charges', [], session('locale')) }}: <span class="font-semibold" x-text="selectedNotesItem.delivery_charges + ' ر.ع'"></span>
            </p>
            <p class="text-sm text-gray-700" x-show="selectedNotesItem.repair_cost">
              {{ trans('messages.repair_cost', [], session('locale')) }}: <span class="font-semibold" x-text="selectedNotesItem.repair_cost + ' ر.ع'"></span>
            </p>
            <p class="text-sm text-gray-700 mt-1" x-show="selectedNotesItem.cost_bearer">
              {{ trans('messages.cost_bearer', [], session('locale')) }}: <span class="font-semibold" x-text="selectedNotesItem.cost_bearer === 'customer' ? '{{ trans('messages.customer_bearer', [], session('locale')) }}' : '{{ trans('messages.company_bearer', [], session('locale')) }}'"></span>
            </p>
          </div>
        </template>
      </div>

      <div class="flex justify-end">
        <button @click="showNotesModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          {{ trans('messages.close', [], session('locale')) }}
        </button>
      </div>
    </div>
      </div>
  
  <!-- 🚚 Modal: Deliver -->
  <div x-show="showDeliverModal" 
       x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
       @click.away="showDeliverModal = false">
    <div @click.stop
         x-transition.scale
         class="bg-white w-full max-w-lg mx-2 md:mx-4 rounded-2xl shadow-2xl p-4 md:p-6 max-h-[90vh] overflow-y-auto">
      <h2 class="text-2xl font-bold mb-4">{{ trans('messages.deliver', [], session('locale')) ?: 'Deliver' }}</h2>
      
      <div class="mb-4">
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.design', [], session('locale')) }}: ' + (selectedDeliverItem.design_name || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + (selectedDeliverItem.abaya_code || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-show="selectedDeliverItem.order_no" x-text="'{{ trans('messages.order_no', [], session('locale')) }}: ' + selectedDeliverItem.order_no"></p>
        <p class="text-gray-600 mb-2" x-text="'{{ trans('messages.customer', [], session('locale')) }}: ' + (selectedDeliverItem.customer_name || 'N/A') + ' (' + (selectedDeliverItem.customer_phone || 'N/A') + ')'"></p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.cost_bearer', [], session('locale')) }} <span class="text-red-500">*</span></label>
        <select x-model="costBearer" 
                @change="handleCostBearerChange()"
                class="form-select w-full border-gray-300 rounded-lg">
          <option value="">{{ trans('messages.select_cost_bearer', [], session('locale')) }}</option>
          <option value="customer">{{ trans('messages.customer_bearer', [], session('locale')) }}</option>
          <option value="company">{{ trans('messages.company_bearer', [], session('locale')) }}</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.delivery_charges_omr', [], session('locale')) }}</label>
        <input type="number" 
               step="0.001"
               x-model="deliveryCharges"
               @input="updatePaymentAmount()"
               :disabled="costBearer === 'company'"
               :class="costBearer === 'company' ? 'form-input w-full border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed' : 'form-input w-full border-gray-300 rounded-lg'">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.repair_cost_omr', [], session('locale')) }}</label>
        <input type="number" 
               step="0.001"
               x-model="repairCost"
               @input="updatePaymentAmount()"
               :disabled="costBearer === 'company'"
               :class="costBearer === 'company' ? 'form-input w-full border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed' : 'form-input w-full border-gray-300 rounded-lg'">
      </div>

      <!-- Payment Section (only show if customer is bearer and there are costs) -->
      <div class="mb-4" x-show="costBearer === 'customer' && (parseFloat(deliveryCharges) > 0 || parseFloat(repairCost) > 0)">
        <h3 class="text-lg font-semibold mb-3">{{ trans('messages.payment', [], session('locale')) ?: 'Payment' }}</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">{{ trans('messages.select_account', [], session('locale')) ?: 'Select Account' }} <span class="text-red-500">*</span></label>
          <select x-model="deliverAccountId" 
                  class="form-select w-full border-gray-300 rounded-lg">
            <option value="">{{ trans('messages.select_an_account', [], session('locale')) ?: 'Select an account' }}</option>
            <template x-for="account in accounts" :key="account.id">
              <option :value="account.id" x-text="(account.account_name || 'Account #' + account.id) + (account.account_branch ? ' - ' + account.account_branch : '')"></option>
            </template>
          </select>
          <p class="text-xs text-gray-500 mt-1" x-show="accounts.length === 0">
            {{ trans('messages.no_accounts_found', [], session('locale')) ?: 'No accounts found. Please add accounts first.' }}
          </p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">{{ trans('messages.payment_amount', [], session('locale')) }} <span class="text-red-500">*</span></label>
          <input type="number" 
                 step="0.001"
                 x-model="deliverPaymentAmount"
                 readonly
                 class="form-input w-full border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                 :class="paymentAmountError ? 'border-red-500' : ''">
          <p class="text-xs text-gray-500 mt-1">
            {{ trans('messages.total_cost', [], session('locale')) ?: 'Total Cost' }}: <span class="font-semibold" x-text="formatCurrency(calculateDeliverTotalCost())"></span>
          </p>
          <p class="text-xs text-red-500 mt-1" x-show="paymentAmountError" x-text="paymentAmountError"></p>
        </div>
      </div>

      <div class="flex gap-3 justify-end">
        <button @click="showDeliverModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>
        <button @click="performDeliver()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
          {{ trans('messages.confirm_deliver', [], session('locale')) ?: 'Confirm Deliver' }}
        </button>
      </div>
    </div>
  </div>

  <!-- 📦 Modal: Order Items (from delivered orders) -->
  <div x-show="showOrderItemsModal" 
       x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
       @click.away="showOrderItemsModal = false">
    <div @click.stop
         x-transition.scale
         class="bg-white w-full max-w-4xl mx-2 md:mx-4 rounded-2xl shadow-2xl p-4 md:p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">{{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }}</h2>
        <button @click="showOrderItemsModal = false"
                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      
      <div class="mb-4 p-3 bg-gray-50 rounded-lg">
        <p class="text-sm text-gray-600">
          <span class="font-medium">{{ trans('messages.order_no', [], session('locale')) }}: </span>
          <span x-text="selectedOrder.order_no"></span>
        </p>
        <p class="text-sm text-gray-600">
          <span class="font-medium">{{ trans('messages.customer', [], session('locale')) }}: </span>
          <span x-text="selectedOrder.customer_name + ' (' + selectedOrder.customer_phone + ')'"></span>
        </p>
      </div>

      <div x-show="loadingOrderItems" class="text-center py-8">
        <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
        <p class="mt-2 text-gray-500">{{ trans('messages.loading', [], session('locale')) }}</p>
      </div>

      <div x-show="!loadingOrderItems && orderItems.length > 0" class="space-y-3">
        <template x-for="item in orderItems" :key="item.id">
          <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input type="checkbox" 
                   :value="item.id"
                   x-model="selectedOrderItems"
                   :disabled="item.maintenance_status"
                   :class="item.maintenance_status ? 'opacity-50 cursor-not-allowed' : ''"
                   class="w-5 h-5 text-indigo-600 rounded">
            <img :src="item.image" 
                 :alt="item.design_name"
                 class="w-16 h-16 object-cover rounded-lg">
            <div class="flex-1">
              <p class="font-semibold" x-text="item.design_name"></p>
              <p class="text-sm text-gray-600" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + item.abaya_code"></p>
              <p class="text-xs text-gray-500" x-text="'{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}: ' + item.quantity"></p>
            </div>
            <div>
              <span x-show="item.maintenance_status" 
                    :class="item.maintenance_status === 'delivered_to_tailor' ? 'bg-orange-100 text-orange-800' : item.maintenance_status === 'received_from_tailor' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                    class="px-3 py-1 rounded-full text-xs font-semibold"
                    x-text="getMaintenanceStatusLabel(item.maintenance_status)"></span>
              <span x-show="!item.maintenance_status" 
                    class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                {{ trans('messages.not_in_maintenance', [], session('locale')) }}
              </span>
            </div>
          </div>
        </template>
      </div>

      <div x-show="!loadingOrderItems && orderItems.length === 0" class="text-center py-8 text-gray-500">
        {{ trans('messages.no_items_found', [], session('locale')) }}
      </div>

      <div x-show="!loadingOrderItems && orderItems.length > 0" class="mb-4">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.select_tailor', [], session('locale')) }} <span class="text-red-500">*</span></label>
        <select x-model="selectedTailorId" 
                class="form-select w-full border-gray-300 rounded-lg">
          <option value="">{{ trans('messages.select_a_tailor', [], session('locale')) }}</option>
          <template x-for="tailor in tailors" :key="tailor.id">
            <option :value="tailor.id" x-text="tailor.name"></option>
          </template>
        </select>
      </div>

      <div x-show="!loadingOrderItems && orderItems.length > 0" class="mb-4">
        <label class="block text-sm font-medium mb-2">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea x-model="maintenanceNotes" 
                  placeholder="{{ trans('messages.enter_notes', [], session('locale')) }}"
                  rows="3"
                  class="form-textarea w-full border-gray-300 rounded-lg resize-none"></textarea>
      </div>

      <div class="flex gap-3 justify-end mt-6" x-show="!loadingOrderItems && orderItems.length > 0">
        <button @click="showOrderItemsModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>
        <button @click="sendSelectedItemsToMaintenance()"
                :disabled="selectedOrderItems.length === 0 || !selectedTailorId"
                :class="(selectedOrderItems.length === 0 || !selectedTailorId) ? 'opacity-50 cursor-not-allowed' : ''"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
          {{ trans('messages.send_to_maintenance', [], session('locale')) ?: 'Send to Maintenance' }}
        </button>
      </div>
    </div>
  </div>

  </main>
  


@include('layouts.footer')
@endsection


