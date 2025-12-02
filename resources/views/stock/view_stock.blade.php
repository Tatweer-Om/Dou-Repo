@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_stock_lang', [], session('locale')) }}</title>
@endpush

<style>
    [x-cloak] {
        display: none !important;
    }
    
    .modal-backdrop.show {
    opacity: 0 !important;
    display: none !important; /* Optional: removes the backdrop entirely */
}
    /* Ensure modal footer stays visible during form submission */
    #quantityModal .modal-footer {
        display: flex !important;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
    }
    
    /* Ensure modal body is scrollable and footer stays at bottom */
    #quantityModal .modal-dialog-scrollable .modal-body {
        overflow-y: auto;
        max-height: calc(100vh - 200px);
    }
    
    /* Prevent modal from closing during form submission */
    #quantityModal.show {
        display: block !important;
    }
    
    /* Ensure modal content structure stays intact */
    #quantityModal .modal-content {
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }
    
    #quantityModal .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
    }
</style>
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
                        <input id="stock_search" type="search"
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
                            <img id="stock_main_image" src="" class="w-full h-96 object-cover" alt="Abaya Image">
                        </div>


                        <div class="space-y-4 text-gray-700">
                            <p><strong>Code:</strong> <span id="abaya_code"></span></p>
                            <p><strong>Design:</strong> <span id="design_name">-</span></p>
                            <p><strong>Description:</strong> <span id="description">-</span></p>
                            <p><strong>Barcode:</strong> <span id="barcode">-</span></p>

                            <p>
                                <strong>Status:</strong>
                                <span id="status" class="font-bold"></span>
                            </p>
                        </div>

                    </div>

                    <hr class="border-dashed border-gray-300">

                    <!-- Quantities Section -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-[var(--primary-color)]">{{ __('messages.quantity_details') }}</h3>

                        <!-- By Size -->
                        <div>
                            <h4 class="font-semibold mb-3">{{ __('messages.by_size') }}</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="size_container">
                                <!-- Sizes will be injected here -->
                            </div>
                        </div>

                        <div>
                            <div>
                                <h4 class="font-semibold mb-3">By Color</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="color_container">
                                    <!-- Sizes will be injected here -->
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold mb-2">By Size and Color</h4>
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




</main>

<!-- Bootstrap Quantity Modal -->

<!-- Enhanced Quantity Modal - Laravel Blade + trans() -->
<!-- Modal -->
<div class="modal fade" id="quantityModal" tabindex="-1" aria-hidden="true" x-data="{ actionType: 'add' }">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

         
            <!-- Form start -->
            <form id="save_qty">
                @csrf

                <!-- Header -->
                <div class="modal-header border-0 bg-gradient">
                    <h5 class="modal-title text-white fw-bold fs-4">
                        <i class="bi bi-box-seam me-2"></i>
                        {{ trans('stock.manage_quantities', [], session('locale')) }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>              

                <!-- Body -->
                <div class="modal-body p-4 p-lg-5" style="background:#fafafa;">

                    <!-- Action Type Tabs -->
                    <div class="d-flex justify-content-center mb-5">
                        <div class="btn-group shadow-sm" role="group">
                            <input type="radio" class="btn-check" name="qtyType" id="add" value="add" x-model="actionType">
                            <label class="btn btn-outline-primary fw-semibold px-5" for="add">
                                <i class="bi bi-plus-circle me-2"></i>
                                {{ trans('stock.add_new', [], session('locale')) }}
                            </label>

                            <input type="radio" class="btn-check" name="qtyType" id="pull" value="pull" x-model="actionType">
                            <label class="btn btn-outline-danger fw-semibold px-5" for="pull">
                                <i class="bi bi-dash-circle me-2"></i>
                                {{ trans('stock.pull_quantity', [], session('locale')) }}
                            </label>
                        </div>
                    </div>

                    <!-- 1. By Size -->
                    <section class="mb-5">
                        <h6 class="fw-bold text-primary mb-3">
                            <span class="badge bg-primary rounded-pill me-2">1</span>
                            {{ trans('stock.by_size', [], session('locale')) }}
                        </h6>
                        <div id="sizecont"></div>
                    </section>

                    <hr class="my-5">

                    <!-- 2. By Size + Color -->
                    <section class="mb-5">
                        <h6 class="fw-bold text-primary mb-3">
                            <span class="badge bg-primary rounded-pill me-2">2</span>
                            {{ trans('stock.by_size_and_color', [], session('locale')) }}
                        </h6>
                        <div id="colorsize_container"></div>
                    </section>

                    <hr class="my-5">

                    <!-- 3. By Color Only -->
                    <section class="mb-5">
                        <h6 class="fw-bold text-primary mb-3">
                            <span class="badge bg-primary rounded-pill me-2">3</span>
                            {{ trans('stock.by_color_only', [], session('locale')) }}
                        </h6>
                        <div id="colorcont"></div>
                    </section>

                    <!-- Pull Reason (only visible when actionType is 'pull') -->
                    <div x-show="actionType === 'pull'" x-transition class="mt-5 p-4 bg-danger bg-opacity-10 border border-danger rounded-3">
                        <label class="form-label fw-bold text-danger mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ trans('stock.pull_reason_required', [], session('locale')) }}
                        </label>
                        <textarea name="pull_reason" class="form-control border-danger focus-ring focus-ring-danger" id="pull_reason" rows="4"
                            placeholder="{{ trans('stock.pull_reason_placeholder', [], session('locale')) }}" ></textarea>
                    </div>

                    <!-- Hidden stock_id input -->
                    <input type="hidden" name="stock_id" id="stock_id" value="">
 <div class="modal-footer border-0 bg-light px-5 py-4" style="display: flex !important; flex-shrink: 0;">
                    <button type="button" class="btn btn-lg btn-outline-secondary px-5" data-bs-dismiss="modal">
                        {{ trans('global.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" class="btn btn-lg px-5 text-white shadow"
                        style="background: linear-gradient(135deg, var(--primary-color), #5e4a9e);">
                        <i class="bi bi-check2-all me-2"></i>
                        {{ trans('global.save_operation', [], session('locale')) }}
                    </button>
                </div>
                </div>

                <!-- Footer -->
               
            </form>
            <!-- Form end -->

        </div>
    </div>
</div>





@include('layouts.footer')
@endsection