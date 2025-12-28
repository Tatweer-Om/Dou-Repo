@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.tailor_profile', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="tailorProfile()">

  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $tailor->tailor_name ?? trans('messages.tailor_profile_name', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">{{ trans('messages.phone', [], session('locale')) }}: {{ $tailor->tailor_phone ?? '-' }} • {{ $tailor->tailor_address ?? '' }}</p>
      </div>
      <div class="flex gap-2">
        <a href="/tailor" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">arrow_back</span> {{ trans('messages.back', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- General Information Card -->
    <div class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4">{{ trans('messages.general_information', [], session('locale')) }}</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.tailor_name', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $tailor->tailor_name }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.tailor_phone', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $tailor->tailor_phone }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.tailor_address', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $tailor->tailor_address }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 border-b border-pink-100 overflow-x-auto no-scrollbar bg-white rounded-xl shadow-sm px-4">
      <button @click="tab='special_orders'"
              :class="tab==='special_orders' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">assignment</span> {{ trans('messages.special_orders', [], session('locale')) }}
      </button>
      <button @click="tab='stock_received'"
              :class="tab==='stock_received' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">inventory_2</span> {{ trans('messages.stock_received', [], session('locale')) }}
      </button>
      <button @click="tab='send_material'"
              :class="tab==='send_material' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">send</span> {{ trans('messages.send_material', [], session('locale')) }}
      </button>
      <button @click="tab='repair_history'"
              :class="tab==='repair_history' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">build</span> Repair History
      </button>
      <button @click="tab='late_delivery'"
              :class="tab==='late_delivery' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">error</span> {{ trans('messages.late_delivery_history', [], session('locale')) ?: 'Late Delivery History' }}
      </button>
    </div>

    <!-- SPECIAL ORDERS TAB -->
    <section x-show="tab==='special_orders'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.special_orders', [], session('locale')) }}</h3>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_sent', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ $specialOrdersData['total_sent'] }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 shadow-md border border-green-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_received', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-green-600">{{ $specialOrdersData['total_received'] }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 shadow-md border border-orange-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.pending', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-orange-600">{{ $specialOrdersData['pending'] }}</h3>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.order_id', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.customer_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.abaya_code', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.design_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.status', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.sent_date', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.received_date', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($specialOrdersData['items'] as $item)
              <tr class="border-b border-gray-100 hover:bg-pink-50/50">
                <td class="px-4 py-3">{{ $item['order_id'] }}</td>
                <td class="px-4 py-3">{{ $item['customer_name'] }}</td>
                <td class="px-4 py-3">{{ $item['abaya_code'] }}</td>
                <td class="px-4 py-3">{{ $item['design_name'] }}</td>
                <td class="px-4 py-3">{{ $item['quantity'] }}</td>
                <td class="px-4 py-3">
                  @if($item['status'] === 'received')
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ trans('messages.received', [], session('locale')) }}</span>
                  @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">{{ trans('messages.processing', [], session('locale')) }}</span>
                  @endif
                </td>
                <td class="px-4 py-3">{{ $item['sent_date'] }}</td>
                <td class="px-4 py-3">{{ $item['received_date'] ?? '-' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- STOCK RECEIVED TAB -->
    <section x-show="tab==='stock_received'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.stock_received', [], session('locale')) }}</h3>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ $stockReceivedData['total_items'] }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-pink-100 shadow-md border border-pink-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_value', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-[var(--primary-color)]">{{ number_format($stockReceivedData['total_value'], 3) }} ر.ع</h3>
          </div>
        </div>

        <!-- Stock Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.abaya_code', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.design_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.received_date', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($stockReceivedData['items'] as $item)
              <tr class="border-b border-gray-100 hover:bg-pink-50/50">
                <td class="px-4 py-3">{{ $item['abaya_code'] }}</td>
                <td class="px-4 py-3">{{ $item['design_name'] }}</td>
                <td class="px-4 py-3">{{ $item['color'] }}</td>
                <td class="px-4 py-3">{{ $item['size'] }}</td>
                <td class="px-4 py-3">{{ $item['quantity'] }}</td>
                <td class="px-4 py-3">{{ $item['received_date'] }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- SEND MATERIAL TAB -->
    <section x-show="tab==='send_material'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.send_material', [], session('locale')) }}</h3>
        
        <!-- Summary Cards -->
        <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100 shadow-md border border-indigo-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_materials_sent', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-indigo-600">{{ $materialsSentData['total_materials'] }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-teal-50 to-teal-100 shadow-md border border-teal-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_abayas_expected', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-teal-600">{{ $materialsSentData['total_abayas_expected'] }}</h3>
          </div>
        </div> -->

        <!-- Send Material Form -->
        <div class="bg-gradient-to-br from-gray-50 to-pink-50 rounded-2xl p-6 mb-6 border border-gray-200 shadow-lg">
          <div class="flex items-center gap-3 mb-6">
            <div class="bg-[var(--primary-color)] rounded-full p-3">
              <span class="material-symbols-outlined text-white text-2xl">send</span>
            </div>
            <div>
              <h4 class="text-xl font-bold text-gray-800">{{ trans('messages.send_new_material', [], session('locale')) }}</h4>
              <p class="text-sm text-gray-600">{{ trans('messages.send_material_description', [], session('locale')) ?: 'Select material and enter quantity to send to tailor' }}</p>
            </div>
          </div>
          
          <form id="send_material_form" class="space-y-6">
            @csrf
            <input type="hidden" name="tailor_id" value="{{ $tailor->id }}">
            <input type="hidden" name="material_id" id="material_id" value="">
            
            <!-- Material Selection -->
            <div class="bg-white rounded-xl p-6 border-2 border-pink-200 shadow-md">
              <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-[var(--primary-color)] text-xl">inventory_2</span>
                <h2 class="text-xl font-bold text-gray-800">{{ trans('messages.select_material', [], session('locale')) }}</h2>
              </div>
              
              <div class="space-y-5">
                <!-- Material Name Selection with Search -->
                <label class="flex flex-col relative">
                  <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</p>
                    <span class="text-xs text-gray-500" x-show="materials.length > 0" x-text="`${materials.length} {{ trans('messages.materials_available', [], session('locale')) ?: 'materials available' }}`"></span>
                  </div>
                  <div class="relative" @click.away="showMaterialDropdown = false">
                    <input type="text" 
                           id="material_search" 
                           x-model="materialSearch"
                           placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}"
                           class="form-input h-12 rounded-lg px-4 pr-10 border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)] w-full"
                           @input="filterMaterials()"
                           @focus="if (filteredMaterials.length > 0) showMaterialDropdown = true"
                           @keydown.escape="showMaterialDropdown = false"
                           autocomplete="off">
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">search</span>
                    
                    <!-- Material Dropdown with Category -->
                    <div x-show="showMaterialDropdown && filteredMaterials.length > 0" 
                         x-transition
                         x-cloak
                         class="absolute top-full left-0 right-0 mt-1 bg-white border-2 border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto z-50">
                      <template x-if="filteredMaterials.length === 0 && materialSearch">
                        <div class="px-4 py-3 text-center text-gray-500 text-sm">
                          {{ trans('messages.no_materials_found', [], session('locale')) ?: 'No materials found' }}
                        </div>
                      </template>
                      <template x-for="material in filteredMaterials" :key="material.id">
                        <div @click="selectMaterial(material)" 
                             class="px-4 py-3 hover:bg-pink-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition">
                          <div class="flex items-center justify-between">
                            <div class="flex-1">
                              <p class="font-semibold text-gray-800" x-text="material.material_name"></p>
                              <p class="text-xs text-gray-500 mt-1">
                                <span class="inline-flex items-center gap-1">
                                  <span class="material-symbols-outlined text-xs">category</span>
                                  <span x-text="getCategoryLabel(material.category)"></span>
                                </span>
                                <span class="mx-2">•</span>
                                <span class="inline-flex items-center gap-1">
                                  <span class="material-symbols-outlined text-xs">straighten</span>
                                  <span x-text="material.unit || '-'"></span>
                                </span>
                              </p>
                            </div>
                            <span class="material-symbols-outlined text-[var(--primary-color)] text-sm">arrow_forward</span>
                          </div>
                        </div>
                      </template>
                    </div>
                  </div>
                  
                  <!-- Hidden select for form submission -->
                  <select class="hidden" name="material_select" id="material_select" @change="onMaterialSelect()">
                    <option value="">{{ trans('messages.choose_material', [], session('locale')) }}</option>
                  </select>
                </label>

                <!-- Selected Material Info Card -->
                <div x-show="selectedMaterial" 
                     x-transition
                     class="bg-gradient-to-r from-indigo-50 via-pink-50 to-purple-50 rounded-xl p-5 border-2 border-indigo-200 shadow-md">
                  <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white rounded-full p-2 shadow-sm">
                      <span class="material-symbols-outlined text-[var(--primary-color)]">check_circle</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">{{ trans('messages.selected_material', [], session('locale')) ?: 'Selected Material' }}</h3>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 border border-indigo-100 shadow-sm">
                      <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">{{ trans('messages.material_name', [], session('locale')) }}</p>
                      <p class="text-base font-bold text-gray-900" x-text="selectedMaterialName || '-'"></p>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-indigo-100 shadow-sm">
                      <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">{{ trans('messages.category', [], session('locale')) }}</p>
                      <p class="text-base font-bold text-[var(--primary-color)]" x-text="getCategoryLabel(materialCategory)"></p>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-indigo-100 shadow-sm">
                      <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">{{ trans('messages.unit', [], session('locale')) }}</p>
                      <p class="text-base font-bold text-[var(--primary-color)]" x-text="materialUnit || '-'"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quantity and Abayas Expected -->
            <div class="bg-white rounded-xl p-6 border-2 border-gray-200 shadow-md" x-show="selectedMaterial" x-transition>
              <div class="flex items-center gap-2 mb-5">
                <span class="material-symbols-outlined text-[var(--primary-color)] text-xl">numbers</span>
                <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.quantity_details', [], session('locale')) ?: 'Quantity Details' }}</h2>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label class="flex flex-col">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-gray-500 text-sm">scale</span>
                    <p class="text-sm font-semibold text-gray-700" x-text="quantityLabel"></p>
                  </div>
                  <input type="number" 
                         placeholder="0" 
                         min="0.01" 
                         step="0.01"
                         class="form-input h-14 rounded-xl px-4 border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)] text-lg font-semibold" 
                         name="quantity" 
                         id="quantity" />
                  <p class="text-xs text-gray-500 mt-1" x-show="materialUnit">
                    {{ trans('messages.unit', [], session('locale')) }}: <span x-text="materialUnit" class="font-semibold"></span>
                  </p>
                </label>

                <label class="flex flex-col">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-gray-500 text-sm">checklist</span>
                    <p class="text-sm font-semibold text-gray-700">{{ trans('messages.abayas_expected', [], session('locale')) }}</p>
                  </div>
                  <input type="number" 
                         placeholder="{{ trans('messages.abayas_expected_placeholder', [], session('locale')) }}" 
                         min="1"
                         class="form-input h-14 rounded-xl px-4 border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)] text-lg font-semibold" 
                         name="abayas_expected" 
                         id="abayas_expected" />
                  <p class="text-xs text-gray-500 mt-1">{{ trans('messages.expected_abayas_hint', [], session('locale')) ?: 'Number of abayas expected from this material' }}</p>
                </label>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center" x-show="selectedMaterial" x-transition>
              <button type="submit" 
                      class="px-10 py-4 bg-[var(--primary-color)] text-white text-lg font-bold rounded-2xl shadow-lg hover:bg-[var(--primary-darker)] hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-3 mx-auto">
                <span class="material-symbols-outlined text-2xl">save</span>
                <span>{{ trans('messages.send_material', [], session('locale')) }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Materials Sent History -->
        <div class="overflow-x-auto">
          <h4 class="text-lg font-bold text-gray-800 mb-4">{{ trans('messages.materials_sent_history', [], session('locale')) }}</h4>
          <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.material_code', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.abayas_expected', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.sent_date', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.status', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($materialsSentData['items'] as $item)
              <tr class="border-b border-gray-100 hover:bg-pink-50/50">
                <td class="px-4 py-3">{{ $item['material_code'] }}</td>
                <td class="px-4 py-3">{{ $item['material_name'] }}</td>
                <td class="px-4 py-3">{{ $item['quantity'] }}</td>
                <td class="px-4 py-3">{{ $item['abayas_expected'] }}</td>
                <td class="px-4 py-3">{{ $item['sent_date'] }}</td>
                <td class="px-4 py-3">
                  @if($item['status'] === 'completed')
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ trans('messages.completed', [], session('locale')) }}</span>
                  @elseif($item['status'] === 'in_progress')
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">{{ trans('messages.in_progress', [], session('locale')) }}</span>
                  @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">{{ trans('messages.pending', [], session('locale')) }}</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- REPAIR HISTORY TAB -->
    <section x-show="tab==='repair_history'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">Repair History</h3>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 shadow-md border border-orange-200">
            <p class="text-sm text-gray-600 mb-1">Total Repairs</p>
            <h3 class="text-3xl font-extrabold text-orange-600">{{ $repairHistoryData['total_repairs'] ?? 0 }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
            <p class="text-sm text-gray-600 mb-1">Total Delivery Charges</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ number_format($repairHistoryData['total_delivery_charges'] ?? 0, 3) }} ر.ع</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
            <p class="text-sm text-gray-600 mb-1">Total Repair Cost</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ number_format($repairHistoryData['total_repair_cost'] ?? 0, 3) }} ر.ع</h3>
          </div>
        </div>

        <!-- Repair History Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-700">Transfer Number</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Design Name</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Code</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Customer</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Sent Date</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Received Date</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Delivery Charges</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Repair Cost</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Cost Bearer</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Notes</th>
                <th class="px-4 py-3 font-semibold text-gray-700">Status</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($repairHistoryData['items']) && count($repairHistoryData['items']) > 0)
                @foreach($repairHistoryData['items'] as $item)
                <tr class="border-b border-gray-100 hover:bg-pink-50/50">
                  <td class="px-4 py-3 font-semibold text-indigo-600">{{ $item['transfer_number'] ?? '—' }}</td>
                  <td class="px-4 py-3">{{ $item['design_name'] ?? 'N/A' }}</td>
                  <td class="px-4 py-3">{{ $item['abaya_code'] ?? 'N/A' }}</td>
                  <td class="px-4 py-3">
                    <div>
                      <p class="font-medium">{{ $item['customer_name'] ?? 'N/A' }}</p>
                      <p class="text-xs text-gray-500">{{ $item['customer_phone'] ?? 'N/A' }}</p>
                    </div>
                  </td>
                  <td class="px-4 py-3">{{ $item['sent_date'] ?? '—' }}</td>
                  <td class="px-4 py-3">{{ $item['received_date'] ?? '—' }}</td>
                  <td class="px-4 py-3 font-semibold">{{ $item['delivery_charges'] ? number_format($item['delivery_charges'], 3) . ' ر.ع' : '—' }}</td>
                  <td class="px-4 py-3 font-semibold">{{ $item['repair_cost'] ? number_format($item['repair_cost'], 3) . ' ر.ع' : '—' }}</td>
                  <td class="px-4 py-3">
                    @if($item['cost_bearer'] === 'customer')
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Customer</span>
                    @elseif($item['cost_bearer'] === 'company')
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Company</span>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700 max-w-xs">
                    <p class="truncate" title="{{ $item['maintenance_notes'] ?? '—' }}">{{ $item['maintenance_notes'] ?? '—' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    @if($item['status'] === 'received_from_tailor')
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Received</span>
                    @elseif($item['status'] === 'delivered_to_tailor')
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">Delivered</span>
                    @else
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $item['status'] ?? '—' }}</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="10" class="px-4 py-8 text-center text-gray-500">No repair history found</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- LATE DELIVERY HISTORY TAB -->
    <section x-show="tab==='late_delivery'" x-transition x-cloak>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.late_delivery_history', [], session('locale')) ?: 'Late Delivery History' }}</h3>
        
        <!-- Summary Card -->
        <div class="mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-red-50 to-red-100 shadow-md border border-red-200 inline-block">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_late_deliveries', [], session('locale')) ?: 'Total Late Deliveries' }}</p>
            <h3 class="text-3xl font-extrabold text-red-600">{{ $lateDeliveryHistory['total_late'] ?? 0 }}</h3>
          </div>
        </div>

        <!-- Late Delivery Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.order_number', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.customer_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.abaya_code', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.design_name', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.sent_date', [], session('locale')) }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.days_late', [], session('locale')) ?: 'Days Late' }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.marked_late_at', [], session('locale')) ?: 'Marked Late At' }}</th>
                <th class="px-4 py-3 font-semibold text-gray-700">{{ trans('messages.status', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($lateDeliveryHistory['items']) && count($lateDeliveryHistory['items']) > 0)
                @foreach($lateDeliveryHistory['items'] as $item)
                <tr class="border-b hover:bg-red-50 transition">
                  <td class="px-4 py-3 font-semibold text-red-600">{{ $item['order_no'] }}</td>
                  <td class="px-4 py-3">{{ $item['customer_name'] }}</td>
                  <td class="px-4 py-3">{{ $item['abaya_code'] }}</td>
                  <td class="px-4 py-3">{{ $item['design_name'] }}</td>
                  <td class="px-4 py-3 text-center">{{ $item['quantity'] }}</td>
                  <td class="px-4 py-3">{{ $item['sent_date'] ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                      {{ $item['days_late'] }} {{ trans('messages.days', [], session('locale')) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600">{{ $item['marked_late_at'] ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold 
                      {{ $item['current_status'] === 'received' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                      {{ $item['current_status'] === 'received' ? trans('messages.received', [], session('locale')) : trans('messages.processing', [], session('locale')) }}
                    </span>
                  </td>
                </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="9" class="px-4 py-8 text-center text-gray-500">{{ trans('messages.no_late_deliveries', [], session('locale')) ?: 'No late deliveries found' }}</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div>
</main>



@include('layouts.footer')
@endsection

