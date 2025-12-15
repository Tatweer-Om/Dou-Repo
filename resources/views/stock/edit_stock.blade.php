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



  <!-- محتوى الصفحة -->
  <div class="p-4 sm:p-6 space-y-6 overflow-y-auto" style="max-height: calc(100vh - 120px);">
 
    <div x-transition>
      <div class="space-y-6">

      <form id="update_abaya" enctype="multipart/form-data">
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
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" value="{{$stock->abaya_code ?? ''}}" />
              </label>

              <!-- Design Name -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.design_name', [], session('locale')) }}</p>
                <input type="text" name="design_name" id="design_name"
                  placeholder="{{ trans('messages.design_name_placeholder', [], session('locale')) }}"
                  class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" value="{{$stock->design_name ?? ''}}" />
              </label>

              <!-- Generate Barcode -->
      <label class="flex flex-col">
    <p class="text-sm font-medium mb-2">Barcode</p>

    <!-- Show barcode number -->
    <input type="text" name="barcode" id="barcode"
        value="{{ $stock->barcode ?? '' }}"
        class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" 
        readonly />

    <!-- Barcode SVG -->
</label>

<div class="col-span-3 space-y-2" x-data="imageUploader()">

    <p class="text-sm font-medium mb-2">{{ trans('messages.abaya_images', [], session('locale')) }}</p>

    <!-- Upload Input -->
    <input type="file" multiple class="form-input border rounded-lg px-4 py-2 w-full"
           name="images[]" @change="handleFiles($event)" />

    <!-- EXISTING DB IMAGES -->
    <div class="flex flex-wrap gap-3 mt-3" id="imageContainer">
        @foreach($stock->images as $image)
            <div class="relative w-32 h-32 border rounded-lg overflow-hidden" id="image-{{ $image->id }}">
                <img src="{{ asset($image->image_path) }}" class="object-cover w-full h-full" />

                <button type="button"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 delete-image"
                        data-id="{{ $image->id }}">
                    &times;
                </button>
            </div>
        @endforeach
    </div>

    <!-- NEW PREVIEW IMAGES -->
    <div class="flex flex-wrap gap-3 mt-3">
        <template x-for="(img, index) in images" :key="index">
            <div class="relative w-32 h-32 border rounded-lg overflow-hidden">
                <img :src="img.url" class="object-cover w-full h-full" />

                <!-- Remove Preview -->
                <button type="button"
                        @click="removeImage(index)"
                        class="absolute top-1 right-1 bg-gray-700 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-black">
                    &times;
                </button>
            </div>
        </template>
    </div>

</div>

<input type="hidden" value="{{$stock->id}}" name="stock_id" id="stock_id"/>
              <!-- Category -->
              <label class="flex flex-col">
                <p class="text-sm font-medium mb-2">{{ trans('messages.category', [], session('locale')) }}</p>
                <select class="form-select h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="category_id" id="category_id">
                  <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $stock->category_id == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                  @endforeach
                </select>
              </label>

              <!-- Description (spans all columns) -->
              <label class="flex flex-col col-span-3">
    <p class="text-sm font-medium mb-2">{{ trans('messages.description', [], session('locale')) }}</p>
    <textarea rows="3" name="abaya_notes" id="abaya_notes"
      placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}"
      class="form-textarea rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary/50">{{ $stock->abaya_notes ?? '' }}</textarea>
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
    name="cost_price" id="cost_price"
    value="{{ $stock->cost_price ?? '' }}"
    placeholder="{{ trans('messages.cost_price_placeholder', [], session('locale')) }}"
    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
</label>

<label class="flex flex-col">
  <p class="text-sm font-medium mb-2">
    {{ trans('messages.sale_price', [], session('locale')) }}
  </p>
  <input type="number"
    name="sales_price" id="sales_price"
    value="{{ $stock->sales_price ?? '' }}"
    placeholder="{{ trans('messages.sale_price_placeholder', [], session('locale')) }}"
    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
</label>

<label class="flex flex-col">
  <p class="text-sm font-medium mb-2">
    {{ trans('messages.tailor_value', [], session('locale')) }}
  </p>
  <input type="number"
    name="tailor_charges" id="tailor_charges"
    value="{{ $stock->tailor_charges ?? '' }}"
    placeholder="{{ trans('messages.tailor_value_placeholder', [], session('locale')) }}"
    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
