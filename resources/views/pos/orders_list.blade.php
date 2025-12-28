@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.pos_orders_list', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="w-full mx-auto">
        <!-- Page title -->
        <div class="mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.pos_orders_list', [], session('locale')) }}
            </h2>
        </div>

        <!-- Search bar -->
        <div class="w-full mb-6">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
                <input
                    id="search_order"
                    type="text"
                    placeholder="{{ trans('messages.search_order', [], session('locale')) }}"
                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Orders table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.order_number', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.customer_name', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.order_type', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.date', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.time', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.items', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.price', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.discount', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.paid_amount', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap min-w-[150px]">{{ trans('messages.payment_method', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap min-w-[140px]">{{ trans('messages.delivery_status', [], session('locale')) }}</th>
                            <th class="px-3 sm:px-4 md:px-6 py-4 font-semibold text-[var(--text-secondary)] text-center whitespace-nowrap">{{ trans('messages.actions', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="12" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                                {{ trans('messages.loading', [], session('locale')) }}...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <ul id="pagination" class="dress_pagination flex gap-2"></ul>
        </div>
    </div>
</main>

<!-- Product Details Modal -->
<div id="productDetailsModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-premium max-h-[90vh] flex flex-col m-4">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-extrabold text-gray-800">{{ trans('messages.order_details', [], session('locale')) }}</h3>
            <button onclick="closeProductModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div id="productDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Delivery Status Modal -->
<div id="deliveryStatusModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-premium flex flex-col m-4">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-xl font-extrabold text-gray-800">{{ trans('messages.update_delivery_status', [], session('locale')) }}</h3>
            <button onclick="closeDeliveryStatusModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Content -->
        <form id="updateDeliveryStatusForm" class="flex-1 p-6">
            <input type="hidden" id="deliveryStatusOrderId" name="order_id">
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ trans('messages.delivery_status', [], session('locale')) }}
                </label>
                <select id="deliveryStatusSelect" name="delivery_status" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    <option value="not_delivered">{{ trans('messages.not_delivered', [], session('locale')) }}</option>
                    <option value="delivered">{{ trans('messages.delivered', [], session('locale')) }}</option>
                    <option value="pending">{{ trans('messages.pending', [], session('locale')) }}</option>
                    <option value="shipped">{{ trans('messages.shipped', [], session('locale')) }}</option>
                    <option value="under_preparation">{{ trans('messages.under_preparation', [], session('locale')) }}</option>
                    <option value="under_repairing">{{ trans('messages.under_repairing', [], session('locale')) }}</option>
                </select>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeDeliveryStatusModal()" 
                        class="px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                    {{ trans('messages.cancel', [], session('locale')) }}
                </button>
                <button type="submit" 
                        class="px-6 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:bg-[var(--primary-darker)] transition">
                    {{ trans('messages.update', [], session('locale')) }}
                </button>
            </div>
        </form>
    </div>
</div>

@include('layouts.footer')
@endsection
