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
            <button type="button" @click="open = true; window.resetUserForm && window.resetUserForm()"
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
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">
                            {{ trans('messages.user_table_type', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.user_table_staff', [], session('locale')) }}
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

    @php
        $loc = session('locale');
        $boutiquePermissionMap = [
            1 => trans('messages.user', [], $loc) ?: 'User',
            2 => trans('messages.accounts', [], $loc) ?: 'Account',
            3 => trans('messages.expenses', [], $loc) ?: 'Expense',
            4 => trans('messages.sms_panel', [], $loc) ?: 'SMS',
            5 => trans('messages.special_orders', [], $loc) ?: 'Special Orders',
            6 => trans('messages.transfer_stock', [], $loc) ?: 'Manage Quantity',
            7 => trans('messages.tailor_orders', [], $loc) ?: 'Tailor Orders',
            8 => trans('messages.pos', [], $loc) ?: 'POS',
            9 => trans('messages.view_stock_lang', [], $loc) ?: 'Stock',
            10 => trans('messages.reports', [], $loc) ?: 'Reports',
            11 => trans('messages.boutique_management', [], $loc) ?: 'Boutique',
            12 => trans('messages.tailors', [], $loc) ?: 'Tailor',
            13 => trans('messages.delete_order', [], $loc) ?: 'Delete Order',
        ];
        $salonPermissionMap = [
            14 => trans('messages.perm_salon_dashboard', [], $loc),
            15 => trans('messages.perm_salon_bookings_list', [], $loc),
            16 => trans('messages.perm_salon_booking_page', [], $loc),
            17 => trans('messages.perm_salon_view_bookings', [], $loc),
            18 => trans('messages.perm_salon_booking_management', [], $loc),
            19 => trans('messages.perm_salon_team', [], $loc),
            20 => trans('messages.perm_salon_staff', [], $loc),
            21 => trans('messages.perm_salon_tools', [], $loc),
            22 => trans('messages.perm_salon_expense_category', [], $loc),
            23 => trans('messages.perm_salon_expense', [], $loc),
            24 => trans('messages.perm_salon_expense_report', [], $loc),
            25 => trans('messages.perm_salon_service', [], $loc),
            26 => trans('messages.perm_salon_customer', [], $loc),
            27 => trans('messages.perm_salon_monthly_income', [], $loc),
            28 => trans('messages.perm_salon_income_expense', [], $loc),
        ];
    @endphp

    <!-- Add user Modal -->
    <div x-show="open" x-cloak
        class="fixed inset-0 flex items-start sm:items-center justify-center bg-black/55 z-50 p-2 sm:p-4 overflow-y-auto" id="add_user_modal" x-ref="userModal">
        <div @click.away="open = false"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-5xl xl:max-w-7xl 2xl:max-w-[92rem] my-2 sm:my-4 flex flex-col max-h-[96vh] border border-gray-200/80 dark:border-gray-600 text-[11px] sm:text-xs leading-snug">
            <!-- Modal Header -->
            <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h1 class="text-sm sm:text-base font-bold text-gray-800 dark:text-gray-200 tracking-tight">
                    {{ trans('messages.add_user', [], session('locale')) }}
                </h1>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-0.5" id="close_modal" aria-label="Close">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto flex-1 px-3 sm:px-4 py-3 min-h-0">
                <form id="user_form">
                    @csrf
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="space-y-3">
                        <div>
                            <h3 class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 border-b border-gray-100 dark:border-gray-700 pb-1">
                                {{ trans('messages.basic_information', [], session('locale')) ?: 'Basic Information' }}
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                <div class="sm:col-span-1">
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">
                                        {{ trans('messages.user_name', [], session('locale')) }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="user_name" id="user_name"
                                        placeholder="{{ trans('messages.user_name_placeholder_en', [], session('locale')) }}"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">
                                        {{ trans('messages.user_phone', [], session('locale')) }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="user_phone" id="user_phone"
                                        placeholder="{{ trans('messages.user_phone_placeholder', [], session('locale')) }}"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">
                                        {{ trans('messages.user_email', [], session('locale')) }}
                                    </label>
                                    <input type="email" name="user_email" id="user_email"
                                        placeholder="{{ trans('messages.user_email_placeholder', [], session('locale')) }}"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">
                                        {{ trans('messages.user_password', [], session('locale')) }}
                                    </label>
                                    <input type="password" name="user_password" id="user_password"
                                        placeholder="{{ trans('messages.user_password_placeholder', [], session('locale')) }}"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-900/40 px-3 py-2">
                            <span class="block font-bold text-gray-700 dark:text-gray-200 mb-1.5">{{ trans('messages.user_scope_label', [], session('locale')) }}</span>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="user_scope" value="boutique" checked class="w-3.5 h-3.5 text-[var(--primary-color)]">
                                    {{ trans('messages.user_scope_boutique', [], session('locale')) }}
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="user_scope" value="saloon" class="w-3.5 h-3.5 text-[var(--primary-color)]">
                                    {{ trans('messages.user_scope_saloon', [], session('locale')) }}
                                </label>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-900/40 px-3 py-2 space-y-2">
                            <span class="block font-bold text-gray-700 dark:text-gray-200">{{ trans('messages.user_type_label', [], session('locale')) }}</span>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="user_type" value="admin" checked class="w-3.5 h-3.5 text-[var(--primary-color)]" id="user_type_admin">
                                    {{ trans('messages.user_type_admin', [], session('locale')) }}
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="user_type" value="user" class="w-3.5 h-3.5 text-[var(--primary-color)]" id="user_type_staff_user">
                                    {{ trans('messages.user_type_staff_user', [], session('locale')) }}
                                </label>
                            </div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-snug">{{ trans('messages.user_type_staff_hint', [], session('locale')) }}</p>
                            <div class="pt-1">
                                <label for="salon_staff_id" class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">
                                    {{ trans('messages.salon_staff_link_label', [], session('locale')) }}
                                    <span id="salon_staff_required_mark" class="text-red-500 hidden">*</span>
                                </label>
                                <select name="salon_staff_id" id="salon_staff_id" disabled
                                    class="w-full max-w-md border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <option value="">{{ trans('messages.salon_staff_select_placeholder', [], session('locale')) }}</option>
                                    @foreach($staffForUserForm ?? [] as $st)
                                        <option value="{{ $st->id }}">
                                            {{ $st->name ?? '—' }}@if($st->salonTeam) — {{ $st->salonTeam->displayName(session('locale')) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-2 mb-2 border-b border-gray-100 dark:border-gray-700 pb-1.5">
                                <h3 class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ trans('messages.permissions', [], session('locale')) ?: 'Permissions' }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button" id="btnPermAll"
                                        class="px-2 py-1 rounded-md bg-gray-800 text-white text-[10px] font-bold hover:bg-gray-700 transition">
                                        {{ trans('messages.perm_quick_all', [], session('locale')) }}
                                    </button>
                                    <button type="button" id="btnPermSalonOnly"
                                        class="px-2 py-1 rounded-md bg-[#8a4853] text-white text-[10px] font-bold hover:opacity-90 transition">
                                        {{ trans('messages.perm_quick_salon_only', [], session('locale')) }}
                                    </button>
                                    <button type="button" id="btnPermBoutiqueOnly"
                                        class="px-2 py-1 rounded-md bg-slate-600 text-white text-[10px] font-bold hover:bg-slate-500 transition">
                                        {{ trans('messages.perm_quick_boutique_only', [], session('locale')) }}
                                    </button>
                                    <button type="button" id="toggleAllPermissions"
                                        class="px-2 py-1 rounded-md border border-[var(--primary-color)] text-[var(--primary-color)] text-[10px] font-bold hover:bg-[var(--primary-color)]/10 transition">
                                        {{ trans('messages.select_all', [], session('locale')) ?: 'Select All' }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                                <div class="rounded-lg border border-gray-200 dark:border-gray-600 p-2 bg-white/60 dark:bg-gray-800/50">
                                    <p class="font-bold text-[10px] uppercase text-slate-500 mb-1.5">{{ trans('messages.perm_section_boutique', [], session('locale')) }}</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-1 max-h-[220px] overflow-y-auto pr-0.5">
                                        @foreach($boutiquePermissionMap as $key => $label)
                                            <label class="flex items-start gap-1 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700/80 cursor-pointer border border-transparent hover:border-gray-200 dark:hover:border-gray-600">
                                                <input type="checkbox" name="permissions[]" value="{{ $key }}" id="permission_{{ $key }}"
                                                    data-perm-scope="boutique"
                                                    class="perm-cb mt-0.5 w-3 h-3 shrink-0 text-[var(--primary-color)] rounded border-gray-300">
                                                <span class="text-[10px] font-medium text-gray-700 dark:text-gray-300 leading-tight">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="rounded-lg border border-rose-200/60 dark:border-rose-900/40 p-2 bg-rose-50/30 dark:bg-rose-950/20">
                                    <p class="font-bold text-[10px] uppercase text-rose-800/80 dark:text-rose-300 mb-1.5">{{ trans('messages.perm_section_saloon', [], session('locale')) }}</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-1 max-h-[220px] overflow-y-auto pr-0.5">
                                        @foreach($salonPermissionMap as $key => $label)
                                            <label class="flex items-start gap-1 p-1 rounded hover:bg-white/80 dark:hover:bg-gray-800/80 cursor-pointer border border-transparent hover:border-rose-200 dark:hover:border-rose-800">
                                                <input type="checkbox" name="permissions[]" value="{{ $key }}" id="permission_{{ $key }}"
                                                    data-perm-scope="salon"
                                                    class="perm-cb mt-0.5 w-3 h-3 shrink-0 text-[var(--primary-color)] rounded border-gray-300">
                                                <span class="text-[10px] font-medium text-gray-700 dark:text-gray-300 leading-tight">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-[11px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1 border-b border-gray-100 dark:border-gray-700 pb-1">
                                {{ trans('messages.additional_information', [], session('locale')) ?: 'Additional Information' }}
                            </h3>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-0.5">{{ trans('messages.notes', [], session('locale')) }}</label>
                            <textarea name="notes" id="notes" rows="2"
                                placeholder="{{ trans('messages.notes_placeholder_en', [], session('locale')) }}"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md py-1.5 px-2 text-xs resize-none focus:ring-1 focus:ring-[var(--primary-color)] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="px-3 sm:px-4 py-2 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex gap-2">
                    <button type="button" @click="open = false" id="close_modal_btn"
                        class="flex-1 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        {{ trans('messages.cancel', [], session('locale')) ?: 'Cancel' }}
                    </button>
                    <button type="submit" form="user_form"
                        class="flex-1 px-3 py-1.5 text-xs font-bold bg-[var(--primary-color)] text-white rounded-md hover:bg-[var(--primary-darker)] transition shadow-sm">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>



@include('layouts.footer')
@endsection