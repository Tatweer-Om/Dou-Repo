@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.edit_material', [], session('locale')) ?: 'Edit Material' }}</title>
@endpush

<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
</style>
<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">
    
    <!-- Header with back button -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ url('view_material') }}" 
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ trans('messages.edit_material', [], session('locale')) ?: 'Edit Material' }}
        </h1>
      </div>
    </div>

    <!-- Single compact form card -->
    <form id="update_material" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      <input type="hidden" name="material_id" id="material_id" value="{{ $material->id }}" />
      
      <!-- Form Content -->
      <div class="p-6 sm:p-8 space-y-6">
        
        <!-- Basic Information Section -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.basic_info', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Material Name -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.material_name', [], session('locale')) }}</span>
              <input type="text" 
                     placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}"
                     value="{{ $material->material_name ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="material_name" 
                     id="material_name" />
            </label>

            <!-- Unit -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.unit', [], session('locale')) }}</span>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="material_unit" 
                      id="material_unit">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                <option value="meter" {{ ($material->unit ?? '') === 'meter' ? 'selected' : '' }}>{{ trans('messages.meter', [], session('locale')) }}</option>
                <option value="piece" {{ ($material->unit ?? '') === 'piece' ? 'selected' : '' }}>{{ trans('messages.piece', [], session('locale')) }}</option>
                <option value="roll" {{ ($material->unit ?? '') === 'roll' ? 'selected' : '' }}>{{ trans('messages.roll', [], session('locale')) }}</option>
              </select>
            </label>

            <!-- Category -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.category', [], session('locale')) }}</span>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="material_category" 
                      id="material_category">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                <option value="fabric" {{ ($material->category ?? '') === 'fabric' ? 'selected' : '' }}>{{ trans('messages.fabric', [], session('locale')) }}</option>
                <option value="embroidery" {{ ($material->category ?? '') === 'embroidery' ? 'selected' : '' }}>{{ trans('messages.embroidery', [], session('locale')) }}</option>
                <option value="accessories" {{ ($material->category ?? '') === 'accessories' ? 'selected' : '' }}>{{ trans('messages.accessories', [], session('locale')) }}</option>
              </select>
            </label>

          </div>

          <!-- Description and Image in One Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Description -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.description', [], session('locale')) }}</span>
              <textarea placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}" 
                        rows="5"
                        class="rounded-lg px-4 py-2.5 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition resize-none" 
                        name="material_notes" 
                        id="material_notes">{{ $material->description ?? '' }}</textarea>
            </label>

            <!-- Image Upload -->
            <div class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.raw_material_image', [], session('locale')) }}</span>
              <label class="flex flex-col items-center justify-center w-full h-full min-h-[140px] border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all relative group"
                     id="imageBoxLabel">
                
                <!-- Image preview -->
                <img id="imagePreview" 
                     src="{{ $material->material_image ? asset('images/materials/' . $material->material_image) : '' }}"
                     alt="{{ trans('messages.preview', [], session('locale')) }}" 
                     class="{{ $material->material_image ? 'absolute inset-0 w-full h-full object-cover rounded-xl' : 'hidden' }}" />

                <!-- Upload Icon -->
                <span class="material-symbols-outlined text-3xl text-gray-400 group-hover:text-primary transition-colors z-10 {{ $material->material_image ? 'hidden' : '' }}" id="uploadIcon">
                  cloud_upload
                </span>

                <!-- Upload Text -->
                <p class="text-sm text-gray-500 mt-2 z-10 {{ $material->material_image ? 'hidden' : '' }}" id="uploadText">
                  {{ trans('messages.upload_image', [], session('locale')) }}
                </p>

                <input type="file" 
                       class="hidden" 
                       name="material_image" 
                       id="material_image" 
                       accept="image/*" />
              </label>
            </div>
          </div>
        </div>

        <!-- Pricing & Quantity Section -->
        <div class="space-y-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">attach_money</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.qty_price', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Buy Price -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.buy_price', [], session('locale')) }}</span>
              <input type="number" 
                     step="0.01"
                     placeholder="0.00"
                     value="{{ $material->buy_price ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="purchase_price" 
                     id="purchase_price" />
            </label>

            <!-- Rolls Count -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.rolls_count', [], session('locale')) }}</span>
              <input type="number" 
                     placeholder="0"
                     value="{{ $material->rolls_count ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="roll_count" 
                     id="roll_count" />
            </label>

            <!-- Meters Per Roll -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.meters_per_roll', [], session('locale')) }}</span>
              <input type="number" 
                     placeholder="0"
                     value="{{ $material->meters_per_roll ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="meter_per_roll" 
                     id="meter_per_roll" />
            </label>
          </div>
          
          <!-- Hidden sell_price field to preserve existing value if any -->
          <input type="hidden" name="sale_price" id="sale_price" value="{{ $material->sell_price ?? '' }}" />
        </div>

      </div>

      <!-- Form Footer with Submit Button -->
      <div class="bg-gray-50 px-6 sm:px-8 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="{{ url('view_material') }}" 
           class="text-sm text-gray-600 hover:text-gray-800 transition-colors">
          {{ trans('messages.cancel', [], session('locale')) }}
        </a>
        <button type="submit"
                class="w-full sm:w-auto px-8 py-3 bg-primary text-white font-semibold rounded-xl shadow-md hover:bg-primary/90 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span>
          <span>{{ trans('messages.save', [], session('locale')) }}</span>
        </button>
      </div>

    </form>

  </div>
</main>

@include('layouts.footer')
@include('custom_js.edit_material_js')
@endsection
