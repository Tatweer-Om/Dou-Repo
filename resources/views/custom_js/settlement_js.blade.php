<!-- <script>
function settlementPage(){
  return {
    tab: 'new',
    boutiques: [],
    filters: {boutique:'', from:'', to:''},
    attachment: {name:'', sizeLabel:'', file: null},
    rows: [],

    // Movements log (dummy - can be enhanced later)
    movements: [],
    history: [],
    histSearch: '', histMonth: '',

    showMov:false, movRow:{}, movDetails:[],
    showSettlementDetails: false,
    settlementDetails: {},
    settlementDetailsLoading: false,
    loading: false,

    // Helpers
    formatCurrency(n){ const v=Number(n||0); return v.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2}) + ' ر.ع'; },
    boutiqueName(id){ 
      if (!id) return '';
      const b = this.boutiques.find(x => x.id == id || 'boutique-' + x.id == id);
      return b ? b.name : id; 
    },

    // Load boutiques from backend
    async loadBoutiques(){
      try {
        const response = await fetch('/get_boutiques_list');
        const data = await response.json();
        this.boutiques = data || [];
      } catch (error) {
        console.error('Error loading boutiques:', error);
        this.boutiques = [];
      }
    },

    // Load transfers based on filters from backend
    async loadTransfers(){
      if(!this.filters.boutique || !this.filters.from || !this.filters.to){
        alert('{{ trans('messages.please_select_boutique_and_period', [], session('locale')) }}');
        return;
      }

      this.loading = true;
      try {
        // Extract boutique ID (handle both 'boutique-{id}' and just '{id}' formats)
        let boutiqueId = this.filters.boutique;
        if (boutiqueId.toString().startsWith('boutique-')) {
          boutiqueId = boutiqueId.replace('boutique-', '');
        }

        const params = new URLSearchParams({
          boutique_id: boutiqueId,
          date_from: this.filters.from,
          date_to: this.filters.to
        });

        const response = await fetch('/get_settlement_data?' + params.toString());
        const data = await response.json();

        if (response.ok && Array.isArray(data)) {
          this.rows = data.map(item => ({
            uid: item.uid || (item.code + '|' + (item.size || '') + '|' + (item.color || '')),
            code: item.code,
            color: item.color,
            color_id: item.color_id || null,
            color_code: item.color_code || '#000000',
            size: item.size,
            size_id: item.size_id || null,
            sent: item.sent || 0,
            pulled: item.pulled || 0,
            sellable: item.sellable || 0,
            sold: item.sold || 0,
            price: item.price || 0,
            diff: item.diff || 0,
            total: item.total || 0
          }));
          this.toast('{{ trans('messages.shipments_data_loaded', [], session('locale')) }}');
        } else {
          this.rows = [];
          alert(data.error || '{{ trans('messages.error_loading_data', [], session('locale')) }}');
        }
      } catch (error) {
        console.error('Error loading settlement data:', error);
        this.rows = [];
        alert('{{ trans('messages.error_loading_data', [], session('locale')) }}');
      } finally {
        this.loading = false;
      }
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

    async openMovement(r){
      this.movRow = r;
      this.showMov = true;
      this.movDetails = []; // Clear previous data
      
      // Show loading state
      this.movDetails = [{id: 'loading', date: '...', type: '...', from: '...', to: '...', qty: '...'}];
      
      try {
        // Extract boutique ID
        let boutiqueId = this.filters.boutique;
        if (boutiqueId.toString().startsWith('boutique-')) {
          boutiqueId = boutiqueId.replace('boutique-', '');
        }

        const params = new URLSearchParams({
          boutique_id: boutiqueId,
          date_from: this.filters.from,
          date_to: this.filters.to,
          code: r.code,
          color: r.color || '',
          size: r.size || ''
        });

        const response = await fetch('/get_settlement_transfer_details?' + params.toString());
        const data = await response.json();

        if (response.ok && Array.isArray(data)) {
          this.movDetails = data;
          if (data.length === 0) {
            this.movDetails = [{id: 'no-data', date: '-', type: '{{ trans('messages.no_data_available', [], session('locale')) }}', from: '-', to: '-', qty: '-'}];
          }
        } else {
          this.movDetails = [{id: 'error', date: '-', type: data.error || '{{ trans('messages.error_loading_data', [], session('locale')) }}', from: '-', to: '-', qty: '-'}];
        }
      } catch (error) {
        console.error('Error loading transfer details:', error);
        this.movDetails = [{id: 'error', date: '-', type: '{{ trans('messages.error_loading_data', [], session('locale')) }}', from: '-', to: '-', qty: '-'}];
      }
    },

    onAttach(e){
      const f = e.target.files[0];
      if(!f) return;
      const size = f.size >= 1024*1024
        ? (f.size/1024/1024).toFixed(2)+' MB'
        : (f.size/1024).toFixed(1)+' KB';
      this.attachment = {name:f.name, sizeLabel:size, file: f};
    },
    removeAttachment(){ this.attachment = {name:'', sizeLabel:'', file: null}; },

    async saveSettlement(){
      if(this.rows.length===0){ 
        alert('{{ trans('messages.no_data_to_save', [], session('locale')) }}'); 
        return; 
      }

      const month = (this.filters.from||'').slice(0,7) || new Date().toISOString().slice(0,7);
      const amount = this.sum('total');
      const items  = this.sum('sold');
      const diff   = this.sum('diff');

      // Extract boutique ID and name
      let boutiqueId = this.filters.boutique;
      let boutiqueName = this.boutiqueName(boutiqueId);
      if (boutiqueId.toString().startsWith('boutique-')) {
        boutiqueId = boutiqueId.replace('boutique-', '');
      }

      // Prepare form data
      const formData = new FormData();
      formData.append('boutique_id', boutiqueId);
      formData.append('boutique_name', boutiqueName);
      formData.append('month', month);
      formData.append('date_from', this.filters.from);
      formData.append('date_to', this.filters.to);
      formData.append('number_of_items', items);
      formData.append('total_sales', amount);
      formData.append('total_difference', diff);
      formData.append('items_data', JSON.stringify(this.rows));
      
      if (this.attachment.file) {
        formData.append('attachment', this.attachment.file);
      }

      try {
        const response = await fetch('/save_settlement', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          this.toast('{{ trans('messages.settlement_saved_successfully', [], session('locale')) }}');
          // Reset editable values
          this.rows.forEach(r=>{ r.sold=0; r.diff=0; r.total=0; });
          // Clear attachment
          this.attachment = {name:'', sizeLabel:'', file: null};
          // Reload history
          await this.loadHistory();
        } else {
          alert(result.message || '{{ trans('messages.error_saving_settlement', [], session('locale')) }}');
        }
      } catch (error) {
        console.error('Error saving settlement:', error);
        alert('{{ trans('messages.error_saving_settlement', [], session('locale')) }}');
      }
    },

    // Load settlement history from backend
    async loadHistory(){
      try {
        const params = new URLSearchParams();
        if (this.histSearch) params.append('search', this.histSearch);
        if (this.histMonth) params.append('month', this.histMonth);

        const response = await fetch('/get_settlement_history?' + params.toString());
        const data = await response.json();

        if (response.ok && Array.isArray(data)) {
          this.history = data;
        } else {
          this.history = [];
        }
      } catch (error) {
        console.error('Error loading settlement history:', error);
        this.history = [];
      }
    },

    // History filter (now just returns history since filtering is done on backend)
    get historyFiltered(){
      return this.history;
    },

    // Open history details modal
    async openHistoryDetails(h){
      this.showSettlementDetails = true;
      this.settlementDetails = {};
      this.settlementDetailsLoading = true;

      try {
        const params = new URLSearchParams({
          settlement_code: h.no
        });

        const response = await fetch('/get_settlement_details?' + params.toString());
        const data = await response.json();

        if (response.ok && data.settlement_code) {
          this.settlementDetails = data;
        } else {
          alert(data.error || '{{ trans('messages.error_loading_data', [], session('locale')) }}');
          this.showSettlementDetails = false;
        }
      } catch (error) {
        console.error('Error loading settlement details:', error);
        alert('{{ trans('messages.error_loading_data', [], session('locale')) }}');
        this.showSettlementDetails = false;
      } finally {
        this.settlementDetailsLoading = false;
      }
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

    async init(){
      // Load boutiques from backend
      await this.loadBoutiques();
      
      // Load settlement history
      await this.loadHistory();
      
      // Prefill month range
      const now = new Date();
      const y = now.getFullYear(), m = String(now.getMonth()+1).padStart(2,'0');
      this.filters.from = `${y}-${m}-01`;
      this.filters.to   = `${y}-${m}-30`;
      
      // Set default boutique if available
      if (this.boutiques.length > 0) {
        this.filters.boutique = this.boutiques[0].id;
      }
    }
  }
}
</script> -->