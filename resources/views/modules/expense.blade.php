@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.expenses', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-7xl mx-auto">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_expenses', [], session('locale')) }}
            </h2>
            <button @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_expense', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- Search bar -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
              <input
                id="search_expense"
                type="text"
                placeholder="{{ trans('messages.search_expense', [], session('locale')) }}"
                class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Expenses table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)] overflow-x-auto">
            <table class="w-full text-sm text-right min-w-full border-collapse">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.expense_name', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.category', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.amount', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.payment_method', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.expense_date', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)] text-center">{{ trans('messages.actions', [], session('locale')) }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center mt-6">
        <ul id="pagination" class="dress_pagination flex gap-2"></ul>
    </div>

    <!-- Add Expense Modal -->
    <div x-show="open" x-cloak 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_expense_modal" x-ref="expenseModal">
        <div @click.away="open = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-4xl p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    <span x-text="edit ? '{{ trans('messages.edit_expense', [], session('locale')) }}' : '{{ trans('messages.add_expense', [], session('locale')) }}'"></span>
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form id="expense_form" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_name', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.expense_name_placeholder', [], session('locale')) }}"
                            name="expense_name" id="expense_name" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.category', [], session('locale')) }}
                        </label>
                        <select name="category_id" id="category_id"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="">{{ trans('messages.select_category', [], session('locale')) }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.amount', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.001"
                            placeholder="{{ trans('messages.amount_placeholder', [], session('locale')) }}"
                            name="amount" id="amount" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_date', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                            name="expense_date" id="expense_date" required
                            value="{{ date('Y-m-d') }}"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.payment_method', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <select name="account_id" id="account_id" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
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
                        <input type="text"
                            placeholder="{{ trans('messages.reciept_no_placeholder', [], session('locale')) }}"
                            name="reciept_no" id="reciept_no"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.expense_file', [], session('locale')) }}
                        </label>
                        <input type="file"
                            name="expense_file" id="expense_file" accept="image/*,.pdf"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                        <p class="text-xs text-gray-500 mt-1">{{ trans('messages.expense_file_hint', [], session('locale')) }}</p>
                        <div id="expense_file_preview" class="mt-2 hidden">
                            <img id="expense_file_preview_img" src="" alt="Preview" class="max-w-xs rounded-lg border">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.notes', [], session('locale')) }}
                        </label>
                        <textarea
                            placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}"
                            name="notes" id="notes" rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]"></textarea>
                    </div>
                </div>

                <input type="hidden" id="expense_id" name="expense_id">

                <div class="mt-8 pt-6 border-t">
                    <button type="submit"
                        class="w-full bg-[var(--primary-color)] text-white font-bold py-3 rounded-lg hover:bg-[var(--primary-darker)]">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

@include('layouts.footer')
@include('custom_js.expense_js')
@endsection

