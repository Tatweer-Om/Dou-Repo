@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.user_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false, del: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-4xl mx-auto">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_users', [], session('locale')) }}
            </h2>
            <button @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_user', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- شريط بحث احترافي -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
                <input
                    id="search_user"
                    type="text"
                    placeholder="{{ trans('messages.search_user', [], session('locale')) }}"
                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>


        <!-- users table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.user_name', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.user_phone', [], session('locale')) }}
                        </th>
                      
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.user_email', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)] text-center">
                            {{ trans('messages.actions', [], session('locale')) }}
                        </th>
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

    <!-- Add user Modal -->
    <div x-show="open" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4 overflow-y-auto" id="add_user_modal" x-ref="userModal">
        <div @click.away="open = false"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl my-8 flex flex-col max-h-[90vh]">
            <!-- Modal Header (Fixed) -->
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ trans('messages.add_user', [], session('locale')) }}
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            
            <!-- Modal Body (Scrollable) -->
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <form id="user_form">
                    @csrf
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="space-y-5">
                        <!-- Basic Information Section -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
                                {{ trans('messages.basic_information', [], session('locale')) ?: 'Basic Information' }}
                            </h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        {{ trans('messages.user_name', [], session('locale')) }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                        placeholder="{{ trans('messages.user_name_placeholder_en', [], session('locale')) }}"
                                        name="user_name" id="user_name"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        {{ trans('messages.user_phone', [], session('locale')) }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                        placeholder="{{ trans('messages.user_phone_placeholder', [], session('locale')) }}"
                                        name="user_phone" id="user_phone"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        {{ trans('messages.user_email', [], session('locale')) }}
                                    </label>
                                    <input type="email"
                                        placeholder="{{ trans('messages.user_email_placeholder', [], session('locale')) }}"
                                        name="user_email" id="user_email"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        {{ trans('messages.user_password', [], session('locale')) }}
                                    </label>
                                    <input type="password"
                                        placeholder="{{ trans('messages.user_password_placeholder', [], session('locale')) }}"
                                        name="user_password" id="user_password"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Section -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    {{ trans('messages.permissions', [], session('locale')) ?: 'Permissions' }}
                                </h3>
                                <button type="button" id="toggleAllPermissions" class="text-sm text-[var(--primary-color)] hover:underline font-medium transition">
                                    {{ trans('messages.select_all', [], session('locale')) ?: 'Select All' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto">
                                @php
                                    $permissions = [
                                        1 => trans('messages.user', [], session('locale')) ?: 'User',
                                        2 => trans('messages.accounts', [], session('locale')) ?: 'Account',
                                        3 => trans('messages.expenses', [], session('locale')) ?: 'Expense',
                                        4 => trans('messages.sms_panel', [], session('locale')) ?: 'SMS',
                                        5 => trans('messages.special_orders', [], session('locale')) ?: 'Special Orders',
                                        6 => trans('messages.transfer_stock', [], session('locale')) ?: 'Manage Quantity',
                                        7 => trans('messages.tailor_orders', [], session('locale')) ?: 'Tailor Orders',
                                        8 => trans('messages.pos', [], session('locale')) ?: 'POS',
                                        9 => trans('messages.view_stock_lang', [], session('locale')) ?: 'Stock',
                                        10 => trans('messages.reports', [], session('locale')) ?: 'Reports',
                                        11 => trans('messages.boutique_management', [], session('locale')) ?: 'Boutique',
                                        12 => trans('messages.tailors', [], session('locale')) ?: 'Tailor',
                                    ];
                                @endphp
                                @foreach($permissions as $key => $label)
                                    <label class="flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition cursor-pointer group border border-transparent hover:border-[var(--primary-color)]/30">
                                        <input 
                                            type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $key }}" 
                                            id="permission_{{ $key }}"
                                            class="w-4 h-4 text-[var(--primary-color)] bg-gray-100 border-gray-300 rounded focus:ring-[var(--primary-color)] focus:ring-2 cursor-pointer transition appearance-none checked:bg-[var(--primary-color)] checked:border-[var(--primary-color)] relative">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-[var(--primary-color)] transition flex-1">
                                            {{ $label }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
                                {{ trans('messages.additional_information', [], session('locale')) ?: 'Additional Information' }}
                            </h3>
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                    {{ trans('messages.notes', [], session('locale')) }}
                                </label>
                                <textarea
                                    placeholder="{{ trans('messages.notes_placeholder_en', [], session('locale')) }}"
                                    name="notes" id="notes"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 resize-none"
                                    rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer (Fixed) -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex gap-3">
                    <button type="button" @click="open = false" id="close_modal_btn"
                        class="flex-1 px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        {{ trans('messages.cancel', [], session('locale')) ?: 'Cancel' }}
                    </button>
                    <button type="submit" form="user_form"
                        class="flex-1 px-4 py-2.5 bg-[var(--primary-color)] text-white rounded-lg font-medium hover:bg-[var(--primary-darker)] transition shadow-sm">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>



@include('layouts.footer')
@endsection