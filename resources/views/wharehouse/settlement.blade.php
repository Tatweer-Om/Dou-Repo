@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.settlement_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6" x-data="settlementPage()" x-init="init()">
  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.monthly_sales_settlements', [], session('locale')) }}</h1>
        <p class="text-gray-500 text-sm">{{ trans('messages.settlement_description', [], session('locale')) }}</p>
      </div>
      <a href="/boutiques/index.php"
         class="w-full sm:w-auto px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white hover:opacity-90 font-semibold text-center">
        {{ trans('messages.back_to_boutiques', [], session('locale')) }}
      </a>
    </div>

    <!-- Tabs -->
    <div class="bg-white border border-pink-100 rounded-2xl shadow-sm">
      <div class="flex gap-2 p-2 border-b bg-gradient-to-r from-pink-50 via-purple-50 to-gray-50 overflow-x-auto no-scrollbar">
        <button @click="tab='new'" :class="tab==='new' ? 'bg-[var(--primary-color)] text-white' : 'bg-white text-gray-700 border'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition whitespace-nowrap">
          {{ trans('messages.new_settlement', [], session('locale')) }}
        </button>
        <button @click="tab='history'" :class="tab==='history' ? 'bg-[var(--primary-color)] text-white' : 'bg-white text-gray-700 border'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition whitespace-nowrap">
          {{ trans('messages.settlements_log', [], session('locale')) }}
        </button>
      </div>

      <!-- New Settlement -->
      <section x-show="tab==='new'" class="p-4 space-y-6" x-cloak>

        <!-- Filters + Attach -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-3">
            <label class="text-sm font-semibold text-gray-700">{{ trans('messages.boutique', [], session('locale')) }}</label>
            <select class="w-full h-12 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"
                    x-model="filters.boutique">
              <option value="" disabled>{{ trans('messages.select_boutique', [], session('locale')) }}</option>
              <template x-for="b in boutiques" :key="b.id">
                <option :value="b.id" x-text="b.name"></option>
              </template>
            </select>
          </div>
          <div class="md:col-span-3">
            <label class="text-sm font-semibold text-gray-700">{{ trans('messages.from', [], session('locale')) }}</label>
            <input type="date" class="w-full h-12 rounded-xl border border-pink-200 px-3"
                   x-model="filters.from">
          </div>
          <div class="md:col-span-3">
            <label class="text-sm font-semibold text-gray-700">{{ trans('messages.to', [], session('locale')) }}</label>
            <input type="date" class="w-full h-12 rounded-xl border border-pink-200 px-3"
                   x-model="filters.to">
          </div>
          <div class="md:col-span-3 flex items-end gap-2">
            <button @click="loadTransfers()" class="flex-1 h-12 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-700 font-semibold">
              {{ trans('messages.load_shipments_data', [], session('locale')) }}
            </button>
            <label class="flex items-center gap-2 h-12 px-3 rounded-xl border border-pink-200 cursor-pointer text-sm">
              <span class="material-symbols-outlined">attach_file_add</span>
              <span>{{ trans('messages.attach_report', [], session('locale')) }}</span>
              <input type="file" class="hidden" @change="onAttach($event)">
            </label>
          </div>
        </div>

        <template x-if="attachment.name">
          <div class="flex items-center gap-3 p-3 rounded-xl bg-pink-50 border border-pink-100 text-sm">
            <span class="material-symbols-outlined text-[var(--primary-color)]">description</span>
            <div class="flex-1 min-w-0">
              <div class="font-semibold truncate" x-text="attachment.name"></div>
              <div class="text-gray-500" x-text="attachment.sizeLabel"></div>
            </div>
            <button @click="removeAttachment()" class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-xs">{{ trans('messages.delete', [], session('locale')) }}</button>
          </div>
        </template>

        <!-- Legend + CSV -->
        <div class="flex flex-wrap items-center gap-3 justify-between">
          <div class="text-xs text-gray-600 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-green-50 border border-green-200"></span> {{ trans('messages.matching', [], session('locale')) }}</span>
            <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-pink-50 border border-pink-200"></span> {{ trans('messages.difference_less_or_more', [], session('locale')) }}</span>
            <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-red-50 border border-red-200"></span> {{ trans('messages.suspicious_sold_greater_than_sellable', [], session('locale')) }}</span>
          </div>
          <button @click="exportNewCSV()"
                  class="px-3 py-2 rounded-lg bg-white border hover:bg-gray-50 text-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-base">download</span> {{ trans('messages.export_csv', [], session('locale')) }}
          </button>
        </div>

        <!-- Comparison Table -->
        <div class="overflow-x-auto rounded-2xl border border-pink-100">
          <table class="w-full text-sm min-w-[1100px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.code', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.color', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.size', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.sent', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.pulled', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.sellable', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.sold_report', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.difference', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.price', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.total', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.details', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="rows.length===0">
                <tr><td colspan="11" class="text-center text-gray-400 py-10">
                  {{ trans('messages.no_data_use_filters', [], session('locale')) }}
                </td></tr>
              </template>

              <template x-for="(r,idx) in rows" :key="r.uid">
                <tr class="border-t"
                    :class="rowClass(r)">
                  <td class="px-3 py-2 font-semibold" x-text="r.code"></td>
                  <td class="px-3 py-2">
                    <template x-if="r.color">
                      <span class="inline-flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full border" :style="'background:'+r.color_code"></span>
                        <span x-text="r.color"></span>
                      </span>
                    </template>
                    <template x-if="!r.color">—</template>
                  </td>
                  <td class="px-3 py-2" x-text="r.size || '—'"></td>
                  <td class="px-3 py-2" x-text="r.sent"></td>
                  <td class="px-3 py-2" x-text="r.pulled"></td>
                  <td class="px-3 py-2" x-text="r.sellable"></td>
                  <td class="px-3 py-2">
                    <input type="number" min="0" class="h-11 w-24 text-center rounded-lg border border-pink-200"
                           x-model.number="r.sold" @input="recalc(idx)">
                  </td>
                  <td class="px-3 py-2 font-semibold"
                      :class="r.diff!==0 ? 'text-[var(--primary-color)]' : 'text-gray-700'"
                      x-text="r.diff"></td>
                  <td class="px-3 py-2">
                    <input type="number" min="0" class="h-11 w-24 text-center rounded-lg border border-pink-200"
                           x-model.number="r.price" @input="recalc(idx)">
                  </td>
                  <td class="px-3 py-2 font-bold text-gray-900" x-text="formatCurrency(r.total)"></td>
                  <td class="px-3 py-2 text-center">
                    <button @click="openMovement(r)" class="px-3 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-[var(--primary-color)] text-xs font-semibold">
                      {{ trans('messages.view_details', [], session('locale')) }}
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>

            <!-- Totals -->
            <tfoot x-show="rows.length">
              <tr class="bg-gray-50 border-t font-bold">
                <td class="px-3 py-3" colspan="3">{{ trans('messages.totals', [], session('locale')) }}</td>
                <td class="px-3 py-3" x-text="sum('sent')"></td>
                <td class="px-3 py-3" x-text="sum('pulled')"></td>
                <td class="px-3 py-3" x-text="sum('sellable')"></td>
                <td class="px-3 py-3" x-text="sum('sold')"></td>
                <td class="px-3 py-3" x-text="sum('diff')"></td>
                <td class="px-3 py-3">—</td>
                <td class="px-3 py-3" x-text="formatCurrency(sum('total'))"></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
          <div class="text-sm text-gray-600">
            {{ trans('messages.settlement_actions_note', [], session('locale')) }}
          </div>
          <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 w-full sm:w-auto">
            <button class="px-4 py-3 rounded-lg bg-white border hover:bg-gray-50 w-full sm:w-auto">{{ trans('messages.preview_invoice', [], session('locale')) }}</button>
            <button @click="saveSettlement()" class="px-6 py-3 rounded-lg bg-[var(--primary-color)] text-white font-bold hover:opacity-90 w-full sm:w-auto">
              {{ trans('messages.save_settlement', [], session('locale')) }}
            </button>
          </div>
        </div>
      </section>

      <!-- History Tab -->
      <section x-show="tab==='history'" class="p-4 space-y-4" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <input type="search" class="h-12 px-3 border border-pink-200 rounded-lg w-full"
                 placeholder="{{ trans('messages.search_by_month_boutique', [], session('locale')) }}" x-model="histSearch">
          <input type="month" class="h-12 px-2 border border-pink-200 rounded-lg w-full" x-model="histMonth">
          <div class="flex items-center justify-end">
            <button @click="exportHistoryCSV()"
                    class="px-3 py-2 rounded-lg bg-white border hover:bg-gray-50 text-sm flex items-center gap-1 w-full sm:w-auto justify-center">
              <span class="material-symbols-outlined text-base">download</span> {{ trans('messages.export_csv', [], session('locale')) }}
            </button>
          </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-pink-100">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.month', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.boutique', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.number_of_items', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.sales_currency', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.difference', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.attachment', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-center font-bold">{{ trans('messages.details', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="history.length===0">
                <tr><td colspan="7" class="text-center text-gray-400 py-10">{{ trans('messages.no_record_yet', [], session('locale')) }}</td></tr>
              </template>
              <template x-for="h in historyFiltered" :key="h.no">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2" x-text="h.month"></td>
                  <td class="px-3 py-2" x-text="boutiqueName(h.boutique)"></td>
                  <td class="px-3 py-2" x-text="h.items"></td>
                  <td class="px-3 py-2 font-bold" x-text="formatCurrency(h.amount)"></td>
                  <td class="px-3 py-2" x-text="h.diff"></td>
                  <td class="px-3 py-2 text-center">
                    <button class="px-3 py-1 rounded-lg bg-white border hover:bg-gray-50 text-xs">{{ trans('messages.view', [], session('locale')) }}</button>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button class="px-3 py-1 rounded-lg bg-pink-100 hover:bg-pink-200 text-[var(--primary-color)] text-xs" @click="openHistoryDetails(h)">{{ trans('messages.view_details', [], session('locale')) }}</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <!-- Movements Modal -->
  <div x-show="showMov" x-transition.opacity x-cloak class="fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4">
    <div @click.away="showMov=false" class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden">
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-bold text-[var(--primary-color)]">
          {{ trans('messages.movements_details_for_code', [], session('locale')) }} <span x-text="movRow.code"></span>
        </h3>
        <button @click="showMov=false" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>
      <div class="p-4 max-h-[70vh] overflow-y-auto">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-pink-50 text-gray-700">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.date', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.type', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.from', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.to', [], session('locale')) }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="m in movDetails" :key="m.id">
                <tr class="border-t">
                  <td class="px-3 py-2" x-text="m.date"></td>
                  <td class="px-3 py-2" x-text="m.type"></td>
                  <td class="px-3 py-2" x-text="m.from"></td>
                  <td class="px-3 py-2" x-text="m.to"></td>
                  <td class="px-3 py-2" x-text="m.qty"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
      <div class="p-4 border-t text-right">
        <button @click="showMov=false" class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 w-full sm:w-auto">{{ trans('messages.close', [], session('locale')) }}</button>
      </div>
    </div>
  </div>
</main>

<script>
function settlementPage(){
  return {
    tab: 'new',
    boutiques: [
      {id:'btk-noor', name:'بوتيك النور'},
      {id:'btk-almouj', name:'بوتيك الموج'},
      {id:'btk-salam', name:'بوتيك السلام'}
    ],
    filters: {boutique:'', from:'', to:''},
    attachment: {name:'', sizeLabel:''},
    rows: [],

    // Dummy transfers & pulls (would come from backend)
    transfers: [
      // code, color, color_code, size, sent, pulled
      {code:'ABY101', color:null, color_code:'#000000', size:'M', sent:10, pulled:2, price:16.5},
      {code:'ABY205', color:'أسود', color_code:'#000000', size:null, sent:8, pulled:0, price:18},
      {code:'ABY330', color:'بيج', color_code:'#f5deb3', size:'M', sent:6, pulled:1, price:20},
      {code:'ABY330', color:'رمادي', color_code:'#b0b0b0', size:'L', sent:4, pulled:0, price:20},
    ],

    // Movements log (dummy)
    movements: [
      {id:1, code:'ABY101', date:'2025-11-01', type:'إرسال', from:'المخزن', to:'بوتيك النور', qty:6},
      {id:2, code:'ABY101', date:'2025-11-05', type:'إرسال', from:'المخزن', to:'بوتيك النور', qty:4},
      {id:3, code:'ABY101', date:'2025-11-20', type:'سحب', from:'بوتيك النور', to:'المخزن', qty:2},
      {id:4, code:'ABY205', date:'2025-11-08', type:'إرسال', from:'المخزن', to:'بوتيك النور', qty:8},
      {id:5, code:'ABY330', date:'2025-11-10', type:'إرسال', from:'المخزن', to:'بوتيك النور', qty:6},
      {id:6, code:'ABY330', date:'2025-11-20', type:'سحب', from:'بوتيك النور', to:'بوتيك الموج', qty:1},
      {id:7, code:'ABY330', date:'2025-11-22', type:'إرسال', from:'المخزن', to:'بوتيك النور', qty:4},
    ],

    // History (dummy)
    history: [
      {no:'STL-2025-10-01', month:'2025-10', boutique:'btk-noor', items:32, amount:510.5, diff:0},
      {no:'STL-2025-10-02', month:'2025-10', boutique:'btk-almouj', items:21, amount:360.0, diff:2},
    ],
    histSearch: '', histMonth: '',

    showMov:false, movRow:{}, movDetails:[],

    // Helpers
    formatCurrency(n){ const v=Number(n||0); return v.toLocaleString('ar-EG',{minimumFractionDigits:2, maximumFractionDigits:2}) + ' ر.ع'; },
    boutiqueName(id){ const b=this.boutiques.find(x=>x.id===id); return b?b.name:id; },

    // Load transfers based on filters (dummy: we just map transfers)
    loadTransfers(){
      if(!this.filters.boutique || !this.filters.from || !this.filters.to){
        alert('{{ trans('messages.please_select_boutique_and_period', [], session('locale')) }}');
        return;
      }
      this.rows = this.transfers.map((t,i)=>{
        const sellable = Math.max(0, (t.sent||0) - (t.pulled||0));
        return {
          uid: t.code + '|' + (t.size||'') + '|' + (t.color||''),
          code: t.code, color: t.color, color_code: t.color_code, size: t.size,
          sent: t.sent, pulled: t.pulled, sellable,
          sold: 0, price: t.price||0, diff: 0, total: 0
        };
      });
      // small UX toast
      this.toast('{{ trans('messages.shipments_data_loaded', [], session('locale')) }}');
    },

    recalc(idx){
      const r = this.rows[idx];
      r.diff = Number(r.sold||0) - Number(r.sellable||0);
      r.total = Number(r.sold||0) * Number(r.price||0);
    },

    sum(field){
      return this.rows.reduce((s,r)=> s + Number(r[field]||0), 0);
    },

    rowClass(r){
      if (r.sold===undefined || r.sold===null) return '';
      // suspicious if sold > sellable
      if (Number(r.sold)>Number(r.sellable)) return 'bg-red-50';
      // pink if diff != 0
      if (Number(r.diff)!==0) return 'bg-pink-50';
      // green if ok
      if (Number(r.diff)===0 && (r.sold||0)>0) return 'bg-green-50';
      return '';
    },

    openMovement(r){
      this.movRow = r;
      this.movDetails = this.movements.filter(m => m.code===r.code);
      this.showMov = true;
    },

    onAttach(e){
      const f = e.target.files[0];
      if(!f) return;
      const size = f.size >= 1024*1024
        ? (f.size/1024/1024).toFixed(2)+' MB'
        : (f.size/1024).toFixed(1)+' KB';
      this.attachment = {name:f.name, sizeLabel:size};
    },
    removeAttachment(){ this.attachment = {name:'', sizeLabel:''}; },

    saveSettlement(){
      if(this.rows.length===0){ alert('{{ trans('messages.no_data_to_save', [], session('locale')) }}'); return; }
      const month = (this.filters.from||'').slice(0,7) || new Date().toISOString().slice(0,7);
      const amount = this.sum('total');
      const items  = this.sum('sold');
      const diff   = this.sum('diff');
      const no = 'STL-' + month + '-' + String(this.history.length+1).padStart(2,'0');
      this.history.unshift({no, month, boutique:this.filters.boutique, items, amount, diff});
      this.toast('{{ trans('messages.settlement_saved_successfully', [], session('locale')) }}');
      // reset editable values
      this.rows.forEach(r=>{ r.sold=0; r.diff=0; r.total=0; });
    },

    // History filter
    get historyFiltered(){
      const q = this.histSearch.toLowerCase();
      const m = this.histMonth;
      return this.history.filter(h=>{
        const txt = `${h.month} ${this.boutiqueName(h.boutique)}`.toLowerCase();
        const qOk = !q || txt.includes(q);
        const mOk = !m || h.month===m;
        return qOk && mOk;
      });
    },

    // CSV Export
    exportNewCSV(){
      if(!this.rows.length){ alert('{{ trans('messages.no_data_to_export', [], session('locale')) }}'); return; }
      const head = ['{{ trans('messages.code', [], session('locale')) }}','{{ trans('messages.color', [], session('locale')) }}','{{ trans('messages.size', [], session('locale')) }}','{{ trans('messages.sent', [], session('locale')) }}','{{ trans('messages.pulled', [], session('locale')) }}','{{ trans('messages.sellable', [], session('locale')) }}','{{ trans('messages.sold_report', [], session('locale')) }}','{{ trans('messages.difference', [], session('locale')) }}','{{ trans('messages.price', [], session('locale')) }}','{{ trans('messages.total', [], session('locale')) }}'];
      const lines = [head];
      this.rows.forEach(r=>{
        lines.push([
          r.code,
          r.color || '',
          r.size || '',
          r.sent,
          r.pulled,
          r.sellable,
          r.sold || 0,
          r.diff || 0,
          r.price || 0,
          r.total || 0
        ]);
      });
      this.downloadCSV(lines, 'settlement-new.csv');
    },

    exportHistoryCSV(){
      if(!this.historyFiltered.length){ alert('{{ trans('messages.no_data_to_export', [], session('locale')) }}'); return; }
      const head = ['{{ trans('messages.operation_number', [], session('locale')) }}','{{ trans('messages.month', [], session('locale')) }}','{{ trans('messages.boutique', [], session('locale')) }}','{{ trans('messages.number_of_items', [], session('locale')) }}','{{ trans('messages.sales_currency', [], session('locale')) }}','{{ trans('messages.difference', [], session('locale')) }}'];
      const lines = [head];
      this.historyFiltered.forEach(h=>{
        lines.push([h.no, h.month, this.boutiqueName(h.boutique), h.items, h.amount, h.diff]);
      });
      this.downloadCSV(lines, 'settlement-history.csv');
    },

    downloadCSV(rows, filename){
      const csv = rows.map(r=>r.map(v=>`"${String(v).replace(/"/g,'""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    },

    // Toast
    toast(msg){
      clearTimeout(this._t);
      this._toastMsg = msg;
      const el = document.getElementById('toast');
      if (!el) return;
      el.classList.remove('hidden');
      el.querySelector('.msg').textContent = msg;
      this._t = setTimeout(()=> el.classList.add('hidden'), 2000);
    },

    init(){
      // Prefill month range (dummy)
      const now = new Date();
      const y = now.getFullYear(), m = String(now.getMonth()+1).padStart(2,'0');
      this.filters.from = `${y}-${m}-01`;
      this.filters.to   = `${y}-${m}-30`;
      this.filters.boutique = 'btk-noor';
    }
  }
}
</script>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-[9999] px-4 py-2 rounded-full bg-green-600 text-white shadow-lg font-semibold">
  <span class="msg">{{ trans('messages.done', [], session('locale')) }}</span>
</div>
@include('layouts.footer')
@endsection