</label>


          </div>


          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tailor Input (Half Width) -->
            <div x-data="{ 
    open: false, 
    selected: [
      @if(!empty($selectedTailors) && is_array($selectedTailors))
        @foreach($tailors as $tailor)
          @if(in_array($tailor->id, $selectedTailors))
            {id: {{ $tailor->id }}, name: '{{ addslashes($tailor->tailor_name) }}'},
          @endif
        @endforeach
      @endif
    ]
  }" class="relative">
              <label class="text-sm font-medium mb-2 block">Tailors</label>

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
        <input type="checkbox" name="tailor_id[]" 
          value="{{ $tailor->id }}"
          :checked="selected.some(s => s.id === {{ $tailor->id }})"
          @change="
                 if($event.target.checked){
                     if(!selected.some(s => s.id === {{ $tailor->id }})){
                         selected.push({id: {{ $tailor->id }}, name: '{{ addslashes($tailor->tailor_name) }}'});
                     }
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
                value="{{ $stock->quantity ?? '' }}"
                placeholder="{{ trans('messages.total_quantity_placeholder', [], session('locale')) ?: 'Enter total quantity' }}"
                class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" />
            </label>
          </div> <br>
            <div x-data="{ mode: 'color_size', selectedTailors: [] }"
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
                <input type="number" placeholder="0" name="notification_limit" id="notification_limit" value="{{ $stock->notification_limit ?? '' }}"
                  class="form-input h-11 w-32 sm:w-40 rounded-lg text-center border border-yellow-400 focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 bg-white shadow-sm" />
              </div>
              <!-- Tabs -->
              <div class="w-full">
                <label class="flex items-center gap-2 p-3 border border-primary bg-secondary/40 rounded-lg cursor-pointer transition hover:bg-secondary/50 w-full">
                  <input type="radio" name="mode" value="color_size" x-model="mode" checked/>
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
    rows: [
        @foreach($stock->colors as $row)
        { color_id: {{ $row->color_id }}, qty: {{ $row->qty }} },
        @endforeach
    ]
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
            <!-- Color preview and name -->
            <td class="px-4 py-2 border flex items-center gap-2">
              <div class="w-6 h-6 rounded-full border" :style="'background:' + colors.find(c => c.id === row.color_id).color_code"></div>
              <span class="text-sm font-medium" x-text="colors.find(c => c.id === row.color_id).name"></span>
              <!-- Hidden input to submit -->
              <input type="hidden" :name="'colors[' + row.color_id + '][color_id]'" :value="row.color_id">
            </td>

            <!-- Quantity -->
            <td class="px-4 py-2 border">
              <input type="number"
                class="form-input h-10 w-24 text-center rounded-md border"
                :name="'colors[' + row.color_id + '][qty]'"
                x-model="row.qty"
                placeholder="0">
            </td>

            <!-- Delete button -->
            <td class="px-4 py-2 border text-center">
              <button @click="rows.splice(index,1)" class="text-red-500 hover:text-red-700">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <!-- Add Color Button + Select -->
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
    rows: [
        @foreach($stock->sizes as $row)
        { size_id: {{ $row->size_id }}, size_name: '{{ session('locale') == 'ar' ? $row->size->size_name_ar : $row->size->size_name_en }}', qty: {{ $row->qty }} },
        @endforeach
    ]
}">
    <!-- Table -->
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
            <!-- Size Name -->
            <td class="border px-2 py-1 text-center" x-text="row.size_name">
              <input type="hidden" :name="'sizes['+row.size_id+'][size_id]'" :value="row.size_id">
            </td>

            <!-- Quantity -->
            <td class="border px-2 py-1 text-center">
              <input type="number" class="w-16 text-center border rounded-md"
                :name="'sizes['+row.size_id+'][qty]'" x-model="row.qty" placeholder="0">
            </td>

            <!-- Delete button -->
            <td class="border px-2 py-1 text-center">
              <button @click="rows.splice(index,1)" class="text-red-500 hover:text-red-700">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <!-- Add Size Button with Select -->
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
            {
                id: {{ $c->id }},
                name: '{{ session('locale') == 'ar' ? $c->color_name_ar : $c->color_name_en }}',
                color_code: '{{ $c->color_code }}',
                sizes: [
                    @foreach($stock->colorSizes->where('color_id', $c->id) as $cs)
                        { size_id: {{ $cs->size_id }}, qty: {{ $cs->qty }} },
                    @endforeach
                ]
            },
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
                                <!-- Hidden input for form submission -->
                                <input type="hidden" :name="'color_sizes[' + color.id + '][' + size.size_id + '][color_id]'" :value="color.id">
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
                <span class="material-symbols-outlined text-sm">add</span>
                {{ trans('messages.add') }}
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