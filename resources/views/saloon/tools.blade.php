@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.view_tools_lang', [], session('locale')) }}</title>
@endpush

<main class="min-h-screen bg-surface" x-data="{ edit: false }">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex justify-between items-center w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex items-center gap-6">
            <button
                type="button"
                @click="$store.modals.tool = true; edit = false"
                class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200">
                {{ trans('messages.add_tool_lang', [], session('locale')) }}
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
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.tool_name_lang', [], session('locale')) }}</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-right">{{ trans('messages.tool_price_lang', [], session('locale')) }}</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary">{{ trans('messages.tool_notes_lang', [], session('locale')) }}</th>
                            <th class="px-6 py-5 text-[11px] font-bold uppercase tracking-[0.1em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-surface">
                        @forelse($tools as $index => $tool)
                            <tr data-id="{{ $tool->id }}" class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-6 py-6">{{ $tools->firstItem() + $index }}</td>
                                <td class="px-6 py-6">{{ $tool->name }}</td>
                                <td class="px-6 py-6 text-right">{{ number_format((float) $tool->price, 3) }}</td>
                                <td class="px-6 py-6 max-w-md truncate" title="{{ $tool->notes }}">{{ $tool->notes }}</td>
                                <td class="px-6 py-6 text-center">
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
                                <td colspan="5" class="px-6 py-6 text-center">
                                    {{ trans('messages.no_tools_found', [], session('locale')) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-surface" id="data-pagination">
                <span class="text-xs text-on-surface-variant font-medium">
                    {{ trans('messages.tools_pagination', ['from' => $tools->firstItem() ?? 0, 'to' => $tools->lastItem() ?? 0, 'total' => $tools->total()], session('locale')) }}
                </span>

                <div class="flex items-center gap-2">
                    @if ($tools->onFirstPage())
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $tools->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif

                    @foreach ($tools->getUrlRange(1, $tools->lastPage()) as $page => $url)
                        @if ($page == $tools->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant dress-page-link">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    @if ($tools->hasMorePages())
                        <a href="{{ $tools->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
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
        x-show="$store.modals.tool"
        x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
        id="add_tool_modal">

        <div
            @click.away="$store.modals.tool = false; edit = false"
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8 max-h-[90vh] overflow-y-auto">

            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    <span x-text="edit ? '{{ trans('messages.edit_tool_lang', [], session('locale')) }}' : '{{ trans('messages.add_tool_lang', [], session('locale')) }}'"></span>
                </h1>

                <button
                    type="button"
                    @click="$store.modals.tool = false; edit = false"
                    class="text-gray-400 hover:text-gray-600"
                    id="close_tool_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>

            <form id="tool_form">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.tool_name_lang', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="tool_name"
                            required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)] dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.tool_price_lang', [], session('locale')) }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="price"
                            id="tool_price"
                            step="0.001"
                            min="0"
                            required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)] dark:bg-gray-800 dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.tool_notes_lang', [], session('locale')) }}
                        </label>
                        <textarea
                            name="notes"
                            id="tool_notes"
                            rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)] dark:bg-gray-800 dark:border-gray-600"></textarea>
                    </div>
                </div>

                <input type="hidden" id="tool_id" name="tool_id">

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
@include('custom_js.salontool_js')
@endsection
