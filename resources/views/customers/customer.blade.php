@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.customer_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false, del: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-4xl mx-auto">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_customers', [], session('locale')) }}
            </h2>
            <button @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_customer', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- Search bar -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
                <input
                    id="search_customer"
                    type="text"
                    placeholder="{{ trans('messages.search_customer', [], session('locale')) }}"
                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Customers table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)] overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.customer_name', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.phone_number', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.city', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.area', [], session('locale')) }}
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

    <!-- Add Customer Modal -->
    <div x-show="open" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_customer_modal" x-ref="customerModal">
        <div @click.away="open = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    <span id="modal_title">{{ trans('messages.add_customer', [], session('locale')) }}</span>
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form id="customer_form">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.customer_name', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.customer_name_placeholder', [], session('locale')) }}"
                            name="name" id="customer_name"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]" required>
                    </div>

                    <input type="hidden" id="customer_id" name="customer_id">

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.phone_number', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.phone_number_placeholder', [], session('locale')) }}"
                            name="phone" id="customer_phone"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.area', [], session('locale')) }}
                        </label>
                        <select name="area_id" id="customer_area_id"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="">{{ trans('messages.select_area', [], session('locale')) }}</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">
                                    {{ session('locale') == 'ar' ? ($area->area_name_ar ?: $area->area_name_en) : ($area->area_name_en ?: $area->area_name_ar) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.city', [], session('locale')) }}
                        </label>
                        <select name="city_id" id="customer_city_id"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="">{{ trans('messages.select_city', [], session('locale')) }}</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" data-area-id="{{ $city->area_id }}">
                                    {{ session('locale') == 'ar' ? ($city->city_name_ar ?: $city->city_name_en) : ($city->city_name_en ?: $city->city_name_ar) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.notes', [], session('locale')) }} ({{ trans('messages.address', [], session('locale')) }})
                        </label>
                        <textarea
                            placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}"
                            name="notes" id="customer_notes"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]"
                            rows="3"></textarea>
                    </div>

                </div>

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
@include('custom_js.customer_js')
@endsection
