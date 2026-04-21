@extends('layouts.salon_header')

@section('main')
@push('title')
<title>{{ trans('messages.salon_team_title', [], session('locale')) }}</title>
@endpush

<main class="min-h-screen bg-surface" x-data="{ edit: false }">
    <header class="bg-[#f9f9f9] dark:bg-[#1a1c1c] flex flex-wrap justify-between items-center gap-4 w-full px-8 py-4 sticky top-0 z-10">
        <div class="flex items-center gap-6">
            <button
                type="button"
                @click="$store.modals.salonTeam = true; edit = false"
                class="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-headline font-semibold text-sm transition-transform active:scale-95 duration-200">
                {{ trans('messages.salon_team_add', [], session('locale')) }}
            </button>
        </div>
        <p class="text-xs text-on-surface-variant max-w-xl">{{ trans('messages.salon_team_intro', [], session('locale')) }}</p>
    </header>

    <div class="px-8 py-10 space-y-8">
        <section class="bg-surface-container-lowest rounded-xl editorial-shadow overflow-hidden border border-outline-variant/10">
            <div class="overflow-x-auto" id="data-table">
                <table class="w-full text-left border-collapse min-w-[720px]">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">#</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">{{ trans('messages.salon_team_code', [], session('locale')) }}</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">{{ trans('messages.salon_team_name_en', [], session('locale')) }}</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary">{{ trans('messages.salon_team_name_ar', [], session('locale')) }}</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary text-center">{{ trans('messages.salon_team_sort', [], session('locale')) }}</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary text-center">{{ trans('messages.salon_team_active', [], session('locale')) }}</th>
                            <th class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.12em] text-primary text-center">{{ trans('messages.action_lang', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($teams as $index => $t)
                            <tr data-id="{{ $t->id }}" class="hover:bg-surface-container-low/70 transition-colors">
                                <td class="px-5 py-4 text-sm text-on-surface-variant">{{ $teams->firstItem() + $index }}</td>
                                <td class="px-5 py-4 text-sm font-mono font-semibold text-primary">{{ $t->code }}</td>
                                <td class="px-5 py-4 text-sm font-medium text-on-surface">{{ $t->name }}</td>
                                <td class="px-5 py-4 text-sm text-on-surface-variant" dir="rtl">{{ $t->name_ar ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-center font-semibold">{{ $t->sort_order }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if($t->is_active)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-900">{{ trans('messages.yes', [], session('locale')) }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-on-surface-variant">{{ trans('messages.no', [], session('locale')) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <button type="button" class="edit-team-btn icon-btn hover:text-primary" title="{{ trans('messages.edit', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button type="button" class="delete-team-btn icon-btn hover:text-red-500" title="{{ trans('messages.delete', [], session('locale')) }}">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    {{ trans('messages.salon_team_empty', [], session('locale')) }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 flex items-center justify-between border-t border-outline-variant/10" id="data-pagination">
                <span class="text-xs text-on-surface-variant font-medium">
                    {{ trans('messages.salon_team_pagination', ['from' => $teams->firstItem() ?? 0, 'to' => $teams->lastItem() ?? 0, 'total' => $teams->total()], session('locale')) }}
                </span>
                <div class="flex items-center gap-2">
                    @if ($teams->onFirstPage())
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </span>
                    @else
                        <a href="{{ $teams->previousPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </a>
                    @endif
                    @foreach ($teams->getUrlRange(1, $teams->lastPage()) as $page => $url)
                        @if ($page == $teams->currentPage())
                            <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-xs font-semibold text-on-surface-variant dress-page-link">{{ $page }}</a>
                        @endif
                    @endforeach
                    @if ($teams->hasMorePages())
                        <a href="{{ $teams->nextPageUrl() }}" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant dress-page-link">
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
        x-show="$store.modals.salonTeam"
        x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4"
        id="salon_team_modal">
        <div
            @click.away="$store.modals.salonTeam = false; edit = false"
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8 max-h-[90vh] overflow-y-auto border border-outline-variant/10">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold font-headline text-on-surface" x-text="edit ? @json(trans('messages.salon_team_edit', [], session('locale'))) : @json(trans('messages.salon_team_add', [], session('locale')))"></h1>
                <button type="button" @click="$store.modals.salonTeam = false; edit = false" class="text-on-surface-variant hover:text-on-surface" id="close_salon_team_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>

            <form id="salon_team_form">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-on-surface">{{ trans('messages.salon_team_code', [], session('locale')) }} <span class="text-red-500">*</span></label>
                        <input type="text" name="code" id="st_code" required pattern="[a-z0-9\-_]+" autocomplete="off"
                            class="w-full border border-outline-variant/25 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/15 focus:border-primary/30"
                            placeholder="ghubrah">
                        <p class="text-[11px] text-on-surface-variant mt-1">{{ trans('messages.salon_team_code_hint', [], session('locale')) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-on-surface">{{ trans('messages.salon_team_name_en', [], session('locale')) }} <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="st_name" required class="w-full border border-outline-variant/25 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/15 focus:border-primary/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-on-surface">{{ trans('messages.salon_team_name_ar', [], session('locale')) }}</label>
                        <input type="text" name="name_ar" id="st_name_ar" dir="rtl" class="w-full border border-outline-variant/25 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/15 focus:border-primary/30">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-on-surface">{{ trans('messages.salon_team_sort', [], session('locale')) }}</label>
                        <input type="number" name="sort_order" id="st_sort_order" min="0" max="255" value="0" class="w-full border border-outline-variant/25 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/15 focus:border-primary/30">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="st_is_active" value="1" checked class="rounded border-outline-variant text-primary focus:ring-primary/20">
                        <label for="st_is_active" class="text-sm font-medium text-on-surface">{{ trans('messages.salon_team_active', [], session('locale')) }}</label>
                    </div>
                </div>
                <input type="hidden" id="st_team_id" name="st_team_id" value="">
                <div class="mt-8 pt-6 border-t border-outline-variant/15">
                    <button type="submit" class="w-full bg-primary text-on-primary font-bold py-3 rounded-xl hover:opacity-95 transition-opacity">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('layouts.salon_footer')
@include('custom_js.salon_team_js')
@endsection
