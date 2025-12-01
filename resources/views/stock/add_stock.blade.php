@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.add_stock_lang', [], session('locale')) }}</title>
@endpush


<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
</style>
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "primary": "#6D28D9", // Deep Purple
          "secondary": "#FCE7F3", // Light Pink
          "accent": "#F97316", // Vibrant Orange
          "background-light": "#F9FAFB", // Off-white
          "background-dark": "#111827", // Dark Gray for dark mode
          "card-light": "#FFFFFF",
          "card-dark": "#1F2937",
          "text-primary-light": "#374151",
          "text-primary-dark": "#E5E7EB",
          "text-secondary-light": "#6B7280",
          "text-secondary-dark": "#9CA3AF",
          "border-light": "#E5E7EB",
          "border-dark": "#374151"
        },
        fontFamily: {
          "display": ["IBM Plex Sans Arabic", "sans-serif"]
        },
        borderRadius: {
          "DEFAULT": "0.75rem", // 12px
          "lg": "1rem", // 16px
          "xl": "1.5rem", // 24px
          "full": "9999px"
        },
      },
    },
  }
</script>
<main class="flex-1 flex flex-col" x-data="{ activeTab: 'raw' }">
  <div class="sticky top-0 z-10 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm border-b border-border-light dark:border-border-dark p-4 sm:p-6">
    <div class="flex items-center justify-between">
      <p class="text-text-primary-light dark:text-text-primary-dark text-3xl font-bold leading-tight tracking-tight">
        {{ trans('messages.manage_inventory', [], session('locale')) }}
      </p>
    </div>
  </div>

  <!-- Tabs -->
  <div class="border-b border-border-light dark:border-border-dark p-4 sm:p-6">
    <div class="flex gap-8">

      <button @click="activeTab = 'raw'"
        :class="activeTab === 'raw' ? 'border-b-2 border-b-primary text-primary font-bold' : 'text-text-secondary-light dark:text-text-secondary-dark hover:text-primary'"
        class="flex flex-col items-center justify-center pb-3 transition">
        {{ trans('messages.raw_materials', [], session('locale')) }}
      </button>

      <button @click="activeTab = 'abaya'"
        :class="activeTab === 'abaya' ? 'border-b-2 border-b-primary text-primary font-bold' : 'text-text-secondary-light dark:text-text-secondary-dark hover:text-primary'"
        class="flex flex-col items-center justify-center pb-3 transition">
        {{ trans('messages.ready_abayas', [], session('locale')) }}
      </button>

    </div>
  </div>


  <!-- محتوى الصفحة -->
  <div class="p-4 sm:p-6 space-y-6 overflow-y-auto" style="max-height: calc(100vh - 120px);">
    <!-- ===================== قسم المواد الخام ===================== -->
    <div x-show="activeTab === 'raw'" x-transition>
      <div class="space-y-6">

        <!-- بطاقة البيانات الأساسية -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm">
          <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
            {{ trans('messages.basic_info', [], session('locale')) }}
          </h2>

          <form id="add_material" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

              <label class="flex flex-col col-span-2">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.material_name', [], session('locale')) }}
                </p>
                <input type="text" placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_name" id="material_name" />
              </label>

              <label class="flex flex-col col-span-2">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.description', [], session('locale')) }}
                </p>
                <textarea placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}" rows="3"
                  class="form-textarea rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary/50" name="material_notes" id="material_notes"></textarea>
              </label>

              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.unit', [], session('locale')) }}</p>
                <select class="form-select h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_unit" id="material_unit">
        <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
        <option value="meter">{{ trans('messages.meter', [], session('locale')) }}</option>
        <option value="piece">{{ trans('messages.piece', [], session('locale')) }}</option>
        <option value="roll">{{ trans('messages.roll', [], session('locale')) }}</option>
    </select>
              </label>

              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.category', [], session('locale')) }}</p>
             <select class="form-select h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_category" id="material_category">
        <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
        <option value="fabric">{{ trans('messages.fabric', [], session('locale')) }}</option>
        <option value="embroidery">{{ trans('messages.embroidery', [], session('locale')) }}</option>
        <option value="accessories">{{ trans('messages.accessories', [], session('locale')) }}</option>
    </select>
              </label>
            </div>

            <!-- بطاقة الكمية والأسعار -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm mt-6">
              <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
                {{ trans('messages.qty_price', [], session('locale')) }}
              </h2>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                <label class="flex flex-col">
                  <p class="text-sm font-medium mb-2">{{ trans('messages.buy_price', [], session('locale')) }}</p>
                  <input type="number" placeholder="0.00"
                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="purchase_price" id="purchase_price" />
                </label>

                <label class="flex flex-col">
                  <p class="text-sm font-medium mb-2">{{ trans('messages.suggested_sell_price', [], session('locale')) }}</p>
                  <input type="number" placeholder="0.00"
                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="sale_price" id="sale_price" />
                </label>

                <label class="flex flex-col">
                  <p class="text-sm font-medium mb-2">{{ trans('messages.rolls_count', [], session('locale')) }}</p>
                  <input type="number" placeholder="0"
                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="roll_count" id="roll_count" />
                </label>

                <label class="flex flex-col">
                  <p class="text-sm font-medium mb-2">{{ trans('messages.meters_per_roll', [], session('locale')) }}</p>
                  <input type="number" placeholder="0"
                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="meter_per_roll" id="meter_per_roll" />
                </label>
              </div>
            </div>

            <!-- بطاقة الصورة -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm mt-6">
              <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
                {{ trans('messages.raw_material_image', [], session('locale')) }}
              </h2>

              <label
                class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition relative"
                id="imageBoxLabel">

                <!-- Image preview -->
                <img id="imagePreview" src="" alt="Preview" class="hidden absolute inset-0 w-full h-full object-contain rounded-lg" />

                <span class="material-symbols-outlined text-4xl text-text-secondary-light dark:text-text-secondary-dark" id="uploadIcon">
                  cloud_upload
                </span>

                <p class="text-sm text-gray-500 mt-2" id="uploadText">
                  {{ trans('messages.upload_image', [], session('locale')) }}
                </p>

                <input type="file" class="hidden" name="material_image" id="material_image" />
              </label>
            </div>

        </div>

        <!-- زر الحفظ -->
        <div class="mt-10 text-center">
          <button
            type="submit"
            class="w-full sm:w-auto px-10 py-4 bg-primary text-white text-lg font-bold rounded-2xl shadow-lg hover:bg-primary/90 hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-3 mx-auto">
            <span class="material-symbols-outlined text-2xl">save</span>
            <span>{{ trans('messages.update_material', [], session('locale')) }}</span>
          </button>
        </div>

      </div>
      </form>

    </div>


    <!-- ===================== قسم العبايات ===================== -->
    <div x-show="activeTab === 'abaya'" x-transition>
      <div class="space-y-6">

        <form id="abaya_form" enctype="multipart/form-data">
          @csrf
          <!-- البيانات الأساسية -->
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm">
            <h2 class="text-xl font-bold mb-5">
              {{ trans('messages.basic_info', [], session('locale')) }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5" x-data="{ barcode: '' }">



              <!-- Abaya Code -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.abaya_code', [], session('locale')) }}</p>
                <input type="text" name="abaya_code" id="abaya_code"
                  placeholder="{{ trans('messages.abaya_code_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
              </label>

              <!-- Design Name -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.design_name', [], session('locale')) }}</p>
                <input type="text" name="design_name" id="design_name"
                  placeholder="{{ trans('messages.design_name_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
              </label>

              <!-- Generate Barcode -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">Generate Barcode</p>
                <div class="flex gap-2">
                  <input type="text" name="barcode" id="barcode"
                    x-model="barcode"
                    placeholder="Click button to generate"
                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" readonly />
                  <button type="button"
                    @click="barcode = Math.floor(100000000000 + Math.random() * 900000000000)"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center gap-1">
                    <span class="material-icons">qr_code</span>
                    Generate
                  </button>
                </div>
              </label>
              <div x-data="imageUploader()" class="col-span-3 space-y-2">
                <p class="text-sm font-medium mb-2">{{ trans('messages.abaya_images', [], session('locale')) }}</p>

                <!-- Image Input -->
                <input type="file" multiple x-ref="fileInput" @change="handleFiles($event)"
                  class="form-input border rounded-lg px-4 py-2 w-full" id="images" name="images[]" />

                <!-- Preview Selected Images -->
                <div class="flex flex-wrap gap-3 mt-3">
                  <template x-for="(image, index) in images" :key="index">
                    <div class="relative w-32 h-32 border rounded-lg overflow-hidden">
                      <img :src="image.url" class="object-cover w-full h-full" />
                      <!-- Remove Button -->
                      <button type="button" @click="removeImage(index)"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700">
                        &times;
                      </button>
                    </div>
                  </template>
                </div>
              </div>
              <!-- Description (spans all columns) -->
              <label class="flex flex-col col-span-3">
                <p class="text-sm font-medium mb-2">{{ trans('messages.description', [], session('locale')) }}</p>
                <textarea rows="3" name="abaya_notes" id="abaya_notes"
                  placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}"
                  class="form-textarea rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary/50"></textarea>
              </label>

            </div>

          </div>


          <!-- التكاليف والخياطين -->
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm">
            <h2 class="text-xl font-bold mb-5">
              {{ trans('messages.costs_tailors', [], session('locale')) }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.cost_price', [], session('locale')) }}
                </p>
                <input type="number"
                  placeholder="{{ trans('messages.cost_price_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="cost_price" id="cost_price" />
              </label>

              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.sale_price', [], session('locale')) }}
                </p>
                <input type="number" name="sales_price" id="sales_price"
                  placeholder="{{ trans('messages.sale_price_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
              </label>

              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.tailor_value', [], session('locale')) }}
                </p>
                <input type="number" name="tailor_charges" id="tailor_charges"
                  placeholder="{{ trans('messages.tailor_value_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
              </label>

            </div>


            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Tailor Input (Half Width) -->
              <div x-data="{ open: false, selected: [] }" class="relative">
                <label class="text-sm font-medium mb-2 block">Tailors</label>

                <!-- Dropdown button -->
                <button @click="open = !open"
                  type="button"
                  class="w-full flex items-center justify-between border rounded-lg px-4 py-2 h-12 text-sm text-gray-700 bg-white focus:ring-2 focus:ring-primary/40 transition">
                  <span x-show="selected.length === 0" class="text-gray-400">Select Tailors</span>
                  <div class="flex flex-wrap gap-2" x-show="selected.length > 0">
                    <template x-for="tailor in selected" :key="tailor.id">
                      <span class="bg-primary/10 text-primary px-2 py-1 rounded-md text-xs font-semibold"
                        x-text="tailor.name"></span>
                    </template>
                  </div>
                  <span class="material-symbols-outlined text-gray-500 text-lg">expand_more</span>
                </button>

                <!-- Dropdown list -->
                <div x-show="open" @click.away="open = false"
                  class="absolute w-full mt-2 bg-white border rounded-lg shadow-xl z-50 max-h-56 overflow-y-auto">

                  @foreach($tailors as $tailor)
                  <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 cursor-pointer transition">
                   <input type="checkbox" name="tailor_id[]" value="{{ $tailor->id }}"
    @change="
        if($event.target.checked){
            selected.push({id: {{ $tailor->id }}, name: '{{ $tailor->tailor_name }}'});
        } else {
            selected = selected.filter(x => x.id !== {{ $tailor->id }});
        }"
    class="rounded text-primary focus:ring-primary/50">
                    <span class="text-sm">{{ $tailor->tailor_name }}</span>
                  </label>
                  @endforeach
                </div>
              </div>

              <!-- Total Quantity Input (Half Width) -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">
                  {{ trans('messages.total_quantity', [], session('locale')) ?: 'Total Quantity' }}
                </p>
                <input type="number" 
                  name="total_quantity" 
                  id="total_quantity"
                  placeholder="{{ trans('messages.total_quantity_placeholder', [], session('locale')) ?: 'Enter total quantity' }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
              </label>
            </div>
            <br>
              <div x-data="{ mode: 'color', selectedTailors: [] }"
                class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm space-y-6">

                <div class="flex justify-between items-center mb-2">
                  <h2 class="text-xl font-bold">
                    {{ trans('messages.availability_options', [], session('locale')) }}
                  </h2>
                </div>

                <!-- الحد الأدنى العام -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border border-yellow-300 bg-yellow-50 p-4 rounded-xl">
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-yellow-500 text-2xl">warning</span>
                    <div>
                      <label class="text-sm font-semibold text-yellow-800">
                        {{ trans('messages.general_minimum_abaya', [], session('locale')) }}
                      </label>
                      <p class="text-xs text-yellow-600">
                        {{ trans('messages.system_alert_on_min_stock', [], session('locale')) }}
                      </p>
                    </div>
                  </div>
                  <input type="number" placeholder="0" name="notification_limit" id="notification_limit"
                    class="form-input h-11 w-32 sm:w-40 rounded-lg text-center border border-yellow-400 focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 bg-white shadow-sm" />
                </div>
                <!-- Tabs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition hover:bg-secondary/50"
                    :class="mode === 'color' ? 'border-primary bg-secondary/40' : 'border-gray-300'">
                    <input type="radio" name="mode" value="color" x-model="mode" />
                    <span>{{ trans('messages.by_color', [], session('locale')) }}</span>
                  </label>

                  <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition hover:bg-secondary/50"
                    :class="mode === 'size' ? 'border-primary bg-secondary/40' : 'border-gray-300'">
                    <input type="radio" name="mode" value="size" x-model="mode" />
                    <span>{{ trans('messages.by_size', [], session('locale')) }}</span>
                  </label>

                  <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition hover:bg-secondary/50"
                    :class="mode === 'color_size' ? 'border-primary bg-secondary/40' : 'border-gray-300'">
                    <input type="radio" name="mode" value="color_size" x-model="mode" />
                    <span>{{ trans('messages.by_color_and_size', [], session('locale')) }}</span>
                  </label>
                </div>
                <!-- ========== حسب اللون ========== -->
                <div x-show="mode === 'color'" x-transition x-data="{
    colors: [
        @foreach($colors as $c)
            { id: {{ $c->id }}, name: '{{ session('locale') == 'ar' ? $c->color_name_ar : $c->color_name_en }}', color_code: '{{ $c->color_code }}' },
        @endforeach
    ],
    rows: []
}">
                  <!-- Table -->
                  <table class="min-w-full text-sm border border-gray-200 rounded-lg mt-4">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 border">{{ trans('messages.color') }}</th>
                        <th class="px-4 py-2 border">{{ trans('messages.quantity') }}</th>
                        <th class="px-4 py-2 border">{{ trans('messages.action') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template x-for="(row, index) in rows" :key="index">
                        <tr>
                          <td class="px-4 py-2 border flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full border"
                              :style="'background:' + colors.find(c => c.id === row.color_id).color_code"></div>
                            <span x-text="colors.find(c => c.id === row.color_id).name"></span>
                            <input type="hidden" :name="'colors[' + row.color_id + '][color_id]'" :value="row.color_id">
                          </td>
                          <td class="px-4 py-2 border">
                            <input type="number" class="form-input h-10 w-24 text-center rounded-md border"
                              :name="'colors[' + row.color_id + '][qty]'"
                              x-model="row.qty" placeholder="0">
                          </td>
                          <td class="px-4 py-2 border text-center">
                            <button type="button" @click="rows.splice(index,1)" class="text-red-500 hover:text-red-700">
                              <span class="material-symbols-outlined">delete</span>
                            </button>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>

                  <!-- Add Color Button -->
                  <div class="mt-4 flex gap-3 items-center">
                    <select x-ref="colorSelect" class="border rounded-lg px-3 py-2 text-sm">
                      <template x-for="c in colors" :key="c.id">
                        <option :value="c.id" x-text="c.name"></option>
                      </template>
                    </select>
                    <button type="button" @click="
            const selectedId = Number($refs.colorSelect.value);
            if (!rows.find(r => r.color_id === selectedId)) {
                rows.push({ color_id: selectedId, qty: 0 });
            }
        " class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-1 text-sm">
                      <span class="material-symbols-outlined text-sm">add</span> {{ trans('messages.add_color') }}
                    </button>
                  </div>
                </div>




                <!-- ========== حسب المقاس ========== -->
                <div x-show="mode === 'size'" x-transition x-data="{
    availableSizes: [
        @foreach($sizes as $s)
            { id: {{ $s->id }}, name: '{{ session('locale') == 'ar' ? $s->size_name_ar : $s->size_name_en }}' },
        @endforeach
    ],
    rows: []
}">
                  <table class="min-w-full text-sm border border-gray-200 rounded-lg mt-4">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 border">{{ trans('messages.size') }}</th>
                        <th class="px-4 py-2 border">{{ trans('messages.quantity') }}</th>
                        <th class="px-4 py-2 border">{{ trans('messages.action') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template x-for="(row, index) in rows" :key="index">
                        <tr>
                          <td class="border px-2 py-1 text-center" x-text="row.size_name">
                            <input type="hidden" :name="'sizes['+row.size_id+'][size_id]'" :value="row.size_id">
                          </td>
                          <td class="border px-2 py-1 text-center">
                            <input type="number" class="w-16 text-center border rounded-md"
                              :name="'sizes['+row.size_id+'][qty]'"
                              x-model="row.qty" placeholder="0">
                          </td>
                          <td class="border px-2 py-1 text-center">
                            <button type="button" @click="rows.splice(index,1)" class="text-red-500 hover:text-red-700">
                              <span class="material-symbols-outlined">delete</span>
                            </button>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>

                  <!-- Add Size Button -->
                  <div class="mt-4 flex gap-3 items-center">
                    <select x-ref="sizeSelect" class="border rounded-lg px-4 py-3 text-base w-64">
                      <template x-for="s in availableSizes" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                      </template>
                    </select>
                    <button type="button" @click="
            const selectedId = Number($refs.sizeSelect.value);
            const selectedSize = availableSizes.find(s => s.id === selectedId);
            if (selectedSize && !rows.find(r => r.size_id === selectedId)) {
                rows.push({ size_id: selectedId, size_name: selectedSize.name, qty: 0 });
            }
        " class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-1 text-sm">
                      <span class="material-symbols-outlined text-sm">add</span> {{ trans('messages.add_size') }}
                    </button>
                  </div>
                </div>

                <!-- ========== حسب اللون والمقاس ========== -->
               <div x-show="mode === 'color_size'" x-transition x-data="{
    colors: [
        @foreach($colors as $c)
            { id: {{ $c->id }}, name: '{{ session('locale') == 'ar' ? $c->color_name_ar : $c->color_name_en }}', color_code: '{{ $c->color_code }}', sizes: [] },
        @endforeach
    ],
    availableSizes: [
        @foreach($sizes as $s)
            { id: {{ $s->id }}, name: '{{ session('locale') == 'ar' ? $s->size_name_ar : $s->size_name_en }}' },
        @endforeach
    ]
}">
    <div class="overflow-x-auto mt-4">
        <table class="min-w-full text-sm border border-gray-200 rounded-lg mt-4">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 border">{{ trans('messages.color') }}</th>
                    <th class="px-4 py-2 border">{{ trans('messages.size') }}</th>
                    <th class="px-4 py-2 border">{{ trans('messages.quantity') }}</th>
                    <th class="px-4 py-2 border">{{ trans('messages.action') }}</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(color, ci) in colors" :key="ci">
                    <template x-for="(size, si) in color.sizes" :key="si">
                        <tr>
                            <td class="px-4 py-2 border flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full border" :style="'background:' + color.color_code"></div>
                                <span x-text="color.name"></span>
                            </td>
                            <td class="border px-2 py-1">
                                <select class="form-select h-14 w-48 rounded-lg text-base px-3 py-2"
                                        :name="'color_sizes[' + color.id + '][' + size.size_id + '][size_id]'"
                                        x-model="size.size_id">
                                    <template x-for="s in availableSizes" :key="s.id">
                                        <option :value="s.id" x-text="s.name"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="border px-2 py-1 text-center">
                                <input type="number"
                                       class="w-16 text-center border rounded-md"
                                       :name="'color_sizes[' + color.id + '][' + size.size_id + '][qty]'"
                                       x-model="size.qty"
                                       placeholder="0">
                            </td>
                            <td class="border px-2 py-1 text-center">
                                <button type="button" @click="color.sizes.splice(si, 1)" class="text-red-500 hover:text-red-700">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>

        <!-- Add Color-Size Row -->
        <div class="mt-4 flex gap-3 items-center">
            <label class="text-sm font-medium">{{ trans('messages.add_new_row') }}</label>
            <select x-ref="colorSelect" class="border rounded-lg px-3 py-2 text-sm">
                <template x-for="c in colors" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
            <button type="button" @click="
                const id = Number($refs.colorSelect.value);
                const color = colors.find(c => c.id === id);
                if (color) {
                    color.sizes.push({
                        size_id: availableSizes[0].id,
                        qty: 0
                    });
                }
            " class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-1 text-sm">
                <span class="material-symbols-outlined text-sm">add</span> {{ trans('messages.add') }}
            </button>
        </div>
    </div>
</div>

                <div class="mt-6">
                  <button
                    type="submit"
                    class="w-full bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 text-center">
                    {{ trans('messages.save', [], session('locale')) }}
                  </button>
                </div>


              </div>



            </div>


          </div>

      </div>

    </div>

</main>


</div>
</form>
</div>
</div>

@include('layouts.footer')
@endsection