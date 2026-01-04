@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.transfer_quantities_between_channels', [], session('locale')) }}</title>
@endpush

 

<main class="flex-1 p-4 md:p-6" x-data="transferPage()" x-init="init()">
  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.transfer_quantities_between_channels', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">{{ trans('messages.inventory_management_channels', [], session('locale')) }}</p>
      </div>
      <a href="{{ route('stock') }}"
         class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:opacity-90 font-semibold">
        {{ trans('messages.back_to_inventory', [], session('locale')) }}
      </a>
    </div>

    <!-- Stats -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div><p class="text-sm text-gray-600">{{ trans('messages.main_warehouse', [], session('locale')) }}</p><h3 class="text-2xl font-extrabold text-[var(--primary-color)]" x-text="stats.main + ' {{ trans('messages.pieces', [], session('locale')) }}'"></h3></div>
        <span class="material-symbols-outlined p-3 rounded-full bg-[var(--primary-color)]/10 text-[var(--primary-color)]">warehouse</span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div><p class="text-sm text-gray-600">{{ trans('messages.website', [], session('locale')) }}</p><h3 class="text-2xl font-extrabold text-[var(--primary-color)]" x-text="stats.website + ' {{ trans('messages.pieces', [], session('locale')) }}'"></h3></div>
        <span class="material-symbols-outlined p-3 rounded-full bg-[var(--primary-color)]/10 text-[var(--primary-color)]">shopping_cart</span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div><p class="text-sm text-gray-600">{{ trans('messages.pos_points', [], session('locale')) }}</p><h3 class="text-2xl font-extrabold text-[var(--primary-color)]" x-text="stats.pos + ' {{ trans('messages.pieces', [], session('locale')) }}'"></h3></div>
        <span class="material-symbols-outlined p-3 rounded-full bg-[var(--primary-color)]/10 text-[var(--primary-color)]">point_of_sale</span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div><p class="text-sm text-gray-600">{{ trans('messages.boutiques', [], session('locale')) }}</p><h3 class="text-2xl font-extrabold text-[var(--primary-color)]" x-text="stats.boutiques + ' {{ trans('messages.pieces', [], session('locale')) }}'"></h3></div>
        <span class="material-symbols-outlined p-3 rounded-full bg-[var(--primary-color)]/10 text-[var(--primary-color)]">storefront</span>
      </div>
    </section>

    <!-- Transfer form -->
    <section class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <h3 class="font-bold text-[var(--primary-color)] mb-4">{{ trans('messages.create_transfer_operation', [], session('locale')) }}</h3>

      <!-- Step 1: type -->
      <div class="flex flex-wrap gap-3 mb-5">
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border"
               :class="(basket.length > 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer') + ' ' + (mode==='main_to_channel' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200')">
          <input type="radio" class="sr-only" x-model="mode" value="main_to_channel" @change="resetChannelsForMode()" :disabled="basket.length > 0">
          <span class="material-symbols-outlined text-[var(--primary-color)]">call_made</span> {{ trans('messages.from_warehouse_to_channel', [], session('locale')) }}
        </label>
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border"
               :class="(basket.length > 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer') + ' ' + (mode==='channel_to_channel' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200')">
          <input type="radio" class="sr-only" x-model="mode" value="channel_to_channel" @change="resetChannelsForMode()" :disabled="basket.length > 0">
          <span class="material-symbols-outlined text-[var(--primary-color)]">swap_horiz</span> {{ trans('messages.from_channel_to_channel', [], session('locale')) }}
        </label>
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border"
               :class="(basket.length > 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer') + ' ' + (mode==='channel_to_main' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200')">
          <input type="radio" class="sr-only" x-model="mode" value="channel_to_main" @change="resetChannelsForMode()" :disabled="basket.length > 0">
          <span class="material-symbols-outlined text-[var(--primary-color)]">call_received</span> {{ trans('messages.from_channel_to_warehouse', [], session('locale')) }}
        </label>
      </div>

      <!-- Step 2: from/to/transfer date -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- From -->
        <div>
          <label class="text-sm font-semibold text-gray-700">{{ trans('messages.from', [], session('locale')) }}</label>
          <select class="w-full h-11 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                  :class="basket.length > 0 ? 'bg-gray-100 cursor-not-allowed' : ''"
                  x-model="fromChannel"
                  :disabled="basket.length > 0">
            <option value="" disabled selected>{{ trans('messages.select_channel', [], session('locale')) }}</option>
            <template x-if="mode==='main_to_channel'">
              <option value="main">{{ trans('messages.main_warehouse', [], session('locale')) }}</option>
            </template>
            <template x-if="mode!=='main_to_channel'">
              <template x-if="channelsOnly.length > 0">
                <optgroup label="{{ trans('messages.channels_and_boutiques', [], session('locale')) }}">
                  <template x-for="item in channelsOnly" :key="'from-channel-'+item.id">
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                   <template x-for="item in boutiquesOnly" :key="'from-boutique-'+item.id" >
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                </optgroup>
              </template>
              <!-- <template x-if="boutiquesOnly.length > 0">
                <optgroup label="{{ trans('messages.boutiques', [], session('locale')) }}">
                  <template x-for="item in boutiquesOnly" :key="'from-boutique-'+item.id" >
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                </optgroup>
              </template> -->
            </template>
          </select>
        </div>

        <!-- To -->
        <div>
          <label class="text-sm font-semibold text-gray-700">{{ trans('messages.to', [], session('locale')) }}</label>
          <select class="w-full h-11 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                  :class="basket.length > 0 ? 'bg-gray-100 cursor-not-allowed' : ''"
                  x-model="toChannel"
                  :disabled="basket.length > 0">
            <option value="" disabled selected>{{ trans('messages.select_channel', [], session('locale')) }}</option>
            <template x-if="mode==='channel_to_main'">
              <option value="main">{{ trans('messages.main_warehouse', [], session('locale')) }}</option>
            </template>
            <template x-if="mode!=='channel_to_main'">
              <template x-if="availableToChannels.length > 0">
                <optgroup label="{{ trans('messages.channels_and_boutiques', [], session('locale')) }}">
                  <template x-for="item in availableToChannels" :key="'to-channel-'+item.id">
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                    <template x-for="item in availableToBoutiques" :key="'to-boutique-'+item.id">
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                </optgroup>
              </template>
              <!-- <template x-if="availableToBoutiques.length > 0">
                <optgroup label="{{ trans('messages.boutiques', [], session('locale')) }}">
                  <template x-for="item in availableToBoutiques" :key="'to-boutique-'+item.id">
                    <option :value="item.type + '-' + item.id" x-text="item.display_name"></option>
                  </template>
                </optgroup>
              </template> -->
            </template>
          </select>
        </div>

        <!-- Transfer Date -->
        <div>
          <label class="text-sm font-semibold text-gray-700">{{ trans('messages.transfer_date', [], session('locale')) }} <span class="text-red-500">*</span></label>
          <input type="date" 
                 required
                 class="w-full h-11 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                 x-model="transferDate"
                 :class="!transferDate ? 'border-red-300' : ''">
        </div>
      </div>

      <!-- Step 3: pick items -->
      <div class="mt-5">
        <!-- Button for main warehouse (main_to_channel mode) -->
        <template x-if="mode==='main_to_channel'">
          <button @click="openPicker('main')" class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 font-semibold">
            <span class="material-symbols-outlined align-middle text-base">add_shopping_cart</span> {{ trans('messages.select_abayas_from_warehouse', [], session('locale')) }}
          </button>
        </template>
        
        <!-- Button for channel/boutique (channel_to_channel and channel_to_main modes) -->
        <template x-if="(mode==='channel_to_channel' || mode==='channel_to_main') && fromChannel && fromChannel !== 'main'">
          <button @click="openPicker(fromChannel)" class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 font-semibold">
            <span class="material-symbols-outlined align-middle text-base">add_shopping_cart</span> 
            <span x-text="'{{ trans('messages.select_abayas_from', [], session('locale')) }} ' + channelName(fromChannel)"></span>
          </button>
        </template>
      </div>

      <!-- Transfer Basket -->
      <div class="mt-6">
        <h4 class="font-bold text-gray-800 mb-2">{{ trans('messages.transfer_basket', [], session('locale')) }}</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[920px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.available', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.remove', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="basket.length===0">
                <tr><td colspan="7" class="text-center text-gray-400 py-8">
                  <span class="material-symbols-outlined text-4xl">inventory_2</span><div>{{ trans('messages.no_items_selected', [], session('locale')) }}</div>
                </td></tr>
              </template>
              <template x-for="(row,idx) in basket" :key="row.uid">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 text-center font-semibold" x-text="row.code"></td>
                  <td class="px-3 py-2 text-center" x-text="typeLabel(row.type)"></td>
                  <td class="px-3 py-2 text-center">
                    <template x-if="row.color">
                      <span class="inline-flex items-center justify-center gap-2">
                        <span class="w-4 h-4 rounded-full border" :style="'background:'+row.color_code"></span>
                        <span x-text="row.color"></span>
                      </span>
                    </template>
                    <template x-if="!row.color">—</template>
                  </td>
                  <td class="px-3 py-2 text-center" x-text="row.size ? row.size : '—'"></td>
                  <td class="px-3 py-2 text-center" x-text="row.available"></td>
                  <td class="px-3 py-2 text-center">
                    <input type="number" min="0" :max="row.available"
                           data-validate="quantity"
                           @keydown="validateQuantityInput($event)"
                           @paste="cleanQuantityOnPaste($event)"
                           class="h-10 w-24 border border-pink-200 rounded-lg text-center mx-auto"
                           x-model.number="row.qty">
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button @click="removeFromBasket(idx)" class="px-2 py-1 rounded-md bg-red-50 text-red-600 hover:bg-red-100 text-xs font-semibold">
                      {{ trans('messages.delete', [], session('locale')) }}
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Notes -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-semibold text-gray-700">{{ trans('messages.operation_notes_optional', [], session('locale')) }}</label>
            <textarea class="w-full h-20 rounded-lg border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] text-sm p-2"
                      x-model="transferNote" placeholder="{{ trans('messages.operation_notes_placeholder', [], session('locale')) }}"></textarea>
          </div>
          <div class="flex items-end justify-end">
            <button @click="executeTransfer()"
                    class="px-6 py-3 rounded-xl bg-[var(--primary-color)] text-white font-bold hover:opacity-90 disabled:opacity-50"
                    :disabled="!canExecute">
              🔁 {{ trans('messages.execute_transfer', [], session('locale')) }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- History -->
    <section class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <h3 class="font-bold text-[var(--primary-color)]">{{ trans('messages.movements_log', [], session('locale')) }}</h3>
        <div class="ml-auto flex gap-2 w-full sm:w-auto">
          <a href="{{url('movements_log')}}" 
             class="px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:opacity-90 font-semibold text-sm inline-flex items-center gap-1">
            <span class="material-symbols-outlined text-base">open_in_new</span>
            {{ trans('messages.view_full_log', [], session('locale')) ?: 'View Full Log' }}
          </a>
          <input type="search" class="h-10 px-3 border border-pink-200 rounded-lg flex-1 sm:w-64"
                 placeholder="{{ trans('messages.search_by_operation_number', [], session('locale')) }}" x-model="historySearch" @input="loadHistory()">
          <input type="date" class="h-10 px-2 border border-pink-200 rounded-lg" x-model="dateFromH" @change="loadHistory()">
          <input type="date" class="h-10 px-2 border border-pink-200 rounded-lg" x-model="dateToH" @change="loadHistory()">
          <button @click="exportToExcel()" 
                  class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 text-sm hover:bg-purple-200 inline-flex items-center gap-1">
            <span class="material-symbols-outlined text-base align-middle">download</span> Excel
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
            <tr>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.operation_number', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.from', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.to', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.number_of_items', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-center font-bold">{{ trans('messages.details', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-if="filteredHistory.length===0">
              <tr><td colspan="6" class="text-center text-gray-400 py-8">
                <span class="material-symbols-outlined text-4xl">history</span><div>{{ trans('messages.no_movements_yet', [], session('locale')) }}</div>
              </td></tr>
            </template>
            <template x-for="row in filteredHistory" :key="row.no">
              <tr class="border-t hover:bg-pink-50/60">
                <td class="px-3 py-2 text-center font-semibold" x-text="row.no"></td>
                <td class="px-3 py-2 text-center" x-text="row.date"></td>
                <td class="px-3 py-2 text-center" x-text="channelName(row.from)"></td>
                <td class="px-3 py-2 text-center" x-text="channelName(row.to)"></td>
                <td class="px-3 py-2 text-center" x-text="row.total"></td>
                <td class="px-3 py-2 text-center">
                  <button @click="openHistoryDetails(row)"
                          class="px-3 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-[var(--primary-color)] text-xs font-semibold">
                    {{ trans('messages.view_details', [], session('locale')) }}
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Toast -->
    <div x-show="toast.show" x-transition.opacity class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[9999]">
      <div class="px-4 py-2 rounded-full bg-green-600 text-white shadow-lg font-semibold" x-text="toast.msg"></div>
    </div>

  </div>

  <!-- Picker Modal -->
  <div x-show="showPicker" x-transition.opacity x-cloak
       class="fixed inset-0 bg-black/50 z-[9998] flex items-center justify-center p-4">
    <div @click.away="showPicker=false"
         class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold text-[var(--primary-color)]">
          <template x-if="pickerSource === 'main'">
            <span>{{ trans('messages.select_abayas_from_warehouse', [], session('locale')) }}</span>
          </template>
          <template x-if="pickerSource !== 'main'">
            <span x-text="'{{ trans('messages.select_abayas_from', [], session('locale')) }} ' + channelName(pickerSource)"></span>
          </template>
        </h3>
        <button @click="showPicker=false" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>

      <div class="p-4">
        <div class="rounded-lg bg-pink-50 border border-pink-100 text-sm text-gray-700 p-3 mb-4">
          📊 {{ trans('messages.quantities_displayed_info', [], session('locale')) }}
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input type="search" class="h-10 px-3 border border-pink-200 rounded-lg flex-1"
                 placeholder="{{ trans('messages.search_by_code_name', [], session('locale')) }}" x-model="picker.q">
          <select class="h-10 px-3 border border-pink-200 rounded-lg" x-model="picker.type">
            <option value="">{{ trans('messages.all_types', [], session('locale')) }}</option>
            <option value="size">{{ trans('messages.by_size', [], session('locale')) }}</option>
            <option value="color">{{ trans('messages.by_color', [], session('locale')) }}</option>
            <option value="color_size">{{ trans('messages.by_color_and_size', [], session('locale')) }}</option>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1100px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.name', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold" x-show="mode!=='main_to_channel'">{{ trans('messages.in_source', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold" x-show="mode!=='channel_to_main'">{{ trans('messages.in_destination', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold" x-show="mode!=='channel_to_channel'">{{ trans('messages.available_warehouse', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.add', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="inventoryLoading">
                <tr><td :colspan="mode === 'channel_to_channel' ? 8 : 9" class="text-center text-gray-400 py-8">
                  <span class="material-symbols-outlined text-4xl animate-spin">refresh</span><div>{{ trans('messages.loading', [], session('locale')) }}...</div>
                </td></tr>
              </template>
              <template x-if="!inventoryLoading && pickerFiltered.length===0">
                <tr><td :colspan="mode === 'channel_to_channel' ? 8 : 9" class="text-center text-gray-400 py-8">
                  <span class="material-symbols-outlined text-4xl">inventory_2</span><div>{{ trans('messages.no_items_found', [], session('locale')) }}</div>
                </td></tr>
              </template>
              <template x-for="row in pickerFiltered" :key="row.uid">
                <tr class="border-t hover:bg-pink-50/60"
                    :class="selectedUids.includes(row.uid) ? 'bg-purple-50' : ''">
                  <td class="px-3 py-2 text-center font-semibold" x-text="row.code || '—'"></td>
                  <td class="px-3 py-2 text-center" x-text="row.name || '—'"></td>
                  <td class="px-3 py-2 text-center" x-text="typeLabel(row.type)"></td>
                  <td class="px-3 py-2 text-center">
                    <template x-if="row.color">
                      <span class="inline-flex items-center justify-center gap-2">
                        <span class="w-4 h-4 rounded-full border" :style="'background:'+(row.color_code || '#000000')"></span>
                        <span x-text="row.color"></span>
                      </span>
                    </template>
                    <template x-if="!row.color">—</template>
                  </td>
                  <td class="px-3 py-2 text-center" x-text="row.size ? row.size : '—'"></td>

                  <!-- Qty in source -->
                  <td class="px-3 py-2 text-center" x-show="mode!=='main_to_channel'"
                      x-text="getQtyInChannel(fromChannel, row)"
                      @load="loadChannelStocks(fromChannel)"></td>

                  <!-- Qty in destination -->
                  <td class="px-3 py-2 text-center" x-show="mode!=='channel_to_main'"
                      x-text="getQtyInChannel(toChannel, row)"
                      @load="loadChannelStocks(toChannel)"></td>

                  <td class="px-3 py-2 text-center" x-show="mode!=='channel_to_channel'" x-text="getWarehouseAvailable(row)"></td>
                  <td class="px-3 py-2 text-center">
                    <template x-if="!selectedUids.includes(row.uid)">
                      <button @click="addToBasket(row)"
                              class="px-3 py-1 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs font-semibold">
                        {{ trans('messages.add_to_basket', [], session('locale')) }}
                      </button>
                    </template>
                    <template x-if="selectedUids.includes(row.uid)">
                      <span class="px-3 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-semibold inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">check</span> {{ trans('messages.added', [], session('locale')) }}
                      </span>
                    </template>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t flex justify-end">
          <button @click="showPicker=false" class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">{{ trans('messages.close', [], session('locale')) }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- History Details Modal -->
  <div x-show="showHistory" x-transition.opacity x-cloak
       class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4">
    <div @click.away="showHistory=false" class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.operation_details', [], session('locale')) }} <span x-text="currentHistory.no"></span></h3>
        <button @click="showHistory=false" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>
      <div class="p-4">
        <p class="text-sm text-gray-600 mb-2">
          <span class="font-semibold">{{ trans('messages.from', [], session('locale')) }}:</span> <span x-text="channelName(currentHistory.from)"></span> •
          <span class="font-semibold">{{ trans('messages.to', [], session('locale')) }}:</span> <span x-text="channelName(currentHistory.to)"></span> •
          <span class="font-semibold">{{ trans('messages.date', [], session('locale')) }}:</span> <span x-text="currentHistory.date"></span>
        </p>
        <template x-if="currentHistory.note">
          <div class="mb-3 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm">
              <span class="font-semibold text-gray-700">{{ trans('messages.operation_notes', [], session('locale')) }}:</span>
              <span class="text-gray-600" x-text="currentHistory.note"></span>
            </p>
          </div>
        </template>
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-pink-50 text-gray-700">
              <tr>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="it in currentHistory.items" :key="it.code + (it.size||'') + (it.color||'')">
                <tr>
                  <td class="px-3 py-2 text-center" x-text="it.code"></td>
                  <td class="px-3 py-2 text-center" x-text="it.color || '—'"></td>
                  <td class="px-3 py-2 text-center" x-text="it.size || '—'"></td>
                  <td class="px-3 py-2 text-center" x-text="it.qty"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t text-right">
          <button @click="showHistory=false" class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">{{ trans('messages.close', [], session('locale')) }}</button>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
function transferPage() {
  return {
    // Stats - loaded from backend
    stats: { main: 0, website: 0, pos: 0, boutiques: 0 },

    // Items from controller (channels + boutiques)
    items: @json($items ?? []),


    get channelsOnly() {
      return this.items.filter(i => i.type === 'channel');
    },

    // Computed: Boutiques only
    get boutiquesOnly() {
      return this.items.filter(i => i.type === 'boutique');
    },

    // Computed: Available items for "To" select (excludes selected "from" in channel_to_channel mode)
    get availableToItems() {
      // For channel_to_channel mode, show all items (don't exclude the selected "from")
      return this.items;
    },

    // Computed: Available channels for "To" select
    get availableToChannels() {
      return this.availableToItems.filter(i => i.type === 'channel');
    },

    // Computed: Available boutiques for "To" select
    get availableToBoutiques() {
      return this.availableToItems.filter(i => i.type === 'boutique');
    },

    // Mode & selections
    mode: 'main_to_channel',
    fromChannel: 'main',
    toChannel: '',
    transferDate: '',
    
    // Reset channels when mode changes
    resetChannelsForMode() {
      if (this.mode === 'main_to_channel') {
        this.fromChannel = 'main';
        this.toChannel = '';
      } else if (this.mode === 'channel_to_channel') {
        this.fromChannel = '';
        this.toChannel = '';
      } else if (this.mode === 'channel_to_main') {
        this.fromChannel = '';
        this.toChannel = 'main';
      }
    },

    // Inventory (from main) - loaded from API
    picker: { q:'', type:'' },
    inventory: [],
    warehouseInventory: [], // Main warehouse inventory (for showing available warehouse qty when transferring from channel)
    inventoryLoading: false,
    get pickerFiltered() {
      const q = this.picker.q.toLowerCase();
      return this.inventory.filter(r => {
        const matchQ = !q || (r.code && r.code.toLowerCase().includes(q)) || (r.name && r.name.toLowerCase().includes(q));
        const matchType = !this.picker.type || r.type===this.picker.type;
        return matchQ && matchType;
      });
    },
    async loadInventory() {
      this.inventoryLoading = true;
      try {
        const response = await fetch('/get_inventory');
        const data = await response.json();
        this.inventory = data || [];
        // Also store in warehouseInventory for reference
        this.warehouseInventory = data || [];
      } catch (error) {
        console.error('Error loading inventory:', error);
        this.inventory = [];
        this.warehouseInventory = [];
        this.toast.msg = '{{ trans('messages.error_loading_inventory', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(() => this.toast.show = false, 3000);
      } finally {
        this.inventoryLoading = false;
      }
    },
    
    // Load warehouse inventory separately (for showing available warehouse qty)
    async loadWarehouseInventory() {
      try {
        const response = await fetch('/get_inventory');
        const data = await response.json();
        this.warehouseInventory = data || [];
      } catch (error) {
        console.error('Error loading warehouse inventory:', error);
        this.warehouseInventory = [];
      }
    },
    
    // Get warehouse available quantity for an item
    getWarehouseAvailable(row) {
      if (this.mode !== 'channel_to_main') {
        return row.available || 0;
      }
      // When transferring from channel to warehouse, show warehouse stock
      const found = this.warehouseInventory.find(x =>
        x.code === row.code &&
        ((x.color || null) === (row.color || null)) &&
        ((x.size || null) === (row.size || null))
      );
      return found ? (found.available || 0) : 0;
    },

    // Channel stocks (source/destination) - loaded from backend
    channelStocks: {},

    // Load channel stocks
    async loadChannelStocks(channelId) {
      if (!channelId || channelId === 'main') return;
      if (this.channelStocks[channelId]) return; // Already loaded
      
      try {
        const response = await fetch(`/get_channel_stocks?channel_id=${channelId}`);
        const data = await response.json();
        this.channelStocks[channelId] = data || [];
      } catch (error) {
        console.error('Error loading channel stocks:', error);
        this.channelStocks[channelId] = [];
      }
    },

    // Helpers to get qty in a channel for a row
    getQtyInChannel(channelId, row) {
      if (!channelId || channelId === 'main') return '-';
      // channelId format: "type-id" (e.g., "channel-1" or "boutique-2")
      const list = this.channelStocks[channelId] || [];
      const found = list.find(x =>
        x.code === row.code &&
        ((x.color || null) === (row.color || null)) &&
        ((x.size || null) === (row.size || null))
      );
      return found ? found.qty : 0;
    },

    // Picker selection highlighting
    selectedUids: [],

    // Basket
    basket: [],
    transferNote: '',
    typeLabel(t){ return t==='size' ? '{{ trans('messages.by_size', [], session('locale')) }}' : t==='color' ? '{{ trans('messages.by_color', [], session('locale')) }}' : '{{ trans('messages.by_color_and_size', [], session('locale')) }}'; },
    addToBasket(row){
      // Use the uid from the row (generated by API) or create one if missing
      const uid = row.uid || `${row.code}|${row.size||''}|${row.color||''}`;
      const exists = this.basket.find(b => b.uid===uid);
      if (!exists){
        // When transferring from channel to warehouse, use warehouse available quantity
        const availableQty = this.mode === 'channel_to_main' ? this.getWarehouseAvailable(row) : row.available;
        this.basket.push({...row, uid, qty: 1, available: availableQty});
        if (!this.selectedUids.includes(uid)) this.selectedUids.push(uid);
      }
    },
    removeFromBasket(idx){
      const removed = this.basket.splice(idx,1)[0];
      if (removed){
        const pos = this.selectedUids.indexOf(removed.uid);
        if (pos>-1) this.selectedUids.splice(pos,1);
      }
    },

    // Execute enable
    get canExecute() {
      if (!this.fromChannel || !this.toChannel) return false;
      if (!this.transferDate) return false; // Transfer date is required
      if (this.mode==='main_to_channel' && this.fromChannel!=='main') return false;
      if (this.mode==='channel_to_main' && this.toChannel!=='main') return false;
      if (this.mode==='channel_to_channel' && (this.fromChannel==='main' || this.toChannel==='main')) return false;
      if (this.fromChannel === this.toChannel) return false;
      if (this.basket.length===0) return false;
      return this.basket.every(b => Number(b.qty)>0 && Number(b.qty) <= Number(b.available));
    },

    // Picker open
    showPicker: false,
    pickerSource: 'main', // Track which source we're picking from
    async openPicker(source = 'main'){ 
      this.pickerSource = source;
      this.showPicker = true;
      
      // Load inventory based on source
      if (source === 'main') {
        if (this.inventory.length === 0) {
          await this.loadInventory();
        }
      } else {
        // Load from channel/boutique
        await this.loadChannelInventory(source);
        
        // If transferring from channel to warehouse, also load warehouse inventory
        if (this.mode === 'channel_to_main') {
          if (this.warehouseInventory.length === 0) {
            await this.loadWarehouseInventory();
          }
        }
      }
      
      // Load channel stocks when picker opens (for displaying quantities)
      if (this.fromChannel && this.fromChannel !== 'main') {
        await this.loadChannelStocks(this.fromChannel);
      }
      if (this.toChannel && this.toChannel !== 'main') {
        await this.loadChannelStocks(this.toChannel);
      }
    },
    
    // Load inventory from a specific channel/boutique
    async loadChannelInventory(channelId) {
      this.inventoryLoading = true;
      try {
        const response = await fetch(`/get_channel_inventory?channel_id=${channelId}`);
        const data = await response.json();
        this.inventory = data || [];
      } catch (error) {
        console.error('Error loading channel inventory:', error);
        this.inventory = [];
        this.toast.msg = '{{ trans('messages.error_loading_inventory', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(() => this.toast.show = false, 3000);
      } finally {
        this.inventoryLoading = false;
      }
    },

    // History - loaded from backend
    history: [],
    filteredHistory: [],
    historySearch: '',
    dateFromH: '', dateToH: '',
    showHistory:false,
    currentHistory:{no:'', items:[], from:'', to:'', date:'', total:0},
    channelName(id){
      if (id==='main') return '{{ trans('messages.main_warehouse', [], session('locale')) }}';
      const f = this.items.find(item => {
        const itemValue = item.type + '-' + item.id;
        return itemValue === id;
      });
      return f ? f.display_name : id;
    },
    openHistoryDetails(row){ this.currentHistory = row; this.showHistory = true; },
    async loadHistory(){
      try {
        const params = new URLSearchParams();
        if (this.historySearch) params.append('search', this.historySearch);
        if (this.dateFromH) params.append('date_from', this.dateFromH);
        if (this.dateToH) params.append('date_to', this.dateToH);
        
        const response = await fetch(`/get_transfer_history?${params.toString()}`);
        const data = await response.json();
        this.history = data || [];
        this.filterHistory();
      } catch (error) {
        console.error('Error loading history:', error);
        this.history = [];
      }
    },
    filterHistory(){
      const q = this.historySearch.toLowerCase();
      const from = this.dateFromH ? new Date(this.dateFromH) : null;
      const to   = this.dateToH ? new Date(this.dateToH) : null;
      this.filteredHistory = this.history.filter(r=>{
        const d = new Date(r.date);
        const text = `${r.no} ${this.channelName(r.from)} ${this.channelName(r.to)}`.toLowerCase();
        const matchQ = !q || text.includes(q);
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },

    // Toast
    toast:{show:false, msg:''},

    // Execute transfer - call backend
    async executeTransfer(){
      if (!this.canExecute) {
        if (!this.transferDate) {
          this.toast.msg = '{{ trans('messages.transfer_date_required', [], session('locale')) ?: 'Transfer Date is required' }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 3000);
        }
        return;
      }

      // Validate transfer date is provided
      if (!this.transferDate) {
        this.toast.msg = '{{ trans('messages.transfer_date_required', [], session('locale')) ?: 'Transfer Date is required' }}';
        this.toast.show = true;
        setTimeout(()=> this.toast.show=false, 3000);
        return;
      }

      try {
        const response = await fetch('/execute_transfer', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            mode: this.mode,
            from: this.fromChannel,
            to: this.toChannel,
            transfer_date: this.transferDate,
            note: this.transferNote,
            basket: this.basket.map(b => ({
              code: b.code,
              color: b.color,
              size: b.size,
              qty: Number(b.qty),
              type: b.type,
              available: Number(b.available),
              uid: b.uid
            }))
          })
        });

        const result = await response.json();

        if (result.status === 'success') {
          // Reload stats and history
          await this.loadStats();
          await this.loadHistory();
          
          // Clear channel stocks cache to force reload
          this.channelStocks = {};

          // reset
          this.basket = [];
          this.selectedUids = [];
          this.transferNote = '';
          this.transferDate = '';
          if (this.mode==='main_to_channel'){ this.fromChannel='main'; this.toChannel=''; }
          if (this.mode==='channel_to_channel'){ this.fromChannel=''; this.toChannel=''; }
          if (this.mode==='channel_to_main'){ this.fromChannel=''; this.toChannel='main'; }

          this.toast.msg = result.message || '{{ trans('messages.transfer_executed_successfully', [], session('locale')) }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 2000);
        } else {
          this.toast.msg = result.message || '{{ trans('messages.error_executing_transfer', [], session('locale')) }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 3000);
        }
      } catch (error) {
        console.error('Error executing transfer:', error);
        this.toast.msg = '{{ trans('messages.error_executing_transfer', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(()=> this.toast.show=false, 3000);
      }
    },

    // Load stats from backend
    async loadStats(){
      try {
        const response = await fetch('/get_stats');
        const data = await response.json();
        this.stats = data || { main: 0, website: 0, pos: 0, boutiques: 0 };
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },

    // Export to Excel
    exportToExcel(){
      const params = new URLSearchParams();
      if (this.historySearch) params.append('search', this.historySearch);
      if (this.dateFromH) params.append('date_from', this.dateFromH);
      if (this.dateToH) params.append('date_to', this.dateToH);
      
      const url = '/export_transfers_excel' + (params.toString() ? '?' + params.toString() : '');
      window.location.href = url;
    },

    async init(){
      // defaults
      this.fromChannel='main'; this.toChannel='';
      
      // Set transfer date to today's date (YYYY-MM-DD format)
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const day = String(today.getDate()).padStart(2, '0');
      this.transferDate = `${year}-${month}-${day}`;
      
      // Load data on page load
      await Promise.all([
        this.loadInventory(),
        this.loadStats(),
        this.loadHistory()
      ]);
      
      // Debug: Log items to console
      console.log('All items:', this.items);
      console.log('Channels:', this.channelsOnly);
      console.log('Boutiques:', this.boutiquesOnly);
    }
  }
}
</script>
@include('layouts.footer')
@endsection
