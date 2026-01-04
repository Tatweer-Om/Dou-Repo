@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_stock_lang', [], session('locale')) }}</title>
@endpush

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
<main class="flex-1 p-4 md:p-6"
    x-data="{ showDetails: false, loading: false, showQuantity: false, showFullDetails: false, actionType: 'add', fullDetailsLoading: false }">
    <div class="w-full max-w-[1920px] mx-auto">

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
                        <input id="stock_search" type="search"
                            placeholder="{{ trans('messages.search_placeholder', [], session('locale')) }}"
                            class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
                    </div>
                    <select id="stock_filter" class="shrink-0 rounded-xl border border-pink-200 h-11 text-sm">
                        <option value="all">{{ trans('messages.all', [], session('locale')) }}</option>
                        <option value="available">{{ trans('messages.available', [], session('locale')) }}</option>
                        <option value="low">{{ trans('messages.low', [], session('locale')) }}</option>
                        <option value="out_of_stock">{{ trans('messages.out_of_stock', [], session('locale')) }}</option>
                    </select>
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
        <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition mx-auto">
            <table class="w-full text-sm min-w-full">
                <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800 sticky top-0 z-10">
                    <tr>
                        <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[200px]">{{ trans('messages.image', [], session('locale')) }} / {{ trans('messages.design_name', [], session('locale')) }}</th>
                        <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[100px]">{{ trans('messages.size', [], session('locale')) }}</th>
                        <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[100px]">{{ trans('messages.color', [], session('locale')) }}</th>
                        <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[100px]">{{ trans('messages.quantity', [], session('locale')) }}</th>
                        <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[200px]">{{ trans('messages.actions', [], session('locale')) }}</th>
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
    <div
        x-data="{
        ...stockDetails(),
       
    }"
        @open-stock-details.window="openStockDetails($event.detail)"
        class="relative">
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

            <div @click.away="showDetails = false" @click.stop
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
                            <img id="stock_main_image" src="" class="w-full h-96 object-cover" alt="{{ trans('messages.abaya_image', [], session('locale')) }}">
                        </div>


                        <div class="space-y-4 text-gray-700">
                            <p><strong>{{ trans('messages.code', [], session('locale')) }}:</strong> <span id="abaya_code"></span></p>
                            <p><strong>{{ trans('messages.design', [], session('locale')) }}:</strong> <span id="design_name">-</span></p>
                            <p><strong>{{ trans('messages.description', [], session('locale')) }}:</strong> <span id="description">-</span></p>
                            <p><strong>{{ trans('messages.barcode', [], session('locale')) }}:</strong> <span id="barcode">-</span></p>

                            <p>
                                <strong>{{ trans('messages.status', [], session('locale')) }}:</strong>
                                <span id="status" class="font-bold"></span>
                            </p>
                        </div>

                    </div>

                    <hr class="border-dashed border-gray-300">

                    <!-- Quantities Section -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ __('messages.quantity_details') }}</h3>

                        <!-- By Size -->
                        <!-- <div>
                            <h4 class="font-semibold mb-3">{{ __('messages.by_size') }}</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="size_container">
                            </div>
                        </div> -->

                        <div>
                            <!-- <div>
                                <h4 class="font-semibold mb-3">By Color</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="color_container">
                                </div>
                            </div> -->

                            <div>
                                <h4 class="font-semibold mb-2">{{ trans('messages.by_size_and_color', [], session('locale')) }}</h4>
                                <div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="size_color_container">
                                        <!-- Dynamic items will be injected here -->
                                    </div>
                                </div>


                            </div>
                        </div>

                        <!-- Footer -->
                        <!-- <div class="p-5 border-t bg-gray-50 text-center">
                            <button @click="showDetails = false"
                                class="px-8 py-3 rounded-xl bg-[var(--primary-color)] text-white font-bold hover:opacity-90 transition">
                                {{ __('messages.close') }}
                            </button>
                        </div> -->
                    </div>
                </div>






            </div>

        </div>
     

    </div>




    <!-- Alpine.js Quantity Modal -->
    <div x-show="showQuantity"
        x-transition
        x-cloak
        class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4 overflow-y-auto"
        @keydown.escape.window="showQuantity = false">
        <div @click.away="showQuantity = false" @click.stop
            class="bg-white w-full max-w-6xl my-8 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[calc(100vh-4rem)]">
            
            <!-- Form start -->
            <form id="save_qty" class="flex flex-col h-full">
                @csrf

                <!-- Header -->
                <div class="flex justify-between items-center p-4 md:p-5 border-b bg-gradient-to-r from-[var(--primary-color)] to-[#5e4a9e] flex-shrink-0">
                    <h5 class="text-white text-lg md:text-xl font-bold flex items-center">
                        <span class="material-symbols-outlined me-2">inventory_2</span>
                        {{ trans('messages.manage_quantities', [], session('locale')) }}
                    </h5>
                    <button type="button" @click="showQuantity = false" class="text-white hover:text-gray-200 transition">
                        <span class="material-symbols-outlined text-2xl md:text-3xl">close</span>
                    </button>
                </div>

                <!-- Body - Scrollable -->
                <div class="p-4 md:p-6 overflow-y-auto flex-1 bg-gray-50 min-h-0" style="max-height: calc(90vh - 180px);">

                    <!-- Action Type Tabs -->
                    <div class="flex justify-center mb-4 md:mb-6 sticky top-0 bg-gray-50 pb-4 z-10">
                        <div class="inline-flex rounded-lg shadow-sm border border-gray-200 bg-white overflow-hidden" role="group">
                            <input type="radio" class="hidden" name="qtyType" id="add" value="add" x-model="actionType">
                            <label for="add" 
                                :class="actionType === 'add' ? 'bg-[var(--primary-color)] text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-4 md:px-5 py-2 md:py-3 text-sm md:text-base font-semibold cursor-pointer transition-colors flex items-center border-r border-gray-200">
                                <span class="material-symbols-outlined me-1 md:me-2 text-base md:text-lg">add_circle</span>
                                {{ trans('messages.add_new', [], session('locale')) }}
                            </label>

                            <input type="radio" class="hidden" name="qtyType" id="pull" value="pull" x-model="actionType">
                            <label for="pull"
                                :class="actionType === 'pull' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-4 md:px-5 py-2 md:py-3 text-sm md:text-base font-semibold cursor-pointer transition-colors flex items-center">
                                <span class="material-symbols-outlined me-1 md:me-2 text-base md:text-lg">remove_circle</span>
                                {{ trans('messages.pull_quantity', [], session('locale')) }}
                            </label>
                        </div>
                    </div>

                    <!-- 1. By Size -->
                    <section class="mb-4 md:mb-6 hidden">
                        <h6 class="font-bold text-[var(--primary-color)] mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <span class="bg-[var(--primary-color)] text-white rounded-full w-5 h-5 md:w-6 md:h-6 flex items-center justify-center text-xs mr-2">1</span>
                            {{ trans('messages.by_size', [], session('locale')) }}
                        </h6>
                        <div id="sizecont"></div>
                    </section>

                    <hr class="my-4 md:my-6 border-gray-300">

                    <!-- 2. By Size + Color -->
                    <section class="mb-4 md:mb-6">
                        <h6 class="font-bold text-[var(--primary-color)] mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <span class="bg-[var(--primary-color)] text-white rounded-full w-5 h-5 md:w-6 md:h-6 flex items-center justify-center text-xs mr-2">2</span>
                            {{ trans('messages.by_size_color', [], session('locale')) }}
                        </h6>
                        <div id="colorsize_container"></div>
                    </section>

                    <hr class="my-4 md:my-6 border-gray-300">

                    <!-- 3. By Color Only -->
                    <section class="mb-4 md:mb-6 hidden">
                        <h6 class="font-bold text-[var(--primary-color)] mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <span class="bg-[var(--primary-color)] text-white rounded-full w-5 h-5 md:w-6 md:h-6 flex items-center justify-center text-xs mr-2">3</span>
                            {{ trans('messages.by_color_only', [], session('locale')) }}
                        </h6>
                        <div id="colorcont"></div>
                    </section>

                    <!-- Pull Reason (only visible when actionType is 'pull') -->
                    <div x-show="actionType === 'pull'" 
                        x-transition
                        class="mt-4 md:mt-6 p-3 md:p-4 bg-red-50 border-2 border-red-200 rounded-xl">
                        <label class="block font-bold text-red-600 mb-2 md:mb-3 flex items-center text-sm md:text-base">
                            <span class="material-symbols-outlined me-2 text-base md:text-lg">warning</span>
                            {{ trans('messages.pull_reason_required', [], session('locale')) }}
                        </label>
                        <textarea name="pull_reason" 
                            id="pull_reason" 
                            rows="3"
                            placeholder="{{ trans('messages.pull_reason_placeholder', [], session('locale')) }}"
                            class="w-full border-2 border-red-300 rounded-lg p-2 md:p-3 text-sm md:text-base focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition resize-none"></textarea>
                    </div>

                    <!-- Hidden stock_id input -->
                    <input type="hidden" name="stock_id" id="stock_id" value="">
                </div>

                <!-- Footer - Always Visible -->
                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 p-4 md:p-5 border-t bg-white flex-shrink-0 shadow-lg">
                    <button type="button" 
                        @click="showQuantity = false"
                        class="w-full sm:w-auto px-4 md:px-5 py-2.5 md:py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50 transition text-sm md:text-base">
                        {{ trans('messages.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" 
                        class="w-full sm:w-auto px-4 md:px-5 py-2.5 md:py-3 rounded-lg text-white font-semibold shadow-md hover:shadow-lg transition text-sm md:text-base"
                        style="background: linear-gradient(135deg, var(--primary-color), #5e4a9e);">
                        <span class="material-symbols-outlined align-middle me-2 text-base">check</span>
                        {{ trans('messages.save_operation', [], session('locale')) }}
                    </button>
                </div>
        </form>
        <!-- Form end -->
    </div>
</div>





    <!-- Full Stock Details Modal - Alpine.js -->
   <div x-show="showFullDetails"
    x-transition
    x-cloak
    class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4"
    @keydown.escape.window="showFullDetails = false">

    <div @click.away="showFullDetails = false" @click.stop
        class="bg-white w-full max-w-6xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="flex justify-between items-center p-5 border-b bg-gradient-to-r from-pink-50 to-purple-50 flex-shrink-0">
            <h5 class="text-[var(--primary-color)] text-xl font-bold">
                {{ trans('messages.abaya_details', [], session('locale')) }}:
                <span id="full_modal_abaya_code">...</span>
            </h5>
            <button type="button" @click="showFullDetails = false"
                class="text-gray-500 hover:text-gray-800 transition">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
        </div>

        <!-- Loader -->
        <div x-show="fullDetailsLoading" class="flex flex-col items-center justify-center p-12">
            <div class="border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-16 h-16 animate-spin mb-4"></div>
            <p class="text-gray-600 font-semibold">
                {{ trans('messages.loading_details', [], session('locale')) }}
            </p>
        </div>

        <!-- Body -->
        <div x-show="!fullDetailsLoading"
            class="p-4 md:p-6 overflow-y-auto flex-1"
            id="fullStockDetailsBody">

            <!-- Total Quantity -->
            <div class="text-center mb-6">
                <div class="inline-block p-4 rounded-2xl shadow-md"
                    style="background: linear-gradient(135deg, var(--primary-color), #5e4a9e);">
                    <h6 class="text-white mb-1 font-semibold text-sm">
                        {{ trans('messages.total_quantity', [], session('locale')) }}
                    </h6>
                    <h3 class="text-white mb-0 font-bold text-3xl" id="full_total_quantity">0</h3>
                </div>
            </div>

            <!-- Images -->
            <div class="mb-6">
                <h6 class="font-bold text-[var(--primary-color)] mb-3 flex items-center">
                    <span class="material-symbols-outlined me-2">images</span>
                    {{ trans('messages.images', [], session('locale')) }}
                </h6>
                <div id="full_stock_images_container"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>
            </div>

            <hr class="my-6 border-dashed border-gray-300">

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h6 class="font-bold text-[var(--primary-color)] mb-3">
                        {{ trans('messages.basic_info', [], session('locale')) }}
                    </h6>

                    <p><strong>{{ trans('messages.code', [], session('locale')) }}:</strong>
                        <span id="full_abaya_code">-</span></p>

                    <p><strong>{{ trans('messages.design', [], session('locale')) }}:</strong>
                        <span id="full_design_name">-</span></p>

                    <p><strong>{{ trans('messages.description', [], session('locale')) }}:</strong>
                        <span id="full_description">-</span></p>

                    <p><strong>{{ trans('messages.barcode', [], session('locale')) }}:</strong>
                        <span id="full_barcode">-</span></p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h6 class="font-bold text-[var(--primary-color)] mb-3">
                        {{ trans('messages.price_info', [], session('locale')) }}
                    </h6>

                    <p><strong>{{ trans('messages.cost_price', [], session('locale')) }}:</strong>
                        <span id="full_cost_price">-</span></p>

                    <p><strong>{{ trans('messages.sales_price', [], session('locale')) }}:</strong>
                        <span id="full_sales_price">-</span></p>

                    <p><strong>{{ trans('messages.tailor_charges', [], session('locale')) }}:</strong>
                        <span id="full_tailor_charges">-</span></p>

                    <p><strong>{{ trans('messages.tailors', [], session('locale')) }}:</strong>
                        <span id="full_tailor_names">-</span></p>
                </div>
            </div>

            <hr class="my-6 border-dashed border-gray-300">

            <!-- Color / Size -->
            <div>
                <h6 class="font-bold text-[var(--primary-color)] mb-3 flex items-center">
                    <span class="material-symbols-outlined me-2">palette</span>
                    {{ trans('messages.by_color_and_size', [], session('locale')) }}
                </h6>

                <div id="full_size_color_container"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-5 border-t bg-gray-50">
            <button @click="showFullDetails = false"
                class="px-5 py-3 rounded-lg border bg-white text-gray-700 font-semibold hover:bg-gray-50">
                {{ trans('messages.close', [], session('locale')) }}
            </button>
        </div>
    </div>
</div>


</main>

@include('layouts.footer')
@endsection