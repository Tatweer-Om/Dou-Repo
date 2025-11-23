@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_stock_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="{ showDetails: false, loading: false, showQuantity: false, actionType: 'add' }">
  <div class="w-full max-w-screen-xl xl:pr-8 xl:pl-64 mx-auto">

    <!-- Page title and add button -->
  <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
    <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
        {{ trans('messages.manage_abayas', [], session('locale')) }}
    </h2>
    <a href="/abaya/view_abaya.php"
       class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
        <span class="material-symbols-outlined me-1">add</span>
        {{ trans('messages.add_abaya', [], session('locale')) }}
    </a>
</div>

<!-- Search and filters -->
<div class="sticky top-[var(--header-h,64px)] z-10 bg-white/80 backdrop-blur border border-pink-100 rounded-2xl shadow-sm">
    <div class="py-3 px-4">
        <div class="flex flex-wrap items-center gap-2 overflow-x-auto no-scrollbar">
            <div class="flex-1 min-w-[45%]">
                <input id="q" type="search" 
                       placeholder="{{ trans('messages.search_placeholder', [], session('locale')) }}"
                       class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
            </div>
            <select class="shrink-0 rounded-xl border border-pink-200 h-11 text-sm">
                <option>{{ trans('messages.all', [], session('locale')) }}</option>
                <option>{{ trans('messages.available', [], session('locale')) }}</option>
                <option>{{ trans('messages.low', [], session('locale')) }}</option>
                <option>{{ trans('messages.out_of_stock', [], session('locale')) }}</option>
                <option>{{ trans('messages.hidden', [], session('locale')) }}</option>
            </select>
            <button class="shrink-0 inline-flex items-center gap-1 rounded-xl px-3 h-11 text-sm text-white bg-[var(--primary-color)] hover:bg-pink-700 transition-all">
                <span class="material-symbols-outlined text-base">tune</span>
                {{ trans('messages.filter', [], session('locale')) }}
            </button>
        </div>
    </div>
</div>


    <!-- Mobile cards -->
  <section class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:hidden gap-4">
    @for ($i = 1; $i <= 2; $i++)
        <div class="bg-white rounded-xl shadow-sm border border-pink-100 p-4 flex flex-col gap-3">
            <div class="flex gap-4">
                <div class="w-20 h-24 rounded-md overflow-hidden bg-gray-100 flex-shrink-0">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvKg5AhaDdRqA3r4CQmvGTzP9_cvocRFo_JpwXjGANrU-NTxnLJbPXHosBJvcOJrOMF7iniPDAqlDISIoKa9vYPlxQl1fxFUf_wWcg-2ZWZ4zVtj8DtYntIcmMCef6Gi9kc2-SNeJuOFhmVe3ktBod2zxXdlJVBktsokamFz6WtCj96iytmlQLinBdB_5yxzeepfYJBESQ9mj3dmkh_xJ9jv55Un9VL_VDKXordI9gSug-gM3t_dTLQp4G7Bzh8K5I0OZICpGkG5M"
                         alt="{{ trans('messages.abaya_image', [], session('locale')) }}" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1 text-sm">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-gray-900">ABY10{{ $i }}</h3>
                        <span class="text-[var(--primary-color)] font-semibold">
                            {{ trans('messages.size', [], session('locale')) }}: M
                        </span>
                    </div>
                    <p class="text-gray-600">
                        {{ trans('messages.color_material', [], session('locale')) }}
                    </p>
                    <p class="text-gray-600">
                        {{ trans('messages.quantity', [], session('locale')) }}: {{ 8 + $i }}
                    </p>
                </div>
            </div>

            <div class="mt-4 border-t pt-3">
                <div class="flex justify-around text-xs font-semibold text-gray-600">
                    <button @click="loading = true; setTimeout(() => { loading = false; showDetails = true }, 800)"
                            class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition">
                        <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full">info</span>
                        {{ trans('messages.details', [], session('locale')) }}
                    </button>

                    <button @click="showQuantity = true"
                            class="flex flex-col items-center gap-1 hover:text-green-600 transition">
                        <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                        {{ trans('messages.enter_quantity', [], session('locale')) }}
                    </button>

                    <button class="flex flex-col items-center gap-1 hover:text-blue-500 transition">
                        <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full">edit</span>
                        {{ trans('messages.edit', [], session('locale')) }}
                    </button>

                    <button class="flex flex-col items-center gap-1 hover:text-red-500 transition">
                        <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full">delete</span>
                        {{ trans('messages.delete', [], session('locale')) }}
                    </button>
                </div>
            </div>
        </div>
    @endfor
