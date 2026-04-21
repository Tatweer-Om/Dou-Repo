@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.tailor_orders_list', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-6 md:p-8 bg-background-light dark:bg-background-dark overflow-y-auto text-xs md:text-[13px]">
    <div class="w-full max-w-[1400px] mx-auto">
        <!-- Page title -->
        <div class="mb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-[var(--text-primary)] tracking-tight">
                {{ trans('messages.tailor_orders_list', [], session('locale')) }}
            </h2>
        </div>

                <!-- Tailor Selection and Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-5 mb-4 border border-[var(--border-color)] text-[11px] md:text-[12px]">
            <div class="flex flex-col gap-3">
                <!-- Date Filters -->
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            @if(session('locale') == 'ar')
                                تاريخ البداية
                            @else
                                Start Date
                            @endif
                        </label>
                        <input type="date" id="startDate" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[var(--primary-color)] focus:border-transparent text-xs">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            @if(session('locale') == 'ar')
                                تاريخ النهاية
                            @else
                                End Date
                            @endif
                        </label>
                        <input type="date" id="endDate" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[var(--primary-color)] focus:border-transparent text-xs">
                    </div>
                </div>
                
                        <!-- Tailor Selection, List Filter and Export Buttons -->
                        <div class="flex flex-col lg:flex-row gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    {{ trans('messages.select_tailor', [], session('locale')) }}
                                </label>
                                <select id="tailorSelect" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-1 focus:ring-[var(--primary-color)] focus:border-transparent bg-white text-xs">
                                    <option value="">{{ trans('messages.select_tailor', [], session('locale')) }}</option>
                                    @foreach($tailors as $tailor)
                                        <option value="{{ $tailor->id }}">{{ $tailor->tailor_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                    {{ trans('messages.list_number', [], session('locale')) ?: 'List Number' }}
                                </label>
                                <select id="listNumberFilter" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-1 focus:ring-[var(--primary-color)] focus:border-transparent bg-white text-xs">
                                    <option value="">{{ trans('messages.all', [], session('locale')) ?: 'All' }}</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button id="exportPdfBtn" disabled class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 text-xs font-semibold shadow-sm">
                                    <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                                    {{ trans('messages.export_pdf', [], session('locale')) }}
                                </button>
                                <button id="exportExcelBtn" disabled class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 text-xs font-semibold shadow-sm">
                                    <span class="material-symbols-outlined text-base">file_download</span>
                                    {{ trans('messages.export_excel', [], session('locale')) }}
                                </button>
                            </div>
                        </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100" style="max-height: none;">
                <table class="w-full min-w-max text-xs md:text-[11px] text-right" id="ordersTable">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.special_order_number', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.list_number', [], session('locale')) ?: 'List Number' }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.sending_summary_number', [], session('locale')) ?: 'Summary No.' }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.tailor', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.quantity', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.abaya_code', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.design_name', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.abaya_length', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.bust', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.sleeves_length', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.customer_name', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.phone', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap min-w-[200px]">{{ trans('messages.address', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">{{ trans('messages.country', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">{{ trans('messages.status', [], session('locale')) }}</th>
                            <th class="px-3 py-2 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') تاريخ الإرسال @else Sent Date @endif</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="14" class="px-4 py-8 text-center text-gray-500">
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

