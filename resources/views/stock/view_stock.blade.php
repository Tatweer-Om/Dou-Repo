@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_stock_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
    x-data="{ showDetails: false, loading: false, showQuantity: false, actionType: 'add' }">
    <div class="w-full max-w-screen-xl xl:pr-8 xl:pl-64 mx-auto">

        <!-- Page title and add button -->
        <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.manage_abayas', [], session('locale')) }}
            </h2>
            <a href="{{url('stock')}}"
                class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <span class="material-symbols-outlined me-1">add</span>
                {{ trans('messages.add_abaya', [], session('locale')) }}
            </a>
        </div>

        <!-- Search and filters -->
        <div class="sticky top-[var(--header-h,64px)] z-10 bg-white/80 backdrop-blur border border-pink-100 rounded-2xl shadow-sm">
            <div class="py-3 px-4">
                <div class="flex flex-wrap items-center gap-2 overflow-x-auto no-scrollbar">
                    <div class="flex-1 min-w-[45%]">
                        <input id="q" type="search"
                            placeholder="{{ trans('messages.search_placeholder', [], session('locale')) }}"
                            class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
                    </div>
                    <select class="shrink-0 rounded-xl border border-pink-200 h-11 text-sm">
                        <option>{{ trans('messages.all', [], session('locale')) }}</option>
                        <option>{{ trans('messages.available', [], session('locale')) }}</option>
                        <option>{{ trans('messages.low', [], session('locale')) }}</option>
                        <option>{{ trans('messages.out_of_stock', [], session('locale')) }}</option>
                        <option>{{ trans('messages.hidden', [], session('locale')) }}</option>
                    </select>
                    <button class="shrink-0 inline-flex items-center gap-1 rounded-xl px-3 h-11 text-sm text-white bg-[var(--primary-color)] hover:bg-pink-700 transition-all">
                        <span class="material-symbols-outlined text-base">tune</span>
                        {{ trans('messages.filter', [], session('locale')) }}
                    </button>
                </div>
            </div>
        </div>


        <!-- Mobile cards -->
        <section class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:hidden gap-4">
            @for ($i = 1; $i <= 2; $i++)
                <div class="bg-white rounded-xl shadow-sm border border-pink-100 p-4 flex flex-col gap-3">
                <div class="flex gap-4">
                    <div class="w-20 h-24 rounded-md overflow-hidden bg-gray-100 flex-shrink-0">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvKg5AhaDdRqA3r4CQmvGTzP9_cvocRFo_JpwXjGANrU-NTxnLJbPXHosBJvcOJrOMF7iniPDAqlDISIoKa9vYPlxQl1fxFUf_wWcg-2ZWZ4zVtj8DtYntIcmMCef6Gi9kc2-SNeJuOFhmVe3ktBod2zxXdlJVBktsokamFz6WtCj96iytmlQLinBdB_5yxzeepfYJBESQ9mj3dmkh_xJ9jv55Un9VL_VDKXordI9gSug-gM3t_dTLQp4G7Bzh8K5I0OZICpGkG5M"
                            alt="{{ trans('messages.abaya_image', [], session('locale')) }}" class="w-full h-full object-cover" />
                    </div>
                    <div class="flex-1 text-sm">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold text-gray-900">ABY10{{ $i }}</h3>
                            <span class="text-[var(--primary-color)] font-semibold">
                                {{ trans('messages.size', [], session('locale')) }}: M
                            </span>
                        </div>
                        <p class="text-gray-600">
                            {{ trans('messages.color_material', [], session('locale')) }}
                        </p>
                        <p class="text-gray-600">
                            {{ trans('messages.quantity', [], session('locale')) }}: {{ 8 + $i }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 border-t pt-3">
                    <div class="flex justify-around text-xs font-semibold text-gray-600">
                        <button @click="loading = true; setTimeout(() => { loading = false; showDetails = true }, 800)"
                            class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition">
                            <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full">info</span>
                            {{ trans('messages.details', [], session('locale')) }}
                        </button>

                        <button @click="showQuantity = true"
                            class="flex flex-col items-center gap-1 hover:text-green-600 transition">
                            <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                            {{ trans('messages.enter_quantity', [], session('locale')) }}
                        </button>

                        <button class="flex flex-col items-center gap-1 hover:text-blue-500 transition">
                            <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full">edit</span>
                            {{ trans('messages.edit', [], session('locale')) }}
                        </button>

                        <button class="flex flex-col items-center gap-1 hover:text-red-500 transition">
                            <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full">delete</span>
                            {{ trans('messages.delete', [], session('locale')) }}
                        </button>
                    </div>
                </div>
    </div>
    @endfor
    </section>

    <!-- Desktop table -->
    <section class="hidden xl:block mt-6">
        <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition">
            <table class="w-full text-sm min-w-[1024px]">
                <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                    <tr>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.image', [], session('locale')) }}</th>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                        <th class="text-right px-3 py-3 font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.actions', [], session('locale')) }}</th>
                    </tr>
                </thead>

                <tbody id="desktop_stock_body"></tbody>
            </table>
            <div id="mobile_stock_cards" class="md:hidden"></div>

            <!-- Pagination -->
        </div>
    </section>
    <ul id="stock_pagination" class="flex justify-center gap-2 mt-4"></ul>

    </div>

    <!-- Loader -->
    <!-- Loading Overlay -->
    <!-- FULL SCREEN LOADER -->
    <div x-data="stockDetails()" @open-stock-details.window="openStockDetails($event.detail)" class="relative">

        <div x-show="loading" class="fixed inset-0 flex flex-col items-center justify-center bg-black/70 z-[9999]">
            <div class="loader border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-16 h-16 animate-spin mb-4"></div>
            <p class="text-white text-lg font-bold">{{ __('messages.loading_details') }}</p>
        </div>

        <!-- MODAL -->
        <div x-show="showDetails"
            x-transition
            x-cloak
            class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4"
            @keydown.escape.window="showDetails = false">

            <div @click.away="showDetails = false"
                class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="flex justify-between items-center p-5 border-b bg-gradient-to-r from-pink-50 to-purple-50">
                    <h2 class="text-xl font-bold text-[var(--primary-color)]">
                        {{ __('messages.abaya_details') }}: <span x-text="stock?.abaya_code || '...'"></span>
                    </h2>
                    <button @click="showDetails = false" class="text-gray-500 hover:text-gray-800">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>

                <!-- Scrollable Body -->
                <div class="p-6 overflow-y-auto flex-1 space-y-8" x-show="!loading">

                    <!-- Image + Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-2xl overflow-hidden shadow-lg">
                            <img :src="mainImage()" class="w-full h-96 object-cover" alt="{{ __('messages.abaya_image') }}">
                        </div>

                        <div class="space-y-4 text-gray-700">
                            <p><strong>{{ __('messages.code') }}:</strong> <span x-text="stock?.abaya_code"></span></p>
                            <p><strong>{{ __('messages.design') }}:</strong> <span x-text="stock?.design_name || '-'"></span></p>
                            <p><strong>{{ __('messages.description') }}:</strong> <span x-text="stock?.abaya_notes || '-'"></span></p>
                            <!-- <p><strong>{{ __('messages.status') }}:</strong>
                            <span class="text-green-600 font-bold" x-show="stock?.total_qty > 0">{{ __('messages.available') }}</span>
                            <span class="text-red-600 font-bold" x-show="stock?.total_qty == 0">{{ __('messages.not_available') }}</span>
                        </p> -->
                        </div>
                    </div>

                    <hr class="border-dashed border-gray-300">

                    <!-- Quantities Section -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ __('messages.quantity_details') }}</h3>

                        <!-- By Size -->
                        <template x-if="stock?.sizes?.length">
                            <div>
                                <h4 class="font-semibold mb-3">{{ __('messages.by_size') }}</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <template x-for="s in stock.sizes" :key="s.size_id">
                                        <div class="p-4 border rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 text-center">
                                            <div class="text-sm font-bold text-gray-600" x-text="s.size.size_name_ar"></div>
                                            <div class="text-2xl font-bold text-[var(--primary-color)] mt-1" x-text="s.qty"></div>
                                            <div class="text-xs text-gray-500">{{ __('messages.pieces') }}</div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- By Color + Size -->
                        <template x-if="stock?.colors?.length">
                            <div>
                                <h4 class="font-semibold mb-3">{{ __('messages.by_color_and_size') }}</h4>
                                <div class="space-y-3">
                                    <template x-for="c in stock.colors" :key="c.color_id">
                                        <div class="border rounded-xl p-4 bg-gray-50">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full border-2" :style="'background-color:' + c.color.color_code"></div>
                                                    <div>
                                                        <div class="font-bold" x-text="c.color.color_name_ar"></div>
                                                        <div class="text-sm text-gray-600">
                                                            <template x-for="s in c.pivot_sizes || stock.sizes">
                                                                <span x-text="s.size.size_name_ar + ': ' + s.qty + ' | '"></span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-xl font-bold text-[var(--primary-color)]" x-text="c.total_qty || c.qty"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-5 border-t bg-gray-50 text-center">
                    <button @click="showDetails = false"
                        class="px-8 py-3 rounded-xl bg-[var(--primary-color)] text-white font-bold hover:opacity-90 transition">
                        {{ __('messages.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: إدخال الكميات -->
    <div x-show="showQuantity" x-transition.opacity x-cloak
        class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center">
        <div @click.away="showQuantity = false"
            class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl transform transition-all duration-300 overflow-hidden">

            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-bold text-[var(--primary-color)]">{{ trans('messages.manage_abaya_quantity', [], session('locale')) }}</h2>
                <button @click="showQuantity = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>

            <!-- Action Type -->
            <div class="flex justify-center gap-6 pt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="qtyType" value="add" x-model="actionType">
                    <span>{{ trans('messages.new_entry', [], session('locale')) }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="qtyType" value="pull" x-model="actionType">
                    <span>{{ trans('messages.pull_quantity', [], session('locale')) }}</span>
                </label>
            </div>

            <!-- Quantity Sections -->
            <div class="p-6 space-y-10 max-h-[70vh] overflow-y-auto text-sm text-gray-700">

                <!-- 1️⃣ By Size -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-[var(--primary-color)]">{{ trans('messages.by_size', [], session('locale')) }}</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <template x-for="size in ['S','M','L','XL','XXL']" :key="size">
                            <div class="flex flex-col border rounded-lg p-3 shadow-sm">
                                <label class="font-bold mb-1" x-text="'{{ trans('messages.size', [], session('locale')) }} ' + size"></label>
                                <input type="number" min="0" placeholder="{{ trans('messages.quantity', [], session('locale')) }}"
                                    class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center">
                            </div>
                        </template>
                    </div>
                </div>

                <hr class="border-dashed">

                <!-- 2️⃣ By Size & Color -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-[var(--primary-color)]">{{ trans('messages.by_size_color', [], session('locale')) }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="(combo, index) in [
              {size:'S', color:'Black', code:'#000'}, 
              {size:'M', color:'Beige', code:'#f5deb3'}, 
              {size:'L', color:'Gray', code:'#b0b0b0'}
          ]" :key="index">
                            <div class="border rounded-lg p-3 shadow-sm">
                                <div class="flex justify-between mb-2">
                                    <span class="font-semibold" x-text="'{{ trans('messages.size', [], session('locale')) }}: ' + combo.size"></span>
                                    <div class="flex items-center gap-1">
                                        <span class="w-4 h-4 rounded-full border" :style="'background-color:' + combo.code"></span>
                                        <span class="font-semibold" x-text="combo.color"></span>
                                    </div>
                                </div>
                                <input type="number" min="0" placeholder="{{ trans('messages.quantity', [], session('locale')) }}"
                                    class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center w-full">
                            </div>
                        </template>
                    </div>
                </div>

                <hr class="border-dashed">

                <!-- 3️⃣ By Color -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-[var(--primary-color)]">{{ trans('messages.by_color', [], session('locale')) }}</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <template x-for="color in [
              {name:'Black', code:'#000'}, 
              {name:'Beige', code:'#f5deb3'}, 
              {name:'Gray', code:'#b0b0b0'}, 
              {name:'Navy', code:'#001f3f'}, 
              {name:'Green', code:'#006400'}
          ]" :key="color.name">
                            <div class="flex flex-col border rounded-lg p-3 shadow-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-4 h-4 rounded-full border" :style="'background-color:' + color.code"></span>
                                    <span class="font-bold" x-text="color.name"></span>
                                </div>
                                <input type="number" min="0" placeholder="{{ trans('messages.quantity', [], session('locale')) }}"
                                    class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Reason for Pull -->
                <div x-show="actionType === 'pull'" class="space-y-2 mt-4">
                    <label class="font-semibold text-red-600">{{ trans('messages.pull_reason', [], session('locale')) }}</label>
                    <textarea class="w-full h-20 rounded-lg border border-red-200 focus:ring-2 focus:ring-red-400 text-sm p-2"
                        placeholder="{{ trans('messages.pull_reason_placeholder', [], session('locale')) }}"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t flex justify-end gap-3 bg-gray-50">
                <button @click="showQuantity = false"
                    class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition text-gray-700 font-semibold">
                    {{ trans('messages.cancel', [], session('locale')) }}
                </button>
                <button class="px-6 py-2 rounded-lg bg-[var(--primary-color)] text-white font-bold hover:bg-opacity-90 transition">
                    {{ trans('messages.save_action', [], session('locale')) }}
                </button>
            </div>
        </div>
    </div>


</main>

@include('layouts.footer')
@endsection