@extends('layouts.pos_header')

@section('main_pos')
@push('title')
<title>{{ trans('messages.pos_lang', [], session('locale')) }}</title>
@endpush

  <main class="flex-1 flex flex-col lg:flex-row overflow-hidden p-3 lg:p-6 gap-4 lg:gap-6">
    <section class="flex flex-col flex-1 bg-white rounded-xl shadow-premium overflow-hidden min-w-0">
      <div class="p-5 border-b border-gray-100">
        <label class="flex items-center w-full h-16 bg-gray-50 rounded-full px-5 focus-within:ring-2 focus-within:ring-primary/50 transition-all">
          <span class="material-symbols-outlined text-gray-400 text-3xl ml-4">search</span>
          <input id="barcodeInput" class="bg-transparent border-none w-full h-full focus:ring-0 text-lg placeholder:text-gray-400" placeholder="{{ trans('messages.scan_barcode_or_search', [], session('locale')) }}" type="text" />
          <button class="p-3 bg-white rounded-full shadow-sm text-gray-600 hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-xl">barcode_scanner</span>
          </button>
        </label>
      </div>
      <div class="px-5 py-4 flex gap-3 overflow-x-auto no-scrollbar border-b border-gray-50">
        <button data-filter="all" class="category-tab shrink-0 h-12 px-7 rounded-full bg-primary text-white text-base font-bold shadow-md shadow-primary/20 whitespace-nowrap">{{ trans('messages.all', [], session('locale')) }}</button>
        @foreach($categories as $category)
        <button data-filter="category_{{ $category->id }}" class="category-tab shrink-0 h-12 px-7 rounded-full bg-gray-100 text-gray-700 text-base font-medium transition-colors whitespace-nowrap">
          {{ session('locale') == 'ar' && $category->category_name_ar ? $category->category_name_ar : $category->category_name }}
        </button>
        @endforeach
      </div>
      <div class="flex-1 overflow-y-auto p-5 bg-gray-50/50">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4" id="productsGrid">
          @foreach($stocks as $stock)
          @php
          $firstImage = $stock->images->first();
          $imageUrl = $firstImage ? asset($firstImage->image_path) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2U1ZTdlYiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5Y2EzYWYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
          $categoryFilter = $stock->category_id ? 'category_' . $stock->category_id : 'all';
          $displayName = session('locale') == 'ar' && $stock->design_name ? $stock->design_name : ($stock->design_name ?: $stock->abaya_code);
          @endphp
          <button
            class="group flex flex-col bg-white rounded-2xl p-4 shadow-sm hover:shadow-lg transition-all active:scale-98 border border-gray-100 hover:border-primary/30 product-item"
            data-id="{{ $stock->id }}"
            data-name="{{ $displayName }}"
            data-price="{{ $stock->sales_price ?? 0 }}"
            data-category="{{ $categoryFilter }}"
            data-image="{{ $imageUrl }}"
            data-barcode="{{ $stock->barcode ?? '' }}"
            data-abaya-code="{{ $stock->abaya_code ?? '' }}"
            data-design-name="{{ $stock->design_name ?? '' }}">
            <div class="w-full aspect-[3/4] rounded-xl bg-gray-100 mb-4 overflow-hidden relative">
              <div class="w-full h-full bg-cover bg-center group-hover:scale-105 transition-transform duration-500" style="background-image: url('{{ $imageUrl }}');"></div>
            </div>
            <div class="text-right w-full">
              <h3 class="font-bold text-gray-900 text-base mb-1 leading-tight">{{ $displayName }}</h3>
              <p class="text-xs text-gray-500 mb-1">{{ trans('messages.code', [], session('locale')) }}: {{ $stock->abaya_code ?? 'N/A' }}</p>
              @if($stock->barcode)
              <p class="text-xs text-gray-500 mb-1">{{ trans('messages.barcode', [], session('locale')) }}: {{ $stock->barcode }}</p>
              @endif
              <p class="text-primary font-black text-xl drop-shadow-sm mt-1">{{ number_format($stock->sales_price ?? 0, 2) }} {{ trans('messages.omr', [], session('locale')) }}</p>
            </div>
          </button>
          @endforeach
          @if($stocks->isEmpty())
          <div class="col-span-full text-center py-12 text-gray-500">
            <p class="text-lg">{{ trans('messages.no_products', [], session('locale')) }}</p>
          </div>
          @endif
        </div>
      </div>
    </section>
    <section
      <section
      id="cartDesktop"
      class="hidden lg:flex flex-col
         w-full lg:w-[450px]
         h-[100dvh] lg:h-[85vh]
         bg-white rounded-xl shadow-premium
         overflow-hidden relative">


      <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-white z-10">

        <!-- Mobile Back Button -->
        <button
          onclick="closeCartMobile()"
          class="lg:hidden flex items-center gap-2 text-primary font-bold">
          <span class="material-symbols-outlined">arrow_forward</span>
          {{ trans('messages.products', [], session('locale')) }}
        </button>

        <h2 class="font-bold text-xl text-gray-800 hidden lg:block">
          {{ trans('messages.current_cart', [], session('locale')) }}
          <span id="cartCount" class="text-gray-400 text-base font-normal mr-1">
            (0 {{ trans('messages.items', [], session('locale')) }})
          </span>
        </h2>

        <!-- Delete -->
        <button
          onclick="clearCart()"
          class="text-red-500 hover:bg-red-50 p-2.5 rounded-full transition-colors"
          title="{{ trans('messages.clear_cart', [], session('locale')) }}">
          <span class="material-symbols-outlined text-2xl">delete</span>
        </button>
      </div>

      <!-- Cart Content Wrapper -->
      <div class="flex-1 relative overflow-hidden">

        <!-- Cart Items -->
        <div id="cartItems"
          class="h-full overflow-y-auto overscroll-contain
            touch-pan-y p-4 space-y-4 pb-32">
        </div>

        <!-- Empty Cart State -->
        <div id="emptyCart"
          class="absolute inset-0 flex flex-col items-center justify-center
              text-gray-300 select-none hidden">
          <span class="material-symbols-outlined text-7xl mb-4">
            shopping_cart
          </span>
          <p class="text-lg font-bold">{{ trans('messages.cart_empty', [], session('locale')) }}</p>
          <p class="text-sm text-gray-400 mt-1">{{ trans('messages.start_adding_products', [], session('locale')) }}</p>
        </div>

      </div>

      </div>
      <div class="p-5 bg-white border-t border-gray-100
            sticky bottom-0 z-20
            shadow-[0_-8px_30px_rgba(0,0,0,0.05)]">
        <div class="space-y-3 mb-5">

          <div class="flex justify-between items-end mt-3 pt-3 border-t border-gray-100">
            <span class="font-bold text-xl text-gray-800">{{ trans('messages.total', [], session('locale')) }}</span>
            <span id="cartTotal" class="font-black text-3xl text-primary-dark">
              0.00 <span class="text-base font-medium text-gray-500">{{ trans('messages.omr', [], session('locale')) }}</span>
            </span>
          </div>
        </div>
        <!-- Discount Box -->
        <div id="discountBox"
          class="hidden mb-4 p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">

          <div class="flex items-center gap-3">
            <!-- Discount Type -->
            <select id="discountType"
              class="rounded-xl border p-3 text-sm font-bold">
              <option value="percent">{{ trans('messages.percentage', [], session('locale')) }} %</option>
              <option value="amount">{{ trans('messages.fixed_amount', [], session('locale')) }}</option>
            </select>

            <!-- Discount Value -->
            <input id="discountValue"
              type="number"
              min="0"
              placeholder="{{ trans('messages.discount_value', [], session('locale')) }}"
              class="flex-1 rounded-xl border p-3 text-sm" />
          </div>

          <div class="flex justify-between text-sm">
            <span>{{ trans('messages.discount_value', [], session('locale')) }}</span>
            <strong id="discountAmount">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <button
            id="discountBtn"
            onclick="toggleDiscount()"
            class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl
         bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
            <span class="material-symbols-outlined text-xl mb-1">percent</span>
            <span class="text-xs font-bold">{{ trans('messages.discount', [], session('locale')) }}</span>
          </button>
          <button id="suspendBtn"
            class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
            <span class="material-symbols-outlined text-xl mb-1">pause_circle</span>
            <span class="text-xs font-bold">{{ trans('messages.suspend', [], session('locale')) }}</span>
          </button>
          <button onclick="openPaymentModal()"
            class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl bg-primary text-white hover:bg-primary-dark transition-colors shadow-md">
            <span class="material-symbols-outlined text-xl mb-1">payments</span>
            <span class="text-xs font-bold">{{ trans('messages.pay', [], session('locale')) }}</span>
          </button>
        </div>
      </div>
    </section>

  </main>

  <!-- PRODUCT MODAL -->
  <!-- PREMIUM PRODUCT MODAL -->
  <div id="productModal"
    class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">

    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-premium
            max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 id="modalName" class="text-xl font-extrabold text-gray-800"></h3>
        <button onclick="closeModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
          <span class="material-symbols-outlined text-xl">close</span>
        </button>
      </div>

      <!-- Content -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6
            overflow-y-auto flex-1">
        <!-- Image -->
        <div class="rounded-xl overflow-hidden bg-gray-100">
          <div id="modalImage"
            class="w-full h-[380px] bg-cover bg-center transition-transform duration-500 hover:scale-105">
          </div>
        </div>

        <!-- Options -->
        <div class="flex flex-col">

          <!-- Price -->
          <div class="mb-5">
            <p class="text-sm text-gray-500 mb-1">{{ trans('messages.price', [], session('locale')) }}</p>
            <p id="modalPrice" class="text-3xl font-black text-primary-dark"></p>
          </div>

          <!-- Colors and Sizes with Quantities -->
          <div class="mb-6">
            <p class="font-bold mb-2 text-gray-800 text-sm">{{ trans('messages.available_colors_sizes', [], session('locale')) }}</p>
            <div id="colorSizeContainer" class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
              <!-- Colors and sizes will be loaded here dynamically -->
              <div class="text-center text-gray-400 py-4">
                <span class="material-symbols-outlined text-3xl mb-2 block">inventory_2</span>
                <p class="text-xs">{{ trans('messages.loading', [], session('locale')) }}...</p>
              </div>
            </div>
          </div>

          <!-- Quantity -->
          <div class="flex items-center justify-between mb-6 bg-gray-50 rounded-xl p-3">
            <span class="font-bold">{{ trans('messages.quantity', [], session('locale')) }}</span>
            <div class="flex items-center gap-3">
              <button onclick="changeQty(-1)"
                class="size-9 rounded-full bg-white shadow flex items-center justify-center">−</button>
              <span id="modalQty" class="w-8 text-center font-bold">1</span>
              <button onclick="changeQty(1)"
                class="size-9 rounded-full bg-primary text-white shadow">+</button>
            </div>
          </div>

          <!-- Action -->
          <button
            type="button"
            ontouchstart="confirmAddToCart()"
            onclick="confirmAddToCart()"
            class="mt-auto h-14 rounded-xl bg-primary hover:bg-primary-dark
         text-white font-extrabold text-lg shadow-glow-primary transition-all">
            {{ trans('messages.add_to_cart', [], session('locale')) }}
          </button>


        </div>
      </div>
    </div>
  </div>


  <!-- <script src="js/pos.js"></script> -->



  <!-- PAYMENT MODAL -->
  <div id="paymentModal"
    class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">

    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-premium border border-gray-100
            max-h-[95vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-xl font-extrabold text-gray-800">{{ trans('messages.complete_payment', [], session('locale')) }}</h3>
        <button onclick="closePaymentModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6 overflow-y-auto flex-1">
        <!-- Subtotal -->
        <div class="flex justify-between items-center bg-gray-50 rounded-xl p-3">
          <span class="font-semibold text-sm text-gray-700">{{ trans('messages.subtotal', [], session('locale')) }}</span>
          <span id="paymentSubtotal" class="font-bold text-lg text-gray-800">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
        </div>

        <!-- Discount -->
        <div id="paymentDiscountRow" class="hidden flex justify-between items-center bg-yellow-50 rounded-xl p-3 border border-yellow-200">
          <span class="font-semibold text-sm text-gray-700">{{ trans('messages.discount', [], session('locale')) }}</span>
          <span id="paymentDiscount" class="font-bold text-lg text-red-600">-0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
        </div>

        <!-- Payable Amount -->
        <div class="flex justify-between items-center bg-primary/10 rounded-xl p-4 border-2 border-primary/30">
          <span class="font-bold text-lg text-gray-800">{{ trans('messages.payable_amount', [], session('locale')) }}</span>
          <span id="paymentTotal" class="font-black text-2xl text-primary-dark">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
        </div>

        <!-- Payment Method -->
        <div>
          <p class="font-bold mb-3 text-gray-800 text-sm tracking-wide">{{ trans('messages.payment_method', [], session('locale')) }}</p>
          <div id="paymentAccounts" class="grid grid-cols-4 gap-3"></div>

          <div id="singlePaymentBox" class="hidden mt-3 space-y-2">
            <label class="block text-xs font-semibold text-gray-700">{{ trans('messages.enter_amount', [], session('locale')) }}</label>
            <input id="singlePaymentAmount"
              type="number"
              min="0"
              step="0.001"
              class="w-full rounded-xl border p-3 text-sm"
              placeholder="{{ trans('messages.enter_amount', [], session('locale')) }}">
          </div>

          <div id="changeBox" class="hidden mt-3 flex justify-between bg-gray-50 p-3 rounded-xl">
            <span>{{ trans('messages.change_amount', [], session('locale')) }}</span>
            <strong id="changeAmount">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
          </div>

        </div>

        <div id="partialPaymentBox" class="hidden space-y-3 mt-3">
          <div id="partialAccounts" class="space-y-2"></div>

          <div class="flex justify-between bg-gray-50 p-3 rounded-xl">
            <span>{{ trans('messages.remaining', [], session('locale')) }}</span>
            <strong id="partialRemaining">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
          </div>
        </div>


        <!-- Order Type -->
        <div>
          <p class="font-bold mb-3">{{ trans('messages.order_type', [], session('locale')) }}</p>
          <div class="flex gap-3">
            <button class="order-type-btn" data-type="direct">{{ trans('messages.direct', [], session('locale')) }}</button>
            <button class="order-type-btn" data-type="delivery">{{ trans('messages.delivery', [], session('locale')) }}</button>
          </div>
        </div>

        <!-- Delivery -->
        <div id="deliverySection" class="hidden space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <select id="deliveryArea" class="w-full rounded-xl border p-3">
              <option value="">{{ trans('messages.select_area', [], session('locale')) }}</option>
              @if(isset($areas))
                @foreach($areas as $area)
                  <option value="{{ $area->id }}">
                    {{ session('locale') == 'ar' ? ($area->area_name_ar ?: $area->area_name_en) : ($area->area_name_en ?: $area->area_name_ar) }}
                  </option>
                @endforeach
              @endif
            </select>

            <select id="deliveryWilayah" class="w-full rounded-xl border p-3">
              <option value="">{{ trans('messages.select_wilayah', [], session('locale')) }}</option>
              <!-- Cities will be loaded dynamically when area is selected -->
            </select>
          </div>

          <!-- Full delivery address -->
          <div>
            <label class="block text-sm font-bold mb-2">
              {{ trans('messages.full_delivery_address', [], session('locale')) }}
            </label>

            <textarea
              id="deliveryAddress"
              rows="3"
              placeholder="{{ trans('messages.delivery_address_placeholder', [], session('locale')) }}"
              class="w-full rounded-xl border p-3 resize-none focus:ring-2 focus:ring-primary/40"></textarea>
          </div>

          <div class="flex justify-between items-center bg-gray-50 rounded-xl p-3">
            <span>{{ trans('messages.delivery_price', [], session('locale')) }}</span>
            <span id="deliveryPrice">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
          </div>

          <label class="flex items-center gap-2">
            <input type="checkbox" id="deliveryPaid" />
            <span class="text-sm">{{ trans('messages.delivery_price_paid', [], session('locale')) }}</span>
          </label>
        </div>

        <!-- Customer -->
        <div>
          <p class="font-bold mb-3">{{ trans('messages.customer_data', [], session('locale')) }}</p>
          <div class="grid grid-cols-2 gap-3">
            <div class="relative">
              <input id="customerPhone"
                type="text"
                placeholder="{{ trans('messages.phone_number', [], session('locale')) }}"
                class="rounded-xl border p-3 w-full" />

              <div id="customerSuggestions"
                class="hidden absolute top-full mt-2 w-full bg-white border rounded-xl shadow-md z-10">
              </div>
            </div>

            <input id="customerName"
              type="text"
              placeholder="{{ trans('messages.customer_name', [], session('locale')) }}" class="rounded-xl border p-3" />

          </div>
          <div id="selectedCustomer"
            class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-xl">
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="p-5 border-t flex gap-3">
        <button
          onclick="closePaymentModal()"
          class="flex-1 h-14 rounded-xl bg-gray-100 font-bold hover:bg-gray-200 transition">
          {{ trans('messages.cancel', [], session('locale')) }}
        </button>
        <button id="confirmPaymentBtn" class="flex-1 h-14 rounded-xl bg-primary text-white font-bold hover:bg-primary-dark transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-70 disabled:cursor-not-allowed disabled:pointer-events-none">
          {{ trans('messages.confirm_payment', [], session('locale')) }}
        </button>
      </div>

    </div>
  </div>

  <div id="suspendedModal"
    class="fixed inset-0 hidden items-center justify-center bg-black/60 z-50 backdrop-blur-sm">

    <div class="bg-white w-full max-w-xl rounded-2xl shadow-premium overflow-hidden">

      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 class="font-bold text-lg">{{ trans('messages.suspended_invoices', [], session('locale')) }}</h3>
        <button onclick="closeSuspendedModal()">✕</button>
      </div>

      <div id="suspendedList" class="p-4 space-y-3 max-h-[60vh] overflow-y-auto">
      </div>

    </div>
  </div>


  <!-- Mobile Cart Button -->
  <button
    id="openCartMobile"
    onclick="openCartMobile()"
    class="lg:hidden fixed bottom-4 right-4 z-40
         w-16 h-16 rounded-full bg-primary text-white
         flex items-center justify-center shadow-xl">

    <span class="material-symbols-outlined text-3xl">shopping_cart</span>

    <!-- Badge -->
    <span
      id="cartMobileBadge"
      class="absolute -top-1 -left-1 w-6 h-6 rounded-full bg-red-500 text-white
           text-xs font-bold flex items-center justify-center">
      0
    </span>
  </button>

  <!-- Mobile Cart Modal -->
    <!-- Mobile Cart Modal -->
    <div
      id="cartMobile"
      class="fixed inset-0 z-50 hidden lg:hidden">

      <!-- Overlay (يستقبل الضغط فقط للإغلاق) -->
      <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onclick="closeCartMobile()">
      </div>

      <!-- Cart Container -->
      <div
        class="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl
           h-[85vh] flex flex-col shadow-2xl
           pointer-events-auto">

        <!-- Header (ثابت) -->
        <div class="flex items-center justify-between p-4 border-b shrink-0">
          <h3 class="font-bold text-lg">{{ trans('messages.cart', [], session('locale')) }}</h3>
          <button
            onclick="closeCartMobile()"
            class="w-10 h-10 rounded-full hover:bg-gray-100">
            ✕
          </button>
        </div>

        <!-- Cart Items (هنا السكرول الحقيقي) -->
        <div
          id="cartMobileContent"
          class="flex-1 overflow-y-auto p-4 space-y-4
             overscroll-contain"
          style="-webkit-overflow-scrolling: touch;">
        </div>

        <!-- Footer (ثابت) -->
        <div class="p-4 border-t shrink-0 space-y-3 bg-white">

          <!-- Buttons -->
          <div class="grid grid-cols-2 gap-3">

            <!-- Suspend -->
            <button
              onclick="suspendCurrentCart(); closeCartMobile();"
              class="h-14 flex flex-col items-center justify-center
             rounded-xl bg-gray-100 text-gray-700
             hover:bg-gray-200 transition">
              <span class="material-symbols-outlined text-xl mb-1">
                pause_circle
              </span>
              <span class="text-xs font-bold">{{ trans('messages.suspend', [], session('locale')) }}</span>
            </button>

            <!-- Pay -->
            <button
              onclick="openPaymentModal(); closeCartMobile();"
              class="h-14 flex flex-col items-center justify-center
             rounded-xl bg-primary text-white font-bold
             hover:bg-primary-dark transition">
              <span class="material-symbols-outlined text-xl mb-1">
                payments
              </span>
              <span class="text-xs font-bold">{{ trans('messages.pay', [], session('locale')) }}</span>
            </button>

          </div>

          <!-- Total -->
          <div class="flex justify-between text-lg font-bold pt-2">
            <span>{{ trans('messages.total', [], session('locale')) }}</span>
            <span id="cartMobileTotal">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
          </div>

        </div>

      </div>


      

@include('layouts.pos_footer')
@endsection