</section>


    <!-- Desktop table -->
    <section class="hidden xl:block mt-6">
      <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition">
        <table class="w-full text-sm min-w-[1024px]">
          <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
            <tr>
              <th class="text-right px-3 py-3 font-bold">الصورة</th>
              <th class="text-right px-3 py-3 font-bold">الكود</th>
              <th class="text-right px-3 py-3 font-bold">النوع</th>
              <th class="text-right px-3 py-3 font-bold">المقاس</th>
              <th class="text-right px-3 py-3 font-bold">اللون</th>
              <th class="text-right px-3 py-3 font-bold">الكمية</th>
              <th class="text-center px-3 py-3 font-bold">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 1; $i <= 3; $i++): ?>
              <tr class="border-t hover:bg-pink-50/60 transition">
                <td class="px-3 py-3"><img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvKg5AhaDdRqA3r4CQmvGTzP9_cvocRFo_JpwXjGANrU-NTxnLJbPXHosBJvcOJrOMF7iniPDAqlDISIoKa9vYPlxQl1fxFUf_wWcg-2ZWZ4zVtj8DtYntIcmMCef6Gi9kc2-SNeJuOFhmVe3ktBod2zxXdlJVBktsokamFz6WtCj96iytmlQLinBdB_5yxzeepfYJBESQ9mj3dmkh_xJ9jv55Un9VL_VDKXordI9gSug-gM3t_dTLQp4G7Bzh8K5I0OZICpGkG5M"
                     alt="صورة" class="w-12 h-16 rounded-md object-cover shadow-sm" /></td>
                <td class="px-3 py-3 font-bold text-gray-800">ABY10<?= $i ?></td>
                <td class="px-3 py-3 text-gray-600">خليجية</td>
                <td class="px-3 py-3 text-gray-600">M</td>
                <td class="px-3 py-3 text-gray-600 flex items-center gap-2">
                  <span class="inline-block w-4 h-4 rounded-full border" style="background-color:black;"></span> أسود
                </td>
                <td class="px-3 py-3 font-bold text-gray-900"><?= 8 + $i ?></td>
                <td class="px-3 py-3 text-center">
                  <div class="flex justify-center gap-5 text-[12px] font-semibold text-gray-700">
                    <button @click="loading = true; setTimeout(() => { loading = false; showDetails = true }, 800)"
                            class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition">
                      <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
                      تفاصيل
                    </button>

                    <button @click="showQuantity = true"
                            class="flex flex-col items-center gap-1 hover:text-green-600 transition">
                      <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                      كميات
                    </button>

                    <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition">
                      <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                      تعديل
                    </button>

                    <button class="flex flex-col items-center gap-1 hover:text-red-600 transition">
                      <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                      حذف
                    </button>
                  </div>
                </td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <!-- Loader -->
  <div x-show="loading" class="fixed inset-0 flex flex-col items-center justify-center bg-black/50 z-50">
    <div class="loader border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin mb-3"></div>
    <p class="text-white font-semibold">جارٍ تحميل التفاصيل...</p>
  </div>

  <!-- Details Modal -->
   <!-- Details Modal -->
  <div x-show="showDetails" x-transition.opacity x-cloak
       class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-3 sm:p-6">
    <div @click.away="showDetails = false"
         class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl transform transition-all duration-300 overflow-hidden">
      <!-- Header -->
      <div class="flex justify-between items-center p-4 border-b">
        <h2 class="text-lg sm:text-xl font-bold text-[var(--primary-color)]">تفاصيل العباية</h2>
        <button @click="showDetails = false" class="text-gray-400 hover:text-gray-600">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6 overflow-y-auto max-h-[75vh] text-sm text-gray-700">
        <!-- صورة + تفاصيل أساسية -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="rounded-xl overflow-hidden shadow">
            <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBvKg5AhaDdRqA3r4CQmvGTzP9_cvocRFo_JpwXjGANrU-NTxnLJbPXHosBJvcOJrOMF7iniPDAqlDISIoKa9vYPlxQl1fxFUf_wWcg-2ZWZ4zVtj8DtYntIcmMCef6Gi9kc2-SNeJuOFhmVe3ktBod2zxXdlJVBktsokamFz6WtCj96iytmlQLinBdB_5yxzeepfYJBESQ9mj3dmkh_xJ9jv55Un9VL_VDKXordI9gSug-gM3t_dTLQp4G7Bzh8K5I0OZICpGkG5M"
                 class="w-full h-full object-cover" alt="">
          </div>
          <div class="space-y-2">
            <p><span class="font-semibold">الكود:</span> ABY101</p>
            <p><span class="font-semibold">النوع:</span> خليجية فاخرة</p>
            <p><span class="font-semibold">الوصف:</span> عباية متعددة المقاسات والألوان.</p>
            <p><span class="font-semibold">الحالة:</span> <span class="text-green-600 font-bold">متوفرة</span></p>
          </div>
        </div>

        <hr class="border-dashed">

        <!-- 🟣 الكميات بالتفصيل -->
        <div class="space-y-6">
          <h3 class="font-bold text-[var(--primary-color)] text-base sm:text-lg">تفاصيل الكميات</h3>

          <!-- 1️⃣ حسب المقاس -->
          <div>
            <h4 class="font-semibold mb-2">حسب المقاس</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 sm:gap-3">
              <template x-for="item in [
                {size:'S', qty:5},
                {size:'M', qty:3},
                {size:'L', qty:7}
              ]" :key="item.size">
                <div class="p-3 border rounded-lg bg-gray-50 text-center font-bold text-gray-700 text-xs sm:text-sm">
                  <span x-text="'المقاس '+item.size"></span>
                  <span class="block text-[var(--primary-color)] mt-1" x-text="item.qty + ' قطعة'"></span>
                </div>
              </template>
            </div>
          </div>

          <!-- 2️⃣ حسب المقاس واللون -->
          <div>
            <h4 class="font-semibold mb-2">حسب المقاس واللون</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <template x-for="item in [
                {size:'S', color:'أسود', code:'#000', qty:2},
                {size:'S', color:'رمادي', code:'#b0b0b0', qty:3},
                {size:'M', color:'بيج', code:'#f5deb3', qty:4}
              ]" :key="item.size + item.color">
                <div class="flex justify-between items-center border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                  <div class="flex flex-col">
                    <span class="font-semibold" x-text="'المقاس: '+item.size"></span>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="w-4 h-4 rounded-full border" :style="'background:'+item.code"></span>
                      <span x-text="item.color"></span>
                    </div>
                  </div>
                  <span class="font-bold text-[var(--primary-color)]" x-text="item.qty + ' قطعة'"></span>
                </div>
              </template>
            </div>
          </div>

          <!-- 3️⃣ حسب اللون -->
          <div>
            <h4 class="font-semibold mb-2">حسب اللون</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 sm:gap-3">
              <template x-for="color in [
                {name:'أسود', code:'#000', qty:6},
                {name:'بيج', code:'#f5deb3', qty:2},
                {name:'رمادي', code:'#b0b0b0', qty:4}
              ]" :key="color.name">
                <div class="flex items-center justify-between border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                  <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full border" :style="'background:'+color.code"></span>
                    <span class="font-semibold" x-text="color.name"></span>
                  </div>
                  <span class="font-bold text-[var(--primary-color)]" x-text="color.qty + ' قطعة'"></span>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t bg-gray-50 flex justify-end">
        <button @click="showDetails = false"
                class="px-6 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition font-bold text-gray-700">
          إغلاق
        </button>
      </div>
    </div>
  </div>


  <!-- Modal: إدخال الكميات -->
  <div x-show="showQuantity" x-transition.opacity x-cloak 
       class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center">
    <div @click.away="showQuantity = false"
         class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl transform transition-all duration-300 overflow-hidden">

      <div class="flex justify-between items-center p-4 border-b">
        <h2 class="text-lg font-bold text-[var(--primary-color)]">إدارة كميات العباية</h2>
        <button @click="showQuantity = false" class="text-gray-400 hover:text-gray-600">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="flex justify-center gap-6 pt-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="qtyType" value="add" x-model="actionType">
          <span>إدخال جديد</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" name="qtyType" value="pull" x-model="actionType">
          <span>سحب كمية</span>
        </label>
      </div>

      <div class="p-6 space-y-10 max-h-[70vh] overflow-y-auto text-sm text-gray-700">

        <!-- 1️⃣ حسب المقاس -->
        <div class="space-y-3">
          <h3 class="font-semibold text-[var(--primary-color)]">1️⃣ حسب المقاس</h3>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <template x-for="size in ['S','M','L','XL','XXL']" :key="size">
              <div class="flex flex-col border rounded-lg p-3 shadow-sm">
                <label class="font-bold mb-1" x-text="'المقاس ' + size"></label>
                <input type="number" min="0" placeholder="الكمية"
                       class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center">
              </div>
            </template>
          </div>
        </div>

        <hr class="border-dashed">

        <!-- 2️⃣ حسب المقاس واللون -->
        <div class="space-y-3">
          <h3 class="font-semibold text-[var(--primary-color)]">2️⃣ حسب المقاس واللون</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="(combo, index) in [
                {size:'S', color:'أسود', code:'#000'}, 
                {size:'M', color:'بيج', code:'#f5deb3'}, 
                {size:'L', color:'رمادي', code:'#b0b0b0'}
            ]" :key="index">
              <div class="border rounded-lg p-3 shadow-sm">
                <div class="flex justify-between mb-2">
                  <span class="font-semibold" x-text="'المقاس: ' + combo.size"></span>
                  <div class="flex items-center gap-1">
                    <span class="w-4 h-4 rounded-full border" :style="'background-color:' + combo.code"></span>
                    <span class="font-semibold" x-text="combo.color"></span>
                  </div>
                </div>
                <input type="number" min="0" placeholder="الكمية"
                       class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center w-full">
              </div>
            </template>
          </div>
        </div>

        <hr class="border-dashed">

        <!-- 3️⃣ حسب اللون -->
        <div class="space-y-3">
          <h3 class="font-semibold text-[var(--primary-color)]">3️⃣ حسب اللون</h3>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <template x-for="color in [
                {name:'أسود', code:'#000'}, 
                {name:'بيج', code:'#f5deb3'}, 
                {name:'رمادي', code:'#b0b0b0'}, 
                {name:'كحلي', code:'#001f3f'}, 
                {name:'أخضر', code:'#006400'}
            ]" :key="color.name">
              <div class="flex flex-col border rounded-lg p-3 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                  <span class="w-4 h-4 rounded-full border" :style="'background-color:' + color.code"></span>
                  <span class="font-bold" x-text="color.name"></span>
                </div>
                <input type="number" min="0" placeholder="الكمية"
                       class="form-input h-10 rounded-lg border focus:ring-2 focus:ring-[var(--primary-color)] text-center">
              </div>
            </template>
          </div>
        </div>

        <!-- سبب السحب -->
        <div x-show="actionType === 'pull'" class="space-y-2 mt-4">
          <label class="font-semibold text-red-600">سبب سحب الكمية</label>
          <textarea class="w-full h-20 rounded-lg border border-red-200 focus:ring-2 focus:ring-red-400 text-sm p-2"
                    placeholder="مثلاً: تلف، خطأ في الإدخال السابق..."></textarea>
        </div>
      </div>

      <div class="p-4 border-t flex justify-end gap-3 bg-gray-50">
        <button @click="showQuantity = false"
                class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition text-gray-700 font-semibold">
          إلغاء
        </button>
        <button
                class="px-6 py-2 rounded-lg bg-[var(--primary-color)] text-white font-bold hover:bg-opacity-90 transition">
          حفظ العملية
        </button>
      </div>
    </div>
  </div>

</main>

@include('layouts.footer')
@endsection