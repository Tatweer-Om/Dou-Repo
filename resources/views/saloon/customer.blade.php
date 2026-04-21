@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.view_customer_lang', [], session('locale')) }}</title>
@endpush

<main class="min-h-screen bg-surface" x-data="{ edit: false }">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex justify-between items-center w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex items-center gap-6">
            <button
                type="button"
                @click="$store.modals.customer = true; edit = false"
                class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200">
                {{ trans('messages.add_customer_lang', [], session('locale')) }}
            </button>
        </div>
    </header>

    <div class="px-8 py-10 space-y-8">
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden">
            <div class="overflow-x-auto" id="data-table">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">#</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.customer_name_lang', [], session('locale')) }}</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.customer_phone_lang', [], session('locale')) }}</th> 
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.notes_lang', [], session('locale')) }}</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface">
                        @forelse($customer as $index => $sta)
                            <tr data-id="{{ $sta->id }}" class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-6 py-6">{{ $customer->firstItem() + $index }}</td>
                                <td class="px-6 py-6">{{ $sta->name }}</td>
                                <td class="px-6 py-6">{{ $sta->phone }}</td>
                                 <td class="px-6 py-6">{{ $sta->notes }}</td>
                                <td class="px-6 py-6 text-center">
                                    <a href="{{ route('saloncustomer.profile', $sta) }}" class="icon-btn inline-flex items-center justify-center" title="Profile">
                                        <span class="material-symbols-outlined">person</span>
                                    </a>
                                    <button type="button" class="edit-btn icon-btn">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>

                                    

                                    <button type="button" class="delete-btn icon-btn hover:text-red-500">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center">
                                    No customer found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-surface" id="data-pagination">
                <span class="text-xs text-on-surface-variant font-medium">
                    Showing {{ $customer->firstItem() ?? 0 }} to {{ $customer->lastItem() ?? 0 }} of {{ $customer->total() }} customer
                </span>

                <div class="flex items-center gap-2">
                    @if ($customer->onFirstPage())
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $customer->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($customer->getUrlRange(1, $customer->lastPage()) as $page => $url)
                        @if ($page == $customer->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant dress-page-link">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($customer->hasMorePages())
                        <a href="{{ $customer->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
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

    <!-- Add/Edit customer Modal -->
    <div
        x-show="$store.modals.customer"
        x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
        id="add_customer_modal">

        <div
            @click.away="$store.modals.customer = false; edit = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-4xl p-6 sm:p-8 max-h-[90vh] overflow-y-auto">

            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    <span x-text="edit ? '{{ trans('messages.edit_customer_lang', [], session('locale')) }}' : '{{ trans('messages.add_customer_lang', [], session('locale')) }}'"></span>
                </h1>

                <button
                    type="button"
                    @click="$store.modals.customer = false; edit = false"
                    class="text-gray-400 hover:text-gray-600"
                    id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>

            <form id="customer_form" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.customer_name_lang', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.customer_phone_lang', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="phone"
                            id="phone"
                            required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    

                   

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.notes_lang', [], session('locale')) }}
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]"></textarea>
                    </div>
                </div>

                <input type="hidden" id="customer_id" name="customer_id">

                <div class="mt-8 pt-6 border-t">
                    <button
                        type="submit"
                        class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-[var(--primary-darker)]">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('layouts.salon_footer')
@include('custom_js.saloncustomer_js')
@endsection