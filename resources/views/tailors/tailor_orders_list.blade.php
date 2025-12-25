@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.tailor_orders_list', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.tailor_orders_list', [], session('locale')) }}
            </h2>
        </div>

        <!-- Tailor Selection and Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-[var(--border-color)]">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ trans('messages.select_tailor', [], session('locale')) }}
                    </label>
                    <select id="tailorSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="">{{ trans('messages.select_tailor', [], session('locale')) }}</option>
                        @foreach($tailors as $tailor)
                            <option value="{{ $tailor->id }}">{{ $tailor->tailor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button id="exportPdfBtn" disabled class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        {{ trans('messages.export_pdf', [], session('locale')) }}
                    </button>
                    <button id="exportExcelBtn" disabled class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span class="material-symbols-outlined">file_download</span>
                        {{ trans('messages.export_excel', [], session('locale')) }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100" style="max-height: none;">
                <table class="w-full min-w-max text-sm text-right" id="ordersTable">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.order_number', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.dress_name', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.dress_code', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.size', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.quantity', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.buttons', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.gift', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap min-w-[200px]">{{ trans('messages.notes', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.customer_name', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.phone', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap min-w-[200px]">{{ trans('messages.address', [], session('locale')) }}</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.country', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                {{ trans('messages.select_tailor_to_view_orders', [], session('locale')) }}
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

@include('layouts.footer')
@endsection

