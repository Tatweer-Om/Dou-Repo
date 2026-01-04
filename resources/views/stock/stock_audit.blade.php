@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.stock_audit', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.stock_audit', [], session('locale')) }}
            </h2>
        </div>

        <!-- Info notice -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 text-2xl flex-shrink-0">info</span>
            <p class="text-sm text-blue-800 flex-1">
                {{ trans('messages.click_quantity_for_details', [], session('locale')) }}
            </p>
        </div>

        <!-- Search bar -->
        <div class="w-full mb-6">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
                <input
                    id="search_audit"
                    type="text"
                    placeholder="{{ trans('messages.search_stock_audit', [], session('locale')) }}"
                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Audit table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.barcode', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.code', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.design_name', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_added', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_pulled', [], session('locale')) ?: 'Quantity Pulled' }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_sold_pos', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_transferred_out', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_received', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.remaining_quantity', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr>
                            <td colspan="9" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                                {{ trans('messages.loading', [], session('locale')) }}...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <ul id="pagination" class="flex gap-2 items-center"></ul>
        </div>
    </div>

    <!-- Audit Details Modal -->
    <div id="auditDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-bold text-[var(--text-primary)]" id="modalTitle">
                    {{ trans('messages.audit_details', [], session('locale')) }}
                </h3>
                <button onclick="closeAuditModal()" class="text-gray-500 hover:text-gray-700 transition">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4 overflow-y-auto flex-1">
                <div id="modalContent">
                    <p class="text-gray-500 text-center">{{ trans('messages.loading', [], session('locale')) }}...</p>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="closeAuditModal()" class="px-6 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold transition">
                    {{ trans('messages.close', [], session('locale')) }}
                </button>
            </div>
        </div>
    </div>
</main>

@include('layouts.footer')
@include('custom_js.stock_audit_js')
@endsection
