@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.customer_profile', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="customerProfile()">

  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $customer->name ?? trans('messages.customer', [], session('locale')) }}</h2>
        <p class="text-gray-500 text-sm">
          {{ trans('messages.phone', [], session('locale')) }}: {{ $customer->phone ?? '-' }}
          @if($customer->city)
            • {{ $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '' }}
          @endif
          @if($customer->area)
            • {{ $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '' }}
          @endif
        </p>
      </div>
      <div class="flex gap-2">
        <a href="/customer" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">arrow_back</span> {{ trans('messages.back', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="p-6 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_revenue', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ number_format($totalRevenue, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</h3>
          </div>
          <span class="material-symbols-outlined text-blue-400 text-4xl">payments</span>
        </div>
      </div>
      <div class="p-6 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 shadow-md border border-green-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-green-600">{{ $totalItems }}</h3>
          </div>
          <span class="material-symbols-outlined text-green-400 text-4xl">inventory_2</span>
        </div>
      </div>
      <div class="p-6 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_orders', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ $specialOrdersCount + $posOrdersCount }}</h3>
          </div>
          <span class="material-symbols-outlined text-purple-400 text-4xl">receipt_long</span>
        </div>
      </div>
    </div>

    <!-- Customer Details Card -->
    <div class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4">{{ trans('messages.customer_details', [], session('locale')) }}</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.customer_name', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $customer->name }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.phone', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $customer->phone ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.location', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">
            @if($customer->city)
              {{ $customer->city->city_name_ar ?? $customer->city->city_name_en ?? '-' }}
            @else
              -
            @endif
            @if($customer->area)
              , {{ $customer->area->area_name_ar ?? $customer->area->area_name_en ?? '' }}
            @endif
          </p>
        </div>
        @if($customer->notes)
        <div class="md:col-span-3">
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.notes', [], session('locale')) }}</p>
          <p class="text-lg font-semibold text-gray-800">{{ $customer->notes }}</p>
        </div>
        @endif
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 border-b border-pink-100 overflow-x-auto no-scrollbar bg-white rounded-xl shadow-sm px-4">
      @if($specialOrdersCount > 0)
      <button @click="tab='special_orders'"
              :class="tab==='special_orders' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">assignment</span> 
        {{ trans('messages.special_orders', [], session('locale')) }}
        <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-semibold">{{ $specialOrdersCount }}</span>
      </button>
      @endif
      @if($posOrdersCount > 0)
      <button @click="tab='pos_orders'"
              :class="tab==='pos_orders' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">point_of_sale</span> 
        {{ trans('messages.pos_orders', [], session('locale')) }}
        <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs font-semibold">{{ $posOrdersCount }}</span>
      </button>
      @endif
    </div>

    <!-- SPECIAL ORDERS TAB -->
    @if($specialOrdersCount > 0)
    <section x-show="tab==='special_orders'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.special_orders', [], session('locale')) }}</h3>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_orders', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ $specialOrdersCount }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 shadow-md border border-green-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_revenue', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-green-600">{{ number_format($specialOrdersTotalRevenue, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ $specialOrdersTotalItems }}</h3>
          </div>
        </div>

        <!-- Orders List -->
        <div class="space-y-4">
          @forelse($specialOrders as $order)
          <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Order Header -->
            <div class="bg-gray-50 px-6 py-4 cursor-pointer" @click="expandedOrder{{ $order->id }} = !expandedOrder{{ $order->id }}">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 flex-wrap">
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.order_id', [], session('locale')) }}</p>
                    <p class="text-lg font-bold text-gray-800">#{{ $order->id }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.date', [], session('locale')) }}</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $order->created_at->format('Y-m-d') }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.status', [], session('locale')) }}</p>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                      @if($order->status === 'completed') bg-green-100 text-green-700
                      @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                      @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                      @else bg-gray-100 text-gray-700
                      @endif">
                      {{ trans('messages.' . $order->status, [], session('locale')) ?: $order->status }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.items', [], session('locale')) }}</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $order->items->sum('quantity') }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.total_amount', [], session('locale')) }}</p>
                    <p class="text-sm font-bold text-[var(--primary-color)]">{{ number_format($order->total_amount ?? 0, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.paid_amount', [], session('locale')) }}</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($order->paid_amount ?? 0, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-gray-400 transition-transform" 
                        :class="expandedOrder{{ $order->id }} ? 'rotate-180' : ''">expand_more</span>
                  <a href="{{ route('view_special_order', $order->id) }}" class="text-[var(--primary-color)] hover:underline ml-4">
                    <span class="material-symbols-outlined text-base align-middle">open_in_new</span>
                  </a>
                </div>
              </div>
            </div>
            
            <!-- Order Details (Expandable) -->
            <div x-show="expandedOrder{{ $order->id }}" x-transition class="bg-white p-6 border-t border-gray-200">
              <h4 class="text-lg font-bold text-gray-800 mb-4">{{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }}</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($order->items as $item)
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                  <div class="flex gap-4">
                    @if($item->stock && $item->stock->images && $item->stock->images->first())
                      <img src="{{ asset($item->stock->images->first()->image_path) }}" alt="{{ $item->design_name }}" class="w-20 h-20 object-cover rounded-lg">
                    @else
                      <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-gray-400">image</span>
                      </div>
                    @endif
                    <div class="flex-1">
                      <p class="font-bold text-gray-800">{{ $item->design_name ?? $item->abaya_code ?? 'N/A' }}</p>
                      <p class="text-xs text-gray-500 mb-2">{{ trans('messages.code', [], session('locale')) }}: {{ $item->abaya_code ?? 'N/A' }}</p>
                      <div class="space-y-1 text-xs">
                        <p><span class="text-gray-600">{{ trans('messages.quantity', [], session('locale')) }}:</span> <span class="font-semibold">{{ $item->quantity }}</span></p>
                        <p><span class="text-gray-600">{{ trans('messages.price', [], session('locale')) }}:</span> <span class="font-semibold">{{ number_format($item->price ?? 0, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</span></p>
                        @if($item->abaya_length)
                        <p><span class="text-gray-600">{{ trans('messages.abaya_length', [], session('locale')) }}:</span> {{ $item->abaya_length }} cm</p>
                        @endif
                        @if($item->bust)
                        <p><span class="text-gray-600">{{ trans('messages.bust_one_side', [], session('locale')) }}:</span> {{ $item->bust }} cm</p>
                        @endif
                        @if($item->sleeves_length)
                        <p><span class="text-gray-600">{{ trans('messages.sleeves_length', [], session('locale')) }}:</span> {{ $item->sleeves_length }} cm</p>
                        @endif
                        <p><span class="text-gray-600">{{ trans('messages.buttons', [], session('locale')) }}:</span> {{ $item->buttons ? trans('messages.yes', [], session('locale')) : trans('messages.no', [], session('locale')) }}</p>
                        @if($item->notes)
                        <p class="text-gray-600 mt-2"><span class="font-semibold">{{ trans('messages.notes', [], session('locale')) }}:</span> {{ $item->notes }}</p>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
              @if($order->notes)
              <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm"><span class="font-semibold">{{ trans('messages.order_notes', [], session('locale')) ?: 'Order Notes' }}:</span> {{ $order->notes }}</p>
              </div>
              @endif
            </div>
          </div>
          @empty
          <div class="text-center py-12 text-gray-500">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">shopping_bag</span>
            <p>{{ trans('messages.no_orders_found', [], session('locale')) }}</p>
          </div>
          @endforelse
        </div>
      </div>
    </section>
    @endif

    <!-- POS ORDERS TAB -->
    @if($posOrdersCount > 0)
    <section x-show="tab==='pos_orders'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <h3 class="text-xl font-bold text-[var(--primary-color)] mb-6">{{ trans('messages.pos_orders', [], session('locale')) }}</h3>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-md border border-blue-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_orders', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-blue-600">{{ $posOrdersCount }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 shadow-md border border-green-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_revenue', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-green-600">{{ number_format($posOrdersTotalRevenue, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</h3>
          </div>
          <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 shadow-md border border-purple-200">
            <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_items', [], session('locale')) }}</p>
            <h3 class="text-3xl font-extrabold text-purple-600">{{ $posOrdersTotalItems }}</h3>
          </div>
        </div>

        <!-- Orders List -->
        <div class="space-y-4">
          @forelse($posOrders as $order)
          <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Order Header -->
            <div class="bg-gray-50 px-6 py-4 cursor-pointer" @click="expandedPosOrder{{ $order->id }} = !expandedPosOrder{{ $order->id }}">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 flex-wrap">
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.order_no', [], session('locale')) }}</p>
                    <p class="text-lg font-bold text-gray-800">#{{ $order->order_no ?? $order->id }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.date', [], session('locale')) }}</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $order->created_at->format('Y-m-d') }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.order_type', [], session('locale')) }}</p>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                      @if($order->order_type === 'delivery') bg-blue-100 text-blue-700
                      @elseif($order->order_type === 'pickup') bg-green-100 text-green-700
                      @else bg-gray-100 text-gray-700
                      @endif">
                      {{ trans('messages.' . $order->order_type, [], session('locale')) ?: $order->order_type }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.items', [], session('locale')) }}</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $order->item_count ?? 0 }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.total_amount', [], session('locale')) }}</p>
                    <p class="text-sm font-bold text-[var(--primary-color)]">{{ number_format($order->total_amount ?? 0, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</p>
                  </div>
                  @if($order->order_type === 'delivery')
                  <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.delivery_status', [], session('locale')) }}</p>
                    @if($order->delivery_status === 'delivered')
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        {{ trans('messages.delivered', [], session('locale')) }}
                      </span>
                    @else
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                        {{ trans('messages.not_delivered', [], session('locale')) }}
                      </span>
                    @endif
                  </div>
                  @endif
                </div>
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-gray-400 transition-transform" 
                        :class="expandedPosOrder{{ $order->id }} ? 'rotate-180' : ''">expand_more</span>
                  <a href="{{ url('pos/orders') }}?order_id={{ $order->id }}" class="text-[var(--primary-color)] hover:underline ml-4">
                    <span class="material-symbols-outlined text-base align-middle">open_in_new</span>
                  </a>
                </div>
              </div>
            </div>
            
            <!-- Order Details (Expandable) -->
            <div x-show="expandedPosOrder{{ $order->id }}" x-transition class="bg-white p-6 border-t border-gray-200">
              <h4 class="text-lg font-bold text-gray-800 mb-4">{{ trans('messages.order_items', [], session('locale')) ?: 'Order Items' }}</h4>
              @if($order->details && $order->details->count() > 0)
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($order->details as $detail)
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                  <div class="flex gap-4">
                    @if($detail->stock && $detail->stock->images && $detail->stock->images->first())
                      <img src="{{ asset($detail->stock->images->first()->image_path) }}" alt="{{ $detail->stock->abaya_name ?? 'Item' }}" class="w-20 h-20 object-cover rounded-lg">
                    @else
                      <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-gray-400">image</span>
                      </div>
                    @endif
                    <div class="flex-1">
                      <p class="font-bold text-gray-800">{{ $detail->stock->abaya_name ?? 'N/A' }}</p>
                      @if($detail->stock)
                        <p class="text-xs text-gray-500 mb-2">{{ trans('messages.code', [], session('locale')) }}: {{ $detail->stock->abaya_code ?? 'N/A' }}</p>
                      @endif
                      <div class="space-y-1 text-xs">
                        <p><span class="text-gray-600">{{ trans('messages.quantity', [], session('locale')) }}:</span> <span class="font-semibold">{{ $detail->item_quantity ?? 0 }}</span></p>
                        <p><span class="text-gray-600">{{ trans('messages.price', [], session('locale')) }}:</span> <span class="font-semibold">{{ number_format($detail->item_price ?? 0, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</span></p>
                        @if($detail->size)
                        <p><span class="text-gray-600">{{ trans('messages.size', [], session('locale')) }}:</span> {{ $detail->size->size_name_ar ?? $detail->size->size_name_en ?? 'N/A' }}</p>
                        @endif
                        @if($detail->color)
                        <p><span class="text-gray-600">{{ trans('messages.color', [], session('locale')) }}:</span> {{ $detail->color->color_name_ar ?? $detail->color->color_name_en ?? 'N/A' }}</p>
                        @endif
                        @if($detail->item_total)
                        <p><span class="text-gray-600">{{ trans('messages.total', [], session('locale')) }}:</span> <span class="font-semibold">{{ number_format($detail->item_total, 2) }} {{ trans('messages.currency', [], session('locale')) ?: 'ر.ع' }}</span></p>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
              @else
              <p class="text-gray-500 text-center py-4">{{ trans('messages.no_items_found', [], session('locale')) ?: 'No items found' }}</p>
              @endif
              @if($order->notes)
              <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm"><span class="font-semibold">{{ trans('messages.order_notes', [], session('locale')) ?: 'Order Notes' }}:</span> {{ $order->notes }}</p>
              </div>
              @endif
            </div>
          </div>
          @empty
          <div class="text-center py-12 text-gray-500">
            <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">shopping_bag</span>
            <p>{{ trans('messages.no_orders_found', [], session('locale')) }}</p>
          </div>
          @endforelse
        </div>
      </div>
    </section>
    @endif

    <!-- No Orders Message -->
    @if($specialOrdersCount == 0 && $posOrdersCount == 0)
    <div class="bg-white border border-pink-100 rounded-2xl p-12 mt-4 text-center">
      <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">shopping_bag</span>
      <h3 class="text-xl font-bold text-gray-700 mb-2">{{ trans('messages.no_orders_found', [], session('locale')) }}</h3>
      <p class="text-gray-500">{{ trans('messages.customer_has_no_orders', [], session('locale')) ?: 'This customer has no orders yet.' }}</p>
    </div>
    @endif

  </div>
</main>

@endsection

