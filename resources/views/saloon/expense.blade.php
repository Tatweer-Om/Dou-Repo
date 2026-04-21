@extends('layouts.salon_header')

@section('main')
<style>[x-cloak]{display:none!important}</style>

<main class="min-h-screen bg-surface" x-data="{ edit: false }">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex justify-between items-center w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex items-center gap-6">
            <button
                type="button"
                @click="$store.modals.saloonExpense = true; edit = false"
                class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200">
                {{ trans('messages.saloon_add_expense', [], session('locale')) }}
            </button>
        </div>
    </header>

    <div class="px-8 py-10 space-y-8">
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="overflow-x-auto" id="data-table">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.expense_name', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.category', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.amount', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.payment_method', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.expense_date', [], session('locale')) }}</th>
                            <th class="px-4 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface">
                        @forelse($expenses as $index => $exp)
                            <tr data-id="{{ $exp->id }}" class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-4 py-6">{{ $expenses->firstItem() + $index }}</td>
                                <td class="px-4 py-6 font-medium">{{ $exp->expense_name }}</td>
                                <td class="px-4 py-6">{{ $exp->category->category_name ?? '—' }}</td>
                                <td class="px-4 py-6 text-right font-semibold text-red-600">{{ number_format((float) $exp->amount, 3) }}</td>
                                <td class="px-4 py-6">{{ $exp->account->account_name ?? '—' }}</td>
                                <td class="px-4 py-6">{{ $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '' }}</td>
                                <td class="px-4 py-6 text-center whitespace-nowrap">
                                    <button type="button" class="edit-btn icon-btn">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    @if(!empty($exp->expense_image))
                                        <button type="button" class="view-receipt-btn icon-btn hover:text-blue-500" data-file="{{ $exp->expense_image }}" title="Receipt">
                                            <span class="material-symbols-outlined">receipt</span>
                                        </button>
                                    @endif
                                    <button type="button" class="delete-btn icon-btn hover:text-red-500">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-6 text-center">
                                    {{ trans('messages.saloon_no_expenses', [], session('locale')) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-surface flex-wrap gap-4" id="data-pagination">
                <span class="text-xs text-on-surface-variant font-medium">
                    {{ trans('messages.saloon_expense_pagination', ['from' => $expenses->firstItem() ?? 0, 'to' => $expenses->lastItem() ?? 0, 'total' => $expenses->total()], session('locale')) }}
                </span>

                <div class="flex items-center gap-2 flex-wrap">
                    @if ($expenses->onFirstPage())
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $expenses->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($expenses->getUrlRange(1, $expenses->lastPage()) as $page => $url)
                        @if ($page == $expenses->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant dress-page-link">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($expenses->hasMorePages())
                        <a href="{{ $expenses->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </span>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <div
        x-show="$store.modals.saloonExpense"
        x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4"
        id="saloon_expense_modal">

        <div
            @click.away="$store.modals.saloonExpense = false; edit = false"
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-4xl p-6 sm:p-8 max-h-[90vh] overflow-y-auto">

            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold" x-text="edit ? @json(trans('messages.saloon_edit_expense', [], session('locale'))) : @json(trans('messages.saloon_add_expense', [], session('locale')))"></h1>

                <button
                    type="button"
                    @click="$store.modals.saloonExpense = false; edit = false"
                    class="text-gray-400 hover:text-gray-600"
                    id="close_saloon_expense_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>

            <form id="saloon_expense_form" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_name', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="expense_name" id="se_expense_name" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.category', [], session('locale')) }}
                        </label>
                        <select name="salon_expense_category_id" id="se_category_id"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                            <option value="">{{ trans('messages.select_category', [], session('locale')) }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.amount', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.001" min="0.001" name="amount" id="se_amount" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_date', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="expense_date" id="se_expense_date" required value="{{ date('Y-m-d') }}"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.payment_method', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select name="account_id" id="se_account_id" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                            <option value="">{{ trans('messages.select_account', [], session('locale')) }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->account_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.reciept_no', [], session('locale')) }}
                        </label>
                        <input type="text" name="reciept_no" id="se_reciept_no"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_file', [], session('locale')) }}
                        </label>
                        <input type="file" name="expense_file" id="se_expense_file" accept="image/*,.pdf"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ trans('messages.expense_file_hint', [], session('locale')) }}</p>
                        <div id="se_expense_file_preview" class="mt-2 hidden">
                            <img id="se_expense_file_preview_img" src="" alt="Preview" class="max-w-xs rounded-lg border">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.notes_lang', [], session('locale')) }}
                        </label>
                        <textarea name="notes" id="se_notes" rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-primary dark:bg-gray-800 dark:border-gray-600"></textarea>
                    </div>
                </div>

                <input type="hidden" id="se_expense_id" name="se_expense_id">

                <div class="mt-8 pt-6 border-t">
                    <button type="submit"
                        class="w-full bg-primary text-on-primary font-bold py-3 rounded-lg hover:opacity-90">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('layouts.salon_footer')
@include('custom_js.saloon_expense_js')
@endsection
