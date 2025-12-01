@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.color_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false, del: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

  <div class="max-w-4xl mx-auto">
    <!-- Page title and Add button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
      <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
        {{ trans('messages.manage_colors', [], session('locale')) }}
      </h2>
      <button @click="open = true"
        class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
        <span class="material-symbols-outlined text-base">add_circle</span>
        <span>{{ trans('messages.add_new_color', [], session('locale')) }}</span>
      </button>
    </div>

    <!-- شريط بحث احترافي -->
    <div class="w-full mt-6 mb-8">
      <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
        <input
          id="search_color"
          type="text"
          placeholder="{{ trans('messages.search_color', [], session('locale')) }}"
          class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />

        <button
          class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
          title="{{ trans('messages.search', [], session('locale')) }}">
          <span class="material-symbols-outlined text-[22px]">search</span>
        </button>
      </div>
    </div>


    <!-- colors table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
      <table class="w-full text-sm text-right">
        <thead class="bg-gray-50 border-b border-[var(--border-color)]">
          <tr>
            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
              {{ trans('messages.color_name', [], session('locale')) }}
            </th>
            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
              {{ trans('messages.color_code', [], session('locale')) }}
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

  <!-- Add color Modal -->
  <div x-show="open" x-cloak
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_color_modal" x-ref="colorModal">
    <div @click.away="open = false"
      class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8">
      <div class="flex justify-between items-start mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">
          {{ trans('messages.add_color', [], session('locale')) }}
        </h1>
        <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
          <span class="material-symbols-outlined text-3xl">close</span>
        </button>
      </div>
      <form id="color_form">
        @csrf
        <div class="space-y-6">
          <div>
            <label class="block text-base font-medium mb-2">
              {{ trans('messages.color_name_en', [], session('locale')) }}
            </label>
            <input type="text"
              placeholder="{{ trans('messages.color_name_placeholder_en', [], session('locale')) }}"
              name="color_name_en" id="color_name_en"
              class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
          </div>

          <input type="hidden" id="color_id" name="color_id">

          <div>
            <label class="block text-base font-medium mb-2">
              {{ trans('messages.color_name_ar', [], session('locale')) }}
            </label>
            <input type="text"
              placeholder="{{ trans('messages.color_name_placeholder_ar', [], session('locale')) }}"
              name="color_name_ar" id="color_name_ar"
              class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
          </div>

          <div>
            <label class="block text-base font-medium mb-2">
              {{ trans('messages.color_code_label', [], session('locale')) }}
            </label>
            <input type="color" class="w-16 h-10 rounded cursor-pointer border" name="color_code" id="color_code">
            <span class="text-sm text-gray-500">
              {{ trans('messages.color_code_helper', [], session('locale')) }}
            </span>
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
@endsection