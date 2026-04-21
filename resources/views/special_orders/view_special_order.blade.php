@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_orders_lang', [], session('locale')) }}</title>
@endpush


<main class="flex-1 p-4 md:p-6" x-data="ordersDashboard" x-init="init()">

  <!-- ======================= MODAL: VIEW DETAILS ======================= -->
  <div x-show="showViewModal" x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div @click.away="showViewModal=false"
         x-transition.scale
         class="bg-white w-full max-w-5xl mx-4 rounded-2xl shadow-2xl p-6 overflow-y-auto max-h-[90vh]">

      <h2 class="text-2xl font-bold mb-6">{{ trans('messages.order_details', [], session('locale')) }}</h2>

      <template x-if="viewOrder">
        <div class="space-y-6 text-sm">

          <!-- بيانات أساسية للطلب -->
          <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
              <div>
                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.order_number', [], session('locale')) }}</p>
                <p class="font-bold text-lg" x-text="viewOrder.special_order_no || viewOrder.order_no || viewOrder.id"></p>
              </div>
              <div>
                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.customer_name', [], session('locale')) }}</p>
                <p class="font-semibold" x-text="viewOrder.customer"></p>
              </div>
              <div>
                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.phone_number', [], session('locale')) }}</p>
                <template x-if="viewOrder.customer_phone">
                  <a :href="'tel:' + viewOrder.customer_phone"
                     class="font-semibold text-indigo-600 hover:underline"
                     x-text="viewOrder.customer_phone"></a>
                </template>
                <template x-if="!viewOrder.customer_phone">
                  <p class="font-semibold text-gray-400">—</p>
                </template>
              </div>
              <div>
                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.source', [], session('locale')) }}</p>
                <p class="font-semibold" x-text="sourceLabel(viewOrder.source)"></p>
              </div>
              <div>
                <p class="text-gray-500 text-xs mb-1">{{ trans('messages.date', [], session('locale')) }}</p>
                <p class="font-semibold" x-text="formatDate(viewOrder.date)"></p>
              </div>
              <!-- City, area, address: customer module for all linked customers; website order fields override in API when set -->
              <template x-if="!viewOrder.is_stock_order">
                <div class="lg:col-span-3 space-y-2 md:col-span-2">
                  <div>
                    <p class="text-gray-500 text-xs mb-1">{{ trans('messages.city', [], session('locale')) ?: 'City' }}</p>
                    <p class="font-semibold" x-text="viewOrder.customer_city_name || '—'"></p>
                  </div>
                  <div>
                    <p class="text-gray-500 text-xs mb-1">{{ trans('messages.state_area', [], session('locale')) }}</p>
                    <p class="font-semibold" x-text="viewOrder.customer_area_name || '—'"></p>
                  </div>
                  <div>
                    <p class="text-gray-500 text-xs mb-1">{{ trans('messages.address', [], session('locale')) ?: 'Address' }}</p>
                    <p class="font-semibold whitespace-pre-line" x-text="viewOrder.customer_address || '—'"></p>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- جميع الأصناف -->
          <div class="space-y-4">
            <h3 class="text-lg font-bold text-gray-800 border-b-2 border-indigo-200 pb-2">
              {{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }} 
              <span class="text-indigo-600" x-text="'( ' + (viewOrder.items?.length || 0) + ' )'"></span>
            </h3>

            <template x-if="viewOrder.items && viewOrder.items.length > 0">
              <div class="space-y-4">
                <template x-for="(item, index) in viewOrder.items" :key="item.id">
                  <div class="border border-gray-200 rounded-xl p-4 hover:shadow-lg transition-shadow bg-white">
                    <div class="flex flex-col md:flex-row gap-4">
                      <!-- صورة الصنف -->
                      <div class="w-full md:w-48 flex-shrink-0">
                        <img :src="item.image" 
                             :alt="item.design_name"
                             class="w-full h-48 md:h-full object-cover rounded-xl shadow-md">
                      </div>

                      <!-- تفاصيل الصنف -->
                      <div class="flex-1 space-y-3">
                        <div class="flex justify-between items-start">
                          <div>
                            <h4 class="font-bold text-lg text-gray-800" x-text="item.design_name"></h4>
                            <p class="text-gray-500 text-xs mt-1">
                              {{ trans('messages.code', [], session('locale')) }}: 
                              <span class="font-semibold" x-text="item.abaya_code"></span>
                            </p>
                            <!-- Show color and size for stock orders -->
                            <template x-if="viewOrder.is_stock_order && (item.color_name || item.size_name)">
                              <div class="flex flex-wrap gap-3 mt-2">
                                <template x-if="item.color_name">
                                  <p class="text-gray-600 text-xs">
                                    <span class="text-gray-500">{{ trans('messages.color', [], session('locale')) }}: </span>
                                    <span class="font-semibold" x-text="item.color_name"></span>
                                  </p>
                                </template>
                                <template x-if="item.size_name">
                                  <p class="text-gray-600 text-xs">
                                    <span class="text-gray-500">{{ trans('messages.size', [], session('locale')) }}: </span>
                                    <span class="font-semibold" x-text="item.size_name"></span>
                                  </p>
                                </template>
                              </div>
                            </template>
                          </div>
                          <!-- حالة الصنف -->
                          <div class="flex flex-col items-end gap-2">
                            <span :class="itemStatusBadge(item.tailor_status)" 
                                  class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                  x-text="itemStatusLabel(item.tailor_status)"></span>
                            <template x-if="item.tailor_name">
                              <p class="text-xs text-gray-500" x-text="item.tailor_name"></p>
                            </template>
                            <!-- Take from Stock button: visible for customer orders with a linked stock item -->
                            <template x-if="!viewOrder.is_stock_order && item.stock_id">
                              <div>
                                <!-- Active: status is 'new' -->
                                <template x-if="item.tailor_status === 'new'">
                                  <button @click="takeItemFromStock(item, viewOrder)"
                                          class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow transition">
                                    <span class="material-symbols-outlined text-sm">inventory</span>
                                    {{ trans('messages.take_from_stock', [], session('locale')) }}
                                  </button>
                                </template>
                                <!-- Disabled: item is with tailor (processing) -->
                                <template x-if="item.tailor_status === 'processing'">
                                  <button disabled
                                          :title="'{{ trans('messages.cannot_take_with_tailor', [], session('locale')) }}'"
                                          class="flex items-center gap-1 px-3 py-1.5 bg-gray-200 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed opacity-60">
                                    <span class="material-symbols-outlined text-sm">inventory</span>
                                    {{ trans('messages.take_from_stock', [], session('locale')) }}
                                  </button>
                                </template>
                                <!-- Already done: received or stock-received -->
                                <template x-if="['received','stock-received'].includes(item.tailor_status)">
                                  <span class="flex items-center gap-1 px-3 py-1.5 bg-teal-50 text-teal-600 text-xs font-semibold rounded-lg border border-teal-200">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    {{ trans('messages.taken_from_stock', [], session('locale')) }}
                                  </span>
                                </template>
                              </div>
                            </template>
                          </div>
                        </div>

                        <!-- الكمية والسعر -->
                        <div class="flex flex-wrap gap-4 text-sm">
                          <div>
                            <span class="text-gray-500">{{ trans('messages.quantity', [], session('locale')) }}: </span>
                            <span class="font-semibold text-indigo-600" x-text="item.quantity"></span>
                          </div>
                          <div>
                            <span class="text-gray-500">{{ trans('messages.price', [], session('locale')) }}: </span>
                            <span class="font-semibold text-green-600" x-text="item.price.toFixed(3) + ' ر.ع'"></span>
                          </div>
                          <div>
                            <span class="text-gray-500">{{ trans('messages.subtotal', [], session('locale')) ?: 'Subtotal' }}: </span>
                            <span class="font-semibold text-blue-600" x-text="(item.price * item.quantity).toFixed(3) + ' ر.ع'"></span>
                          </div>
                        </div>

                        <!-- Tailor Information -->
                        <template x-if="item.original_tailor_name || item.tailor_name">
                          <div class="pt-2 border-t border-gray-100">
                            <p class="text-gray-500 text-xs mb-2 font-semibold">{{ trans('messages.tailor', [], session('locale')) }}:</p>
                            <div class="space-y-1">
                              <!-- Show original tailor from stock -->
                              <template x-if="item.original_tailor_name">
                                <div class="flex items-center gap-2">
                                  <span class="text-gray-500 text-xs">{{ trans('messages.original_tailor', [], session('locale')) }}:</span>
                                  <span class="font-semibold text-sm" x-text="item.original_tailor_name"></span>
                                </div>
                              </template>
                              <!-- Show current tailor if different from original (changed when sending to tailor) -->
                              <template x-if="item.tailor_name && item.original_tailor_name && item.tailor_name !== item.original_tailor_name">
                                <div class="flex items-center gap-2">
                                  <span class="text-gray-500 text-xs">{{ trans('messages.current_tailor', [], session('locale')) }}:</span>
                                  <span class="font-semibold text-sm text-indigo-600" x-text="item.tailor_name"></span>
                                </div>
                              </template>
                              <!-- Show only current tailor if no original tailor exists -->
                              <template x-if="item.tailor_name && !item.original_tailor_name">
                                <div class="flex items-center gap-2">
                                  <span class="font-semibold text-sm" x-text="item.tailor_name"></span>
                                </div>
                              </template>
                            </div>
                          </div>
                        </template>

                        <!-- Measurements (for customer orders) or Color/Size (for stock orders) -->
                        <template x-if="!viewOrder.is_stock_order">
                          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-gray-100">
                            <div>
                              <p class="text-gray-500 text-xs">{{ trans('messages.abaya_length', [], session('locale')) }}</p>
                              <p class="font-semibold" x-text="item.length ? item.length + ' {{ trans('messages.inches', [], session('locale')) }}' : '{{ trans('messages.not_available', [], session('locale')) }}'"></p>
                            </div>
                            <div>
                              <p class="text-gray-500 text-xs">{{ trans('messages.bust_one_side', [], session('locale')) }}</p>
                              <p class="font-semibold" x-text="item.bust ? item.bust + ' {{ trans('messages.inches', [], session('locale')) }}' : '{{ trans('messages.not_available', [], session('locale')) }}'"></p>
                            </div>
                            <div>
                              <p class="text-gray-500 text-xs">{{ trans('messages.sleeves_length', [], session('locale')) }}</p>
                              <p class="font-semibold" x-text="item.sleeves ? item.sleeves + ' {{ trans('messages.inches', [], session('locale')) }}' : '{{ trans('messages.not_available', [], session('locale')) }}'"></p>
                            </div>
                            <div>
                              <p class="text-gray-500 text-xs">{{ trans('messages.buttons', [], session('locale')) }}</p>
                              <p class="font-semibold" x-text="item.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></p>
                            </div>
                          </div>
                        </template>
                        
                        <!-- Color and Size for Stock Orders -->
                        <template x-if="viewOrder.is_stock_order">
                          <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                            <template x-if="item.color_name">
                              <div>
                                <p class="text-gray-500 text-xs">{{ trans('messages.color', [], session('locale')) }}</p>
                                <p class="font-semibold text-indigo-600" x-text="item.color_name"></p>
                              </div>
                            </template>
                            <template x-if="item.size_name">
                              <div>
                                <p class="text-gray-500 text-xs">{{ trans('messages.size', [], session('locale')) }}</p>
                                <p class="font-semibold text-indigo-600" x-text="item.size_name"></p>
                              </div>
                            </template>
                          </div>
                        </template>

                        <!-- ملاحظات الصنف -->
                        <template x-if="item.notes">
                          <div class="pt-2 border-t border-gray-100">
                            <p class="text-gray-500 text-xs mb-1">{{ trans('messages.notes', [], session('locale')) }}:</p>
                            <p class="text-gray-700 text-sm" x-text="item.notes"></p>
                          </div>
                        </template>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </template>
          </div>

          <hr class="border-gray-200">

          <!-- المالية -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-2">
            <h3 class="font-semibold text-gray-700 mb-3">{{ trans('messages.financial_details', [], session('locale')) }}</h3>

            <div class="flex justify-between items-center">
              <span class="text-gray-600">{{ trans('messages.total', [], session('locale')) }}:</span>
              <span class="font-bold text-lg text-blue-700" 
                    x-text="viewOrder.total.toFixed(3) + ' ر.ع'"></span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-gray-600">{{ trans('messages.paid', [], session('locale')) }}:</span>
              <span class="font-bold text-lg text-emerald-700" 
                    x-text="viewOrder.paid.toFixed(3) + ' ر.ع'"></span>
            </div>

            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
              <span class="text-gray-700 font-semibold">{{ trans('messages.remaining', [], session('locale')) }}:</span>
              <span class="font-bold text-xl text-red-600"
                    x-text="(viewOrder.total - viewOrder.paid).toFixed(3) + ' ر.ع'"></span>
            </div>
          </div>

          <!-- الخياط والحالة -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-xl p-4">
              <h3 class="font-semibold text-gray-700 mb-2">{{ trans('messages.notes', [], session('locale')) }}</h3>
              <p class="font-semibold" x-text="viewOrder.tailor || '{{ trans('messages.not_available', [], session('locale')) }}'"></p>
            </div>

            <div class="bg-gray-50 rounded-xl p-4">
              <h3 class="font-semibold text-gray-700 mb-2">{{ trans('messages.order_status', [], session('locale')) }}</h3>
              <span :class="statusBadge(viewOrder.status)"
                    x-text="statusLabel(viewOrder.status)"></span>
            </div>
          </div>

          <!-- ملاحظات الطلب -->
          <template x-if="viewOrder.notes">
            <div class="bg-gray-50 rounded-xl p-4">
              <h3 class="font-semibold text-gray-700 mb-2">{{ trans('messages.notes', [], session('locale')) }}</h3>
              <p x-text="viewOrder.notes" 
                 class="text-gray-700 whitespace-pre-line"></p>
            </div>
          </template>

        </div>
      </template>

      <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
        <button @click="showViewModal=false"
                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl font-semibold transition">
          {{ trans('messages.close', [], session('locale')) }}
        </button>
      </div>

    </div>
  </div>

  <!-- ======================= MODAL: دفع ======================= -->
  <div x-show="showPaymentModal" x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div @click.away="showPaymentModal=false"
         x-transition.scale
         class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl p-6">

      <h2 class="text-xl font-bold mb-4">{{ trans('messages.record_payment', [], session('locale')) }}</h2>

      <template x-if="paymentOrder">
        <div class="space-y-3 text-sm">

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.order_number', [], session('locale')) }}:</span>
            <span class="font-semibold" x-text="paymentOrder.id"></span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.total_amount', [], session('locale')) }}:</span>
            <span class="font-semibold text-blue-700"
                  x-text="paymentOrder.total.toFixed(3) + ' ر.ع'"></span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.previously_paid', [], session('locale')) }}:</span>
            <span class="font-semibold text-emerald-700"
                  x-text="paymentOrder.paid.toFixed(3) + ' ر.ع'"></span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.remaining', [], session('locale')) }}:</span>
            <span class="font-semibold text-red-600"
                  x-text="remainingAmount().toFixed(3) + ' ر.ع'"></span>
          </div>

          <template x-if="remainingAmount() > 0.001">
            <div>
              <div class="mt-4">
                <label class="block text-sm mb-1">{{ trans('messages.current_payment_amount', [], session('locale')) }}</label>
                <input type="number" step="0.001" min="0" :max="remainingAmount()"
                       x-model="paymentAmount"
                       class="form-input w-full rounded-xl border-gray-300" />
              </div>

              <div class="mt-3">
                <label class="block text-sm mb-1">{{ trans('messages.payment_method', [], session('locale')) }}</label>
                <select x-model="selectedAccountId"
                        class="form-select w-full rounded-xl border-gray-300">
                  <option value="">{{ trans('messages.select_account', [], session('locale')) ?: 'Select Account' }}</option>
                  <template x-for="account in accounts" :key="account.id">
                    <option :value="account.id" x-text="account.account_name + (account.account_no ? ' (' + account.account_no + ')' : '')"></option>
                  </template>
                </select>
              </div>
            </div>
          </template>

          <template x-if="remainingAmount() <= 0.001">
            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl text-center">
              <span class="text-green-700 font-semibold">{{ trans('messages.fully_paid', [], session('locale')) }}</span>
            </div>
          </template>

        </div>
      </template>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="showPaymentModal=false"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.close', [], session('locale')) }}
        </button>
        <button @click="confirmPayment()"
                :disabled="remainingAmount() <= 0.001"
                :class="remainingAmount() <= 0.001 
                  ? 'px-4 py-2 bg-gray-400 text-white rounded-xl cursor-not-allowed opacity-60' 
                  : 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl'">
          {{ trans('messages.confirm_payment', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>

  <!-- ======================= MODAL: تسليم فردي ======================= -->
  <div x-show="showDeliverModal" x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div @click.away="showDeliverModal=false"
         x-transition.scale
         class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl p-6">

      <h2 class="text-xl font-bold mb-4">{{ trans('messages.confirm_delivery', [], session('locale')) }}</h2>

      <!-- Stock Order Confirmation -->
      <template x-if="deliverOrder && deliverOrder.is_stock_order">
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
          <p class="text-amber-800 font-semibold mb-2">
            {{ trans('messages.stock_order_warning', [], session('locale')) ?: '⚠️ Stock Special Order' }}
          </p>
          <p class="text-gray-700 leading-6 mb-4">
            {{ trans('messages.confirm_save_to_stock', [], session('locale')) ?: 'Are you sure you want to save this order as stock? Items will be added to inventory with their color and size.' }}
          </p>
        </div>
      </template>

      <!-- Regular Delivery Message -->
      <template x-if="!deliverOrder || !deliverOrder.is_stock_order">
        <p class="text-gray-700 leading-6 mb-6">
          {{ trans('messages.confirm_delivery_message', [], session('locale')) }}
          <br>
          <span class="text-xs text-gray-500">{{ trans('messages.delivery_status_change', [], session('locale')) }}</span>
        </p>
      </template>

      <div class="flex justify-end gap-3">
        <button @click="showDeliverModal=false"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>

        <button @click="confirmDeliverSingle()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl">
          {{ trans('messages.confirm', [], session('locale')) }}
        </button>
      </div>

    </div>
  </div>

  <!-- ======================= MODAL: تسليم جماعي ======================= -->
  <div x-show="showBulkDeliverModal" x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div @click.away="showBulkDeliverModal=false"
         x-transition.scale
         class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl p-6">

      <h2 class="text-xl font-bold mb-4">{{ trans('messages.bulk_delivery', [], session('locale')) }}</h2>

      <!-- Stock Orders Warning -->
      <template x-if="selectedReadyIds.length > 0">
        <div x-data="{
          getStockOrdersCount() {
            return orders.filter(o => selectedReadyIds.includes(o.id) && o.is_stock_order).length;
          }
        }">
          <template x-if="getStockOrdersCount() > 0">
            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
              <p class="text-amber-800 font-semibold mb-2">
                {{ trans('messages.stock_orders_in_selection', [], session('locale')) ?: '⚠️ Stock Orders Selected' }}
              </p>
              <p class="text-gray-700 text-sm">
                <span x-text="getStockOrdersCount()"></span> {{ trans('messages.stock_orders_will_be_saved', [], session('locale')) ?: 'stock order(s) will be saved to inventory with color and size.' }}
              </p>
            </div>
          </template>
        </div>
      </template>

      <p class="text-gray-700 leading-6 mb-6">
        {{ trans('messages.bulk_delivery_message', [], session('locale')) }}
        <span class="font-bold text-emerald-700" x-text="selectedReadyIds.length"></span>
        {{ trans('messages.orders_at_once', [], session('locale')) }}
        <br>
        <span class="text-xs text-gray-500">{{ trans('messages.bulk_delivery_status_change', [], session('locale')) }}</span>
      </p>

      <div class="flex justify-end gap-3">
        <button @click="showBulkDeliverModal=false"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>

        <button @click="confirmBulkDeliver()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl">
          {{ trans('messages.confirm', [], session('locale')) }}
        </button>
      </div>

    </div>
  </div>

  <!-- Loading Indicator -->
  <div x-show="loading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 text-center">
      <div class="loader border-4 border-indigo-200 border-t-indigo-600 rounded-full w-12 h-12 animate-spin mx-auto mb-4"></div>
      <p class="text-gray-700 font-semibold">{{ trans('messages.loading_details', [], session('locale')) }}</p>
    </div>
  </div>

  <!-- Pagination Loading Indicator -->
  <div x-show="pageLoading" 
       x-transition.opacity
       class="fixed inset-0 bg-black/30 flex items-center justify-center z-40">
    <div class="bg-white rounded-2xl p-6 text-center shadow-2xl">
      <div class="loader border-4 border-indigo-200 border-t-indigo-600 rounded-full w-12 h-12 animate-spin mx-auto mb-4"></div>
      <p class="text-gray-700 font-semibold text-sm">{{ trans('messages.loading', [], session('locale')) ?: 'Loading...' }}</p>
    </div>
  </div>

  <!-- ======================= MAIN CONTAINER ======================= -->
  <div class="w-full mx-auto bg-white shadow-xl rounded-3xl p-4 md:p-6" x-show="!loading">

    <!-- 📊 البوكسات العلوية (Orders + Abayas per status + Total abayas) -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">

      <!-- New -->
      <div class="group bg-gradient-to-br from-amber-50 to-white border border-amber-200 rounded-2xl p-5 
                  flex items-center gap-4 hover:shadow-lg transition cursor-pointer">
        <div class="bg-amber-500 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-md flex-shrink-0">
          <span class="material-symbols-outlined text-3xl">fiber_new</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-amber-600">{{ trans('messages.new_orders', [], session('locale')) }}</p>
          <h3 class="text-3xl font-extrabold text-amber-800" x-text="countStatus('new')"></h3>
          <p class="text-xs text-amber-600/80 mt-1 font-medium" x-text="countAbayasByStatus('new') + ' {{ trans('messages.abayas', [], session('locale')) ?: 'abayas' }}'"></p>
        </div>
      </div>

      <!-- In progress -->
      <div class="group bg-gradient-to-br from-blue-50 to-white border border-blue-200 rounded-2xl p-5 
                  flex items-center gap-4 hover:shadow-lg transition cursor-pointer">
        <div class="bg-blue-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-md flex-shrink-0">
          <span class="material-symbols-outlined text-3xl">content_cut</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-blue-600">{{ trans('messages.in_progress', [], session('locale')) }}</p>
          <h3 class="text-3xl font-extrabold text-blue-800" x-text="countStatus('processing')"></h3>
          <p class="text-xs text-blue-600/80 mt-1 font-medium" x-text="countAbayasByStatus('processing') + ' {{ trans('messages.abayas', [], session('locale')) ?: 'abayas' }}'"></p>
        </div>
      </div>

      <!-- Delivered -->
      <div class="group bg-gradient-to-br from-emerald-50 to-white border border-emerald-200 rounded-2xl p-5 
                  flex items-center gap-4 hover:shadow-lg transition cursor-pointer">
        <div class="bg-emerald-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-md flex-shrink-0">
          <span class="material-symbols-outlined text-3xl">check_circle</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-emerald-600">{{ trans('messages.delivered', [], session('locale')) }}</p>
          <h3 class="text-3xl font-extrabold text-emerald-800" x-text="countStatus('delivered')"></h3>
          <p class="text-xs text-emerald-600/80 mt-1 font-medium" x-text="countAbayasByStatus('delivered') + ' {{ trans('messages.abayas', [], session('locale')) ?: 'abayas' }}'"></p>
        </div>
      </div>

    </div>

    <!-- Total abayas (all statuses) -->
    <div class="flex justify-center mb-6">
      <p class="text-sm text-gray-500 font-medium">
        {{ trans('messages.total_abayas', [], session('locale')) ?: 'Total abayas' }}:
        <span class="font-bold text-gray-700" x-text="totalAbayas()"></span>
      </p>
    </div>

    <!-- 🔎 البحث + زر طلب جديد + تسليم المحدد -->
    <div class="flex flex-col md:flex-row gap-3 md:gap-0 md:justify-between md:items-center mb-6">
      <input type="text" placeholder="{{ trans('messages.search_order', [], session('locale')) }}"
             x-model="search"
             class="form-input w-full md:w-72 border-gray-300 rounded-xl px-4 py-2 shadow-sm focus:ring-primary">

      <div class="flex items-center gap-3 mt-2 md:mt-0">
        <!-- زر تسليم المحدد -->
        <button x-show="selectedReadyIds.length > 0"
                @click="openBulkDeliverModal()"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">done_all</span>
          {{ trans('messages.deliver_selected', [], session('locale')) }}
        </button>

        <a href="{{ route('spcialorder') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">add</span> {{ trans('messages.new_order', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- 🎛 الفلاتر -->
    <div class="flex flex-wrap gap-3 mb-6">

      <!-- فلاتر الحالة -->
      <button @click="filter='all'" :class="tabClass('all')">{{ trans('messages.all', [], session('locale')) }}</button>
      <button @click="filter='new'" :class="tabClass('new')">{{ trans('messages.new', [], session('locale')) }}</button>
      <button @click="filter='processing'" :class="tabClass('processing')">{{ trans('messages.in_progress', [], session('locale')) }}</button>
      <button @click="filter='ready'" :class="tabClass('ready')">{{ trans('messages.ready_for_delivery', [], session('locale')) }}</button>
      <button @click="filter='saved_in_stock'" :class="tabClass('saved_in_stock')">{{ trans('messages.saved_in_stock', [], session('locale')) ?: 'Saved in Stock' }}</button>
      <button @click="filter='delivered'" :class="tabClass('delivered')">{{ trans('messages.delivered', [], session('locale')) }}</button>

      <span class="mx-2 border-r-2 border-gray-300 hidden md:inline-block"></span>

      <!-- فلاتر المصدر -->
      <div class="flex flex-wrap gap-2">
        <button @click="sourceFilter='all'" :class="tabClass2('all')">{{ trans('messages.all_sources', [], session('locale')) }}</button>
        <button @click="sourceFilter='whatsapp'" :class="tabClass2('whatsapp')">{{ trans('messages.whatsapp', [], session('locale')) }}</button>
        <button @click="sourceFilter='walkin'" :class="tabClass2('walkin')">{{ trans('messages.walk_in', [], session('locale')) }}</button>
        <button @click="sourceFilter='website'" :class="tabClass2('website')">{{ trans('messages.website', [], session('locale')) }}</button>
      </div>

    </div>

    <!-- 📋 الجدول / الكروت -->
    <div class="overflow-x-auto bg-white rounded-2xl border border-gray-200 shadow-md">

      <!-- جدول ديسكتوب -->
      <table class="w-full min-w-full text-xs md:text-sm hidden md:table">
        <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
          <tr>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-center whitespace-nowrap">{{ trans('messages.select', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[120px]">{{ trans('messages.order_no', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[150px]">{{ trans('messages.customer', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[120px]">{{ trans('messages.source', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[110px]">{{ trans('messages.date', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[100px]">{{ trans('messages.ago', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[140px]">{{ trans('messages.list_number', [], session('locale')) ?: 'List / Summary' }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[100px]">{{ trans('messages.total', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[100px]">{{ trans('messages.paid', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[110px]">{{ trans('messages.remaining', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-right whitespace-nowrap min-w-[120px]">{{ trans('messages.status', [], session('locale')) }}</th>
            <th class="py-3 px-3 sm:px-4 md:px-6 text-center whitespace-nowrap min-w-[180px]">{{ trans('messages.actions', [], session('locale')) }}</th>
          </tr>
        </thead>

        <tbody>
          <template x-for="order in paginatedOrders()" :key="order.id">
            <tr class="border-t hover:bg-indigo-50 transition">

              <!-- checkbox -->
              <td class="py-3 px-3 sm:px-4 md:px-6 text-center whitespace-nowrap">
                <input type="checkbox"
                       :disabled="order.status !== 'ready' || order.is_stock_order || (order.total - order.paid > 0.001)"
                       :checked="isReadySelected(order.id)"
                       @change="toggleReadySelection(order)"
                       :title="(order.total - order.paid > 0.001) ? '{{ trans('messages.order_not_fully_paid', [], session('locale')) ?: 'Order not fully paid' }}' : ''"
                       class="w-4 h-4 text-indigo-600">
              </td>

              <td class="py-3 px-3 sm:px-4 md:px-6 font-semibold text-indigo-600 whitespace-nowrap" x-text="order.order_no || '—'"></td>
              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap">
                <div class="font-medium" x-text="order.customer"></div>
                <template x-if="order.customer_phone">
                  <a :href="'tel:' + order.customer_phone"
                     class="text-xs text-indigo-500 hover:underline"
                     x-text="order.customer_phone"></a>
                </template>
              </td>

              <!-- مصدر الطلب -->
              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap">
                <span :class="sourceBadge(order.source)" class="inline-flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs" x-text="sourceIcon(order.source)"></span>
                  <span x-text="sourceLabel(order.source)"></span>
                </span>
              </td>

              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap" x-text="formatDate(order.date)"></td>
              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap" x-text="daysAgo(order.date)"></td>

              <!-- List numbers & sending summaries per special order -->
              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap align-top">
                <template x-if="order.tailor_batches && order.tailor_batches.length">
                  <div class="text-[11px] text-gray-700 leading-4">
                    <template x-for="batch in order.tailor_batches" :key="batch.list_number + '-' + (batch.sending_summary_number || '')">
                      <div class="mb-1">
                        <span class="font-semibold" x-text="batch.list_number"></span>
                        <span x-text="' (×' + (batch.quantity || 0) + ')'"></span>
                        <br>
                        <span x-text="batch.sending_summary_number || '—'"></span>
                        <template x-if="batch.tailor_name">
                          <span x-text="' - ' + batch.tailor_name"></span>
                        </template>
                      </div>
                    </template>
                  </div>
                </template>
              </td>

              <td class="py-3 px-3 sm:px-4 md:px-6 font-semibold text-blue-700 whitespace-nowrap" x-text="order.total.toFixed(3) + ' ر.ع'"></td>
              <td class="py-3 px-3 sm:px-4 md:px-6 font-semibold text-emerald-700 whitespace-nowrap" x-text="order.paid.toFixed(3) + ' ر.ع'"></td>
              <td class="py-3 px-3 sm:px-4 md:px-6 font-semibold text-red-600 whitespace-nowrap" x-text="(order.total - order.paid).toFixed(3) + ' ر.ع'"></td>

              <td class="py-3 px-3 sm:px-4 md:px-6 whitespace-nowrap">
                <span :class="statusBadge(order.status)" x-text="statusLabel(order.status)"></span>
              </td>

              <td class="py-3 px-3 sm:px-4 md:px-6 text-center whitespace-nowrap">
                <div class="flex justify-center gap-2">
                  <button @click="openViewModal(order)"
                          class="text-blue-600 hover:text-blue-800"
                          title="{{ trans('messages.view', [], session('locale')) ?: 'View' }}">
                    <span class="material-symbols-outlined text-base">visibility</span>
                  </button>

                  <a x-show="order.status === 'new'"
                     :href="'{{ url('special-order/edit') }}/' + order.id"
                     class="text-amber-600 hover:text-amber-800 inline-flex"
                     title="{{ trans('messages.edit', [], session('locale')) ?: 'Edit' }}">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>

                  <button @click="printBill(order.id)"
                          class="text-purple-600 hover:text-purple-800"
                          title="{{ trans('messages.print_bill', [], session('locale')) ?: 'Print Bill' }}">
                    <span class="material-symbols-outlined text-base">print</span>
                  </button>

                  <button x-show="!order.is_stock_order"
                          @click="openPaymentModal(order)"
                          class="text-emerald-600 hover:text-emerald-800">
                    <span class="material-symbols-outlined text-base">payments</span>
                  </button>

                  <!-- Hide deliver button for stock orders (they are automatically saved when received) -->
                  <button x-show="!order.is_stock_order"
                          @click="openDeliverModal(order)"
                          :disabled="order.status !== 'ready' || order.paid < order.total - 0.001"
                          :class="(order.status === 'ready' && order.paid >= order.total - 0.001)
                                  ? 'text-amber-600 hover:text-amber-800'
                                  : 'text-gray-300 cursor-not-allowed'">
                    <span class="material-symbols-outlined text-base">done</span>
                  </button>

                  <button @click="deleteOrder(order.id)"
                          x-show="order.status === 'new'"
                          class="text-red-600 hover:text-red-800">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </template>

          <tr x-show="paginatedOrders().length === 0">
            <td colspan="11" class="py-6 text-center text-gray-500">
              {{ trans('messages.no_results', [], session('locale')) }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- كروت الموبايل -->
      <div class="md:hidden divide-y">
        <template x-for="order in paginatedOrders()" :key="order.id">
          <div class="p-4 hover:bg-indigo-50 transition">

            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center gap-2">
                <input type="checkbox"
                       :disabled="order.status !== 'ready' || order.is_stock_order"
                       :checked="isReadySelected(order.id)"
                       @change="toggleReadySelection(order)"
                       class="w-4 h-4 text-indigo-600">
                <span class="text-xs text-gray-500">{{ trans('messages.order_no', [], session('locale')) }}:</span>
                <span class="font-semibold text-indigo-600" x-text="order.order_no || '—'"></span>
              </div>
              <span :class="statusBadge(order.status)" x-text="statusLabel(order.status)"></span>
            </div>

            <div class="text-sm space-y-1 mb-3">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.customer', [], session('locale')) }}:</span>
                <div class="text-right">
                  <div class="font-medium" x-text="order.customer"></div>
                  <template x-if="order.customer_phone">
                    <a :href="'tel:' + order.customer_phone"
                       class="text-xs text-indigo-500 hover:underline"
                       x-text="order.customer_phone"></a>
                  </template>
                </div>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.source', [], session('locale')) }}:</span>
                <span :class="sourceBadge(order.source)" class="inline-flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs" x-text="sourceIcon(order.source)"></span>
                  <span x-text="sourceLabel(order.source)"></span>
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.date', [], session('locale')) }}:</span>
                <span x-text="formatDate(order.date)"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.ago', [], session('locale')) }}:</span>
                <span x-text="daysAgo(order.date)"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.total', [], session('locale')) }}:</span>
                <span class="font-semibold text-blue-700" x-text="order.total.toFixed(3) + ' ر.ع'"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.paid', [], session('locale')) }}:</span>
                <span class="font-semibold text-emerald-700" x-text="order.paid.toFixed(3) + ' ر.ع'"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ trans('messages.remaining', [], session('locale')) }}:</span>
                <span class="font-semibold text-red-600" x-text="(order.total - order.paid).toFixed(3) + ' ر.ع'"></span>
              </div>
            </div>

            <div class="flex justify-end gap-2">
              <button @click="openViewModal(order)"
                      class="text-blue-600 hover:text-blue-800 text-xs">
                <span class="material-symbols-outlined text-base">visibility</span>
              </button>
              <a x-show="order.status === 'new'"
                 :href="'{{ url('special-order/edit') }}/' + order.id"
                 class="text-amber-600 hover:text-amber-800 text-xs inline-flex">
                <span class="material-symbols-outlined text-base">edit</span>
              </a>
              <button @click="printBill(order.id)"
                      class="text-purple-600 hover:text-purple-800 text-xs"
                      title="{{ trans('messages.print_bill', [], session('locale')) ?: 'Print Bill' }}">
                <span class="material-symbols-outlined text-base">print</span>
              </button>
              <button x-show="!order.is_stock_order"
                      @click="openPaymentModal(order)"
                      class="text-emerald-600 hover:text-emerald-800 text-xs">
                <span class="material-symbols-outlined text-base">payments</span>
              </button>
              <!-- Hide deliver button for stock orders (they are automatically saved when received) -->
              <button x-show="!order.is_stock_order"
                      @click="openDeliverModal(order)"
                      :disabled="order.status !== 'ready' || order.paid < order.total - 0.001"
                      :class="(order.status === 'ready' && order.paid >= order.total - 0.001)
                              ? 'text-amber-600 hover:text-amber-800 text-xs'
                              : 'text-gray-300 cursor-not-allowed text-xs'">
                <span class="material-symbols-outlined text-base">done</span>
              </button>
              <button @click="deleteOrder(order.id)"
                      x-show="order.status === 'new'"
                      class="text-red-600 hover:text-red-800 text-xs">
                <span class="material-symbols-outlined text-base">delete</span>
              </button>
            </div>

          </div>
        </template>

        <div x-show="paginatedOrders().length === 0" class="py-6 text-center text-gray-500">
          {{ trans('messages.no_results', [], session('locale')) }}
        </div>
      </div>

    </div>

    <!-- Pagination (same as view_stock: Prev, 1 … 3 … 5, Next — window of buttons, not all pages) -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-6">

      <p class="text-sm text-gray-500">
        {{ trans('messages.showing', [], session('locale')) }}
        <span x-text="startItem()"></span> -
        <span x-text="endItem()"></span>
        {{ trans('messages.of', [], session('locale')) }}
        <span x-text="filteredOrders().length"></span>
      </p>

      <ul class="flex flex-wrap justify-center items-center gap-1.5 mt-4 list-none pl-0 max-w-full">
        <li class="shrink-0">
          <button @click="prevPage()"
                  :disabled="page===1 || pageLoading"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 bg-white hover:bg-gray-100 border-gray-200 disabled:opacity-40 disabled:pointer-events-none disabled:bg-gray-200">
            &laquo; {{ trans('messages.previous', [], session('locale')) }}
          </button>
        </li>
        <template x-for="(p, idx) in pageNumbers()" :key="typeof p === 'number' ? p : 'dot-' + idx">
          <li class="shrink-0">
            <span x-show="p === '...'" class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</span>
            <button x-show="p !== '...'"
                    @click="goToPage(p)"
                    :disabled="pageLoading"
                    :class="page === p
                      ? 'inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md'
                      : 'inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg bg-white hover:bg-gray-100 border-gray-200'"
                    x-text="p">
            </button>
          </li>
        </template>
        <li class="shrink-0">
          <button @click="nextPage()"
                  :disabled="page >= totalPages() || pageLoading"
                  class="inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 bg-white hover:bg-gray-100 border-gray-200 disabled:opacity-40 disabled:pointer-events-none disabled:bg-gray-200">
            {{ trans('messages.next', [], session('locale')) }} &raquo;
          </button>
        </li>
      </ul>

    </div>

  </div> <!-- END container -->

</main>




@include('layouts.footer')
@endsection