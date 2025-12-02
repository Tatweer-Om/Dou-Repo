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
      <a href="/inventory/index.php"
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
        <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border"
               :class="mode==='main_to_channel' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200'">
          <input type="radio" class="sr-only" x-model="mode" value="main_to_channel">
          <span class="material-symbols-outlined text-[var(--primary-color)]">call_made</span> {{ trans('messages.from_warehouse_to_channel', [], session('locale')) }}
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border"
               :class="mode==='channel_to_channel' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200'">
          <input type="radio" class="sr-only" x-model="mode" value="channel_to_channel">
          <span class="material-symbols-outlined text-[var(--primary-color)]">swap_horiz</span> {{ trans('messages.from_channel_to_channel', [], session('locale')) }}
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border"
               :class="mode==='channel_to_main' ? 'border-[var(--primary-color)] bg-pink-50' : 'border-pink-200'">
          <input type="radio" class="sr-only" x-model="mode" value="channel_to_main">
          <span class="material-symbols-outlined text-[var(--primary-color)]">call_received</span> {{ trans('messages.from_channel_to_warehouse', [], session('locale')) }}
        </label>
      </div>

      <!-- Step 2: from/to -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- From -->
        <div>
          <label class="text-sm font-semibold text-gray-700">{{ trans('messages.from', [], session('locale')) }}</label>
          <select class="w-full h-11 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                  x-model="fromChannel">
            <option value="" disabled selected>{{ trans('messages.select_channel', [], session('locale')) }}</option>
            <template x-if="mode!=='main_to_channel'">
              <optgroup label="{{ trans('messages.channels', [], session('locale')) }}">
                <template x-for="c in channels" :key="'from-'+c.id">
                  <option :value="c.id" x-text="c.name"></option>
                </template>
              </optgroup>
            </template>
            <template x-if="mode==='main_to_channel'">
              <option value="main">{{ trans('messages.main_warehouse', [], session('locale')) }}</option>
            </template>
          </select>
        </div>

        <!-- To -->
        <div>
          <label class="text-sm font-semibold text-gray-700">{{ trans('messages.to', [], session('locale')) }}</label>
          <select class="w-full h-11 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                  x-model="toChannel">
            <option value="" disabled selected>{{ trans('messages.select_channel', [], session('locale')) }}</option>
            <template x-if="mode==='channel_to_main'">
              <option value="main">{{ trans('messages.main_warehouse', [], session('locale')) }}</option>
            </template>
            <template x-if="mode!=='channel_to_main'">
              <optgroup label="{{ trans('messages.channels', [], session('locale')) }}">
                <template x-for="c in channels" :key="'to-'+c.id">
                  <option :value="c.id" x-text="c.name"></option>
                </template>
              </optgroup>
            </template>
          </select>
        </div>
      </div>

      <!-- Step 3: pick items -->
      <div class="mt-5">
        <button @click="openPicker()" class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200 font-semibold">
          <span class="material-symbols-outlined align-middle text-base">add_shopping_cart</span> {{ trans('messages.select_abayas_from_warehouse', [], session('locale')) }}
        </button>
      </div>

      <!-- Basket -->
      <div class="mt-6">
        <h4 class="font-bold text-gray-800 mb-2">{{ trans('messages.transfer_basket', [], session('locale')) }}</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[920px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.available', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
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
                  <td class="px-3 py-2 font-semibold" x-text="row.code"></td>
                  <td class="px-3 py-2" x-text="typeLabel(row.type)"></td>
                  <td class="px-3 py-2">
                    <template x-if="row.color">
                      <span class="inline-flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full border" :style="'background:'+row.color_code"></span>
                        <span x-text="row.color"></span>
                      </span>
                    </template>
                    <template x-if="!row.color">—</template>
                  </td>
                  <td class="px-3 py-2" x-text="row.size ? row.size : '—'"></td>
                  <td class="px-3 py-2" x-text="row.available"></td>
                  <td class="px-3 py-2">
                    <input type="number" min="0" :max="row.available"
                           class="h-10 w-24 border border-pink-200 rounded-lg text-center"
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
          <input type="search" class="h-10 px-3 border border-pink-200 rounded-lg flex-1 sm:w-64"
                 placeholder="{{ trans('messages.search_by_operation_number', [], session('locale')) }}" x-model="historySearch" @input="filterHistory()">
          <input type="date" class="h-10 px-2 border border-pink-200 rounded-lg" x-model="dateFromH" @change="filterHistory()">
          <input type="date" class="h-10 px-2 border border-pink-200 rounded-lg" x-model="dateToH" @change="filterHistory()">
          <button class="px-4 py-2 rounded-lg bg-purple-100 text-purple-700 text-sm hover:bg-purple-200">
            <span class="material-symbols-outlined text-base align-middle">download</span> Excel
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
            <tr>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.operation_number', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.from', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.to', [], session('locale')) }}</th>
              <th class="px-3 py-2 text-right font-bold">{{ trans('messages.number_of_items', [], session('locale')) }}</th>
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
                <td class="px-3 py-2 font-semibold" x-text="row.no"></td>
                <td class="px-3 py-2" x-text="row.date"></td>
                <td class="px-3 py-2" x-text="channelName(row.from)"></td>
                <td class="px-3 py-2" x-text="channelName(row.to)"></td>
                <td class="px-3 py-2" x-text="row.total"></td>
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
        <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.select_abayas_from_warehouse', [], session('locale')) }}</h3>
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
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.name', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold" x-show="mode!=='main_to_channel'">{{ trans('messages.in_source', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold" x-show="mode!=='channel_to_main'">{{ trans('messages.in_destination', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.available_warehouse', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.add', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="row in pickerFiltered" :key="row.uid">
                <tr class="border-t hover:bg-pink-50/60"
                    :class="selectedUids.includes(row.uid) ? 'bg-purple-50' : ''">
                  <td class="px-3 py-2 font-semibold" x-text="row.code"></td>
                  <td class="px-3 py-2" x-text="row.name"></td>
                  <td class="px-3 py-2" x-text="typeLabel(row.type)"></td>
                  <td class="px-3 py-2">
                    <template x-if="row.color">
                      <span class="inline-flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full border" :style="'background:'+row.color_code"></span>
                        <span x-text="row.color"></span>
                      </span>
                    </template>
                    <template x-if="!row.color">—</template>
                  </td>
                  <td class="px-3 py-2" x-text="row.size ? row.size : '—'"></td>

                  <!-- Qty in source -->
                  <td class="px-3 py-2 text-center" x-show="mode!=='main_to_channel'"
                      x-text="getQtyInChannel(fromChannel, row)"></td>

                  <!-- Qty in destination -->
                  <td class="px-3 py-2 text-center" x-show="mode!=='channel_to_main'"
                      x-text="getQtyInChannel(toChannel, row)"></td>

                  <td class="px-3 py-2" x-text="row.available"></td>
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
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-pink-50 text-gray-700">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="it in currentHistory.items" :key="it.code + (it.size||'') + (it.color||'')">
                <tr>
                  <td class="px-3 py-2" x-text="it.code"></td>
                  <td class="px-3 py-2" x-text="it.color || '—'"></td>
                  <td class="px-3 py-2" x-text="it.size || '—'"></td>
                  <td class="px-3 py-2" x-text="it.qty"></td>
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
    // Stats (dummy)
    stats: { main: 520, website: 120, pos: 65, boutiques: 180 },

    // Channels
    channels: [
      {id:'web', name:'الموقع الإلكتروني'},
      {id:'pos', name:'نقطة بيع - المكتب'},
      {id:'btk-noor', name:'بوتيك النور'},
      {id:'btk-almouj', name:'بوتيك الموج'}
    ],

    // Mode & selections
    mode: 'main_to_channel',
    fromChannel: 'main',
    toChannel: '',

    // Inventory (from main) - dummy
    picker: { q:'', type:'' },
    inventory: [
      // type: size | color | color_size
      {uid:'1', code:'ABY101', name:'عباية خليجية', type:'size', size:'M', color:null, color_code:'#000000', available:22},
      {uid:'2', code:'ABY101', name:'عباية خليجية', type:'size', size:'L', color:null, color_code:'#000000', available:15},
      {uid:'3', code:'ABY205', name:'عباية فاخرة', type:'color', size:null, color:'أسود', color_code:'#000000', available:30},
      {uid:'4', code:'ABY205', name:'عباية فاخرة', type:'color', size:null, color:'بيج', color_code:'#f5deb3', available:12},
      {uid:'5', code:'ABY330', name:'عباية مودرن', type:'color_size', size:'S', color:'أسود', color_code:'#000000', available:10},
      {uid:'6', code:'ABY330', name:'عباية مودرن', type:'color_size', size:'M', color:'بيج', color_code:'#f5deb3', available:8},
      {uid:'7', code:'ABY447', name:'عباية كلاسيك', type:'color_size', size:'L', color:'رمادي', color_code:'#b0b0b0', available:6},
    ],
    get pickerFiltered() {
      const q = this.picker.q.toLowerCase();
      return this.inventory.filter(r => {
        const matchQ = !q || r.code.toLowerCase().includes(q) || r.name.toLowerCase().includes(q);
        const matchType = !this.picker.type || r.type===this.picker.type;
        return matchQ && matchType;
      });
    },

    // Channel stocks (source/destination) - dummy
    channelStocks: {
      "btk-noor": [
        { code:"ABY101", color:null, size:"M", qty:6 },
        { code:"ABY101", color:null, size:"L", qty:2 },
        { code:"ABY205", color:"أسود", size:null, qty:3 },
        { code:"ABY330", color:"أسود", size:"S", qty:1 }
      ],
      "btk-almouj": [
        { code:"ABY101", color:null, size:"M", qty:1 },
        { code:"ABY205", color:"رمادي", size:null, qty:4 },
        { code:"ABY330", color:"بيج", size:"M", qty:2 }
      ],
      "pos": [
        { code:"ABY101", color:null, size:"M", qty:1 }
      ],
      "web": []
    },

    // Helpers to get qty in a channel for a row
    getQtyInChannel(channelId, row) {
      if (!channelId || channelId === 'main') return '-';
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
      const uid = `${row.code}|${row.size||''}|${row.color||''}`;
      const exists = this.basket.find(b => b.uid===uid);
      if (!exists){
        this.basket.push({...row, uid, qty: 1});
        if (!this.selectedUids.includes(row.uid)) this.selectedUids.push(row.uid);
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
      if (this.mode==='main_to_channel' && this.fromChannel!=='main') return false;
      if (this.mode==='channel_to_main' && this.toChannel!=='main') return false;
      if (this.mode==='channel_to_channel' && (this.fromChannel==='main' || this.toChannel==='main')) return false;
      if (this.fromChannel === this.toChannel) return false;
      if (this.basket.length===0) return false;
      return this.basket.every(b => Number(b.qty)>0 && Number(b.qty) <= Number(b.available));
    },

    // Picker open
    showPicker: false,
    openPicker(){ this.showPicker = true; },

    // History
    history: [
      {no:'TR-2025-001', date:'2025-11-10', from:'main', to:'btk-noor', total:12,
        items:[{code:'ABY101',color:null,size:'M',qty:6},{code:'ABY205',color:'أسود',size:null,qty:6}]}
    ],
    filteredHistory: [],
    historySearch: '',
    dateFromH: '', dateToH: '',
    showHistory:false,
    currentHistory:{no:'', items:[], from:'', to:'', date:'', total:0},
    channelName(id){
      if (id==='main') return '{{ trans('messages.main_warehouse', [], session('locale')) }}';
      const f = this.channels.find(c=>c.id===id);
      return f ? f.name : id;
    },
    openHistoryDetails(row){ this.currentHistory = row; this.showHistory = true; },
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

    // Execute transfer (dummy adjust stats + add to history)
    executeTransfer(){
      if (!this.canExecute) return;

      const total = this.basket.reduce((s,b)=>s+Number(b.qty||0),0);
      const no = 'TR-' + new Date().toISOString().slice(0,10).replaceAll('-','') + '-' + String(this.history.length+1).padStart(3,'0');
      const items = this.basket.map(b=>({code:b.code, color:b.color, size:b.size, qty:Number(b.qty)}));

      // Update dummy stats visually (not persisted)
      const isBoutique = id => ['btk-noor','btk-almouj'].includes(id);
      if (this.fromChannel==='main') this.stats.main -= total;
      if (this.toChannel==='main') this.stats.main += total;
      if (this.fromChannel==='web') this.stats.website -= total;
      if (this.toChannel==='web') this.stats.website += total;
      if (this.fromChannel==='pos') this.stats.pos -= total;
      if (this.toChannel==='pos') this.stats.pos += total;
      if (isBoutique(this.fromChannel)) this.stats.boutiques -= total;
      if (isBoutique(this.toChannel)) this.stats.boutiques += total;

      // record history
      this.history.unshift({
        no, date:new Date().toISOString().slice(0,10),
        from:this.fromChannel, to:this.toChannel, total, items, note:this.transferNote
      });
      this.filterHistory();

      // reset
      this.basket = [];
      this.selectedUids = [];
      this.transferNote = '';
      if (this.mode==='main_to_channel'){ this.fromChannel='main'; this.toChannel=''; }
      if (this.mode==='channel_to_channel'){ this.fromChannel=''; this.toChannel=''; }
      if (this.mode==='channel_to_main'){ this.fromChannel=''; this.toChannel='main'; }

      this.toast.msg = '{{ trans('messages.transfer_executed_successfully', [], session('locale')) }}';
      this.toast.show = true;
      setTimeout(()=> this.toast.show=false, 2000);
    },

    init(){
      // defaults
      this.fromChannel='main'; this.toChannel='';
      this.filterHistory();
    }
  }
}
</script>
@include('layouts.footer')
@endsection
