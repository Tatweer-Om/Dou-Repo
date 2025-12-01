@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.add_stock_lang', [], session('locale')) }}</title>
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
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">{{ trans('messages.cancel', [], session('locale')) }}</button>

        <button @click="confirmReceive()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl">
          {{ trans('messages.confirm', [], session('locale')) }}
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
        <div>
          <img :src="selectedItem.image" class="w-full rounded-xl border object-cover shadow">
        </div>

        <div class="space-y-2 text-sm">

          <div><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> <span x-text="selectedItem.orderId"></span></div>
          <div><strong>{{ trans('messages.order_source', [], session('locale')) }}:</strong> <span x-text="selectedItem.source"></span></div>
          <div><strong>{{ trans('messages.order_date', [], session('locale')) }}:</strong> <span x-text="selectedItem.date"></span></div>

          <div><strong>{{ trans('messages.abaya_length', [], session('locale')) }}:</strong> <span x-text="selectedItem.length"></span></div>
          <div><strong>{{ trans('messages.bust_one_side', [], session('locale')) }}:</strong> <span x-text="selectedItem.bust"></span></div>
          <div><strong>{{ trans('messages.sleeves_length', [], session('locale')) }}:</strong> <span x-text="selectedItem.sleeves"></span></div>

          <div><strong>{{ trans('messages.buttons', [], session('locale')) }}:</strong> <span x-text="selectedItem.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></span></div>

          <div><strong>{{ trans('messages.original_tailor', [], session('locale')) }}:</strong> <span x-text="selectedItem.originalTailor"></span></div>
          <div><strong>{{ trans('messages.current_tailor', [], session('locale')) }}:</strong> <span x-text="selectedItem.tailor"></span></div>

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
              <th class="p-2 border">{{ trans('messages.sizes', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="p-2 border">{{ trans('messages.notes', [], session('locale')) }}</th>
            </tr>
          </thead>

          <tbody>
            <template x-for="i in selectedItems" :key="i.rowId">
              <tr>
                <td class="p-2 border" x-text="i.orderId"></td>
                <td class="p-2 border" x-text="i.customer"></td>
                <td class="p-2 border">
                  {{ trans('messages.abaya_length', [], session('locale')) }}: <span x-text="i.length"></span><br>
                  {{ trans('messages.bust_one_side', [], session('locale')) }}: <span x-text="i.bust"></span><br>
                  {{ trans('messages.sleeves_length', [], session('locale')) }}: <span x-text="i.sleeves"></span><br>
                  {{ trans('messages.buttons', [], session('locale')) }}: <span x-text="i.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></span>
                </td>
                <td class="p-2 border" x-text="i.tailor"></td>
                <td class="p-2 border" x-text="i.notes"></td>
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

      <!-- FILTER BAR (Search + Tailor Filter) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <!-- Search -->
        <div>
          <label class="text-sm font-medium">{{ trans('messages.search', [], session('locale')) }}</label>
          <input type="text" x-model="searchView"
                 placeholder="{{ trans('messages.search_placeholder_tailor', [], session('locale')) }}"
                 class="form-input w-full mt-1 rounded-xl border-gray-300" />
        </div>

        <!-- Tailor Filter -->
        <div>
          <label class="text-sm font-medium">{{ trans('messages.tailor', [], session('locale')) }}</label>
          <select x-model="tailorViewFilter"
                  class="form-select w-full mt-1 rounded-xl border-gray-300">
            <option value="">{{ trans('messages.all_tailors', [], session('locale')) }}</option>
            <template x-for="t in tailors" :key="t.id">
              <option :value="t.id" x-text="t.name"></option>
            </template>
          </select>
        </div>

      </div>

      <!-- PDF Print Button -->
      <div class="flex gap-3 mb-4">
        <button @click="showPrintModal=true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl shadow flex items-center gap-1">
          <span class="material-symbols-outlined">print</span>
          {{ trans('messages.print_all_selected', [], session('locale')) }}
        </button>
      </div>


      <!-- TABLE: PROCESSING ABAYAS -->
      <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-md">

        <table class="w-full text-sm">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-center">{{ trans('messages.receive', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.order', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.customer', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.status', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.ago', [], session('locale')) }}</th>
            </tr>
          </thead>

          <tbody>

            <template x-for="item in sortedProcessing()" :key="item.rowId">

              <tr :class="isLate(item.date) ? 'blink-late border-l-4 border-red-500' : ''"
                  class="border-t hover:bg-indigo-50 transition">

                <!-- checkbox receive -->
                <td class="py-3 px-4 text-center">
                  <input type="checkbox" @change="toggleReceive(item)"
                         class="w-4 h-4 text-indigo-600">
                </td>

                <!-- order id -->
                <td class="py-3 px-4 font-medium" x-text="'#' + item.orderId"></td>

                <!-- customer -->
                <td class="py-3 px-4" x-text="item.customer"></td>

                <!-- abaya -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <img :src="item.image" class="w-12 h-12 rounded-lg border object-cover">
                    <div>
                      <div class="font-semibold" x-text="item.abayaName"></div>
                      <div class="text-xs text-gray-500" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + item.code"></div>
                    </div>
                  </div>
                </td>

                <!-- tailor + details button -->
                <td class="py-3 px-4 flex items-center gap-2">

                  <span x-text="item.tailor_name || item.tailor || tailorNameById(item.tailor_id)"></span>

                  <button @click="openDetails(item)" 
                          class="text-indigo-600 hover:text-indigo-800">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                  </button>

                </td>

                <!-- status -->
                <td class="py-3 px-4">
                  <span :class="isLate(item.date)
                    ? 'bg-red-600 text-white px-3 py-1 rounded-full text-xs font-semibold'
                    : 'bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold'">
                    <span x-text="isLate(item.date) ? '{{ trans('messages.late', [], session('locale')) }}' : '{{ trans('messages.in_progress', [], session('locale')) }}'"></span>
                  </span>
                </td>

                <!-- days ago -->
                <td class="py-3 px-4" x-text="daysAgo(item.date)"></td>

              </tr>
            </template>

            <tr x-show="sortedProcessing().length === 0">
              <td colspan="7" class="py-6 text-center text-gray-500">
                {{ trans('messages.no_abayas_sent_to_tailor', [], session('locale')) }}
              </td>
            </tr>

          </tbody>
        </table>

      </div>


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

      <!-- TABLE: NEW ABAYAS -->
      <div class="overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-md">

        <table class="w-full text-sm">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="py-3 px-4 text-center">{{ trans('messages.select', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.order', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.customer', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.sizes', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.status', [], session('locale')) }}</th>
              <th class="py-3 px-4 text-right">{{ trans('messages.ago', [], session('locale')) }}</th>
            </tr>
          </thead>

          <tbody>

            <template x-for="item in filteredAbayas()" :key="item.rowId">

              <tr class="border-t hover:bg-indigo-50 transition">

                <td class="py-3 px-4 text-center">
                  <input type="checkbox" @change="toggleSelection(item)"
                         class="w-4 h-4 text-indigo-600">
                </td>

                <td class="py-3 px-4 font-medium" x-text="'#' + item.orderId"></td>

                <td class="py-3 px-4" x-text="item.customer"></td>

                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <img :src="item.image" class="w-12 h-12 rounded-lg border object-cover">
                    <div>
                      <div class="font-semibold" x-text="item.abayaName"></div>
                      <div class="text-xs text-gray-500" x-text="'{{ trans('messages.code', [], session('locale')) }}: ' + item.code"></div>
                    </div>
                  </div>
                </td>

                <td class="py-3 px-4">
                  <div class="text-xs leading-5">
                    <div>{{ trans('messages.abaya_length', [], session('locale')) }}: <span x-text="item.length"></span></div>
                    <div>{{ trans('messages.bust_one_side', [], session('locale')) }}: <span x-text="item.bust"></span></div>
                    <div>{{ trans('messages.sleeves_length', [], session('locale')) }}: <span x-text="item.sleeves"></span></div>
                    <div>{{ trans('messages.buttons', [], session('locale')) }}: <span x-text="item.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></span></div>
                  </div>
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
              <td colspan="8" class="text-center py-6 text-gray-400">
                {{ trans('messages.no_matching_abayas', [], session('locale')) }}
              </td>
            </tr>

          </tbody>
        </table>

      </div>


      <!-- SUMMARY -->
      <div x-show="selectedItems.length > 0"
           x-transition 
           class="mt-6 bg-indigo-50 border border-indigo-200 rounded-2xl p-6 shadow">

        <h2 class="text-xl font-bold mb-4 text-indigo-800">📦 {{ trans('messages.sending_summary', [], session('locale')) }}</h2>

        <p class="mb-2 text-sm">{{ trans('messages.number_of_abayas', [], session('locale')) }}:
          <span class="font-bold" x-text="selectedItems.length"></span>
        </p>

        <div class="mb-2 text-sm font-medium">{{ trans('messages.by_tailor', [], session('locale')) }}:</div>

        <template x-for="(count, tailor) in groupByTailor()">
          <div class="text-sm ml-3 mb-1">
            <span class="font-semibold" x-text="tailor"></span> :
            <span x-text="count"></span> {{ trans('messages.abayas', [], session('locale')) }}
          </div>
        </template>

        <button @click="submitToTailor()" 
                class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-semibold">
          {{ trans('messages.send_now', [], session('locale')) }}
        </button>

      </div>

    </div>

  </div>

</main>


<!-- ========================================================= -->
<!-- SCRIPT: ALPINE.JS LOGIC -->
<!-- ========================================================= -->




@include('layouts.footer')
@endsection