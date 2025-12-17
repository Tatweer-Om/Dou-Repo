@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.add_stock_lang', [], session('locale')) }}</title>
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
        <input type="text" x-model="customer.phone" class="form-input w-full border-gray-300 rounded-lg" placeholder="9xxxxxxxx">
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
          <input type="number" min="1" x-model="order.quantity" class="form-input w-full border-gray-300 rounded-lg">
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
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-lg">
      <h2 class="text-xl font-bold mb-4">{{ trans('messages.order_summary', [], session('locale')) }}</h2>
      <p class="mb-3 text-sm text-gray-700">{{ trans('messages.customer', [], session('locale')) }}: <strong x-text="customer.name"></strong> — <span x-text="customer.phone"></span></p>
      <p class="text-sm mb-2 text-gray-600">{{ trans('messages.source', [], session('locale')) }}: <span x-text="customer.source"></span></p>
      <p class="text-sm mb-2 text-gray-600">{{ trans('messages.area', [], session('locale')) }}: <span x-text="(customer.governorate_name || '') + ' - ' + (customer.city || '')"></span></p>
      <p class="text-sm mb-3 text-gray-600">{{ trans('messages.shipping', [], session('locale')) }}: <span x-text="shipping_fee + ' ر.ع (' + '{{ trans('messages.on_delivery', [], session('locale')) }}' + ')'"></span></p>

      <template x-for="(order, i) in orders" :key="i">
        <div class="border-b py-2 mb-2">
          <div class="font-semibold">{{ trans('messages.abaya_number', [], session('locale')) }} <span x-text="i+1"></span></div>
          <div class="text-sm">{{ trans('messages.price', [], session('locale')) }}: <span x-text="order.price"></span> × <span x-text="order.quantity"></span></div>
        </div>
      </template>

      <div class="flex justify-end gap-3 mt-4">
        <button @click="showModal=false" class="px-4 py-2 bg-gray-100 rounded-lg">{{ trans('messages.cancel', [], session('locale')) }}</button>
        <button @click="submitOrders" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">{{ trans('messages.confirm_and_save', [], session('locale')) }}</button>
      </div>
    </div>
  </div>
</main>



@include('layouts.footer')
@endsection