@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.send_new_abayas', [], session('locale')) }}</title>
@endpush
<style>
/* وميض للصف المتأخر */
@keyframes blinkDelay {
  0%, 100% { background-color: #fee2e2; }
  50% { background-color: #fecaca; }
}
.blink-late {
  animation: blinkDelay 1.2s infinite ease-in-out;
}
</style>

<main class="flex-1 p-4 md:p-6" x-data="assignTailorPage" x-init="init()">

  <!-- ========================================================= -->
  <!-- MODAL: CONFIRM RECEIVE -->
  <!-- ========================================================= -->
  <div x-show="showConfirmModal" x-transition.opacity class="fixed inset-0 bg-black/50 flex items-center justify-center z-[70]">
    <div @click.away="showConfirmModal=false" x-transition.scale class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
      
      <h2 class="text-xl font-bold mb-4">{{ trans('messages.confirm_receive_process', [], session('locale')) }}</h2>

      <p class="text-gray-700 leading-6 mb-6">
        {{ trans('messages.confirm_receive_abayas', [], session('locale')) }}
        <br><span class="text-red-600 font-semibold">{{ trans('messages.cannot_undo_process', [], session('locale')) }}</span>
      </p>

      <div class="flex justify-end gap-3">
        <button @click="showConfirmModal=false"
                :disabled="isReceiving"
                :class="isReceiving 
                  ? 'px-4 py-2 bg-gray-300 cursor-not-allowed rounded-xl opacity-50'
                  : 'px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl'">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>

        <button @click="confirmReceive()"
                :disabled="isReceiving"
                :class="isReceiving 
                  ? 'px-4 py-2 bg-gray-400 cursor-not-allowed text-white rounded-xl opacity-75'
                  : 'px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl'">
          <span x-show="!isReceiving">{{ trans('messages.confirm', [], session('locale')) }}</span>
          <span x-show="isReceiving" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ trans('messages.processing', [], session('locale')) ?: 'Processing...' }}
          </span>
        </button>
      </div>
    </div>
  </div>


  <!-- ========================================================= -->
  <!-- MODAL: DETAILS VIEW -->
  <!-- ========================================================= -->
  <div x-show="showDetailsModal" x-transition.opacity class="fixed inset-0 bg-black/60 flex items-center justify-center z-[90]">
    <div @click.away="showDetailsModal=false"
         x-transition.scale
         class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-7">

      <h2 class="text-2xl font-bold mb-6">{{ trans('messages.order_details', [], session('locale')) }}</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- صورة العباية -->
        <div class="flex items-center justify-center">
          <img :src="selectedItem.image" class="max-w-full max-h-80 w-auto h-auto rounded-xl border object-contain shadow-lg">
        </div>

        <div class="space-y-2 text-sm">

          <div><strong>{{ trans('messages.abaya', [], session('locale')) }}:</strong> <span x-text="selectedItem.abayaName || selectedItem.code || 'N/A'"></span></div>
          <div><strong>{{ trans('messages.code', [], session('locale')) }}:</strong> <span x-text="selectedItem.code || '—'"></span></div>
          <div><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> <span x-text="selectedItem.order_no || ('#' + selectedItem.orderId)"></span></div>
          <div><strong>{{ trans('messages.order_source', [], session('locale')) }}:</strong> <span x-text="selectedItem.source"></span></div>
          <div><strong>{{ trans('messages.customer', [], session('locale')) }}:</strong> <span x-text="selectedItem.customer || 'N/A'"></span></div>
          <div><strong>{{ trans('messages.order_date', [], session('locale')) }}:</strong> <span x-text="selectedItem.date"></span></div>
          <div><strong>{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}:</strong> <span x-text="selectedItem.quantity || 1"></span></div>

          <!-- Show color and size for stock orders -->
          <template x-if="selectedItem.is_stock_order && selectedItem.color_name">
            <div><strong>{{ trans('messages.color', [], session('locale')) }}:</strong> <span x-text="selectedItem.color_name"></span></div>
          </template>
          <template x-if="selectedItem.is_stock_order && selectedItem.size_name">
            <div><strong>{{ trans('messages.size', [], session('locale')) }}:</strong> <span x-text="selectedItem.size_name"></span></div>
          </template>

          <!-- Show measurements for customer orders -->
          <template x-if="!selectedItem.is_stock_order">
            <div>
              <div><strong>{{ trans('messages.abaya_length', [], session('locale')) }}:</strong> <span x-text="selectedItem.length || '—'"></span></div>
              <div><strong>{{ trans('messages.bust_one_side', [], session('locale')) }}:</strong> <span x-text="selectedItem.bust || '—'"></span></div>
              <div><strong>{{ trans('messages.sleeves_length', [], session('locale')) }}:</strong> <span x-text="selectedItem.sleeves || '—'"></span></div>
              <div><strong>{{ trans('messages.buttons', [], session('locale')) }}:</strong> <span x-text="selectedItem.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></span></div>
            </div>
          </template>

          <div><strong>{{ trans('messages.original_tailor', [], session('locale')) }}:</strong> <span x-text="selectedItem.originalTailor || '—'"></span></div>
          <div><strong>{{ trans('messages.current_tailor', [], session('locale')) }}:</strong> <span x-text="selectedItem.tailor || '—'"></span></div>

        </div>

      </div>

      <div class="mt-6">
        <span class="font-bold block mb-1">{{ trans('messages.notes', [], session('locale')) }}:</span>
        <p x-text="selectedItem.notes" class="bg-gray-50 p-4 rounded-xl border"></p>
      </div>

      <div class="flex justify-end gap-3 mt-8">

        <button @click="printSingle(selectedItem)"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl flex items-center gap-2">
          <span class="material-symbols-outlined">print</span>
          {{ trans('messages.print', [], session('locale')) }}
        </button>

        <button @click="showDetailsModal=false"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.close', [], session('locale')) }}
        </button>
      </div>

    </div>
  </div>


  <!-- ========================================================= -->
  <!-- MODAL: PRINT MULTIPLE ORDERS -->
  <!-- ========================================================= -->
  <div x-show="showPrintModal" x-transition.opacity class="fixed inset-0 bg-black/60 flex items-center justify-center z-[95]">

    <div x-transition.scale class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl p-8">

      <h2 class="text-2xl font-bold mb-6">{{ trans('messages.print_tailor_sheet', [], session('locale')) }}</h2>

      <p class="text-gray-600 mb-4">{{ trans('messages.print_all_selected_abayas', [], session('locale')) }}</p>

      <div class="overflow-auto max-h-96 border rounded-xl p-4">

        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-gray-100 text-gray-700">
              <th class="p-2 border">{{ trans('messages.order_number', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.customer', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
              <th class="p-2 border">{{ trans('messages.sizes', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.notes', [], session('locale')) }}</th>
            </tr>
          </thead>

          <tbody>
            <template x-if="selectedItems.length === 0">
              <tr>
                <td colspan="6" class="p-4 text-center text-gray-500">
                  {{ trans('messages.no_items_selected', [], session('locale')) }}
                </td>
              </tr>
            </template>
            <template x-for="i in selectedItems" :key="i.rowId">
              <tr>
                <td class="p-2 border" x-text="i.order_no || ('#' + i.orderId)"></td>
                <td class="p-2 border" x-text="i.customer"></td>
                <td class="p-2 border" x-text="i.abayaName || i.code || '—'"></td>
                <td class="p-2 border text-center">
                  <span class="font-semibold" x-text="i.quantity || 1"></span>
                </td>
                <td class="p-2 border">
                  <!-- Show color and size for stock orders -->
                  <template x-if="i.is_stock_order && (i.color_name || i.size_name)">
                    <div>
                      <template x-if="i.color_name">
                        <div>{{ trans('messages.color', [], session('locale')) }}: <span x-text="i.color_name"></span></div>
                      </template>
                      <template x-if="i.size_name">
                        <div>{{ trans('messages.size', [], session('locale')) }}: <span x-text="i.size_name"></span></div>
                      </template>
                    </div>
                  </template>
                  <!-- Show measurements for customer orders -->
                  <template x-if="!i.is_stock_order">
                    <div>
                      {{ trans('messages.abaya_length', [], session('locale')) }}: <span x-text="i.length || '—'"></span><br>
                      {{ trans('messages.bust_one_side', [], session('locale')) }}: <span x-text="i.bust || '—'"></span><br>
                      {{ trans('messages.sleeves_length', [], session('locale')) }}: <span x-text="i.sleeves || '—'"></span><br>
                      {{ trans('messages.buttons', [], session('locale')) }}: <span x-text="i.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></span>
                    </div>
                  </template>
                </td>
                <td class="p-2 border" x-text="i.tailor_name || i.tailor || tailorNameById(i.tailor_id) || '—'"></td>
                <td class="p-2 border" x-text="i.notes || '—'"></td>
              </tr>
            </template>
          </tbody>
        </table>

      </div>

      <div class="flex justify-end gap-3 mt-6">

        <button @click="doPrintList()" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl flex items-center gap-2">
          <span class="material-symbols-outlined">print</span>
          {{ trans('messages.print_now', [], session('locale')) }}
        </button>

        <button @click="showPrintModal=false"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.close', [], session('locale')) }}
        </button>

      </div>

    </div>

  </div>

    <!-- ========================================================= -->
  <!-- MAIN CONTENT CONTAINER -->
  <!-- ========================================================= -->
  <div class="max-w-7xl mx-auto bg-white shadow-xl rounded-3xl p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">{{ trans('messages.orders_sent_to_tailor', [], session('locale')) }}</h1>

      <button @click="mode='assign'"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">add</span>
        {{ trans('messages.send_new_abayas', [], session('locale')) }}
      </button>
    </div>


    <!-- ========================================================= -->
    <!-- VIEW MODE -->
    <!-- ========================================================= -->
    <div x-show="mode === 'view'" x-transition>

      <!-- FILTER BAR (Search) -->
      <div class="mb-6">
        <label class="text-sm font-medium">{{ trans('messages.search', [], session('locale')) }}</label>
        <input type="text" x-model="searchView"
               placeholder="{{ trans('messages.search_placeholder_tailor', [], session('locale')) }}"
               class="form-input w-full mt-1 max-w-xl rounded-xl border-gray-300" />
      </div>

      <!-- Tailor + phone region (combinable, same style as view orders) -->
      <div class="flex flex-wrap gap-3 mb-4">
        <button type="button" @click="tailorViewFilter=''" :class="viewTailorTabClass('')">{{ trans('messages.all_tailors', [], session('locale')) }}</button>
        <template x-for="t in tailors" :key="'vt-'+t.id">
          <button type="button" @click="tailorViewFilter=String(t.id)" :class="viewTailorTabClass(String(t.id))" x-text="t.name"></button>
        </template>
      </div>
      <div class="flex flex-wrap gap-2 items-center mb-6">
        <span class="text-xs text-gray-500 mr-1">{{ trans('messages.phone_number', [], session('locale')) ?: 'Phone' }}:</span>
        <button type="button" @click="phoneRegionViewFilter='all'" :class="viewPhoneTabClass('all')">{{ trans('messages.all', [], session('locale')) }}</button>
        <button type="button" @click="phoneRegionViewFilter='oman'" :class="viewPhoneTabClass('oman')">{{ trans('messages.phone_region_oman', [], session('locale')) ?: 'Oman (968)' }}</button>
        <button type="button" @click="phoneRegionViewFilter='outside'" :class="viewPhoneTabClass('outside')">{{ trans('messages.phone_region_outside_oman', [], session('locale')) ?: 'Outside Oman' }}</button>
      </div>

      <!-- PDF Print Button -->
      <div class="flex gap-3 mb-4">
        <button @click="showPrintModal=true"
                :disabled="selectedItems.length === 0"
                :class="selectedItems.length === 0 
                  ? 'bg-gray-400 cursor-not-allowed text-white px-4 py-2 rounded-xl shadow flex items-center gap-1'
                  : 'bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl shadow flex items-center gap-1'">
          <span class="material-symbols-outlined">print</span>
          {{ trans('messages.print_all_selected', [], session('locale')) }}
          <span x-show="selectedItems.length > 0" class="ml-2 bg-white text-indigo-600 px-2 py-0.5 rounded-full text-xs font-bold" x-text="selectedItems.length"></span>
        </button>
      </div>


      <!-- TABLE: PROCESSING ABAYAS -->
      <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-md">

        <table class="w-full text-sm text-center">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-center">{{ trans('messages.receive', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.select', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.order', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.customer', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.phone_number', [], session('locale')) ?: 'Phone' }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.status', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.ago', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-center">{{ trans('messages.list_number', [], session('locale')) ?: 'List / Summary' }}</th>
            </tr>
          </thead>

          <tbody>

            <template x-for="item in paginatedProcessing()" :key="item.rowId">

              <tr :class="isLate(item.date) ? 'blink-late border-l-4 border-red-500' : ''"
                  class="border-t hover:bg-indigo-50 transition">

                <!-- checkbox receive -->
                <td class="py-3 px-4 text-center">
                  <input type="checkbox" @change="toggleReceive(item)"
                         class="w-4 h-4 text-indigo-600">
                </td>

                <!-- checkbox print -->
                <td class="py-3 px-4 text-center">
                  <input type="checkbox" @change="toggleSelection(item)"
                         :checked="selectedItems.find(i => i.rowId === item.rowId)"
                         class="w-4 h-4 text-indigo-600">
                </td>

                <!-- order no -->
                <td class="py-3 px-4 font-medium text-indigo-600 text-center">
                  <div x-text="item.tailor_order_no && item.tailor_order_no !== '—' ? item.tailor_order_no : '—'"></div>
                  <div class="text-xs text-gray-500 mt-0.5" x-show="item.special_order_no && item.special_order_no !== '—'">
                    <span>{{ trans('messages.special_order', [], session('locale')) }}:</span>
                    <span x-text="item.special_order_no"></span>
                  </div>
                </td>

                <!-- customer -->
                <td class="py-3 px-4 text-center" x-text="item.customer"></td>

                <!-- customer phone -->
                <td class="py-3 px-4 text-center" x-text="item.customer_phone || '—'"></td>

                <!-- abaya -->
                <td class="py-3 px-4 text-center">
                  <button @click="openDetails(item)" 
                          class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:text-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                  </button>
                </td>

                <!-- quantity -->
                <td class="py-3 px-4 text-center">
                  <span class="font-semibold text-indigo-600" x-text="item.quantity || 1"></span>
                </td>

                <!-- tailor -->
                <td class="py-3 px-4 text-center">
                  <span x-text="item.tailor_name || item.tailor || tailorNameById(item.tailor_id)"></span>
                </td>

                <!-- status -->
                <td class="py-3 px-4 text-center">
                  <span :class="isLate(item.date)
                    ? 'bg-red-600 text-white px-3 py-1 rounded-full text-xs font-semibold'
                    : 'bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold'">
                    <span x-text="isLate(item.date) ? '{{ trans('messages.late', [], session('locale')) }}' : '{{ trans('messages.in_progress', [], session('locale')) }}'"></span>
                  </span>
                </td>

                <!-- days ago -->
                <td class="py-3 px-4 text-center" x-text="daysAgo(item.date)"></td>

                <!-- list / summary -->
                <td class="py-3 px-4 text-center">
                  <div x-text="item.list_number || '—'"></div>
                  <div class="text-xs text-gray-500 mt-0.5" x-show="item.sending_summary_number && item.sending_summary_number !== '—'">
                    <span>{{ trans('messages.sending_summary_number', [], session('locale')) }}:</span>
                    <span x-text="item.sending_summary_number"></span>
                  </div>
                </td>

              </tr>
            </template>

            <tr x-show="sortedProcessing().length === 0">
              <td colspan="11" class="py-6 text-center text-gray-500">
                {{ trans('messages.no_abayas_sent_to_tailor', [], session('locale')) }}
              </td>
            </tr>

          </tbody>
        </table>

      </div>

      <!-- Pagination (View mode) - same style as view_stock -->
      <ul x-show="sortedProcessing().length > 0" class="flex flex-wrap justify-center items-center gap-1.5 mt-4 list-none pl-0 max-w-full">
        <li class="shrink-0">
          <button type="button" @click="goToPageView(currentPageView - 1)" :disabled="currentPageView <= 1"
                  :class="currentPageView <= 1 ? 'opacity-40 pointer-events-none bg-gray-200 border-gray-200' : 'bg-white hover:bg-gray-100 border-gray-200'"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0">
            &laquo; {{ trans('messages.previous', [], session('locale')) }}
          </button>
        </li>
        <template x-for="(p, idx) in paginationPagesView()" :key="idx">
          <li class="shrink-0">
            <span x-show="p.type === 'dots'" x-text="p.label" class="px-1 py-1.5 text-gray-400 text-sm"></span>
            <button x-show="p.type === 'num'" type="button" @click="goToPageView(p.page)"
                    :class="currentPageView === p.page ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200'"
                    class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0"
                    x-text="p.label"></button>
          </li>
        </template>
        <li class="shrink-0">
          <button type="button" @click="goToPageView(currentPageView + 1)" :disabled="currentPageView >= totalPagesView()"
                  :class="currentPageView >= totalPagesView() ? 'opacity-40 pointer-events-none bg-gray-200 border-gray-200' : 'bg-white hover:bg-gray-100 border-gray-200'"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0">
            {{ trans('messages.next', [], session('locale')) }} &raquo;
          </button>
        </li>
      </ul>

      <!-- Confirm Receive Button -->
      <div x-show="receivedList.length > 0" x-transition class="mt-4">
        <button @click="showConfirmModal=true"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-semibold">
          ✔ {{ trans('messages.confirm_receive_abayas_button', [], session('locale')) }}
        </button>
      </div>

    </div>
    <!-- ========================================================= -->
    <!-- ASSIGN MODE (إرسال عبايات جديدة للخياط) -->
    <!-- ========================================================= -->
    <div x-show="mode === 'assign'" x-transition>

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">{{ trans('messages.send_new_abayas_to_tailor', [], session('locale')) }}</h2>

        <button @click="mode='view'"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-xl">
          {{ trans('messages.back', [], session('locale')) }}
        </button>
      </div>

      <!-- FILTERS -->
      <div class="grid md:grid-cols-4 gap-4 mb-8">

        <div>
          <label class="block text-sm mb-1">{{ trans('messages.from_date', [], session('locale')) }}</label>
          <input type="date" x-model="filter.from" 
                 class="form-input w-full rounded-xl border-gray-300">
        </div>

        <div>
          <label class="block text-sm mb-1">{{ trans('messages.to_date', [], session('locale')) }}</label>
          <input type="date" x-model="filter.to" 
                 class="form-input w-full rounded-xl border-gray-300">
        </div>

        <div>
          <label class="block text-sm mb-1">{{ trans('messages.search', [], session('locale')) }}</label>
          <input type="text" x-model="search"
                 placeholder="{{ trans('messages.search_placeholder_order', [], session('locale')) }}"
                 class="form-input w-full rounded-xl border-gray-300">
        </div>

        <div class="flex items-end">
          <button @click="statusFilter='new'"
                  class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-xl">
            {{ trans('messages.show_new_only', [], session('locale')) }}
          </button>
        </div>

      </div>

      <div class="flex flex-wrap gap-3 mb-4">
        <button type="button" @click="tailorAssignFilter=''" :class="assignTailorTabClass('')">{{ trans('messages.all_tailors', [], session('locale')) }}</button>
        <template x-for="t in tailors" :key="'at-'+t.id">
          <button type="button" @click="tailorAssignFilter=String(t.id)" :class="assignTailorTabClass(String(t.id))" x-text="t.name"></button>
        </template>
      </div>
      <div class="flex flex-wrap gap-2 items-center mb-8">
        <span class="text-xs text-gray-500 mr-1">{{ trans('messages.phone_number', [], session('locale')) ?: 'Phone' }}:</span>
        <button type="button" @click="phoneRegionAssignFilter='all'" :class="assignPhoneTabClass('all')">{{ trans('messages.all', [], session('locale')) }}</button>
        <button type="button" @click="phoneRegionAssignFilter='oman'" :class="assignPhoneTabClass('oman')">{{ trans('messages.phone_region_oman', [], session('locale')) ?: 'Oman (968)' }}</button>
        <button type="button" @click="phoneRegionAssignFilter='outside'" :class="assignPhoneTabClass('outside')">{{ trans('messages.phone_region_outside_oman', [], session('locale')) ?: 'Outside Oman' }}</button>
      </div>

      <!-- TABLE: NEW ABAYAS -->
      <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-md">

        <table class="w-full text-sm">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-center">{{ trans('messages.select', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.order', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.customer', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.phone_number', [], session('locale')) ?: 'Phone' }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.status', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.ago', [], session('locale')) }}</th>
            </tr>
          </thead>

          <tbody>

            <template x-for="item in paginatedAbayas()" :key="item.rowId">

              <tr class="border-t hover:bg-indigo-50 transition">

                <td class="py-3 px-4 text-center">
                  <input type="checkbox" @change="toggleSelection(item)"
                         class="w-4 h-4 text-indigo-600">
                </td>

                <td class="py-3 px-4 font-medium text-indigo-600" x-text="item.order_no || ('#' + item.orderId)"></td>

                <td class="py-3 px-4" x-text="item.customer"></td>

                <td class="py-3 px-4" x-text="item.customer_phone || '—'"></td>

                <td class="py-3 px-4 text-center">
                  <button @click="openDetails(item)" 
                          class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:text-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                  </button>
                </td>

                <td class="py-3 px-4 text-center">
                  <span class="font-semibold text-indigo-600" x-text="item.quantity || 1"></span>
                </td>

                <td class="py-3 px-4">
                  <select x-model="item.tailor_id" @change="updateTailorSelection(item)" class="form-select rounded-xl border-gray-300">
                    <option value="">{{ trans('messages.select_tailor', [], session('locale')) }}</option>
                    <template x-for="t in tailors" :key="t.id">
                      <option :value="t.id" x-text="t.name"></option>
                    </template>
                  </select>

                  <div class="text-xs text-gray-400 mt-1">
                    {{ trans('messages.original', [], session('locale')) }}: <span x-text="item.originalTailor || '{{ trans('messages.none', [], session('locale')) }}'"></span>
                  </div>
                </td>

                <td class="py-3 px-4">
                  <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-semibold">
                    {{ trans('messages.new', [], session('locale')) }}
                  </span>
                </td>

                <td class="py-3 px-4" x-text="daysAgo(item.date)"></td>

              </tr>

            </template>

            <tr x-show="filteredAbayas().length === 0">
              <td colspan="9" class="text-center py-6 text-gray-400">
                {{ trans('messages.no_matching_abayas', [], session('locale')) }}
              </td>
            </tr>

          </tbody>
        </table>

      </div>

      <!-- Pagination (Assign mode) - same style as view_stock -->
      <ul x-show="filteredAbayas().length > 0" class="flex flex-wrap justify-center items-center gap-1.5 mt-4 list-none pl-0 max-w-full">
        <li class="shrink-0">
          <button type="button" @click="goToPageAssign(currentPageAssign - 1)" :disabled="currentPageAssign <= 1"
                  :class="currentPageAssign <= 1 ? 'opacity-40 pointer-events-none bg-gray-200 border-gray-200' : 'bg-white hover:bg-gray-100 border-gray-200'"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0">
            &laquo; {{ trans('messages.previous', [], session('locale')) }}
          </button>
        </li>
        <template x-for="(p, idx) in paginationPagesAssign()" :key="idx">
          <li class="shrink-0">
            <span x-show="p.type === 'dots'" x-text="p.label" class="px-1 py-1.5 text-gray-400 text-sm"></span>
            <button x-show="p.type === 'num'" type="button" @click="goToPageAssign(p.page)"
                    :class="currentPageAssign === p.page ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white hover:bg-gray-100 border-gray-200'"
                    class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0"
                    x-text="p.label"></button>
          </li>
        </template>
        <li class="shrink-0">
          <button type="button" @click="goToPageAssign(currentPageAssign + 1)" :disabled="currentPageAssign >= totalPagesAssign()"
                  :class="currentPageAssign >= totalPagesAssign() ? 'opacity-40 pointer-events-none bg-gray-200 border-gray-200' : 'bg-white hover:bg-gray-100 border-gray-200'"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0">
            {{ trans('messages.next', [], session('locale')) }} &raquo;
          </button>
        </li>
      </ul>

      <!-- SUMMARY -->
      <div x-show="selectedItems.length > 0"
           x-transition 
           class="mt-6 bg-indigo-50 border border-indigo-200 rounded-2xl p-6 shadow">

        <h2 class="text-xl font-bold mb-4 text-indigo-800">📦 {{ trans('messages.sending_summary', [], session('locale')) }}</h2>

        <div class="mb-4">
          <label class="block text-sm font-medium text-indigo-800 mb-1">{{ trans('messages.sending_summary_number', [], session('locale')) ?: 'Sending Summary Number' }} <span class="text-red-600">*</span></label>
          <input type="text" x-model="sendingSummaryNumber" placeholder="{{ trans('messages.enter_sending_summary_number', [], session('locale')) ?: 'Enter number' }}"
                 class="form-input w-full max-w-xs rounded-xl border-gray-300 border-2 focus:border-indigo-500">
        </div>

        <p class="mb-2 text-sm">{{ trans('messages.number_of_abayas', [], session('locale')) }}:
          <span class="font-bold" x-text="selectedItems.reduce((sum, item) => sum + (item.quantity || 1), 0)"></span>
        </p>

        <div class="mb-2 text-sm font-medium">{{ trans('messages.by_tailor', [], session('locale')) }}:</div>

        <template x-for="(count, tailor) in groupByTailor()">
          <div class="text-sm ml-3 mb-1">
            <span class="font-semibold" x-text="tailor"></span> :
            <span x-text="count"></span> {{ trans('messages.abayas', [], session('locale')) }}
          </div>
        </template>

        <div class="mt-4 flex gap-3">
          <button @click="printSelectedItems()"
                  :disabled="!canSendOrPrint()"
                  :class="canSendOrPrint() ? 'bg-gray-600 hover:bg-gray-700 text-white' : 'bg-gray-400 cursor-not-allowed text-white opacity-70'"
                  class="px-6 py-3 rounded-xl font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">print</span>
            {{ trans('messages.print', [], session('locale')) }}
          </button>
          <button @click="submitToTailor()"
                  :disabled="!canSendOrPrint()"
                  :class="canSendOrPrint() ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-indigo-400 cursor-not-allowed text-white opacity-70'"
                  class="px-6 py-3 rounded-xl font-semibold">
            {{ trans('messages.send_now', [], session('locale')) }}
          </button>
        </div>

      </div>

    </div>

  </div>

</main>


<!-- ========================================================= -->
<!-- SCRIPT: ALPINE.JS LOGIC -->
<!-- ========================================================= -->




@include('layouts.footer')
@endsection