@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.special_order', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6" x-data="tailorApp">

  <!-- 🧍 بيانات العميل -->
  <div class="bg-white shadow rounded-2xl p-5 mb-6 border border-gray-100">
    <h1 class="text-xl font-bold mb-4">{{ trans('messages.customer_data', [], session('locale')) }}</h1>

    <div class="grid md:grid-cols-2 gap-4">
      <!-- مصدر الطلب -->
      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.order_source', [], session('locale')) }}</label>
        <select x-model="customer.source" class="form-select w-full border-gray-300 rounded-lg">
          <option value="">{{ trans('messages.select_source', [], session('locale')) }}</option>
          <option value="whatsapp">{{ trans('messages.whatsapp', [], session('locale')) }}</option>
          <option value="walkin">{{ trans('messages.walk_in', [], session('locale')) }}</option>
        </select>
      </div>

      <div></div>

      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.customer_name', [], session('locale')) }}</label>
        <input type="text" x-model="customer.name" class="form-input w-full border-gray-300 rounded-lg">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.phone_number', [], session('locale')) }}</label>
        <div class="relative">
          <input type="text" 
                 x-model="customer.phone" 
                 @input.debounce.300ms="searchCustomers()"
                 class="form-input w-full border-gray-300 rounded-lg" 
                 placeholder="9xxxxxxxx">
          
          <!-- Customer Suggestions Dropdown -->
          <div x-show="customerSuggestions.length > 0 && customer.phone.length >= 2"
               @click.outside="customerSuggestions = []"
               class="absolute top-full mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto">
            <template x-for="customerItem in customerSuggestions" :key="customerItem.id">
              <div @click="selectCustomer(customerItem)"
                   class="px-4 py-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                <div class="font-medium text-sm" x-text="customerItem.name || '{{ trans('messages.customer', [], session('locale')) }}'"></div>
                <div class="text-xs text-gray-500 mt-1" x-text="customerItem.phone || ''"></div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.governorate', [], session('locale')) }}</label>
        <select x-model="customer.governorate_id" class="form-select w-full border-gray-300 rounded-lg" @change="updateCities($event.target.value)">
          <option value="">{{ trans('messages.select_governorate', [], session('locale')) }}</option>
          <template x-for="area in governorates" :key="area.id">
            <option :value="area.id" x-text="area.name"></option>
          </template>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.state_area', [], session('locale')) }}</label>
        <select x-model="customer.city_id" class="form-select w-full border-gray-300 rounded-lg" @change="selectCity($event.target.value)">
          <option value="">{{ trans('messages.select', [], session('locale')) }}</option>
          <template x-for="city in availableCities" :key="city.id">
            <option :value="city.id" x-text="city.name + (city.charge ? ' - ' + city.charge + ' ر.ع' : '')"></option>
          </template>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">{{ trans('messages.address', [], session('locale')) }}</label>
        <textarea x-model="customer.address" rows="3" class="form-textarea w-full border-gray-300 rounded-lg" placeholder="{{ trans('messages.address_placeholder', [], session('locale')) ?: 'Enter full address' }}"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.send_as_gift', [], session('locale')) }}</label>
        <div class="flex items-center gap-4 mt-1">
          <label class="flex items-center gap-1">
            <input type="radio" value="yes" x-model="customer.is_gift" class="accent-primary">
            <span>{{ trans('messages.yes', [], session('locale')) }}</span>
          </label>
          <label class="flex items-center gap-1">
            <input type="radio" value="no" x-model="customer.is_gift" class="accent-primary">
            <span>{{ trans('messages.no', [], session('locale')) }}</span>
          </label>
        </div>
      </div>

      <div x-show="customer.is_gift === 'yes'" class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">{{ trans('messages.gift_card_message', [], session('locale')) }}</label>
        <textarea x-model="customer.gift_message" rows="2" class="form-textarea w-full border-gray-300 rounded-lg" placeholder="{{ trans('messages.gift_card_placeholder', [], session('locale')) }}"></textarea>
      </div>

      <div class="md:col-span-2 flex items-center gap-4 mt-2">
        <span class="text-sm text-gray-700">{{ trans('messages.delivery_fee', [], session('locale')) }}:</span>
        <span class="text-lg font-semibold text-green-600" x-text="shipping_fee ? shipping_fee + ' ر.ع' : '—'"></span>
        <span class="text-xs text-gray-500">({{ trans('messages.paid_to_courier', [], session('locale')) }})</span>
      </div>
    </div>
  </div>

  <!-- 👗 الطلبات -->
  <template x-for="(order, index) in orders" :key="order.id">
    <section :id="'order-' + order.id" class="bg-white shadow-md rounded-2xl p-5 border border-gray-100 mb-6" x-data="abayaSelector(order)">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">{{ trans('messages.order_number', [], session('locale')) }} <span x-text="index + 1"></span></h2>
        <button @click="removeOrder(index)" class="text-red-500 hover:text-red-700 transition">
          <span class="material-symbols-outlined">delete</span>
        </button>
      </div>

      <!-- اختيار العباية -->
      <div class="grid md:grid-cols-3 gap-4">
        <div class="relative md:col-span-2">

    <label class="block text-sm font-medium mb-1">
        {{ trans('messages.select_abaya_from_stock', [], session('locale')) }}
    </label>

    <input type="text"
           x-model="search"
           @input.debounce.300ms="searchAbayas()"
           placeholder="{{ trans('messages.search_abaya_placeholder', [], session('locale')) }}"
           class="form-input w-full border-gray-300 rounded-lg focus:ring-primary/50 focus:ring-2">

    <ul x-show="search.length > 0"
        @click.outside="search=''; abayas=[]"
        class="absolute bg-white shadow rounded-lg mt-1 border border-gray-100 w-full max-h-60 overflow-y-auto z-20">

        <li x-show="search.length > 0 && search.length < 2"
            class="px-3 py-2 text-gray-400 text-sm">
            {{ trans('messages.search_abaya_placeholder', [], session('locale')) }}
        </li>

        <template x-for="item in abayas" :key="item.id">
            <li @click="selectAbaya(item)"
                class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center gap-2">

                <img :src="item.image"
                     alt=""
                     class="w-10 h-10 object-cover rounded"
                     onerror="this.src='/images/placeholder.png'">

                <div>
                    <div class="font-medium text-sm" x-text="item.name"></div>

                    <div class="text-xs text-gray-500">
                        {{ trans('messages.code', [], session('locale')) }}:
                        <span x-text="item.code"></span>
                        —
                        {{ trans('messages.price', [], session('locale')) }}:
                        <span x-text="item.price"></span> ر.ع
                    </div>
                </div>

            </li>
        </template>

        <li x-show="search.length >= 2 && abayas.length === 0 && !loading"
            class="px-3 py-2 text-gray-400 text-sm">
            {{ trans('messages.no_results', [], session('locale')) }}
        </li>
        
        <li x-show="loading"
            class="px-3 py-2 text-gray-400 text-sm text-center">
            {{ trans('messages.loading_details', [], session('locale')) }}
        </li>
    </ul>

