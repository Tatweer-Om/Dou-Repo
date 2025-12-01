@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.size_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false, del: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-4xl mx-auto">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_sizes', [], session('locale')) }}
            </h2>
            <button @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_size', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- شريط بحث احترافي -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
              <input
                id="search_size"
                type="text"
                placeholder="{{ trans('messages.search_size', [], session('locale')) }}"
                class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>


        <!-- Sizes table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.size_name', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.size_code', [], session('locale')) }}
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

    <!-- Add Size Modal -->
    <div x-show="open" x-cloak 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_size_modal" x-ref="sizeModal">
        <div @click.away="open = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    {{ trans('messages.add_size', [], session('locale')) }}
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form id="size_form">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.size_name_en', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.size_name_placeholder_en', [], session('locale')) }}"
                            name="size_name_en" id="size_name_en"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <input type="hidden" id="size_id" name="size_id">

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.size_name_ar', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.size_name_placeholder_ar', [], session('locale')) }}"
                            name="size_name_ar" id="size_name_ar"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.size_code_en', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.size_code_placeholder_en', [], session('locale')) }}"
                            name="size_code_en" id="size_code_en"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.size_code_ar', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.size_code_placeholder_ar', [], session('locale')) }}"
                            name="size_code_ar" id="size_code_ar"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
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




    <!-- Edit Size Modal -->
    <!-- <div x-show="edit" x-cloak
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div @click.away="edit = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">تعديل المقاس</h1>
                <button @click="edit = false" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form>
                <div class="space-y-6">
                    <div>
                        <label class="block text-base font-medium mb-2">اسم المقاس</label>
                        <input type="text" value="كبير"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>
                    <div>
                        <label class="block text-base font-medium mb-2">الرمز</label>
                        <input type="text" value="L"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>
                </div>
                <div class="mt-8 pt-6 border-t flex gap-4">
                    <button type="button" @click="edit = false"
                        class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-lg">
                        إلغاء
                    </button>
                    <button type="submit"
                        class="flex-1 bg-[var(--primary-color)] text-white font-bold py-3 rounded-lg hover:bg-[var(--primary-darker)]">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div> -->

    <!-- Delete Size Modal -->
    <!-- Delete Confirmation Modal -->
    <!-- <div x-show="del" x-cloak
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div @click.away="del = false"
         class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 sm:p-8 text-center">
        <span class="material-symbols-outlined text-red-500 text-5xl mb-4">warning</span>
        <h2 class="text-xl font-bold mb-4">{{ trans('messages.delete_confirm_title', [], session('locale')) }}</h2>
        <p class="text-gray-600 mb-6">{{ trans('messages.delete_confirm_text', [], session('locale')) }}</p>
        <div class="flex gap-4">
            <button @click="del = false"
                    class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-lg">
                {{ trans('messages.cancel', [], session('locale')) }}
            </button>
            <button @click="confirmDelete()"
                    class="flex-1 bg-red-500 text-white font-bold py-3 rounded-lg hover:bg-red-600">
                {{ trans('messages.confirm_delete', [], session('locale')) }}
            </button>
        </div>
    </div>
</div> -->


</main>



@include('layouts.footer')
@endsection