</div>


        <div>
          <label class="block text-sm font-medium mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
          <input type="number" min="1" x-model="order.quantity" data-validate="quantity" @keydown="validateQuantityInput($event)" @paste="cleanQuantityOnPaste($event)" class="form-input w-full border-gray-300 rounded-lg">
        </div>
      </div>

      <!-- عرض الصورة والسعر -->
      <div x-show="selectedAbaya" class="flex items-center gap-4 mt-4 border-t pt-4">
        <img :src="selectedAbaya.image" alt="{{ trans('messages.abaya_image', [], session('locale')) }}" class="w-24 h-24 object-cover rounded-xl border">
        <div>
          <h4 class="font-semibold" x-text="selectedAbaya.name"></h4>
          <p class="text-gray-600 text-sm mt-1">{{ trans('messages.code', [], session('locale')) }}: <span x-text="selectedAbaya.code"></span></p>
          <p class="text-green-600 font-bold mt-1">{{ trans('messages.price', [], session('locale')) }}: <span x-text="selectedAbaya.price"></span> </p>
        </div>
      </div>

      <!-- المقاسات -->
      <div class="border-t pt-4 mt-4">
        <h3 class="font-semibold mb-2">{{ trans('messages.sizes_inches', [], session('locale')) }}</h3>
        <div class="grid md:grid-cols-3 gap-4">
          <div><label class="block text-sm mb-1">{{ trans('messages.abaya_length', [], session('locale')) }}</label><input type="number" x-model="order.length" class="form-input w-full border-gray-300 rounded-lg"></div>
          <div><label class="block text-sm mb-1">{{ trans('messages.bust_one_side', [], session('locale')) }}</label><input type="number" x-model="order.bust" class="form-input w-full border-gray-300 rounded-lg"></div>
          <div><label class="block text-sm mb-1">{{ trans('messages.sleeves_length', [], session('locale')) }}</label><input type="number" x-model="order.sleeves" class="form-input w-full border-gray-300 rounded-lg"></div>
          <div><label class="block text-sm mb-1">{{ trans('messages.buttons', [], session('locale')) }}</label>
            <select x-model="order.buttons" class="form-select w-full border-gray-300 rounded-lg"><option value="yes">{{ trans('messages.yes', [], session('locale')) }}</option><option value="no">{{ trans('messages.no', [], session('locale')) }}</option></select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm mb-1">{{ trans('messages.notes', [], session('locale')) }}</label>
            <textarea x-model="order.notes" class="form-textarea w-full border-gray-300 rounded-lg" rows="2"></textarea>
          </div>
        </div>
      </div>
    </section>
  </template>

  <!-- زر إضافة طلب -->
  <div class="text-center my-6">
    <button @click="addOrder" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl flex items-center gap-2 mx-auto">
      <span class="material-symbols-outlined">add</span> {{ trans('messages.add_new_abaya', [], session('locale')) }}
    </button>
  </div>

  <!-- زر الحفظ -->
  <div class="text-center">
    <button @click="openPaymentModal" class="bg-green-600 text-white px-8 py-3 rounded-xl hover:bg-green-700">
      {{ trans('messages.confirm_order_and_calculate', [], session('locale')) }}
    </button>
  </div>

  <!-- مودل الدفع -->
  <div x-show="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-transition>
    <div class="bg-white rounded-2xl p-6 w-full max-w-4xl mx-4 shadow-lg max-h-[90vh] overflow-y-auto">
      <h2 class="text-xl font-bold mb-6">{{ trans('messages.order_summary', [], session('locale')) }}</h2>
      
      <!-- Customer Information -->
      <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-4 mb-4">
        <h3 class="font-semibold mb-3 text-gray-800">{{ trans('messages.customer_data', [], session('locale')) }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div>
            <span class="text-gray-600">{{ trans('messages.customer_name', [], session('locale')) }}:</span>
            <strong class="ml-2" x-text="customer.name"></strong>
          </div>
          <div>
            <span class="text-gray-600">{{ trans('messages.phone_number', [], session('locale')) }}:</span>
            <strong class="ml-2" x-text="customer.phone"></strong>
          </div>
          <div>
            <span class="text-gray-600">{{ trans('messages.order_source', [], session('locale')) }}:</span>
            <strong class="ml-2" x-text="customer.source === 'whatsapp' ? '{{ trans('messages.whatsapp', [], session('locale')) }}' : (customer.source === 'walkin' ? '{{ trans('messages.walk_in', [], session('locale')) }}' : customer.source)"></strong>
          </div>
          <div>
            <span class="text-gray-600">{{ trans('messages.governorate', [], session('locale')) }}:</span>
            <strong class="ml-2" x-text="getGovernorateName(customer.governorate_id) || '—'"></strong>
          </div>
          <div>
            <span class="text-gray-600">{{ trans('messages.state_area', [], session('locale')) }}:</span>
            <strong class="ml-2" x-text="getCityName(customer.city_id) || '—'"></strong>
          </div>
          <div class="md:col-span-2">
            <span class="text-gray-600">{{ trans('messages.address', [], session('locale')) }}:</span>
            <strong class="ml-2 block mt-1" x-text="customer.address || '—'"></strong>
          </div>
          <template x-if="customer.is_gift === 'yes'">
            <div class="md:col-span-2">
              <span class="text-gray-600">{{ trans('messages.send_as_gift', [], session('locale')) }}:</span>
              <strong class="ml-2">{{ trans('messages.yes', [], session('locale')) }}</strong>
              <template x-if="customer.gift_message">
                <div class="mt-2 text-sm">
                  <span class="text-gray-600">{{ trans('messages.gift_card_message', [], session('locale')) }}:</span>
                  <p class="mt-1 text-gray-800" x-text="customer.gift_message"></p>
                </div>
              </template>
            </div>
          </template>
        </div>
      </div>

      <!-- Order Items -->
      <div class="mb-4">
        <h3 class="font-semibold mb-3 text-gray-800">{{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }}</h3>
        <div class="space-y-3">
          <template x-for="(order, i) in orders" :key="i">
            <div class="border border-gray-200 rounded-xl p-4 bg-white">
              <div class="flex justify-between items-start mb-3">
                <h4 class="font-semibold text-lg">{{ trans('messages.abaya_number', [], session('locale')) }} <span x-text="i+1"></span></h4>
                <div class="text-right">
                  <div class="text-sm text-gray-600">{{ trans('messages.subtotal', [], session('locale')) ?: 'Subtotal' }}:</div>
                  <div class="font-bold text-indigo-600" x-text="(order.price * order.quantity).toFixed(3) + ' ر.ع'"></div>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="text-gray-600">{{ trans('messages.design_name', [], session('locale')) }}:</span>
                  <strong class="ml-2" x-text="order.design_name || order.abaya_code || '—'"></strong>
                </div>
                <div>
                  <span class="text-gray-600">{{ trans('messages.code', [], session('locale')) }}:</span>
                  <strong class="ml-2" x-text="order.abaya_code || '—'"></strong>
                </div>
                <div>
                  <span class="text-gray-600">{{ trans('messages.quantity', [], session('locale')) }}:</span>
                  <strong class="ml-2" x-text="order.quantity || 1"></strong>
                </div>
                <div>
                  <span class="text-gray-600">{{ trans('messages.price', [], session('locale')) }}:</span>
                  <strong class="ml-2" x-text="order.price.toFixed(3) + ' ر.ع'"></strong>
                </div>
                <template x-if="order.length || order.bust || order.sleeves">
                  <div class="md:col-span-2 border-t pt-2 mt-2">
                    <div class="text-gray-600 mb-1">{{ trans('messages.sizes', [], session('locale')) }}:</div>
                    <div class="flex flex-wrap gap-3 text-sm">
                      <template x-if="order.length">
                        <div>
                          <span class="text-gray-600">{{ trans('messages.abaya_length', [], session('locale')) }}:</span>
                          <strong class="ml-1" x-text="order.length + ' {{ trans('messages.inches', [], session('locale')) }}'"></strong>
                        </div>
                      </template>
                      <template x-if="order.bust">
                        <div>
                          <span class="text-gray-600">{{ trans('messages.bust_one_side', [], session('locale')) }}:</span>
                          <strong class="ml-1" x-text="order.bust + ' {{ trans('messages.inches', [], session('locale')) }}'"></strong>
                        </div>
                      </template>
                      <template x-if="order.sleeves">
                        <div>
                          <span class="text-gray-600">{{ trans('messages.sleeves_length', [], session('locale')) }}:</span>
                          <strong class="ml-1" x-text="order.sleeves + ' {{ trans('messages.inches', [], session('locale')) }}'"></strong>
                        </div>
                      </template>
                      <div>
                        <span class="text-gray-600">{{ trans('messages.buttons', [], session('locale')) }}:</span>
                        <strong class="ml-1" x-text="order.buttons === 'yes' ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'"></strong>
                      </div>
                    </div>
                  </div>
                </template>
                <template x-if="order.notes">
                  <div class="md:col-span-2 border-t pt-2 mt-2">
                    <span class="text-gray-600">{{ trans('messages.notes', [], session('locale')) }}:</span>
                    <p class="mt-1 text-gray-800" x-text="order.notes"></p>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Summary -->
      <div class="bg-gray-50 rounded-xl p-4 mb-4">
        <h3 class="font-semibold mb-3 text-gray-800">{{ trans('messages.financial_details', [], session('locale')) }}</h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.shipping', [], session('locale')) }}:</span>
            <strong x-text="shipping_fee.toFixed(3) + ' ر.ع'"></strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.total', [], session('locale')) }}:</span>
            <strong class="text-lg text-indigo-600" x-text="calculateTotal().toFixed(3) + ' ر.ع'"></strong>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button @click="showModal=false" 
                :disabled="loading"
                :class="loading ? 'px-4 py-2 bg-gray-300 rounded-lg cursor-not-allowed' : 'px-4 py-2 bg-gray-100 rounded-lg'">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>
        <button @click="submitOrders" 
                :disabled="loading"
                :class="loading ? 'px-4 py-2 bg-indigo-400 text-white rounded-lg cursor-not-allowed opacity-75' : 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg'">
          <template x-if="loading">
            <span class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ trans('messages.processing', [], session('locale')) ?: 'Processing...' }}
            </span>
          </template>
          <template x-if="!loading">
            <span>{{ trans('messages.confirm_and_save', [], session('locale')) }}</span>
          </template>
        </button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div x-show="showPaymentModal" x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div @click.away="showPaymentModal=false"
         x-transition.scale
         class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl p-6">

      <h2 class="text-xl font-bold mb-4">{{ trans('messages.record_payment', [], session('locale')) }}</h2>

      <template x-if="savedOrderId">
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.order_number', [], session('locale')) }}:</span>
            <span class="font-semibold" x-text="savedOrderId"></span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.total_amount', [], session('locale')) }}:</span>
            <span class="font-semibold text-blue-700"
                  x-text="calculateTotal().toFixed(3) + ' ر.ع'"></span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.previously_paid', [], session('locale')) }}:</span>
            <span class="font-semibold text-emerald-700">0.000 ر.ع</span>
          </div>

          <div class="flex justify-between">
            <span class="text-gray-600">{{ trans('messages.remaining', [], session('locale')) }}:</span>
            <span class="font-semibold text-red-600"
                  x-text="calculateTotal().toFixed(3) + ' ر.ع'"></span>
          </div>

          <div class="mt-4">
            <label class="block text-sm mb-1">{{ trans('messages.current_payment_amount', [], session('locale')) }}</label>
            <input type="number" step="0.001" min="0" :max="calculateTotal()"
                   x-model="paymentAmount"
                   data-validate="amount"
                   @input="paymentError = ''"
                   @keydown="validateAmountInput($event)"
                   @paste="cleanAmountOnPaste($event)"
                   class="form-input w-full rounded-xl border-gray-300" />
          </div>

          <div class="mt-3">
            <label class="block text-sm mb-1">{{ trans('messages.payment_method', [], session('locale')) }}</label>
            <select x-model="selectedAccountId"
                    @change="paymentError = ''"
                    class="form-select w-full rounded-xl border-gray-300">
              <option value="">{{ trans('messages.select_account', [], session('locale')) ?: 'Select Account' }}</option>
              <template x-for="account in accounts" :key="account.id">
                <option :value="account.id" x-text="account.account_name + (account.account_no ? ' (' + account.account_no + ')' : '')"></option>
              </template>
            </select>
          </div>

          <!-- Error Message -->
          <div x-show="paymentError" 
               x-transition
               class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700" x-text="paymentError"></p>
          </div>

        </div>
      </template>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="skipPayment()"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
          {{ trans('messages.skip', [], session('locale')) ?: 'Skip' }}
        </button>
        <button @click="confirmPayment()"
                :disabled="paymentProcessing"
                :class="paymentProcessing ? 'px-4 py-2 bg-indigo-400 text-white rounded-xl cursor-not-allowed' : 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl'">
          {{ trans('messages.confirm_payment', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>
    </div>
  </div>
</main>



@include('layouts.footer')
@endsection