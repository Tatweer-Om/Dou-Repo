<!-- <script>
function transferPage() {
  return {
    // Stats - loaded from backend
    stats: { main: 0, website: 0, pos: 0, boutiques: 0 },

    // Items from controller (channels + boutiques)
    items: @json($items ?? []),


    get channelsOnly() {
      return this.items.filter(i => i.type === 'channel');
    },

    // Computed: Boutiques only
    get boutiquesOnly() {
      return this.items.filter(i => i.type === 'boutique');
    },

    // Computed: Available items for "To" select (excludes selected "from" in channel_to_channel mode)
    get availableToItems() {
      // For channel_to_channel mode, show all items (don't exclude the selected "from")
      return this.items;
    },

    // Computed: Available channels for "To" select
    get availableToChannels() {
      return this.availableToItems.filter(i => i.type === 'channel');
    },

    // Computed: Available boutiques for "To" select
    get availableToBoutiques() {
      return this.availableToItems.filter(i => i.type === 'boutique');
    },

    // Mode & selections
    mode: 'main_to_channel',
    fromChannel: 'main',
    toChannel: '',
    transferDate: '',
    
    // Reset channels when mode changes
    resetChannelsForMode() {
      if (this.mode === 'main_to_channel') {
        this.fromChannel = 'main';
        this.toChannel = '';
      } else if (this.mode === 'channel_to_channel') {
        this.fromChannel = '';
        this.toChannel = '';
      } else if (this.mode === 'channel_to_main') {
        this.fromChannel = '';
        this.toChannel = 'main';
      }
    },

    // Inventory (from main) - loaded from API
    picker: { q:'', type:'' },
    inventory: [],
    warehouseInventory: [], // Main warehouse inventory (for showing available warehouse qty when transferring from channel)
    inventoryLoading: false,
    get pickerFiltered() {
      const q = this.picker.q.toLowerCase();
      return this.inventory.filter(r => {
        const matchQ = !q || (r.code && r.code.toLowerCase().includes(q)) || (r.name && r.name.toLowerCase().includes(q));
        const matchType = !this.picker.type || r.type===this.picker.type;
        return matchQ && matchType;
      });
    },
    async loadInventory() {
      this.inventoryLoading = true;
      try {
        const response = await fetch('/get_inventory');
        const data = await response.json();
        this.inventory = data || [];
        // Also store in warehouseInventory for reference
        this.warehouseInventory = data || [];
      } catch (error) {
        console.error('Error loading inventory:', error);
        this.inventory = [];
        this.warehouseInventory = [];
        this.toast.msg = '{{ trans('messages.error_loading_inventory', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(() => this.toast.show = false, 3000);
      } finally {
        this.inventoryLoading = false;
      }
    },
    
    // Load warehouse inventory separately (for showing available warehouse qty)
    async loadWarehouseInventory() {
      try {
        const response = await fetch('/get_inventory');
        const data = await response.json();
        this.warehouseInventory = data || [];
      } catch (error) {
        console.error('Error loading warehouse inventory:', error);
        this.warehouseInventory = [];
      }
    },
    
    // Get warehouse available quantity for an item
    getWarehouseAvailable(row) {
      if (this.mode !== 'channel_to_main') {
        return row.available || 0;
      }
      // When transferring from channel to warehouse, show warehouse stock
      const found = this.warehouseInventory.find(x =>
        x.code === row.code &&
        ((x.color || null) === (row.color || null)) &&
        ((x.size || null) === (row.size || null))
      );
      return found ? (found.available || 0) : 0;
    },

    // Channel stocks (source/destination) - loaded from backend
    channelStocks: {},

    // Load channel stocks
    async loadChannelStocks(channelId) {
      if (!channelId || channelId === 'main') return;
      if (this.channelStocks[channelId]) return; // Already loaded
      
      try {
        const response = await fetch(`/get_channel_stocks?channel_id=${channelId}`);
        const data = await response.json();
        this.channelStocks[channelId] = data || [];
      } catch (error) {
        console.error('Error loading channel stocks:', error);
        this.channelStocks[channelId] = [];
      }
    },

    // Helpers to get qty in a channel for a row
    getQtyInChannel(channelId, row) {
      if (!channelId || channelId === 'main') return '-';
      // channelId format: "type-id" (e.g., "channel-1" or "boutique-2")
      const list = this.channelStocks[channelId] || [];
      const found = list.find(x =>
        x.code === row.code &&
        ((x.color || null) === (row.color || null)) &&
        ((x.size || null) === (row.size || null))
      );
      return found ? found.qty : 0;
    },

    // Picker selection highlighting
    selectedUids: [],

    // Basket
    basket: [],
    transferNote: '',
    typeLabel(t){ return t==='size' ? '{{ trans('messages.by_size', [], session('locale')) }}' : t==='color' ? '{{ trans('messages.by_color', [], session('locale')) }}' : '{{ trans('messages.by_color_and_size', [], session('locale')) }}'; },
    addToBasket(row){
      // Use the uid from the row (generated by API) or create one if missing
      const uid = row.uid || `${row.code}|${row.size||''}|${row.color||''}`;
      const exists = this.basket.find(b => b.uid===uid);
      if (!exists){
        // When transferring from channel to warehouse, use warehouse available quantity
        const availableQty = this.mode === 'channel_to_main' ? this.getWarehouseAvailable(row) : row.available;
        this.basket.push({...row, uid, qty: 1, available: availableQty});
        if (!this.selectedUids.includes(uid)) this.selectedUids.push(uid);
      }
    },
    removeFromBasket(idx){
      const removed = this.basket.splice(idx,1)[0];
      if (removed){
        const pos = this.selectedUids.indexOf(removed.uid);
        if (pos>-1) this.selectedUids.splice(pos,1);
      }
    },

    // Execute enable
    get canExecute() {
      if (!this.fromChannel || !this.toChannel) return false;
      if (!this.transferDate) return false; // Transfer date is required
      if (this.mode==='main_to_channel' && this.fromChannel!=='main') return false;
      if (this.mode==='channel_to_main' && this.toChannel!=='main') return false;
      if (this.mode==='channel_to_channel' && (this.fromChannel==='main' || this.toChannel==='main')) return false;
      if (this.fromChannel === this.toChannel) return false;
      if (this.basket.length===0) return false;
      return this.basket.every(b => Number(b.qty)>0 && Number(b.qty) <= Number(b.available));
    },

    // Picker open
    showPicker: false,
    pickerSource: 'main', // Track which source we're picking from
    async openPicker(source = 'main'){ 
      this.pickerSource = source;
      this.showPicker = true;
      
      // Load inventory based on source
      if (source === 'main') {
        if (this.inventory.length === 0) {
          await this.loadInventory();
        }
      } else {
        // Load from channel/boutique
        await this.loadChannelInventory(source);
        
        // If transferring from channel to warehouse, also load warehouse inventory
        if (this.mode === 'channel_to_main') {
          if (this.warehouseInventory.length === 0) {
            await this.loadWarehouseInventory();
          }
        }
      }
      
      // Load channel stocks when picker opens (for displaying quantities)
      if (this.fromChannel && this.fromChannel !== 'main') {
        await this.loadChannelStocks(this.fromChannel);
      }
      if (this.toChannel && this.toChannel !== 'main') {
        await this.loadChannelStocks(this.toChannel);
      }
    },
    
    // Load inventory from a specific channel/boutique
    async loadChannelInventory(channelId) {
      this.inventoryLoading = true;
      try {
        const response = await fetch(`/get_channel_inventory?channel_id=${channelId}`);
        const data = await response.json();
        this.inventory = data || [];
      } catch (error) {
        console.error('Error loading channel inventory:', error);
        this.inventory = [];
        this.toast.msg = '{{ trans('messages.error_loading_inventory', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(() => this.toast.show = false, 3000);
      } finally {
        this.inventoryLoading = false;
      }
    },

    // History - loaded from backend
    history: [],
    filteredHistory: [],
    historySearch: '',
    dateFromH: '', dateToH: '',
    showHistory:false,
    currentHistory:{no:'', items:[], from:'', to:'', date:'', total:0},
    channelName(id){
      if (id==='main') return '{{ trans('messages.main_warehouse', [], session('locale')) }}';
      const f = this.items.find(item => {
        const itemValue = item.type + '-' + item.id;
        return itemValue === id;
      });
      return f ? f.display_name : id;
    },
    openHistoryDetails(row){ this.currentHistory = row; this.showHistory = true; },
    async loadHistory(){
      try {
        const params = new URLSearchParams();
        if (this.historySearch) params.append('search', this.historySearch);
        if (this.dateFromH) params.append('date_from', this.dateFromH);
        if (this.dateToH) params.append('date_to', this.dateToH);
        
        const response = await fetch(`/get_transfer_history?${params.toString()}`);
        const data = await response.json();
        this.history = data || [];
        this.filterHistory();
      } catch (error) {
        console.error('Error loading history:', error);
        this.history = [];
      }
    },
    filterHistory(){
      const q = this.historySearch.toLowerCase();
      const from = this.dateFromH ? new Date(this.dateFromH) : null;
      const to   = this.dateToH ? new Date(this.dateToH) : null;
      this.filteredHistory = this.history.filter(r=>{
        const d = new Date(r.date);
        const text = `${r.no} ${this.channelName(r.from)} ${this.channelName(r.to)}`.toLowerCase();
        const matchQ = !q || text.includes(q);
        const inFrom = !from || d >= from;
        const inTo   = !to   || d <= to;
        return matchQ && inFrom && inTo;
      });
    },

    // Toast
    toast:{show:false, msg:''},

    // Execute transfer - call backend
    async executeTransfer(){
      if (!this.canExecute) {
        if (!this.transferDate) {
          this.toast.msg = '{{ trans('messages.transfer_date_required', [], session('locale')) ?: 'Transfer Date is required' }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 3000);
        }
        return;
      }

      // Validate transfer date is provided
      if (!this.transferDate) {
        this.toast.msg = '{{ trans('messages.transfer_date_required', [], session('locale')) ?: 'Transfer Date is required' }}';
        this.toast.show = true;
        setTimeout(()=> this.toast.show=false, 3000);
        return;
      }

      try {
        const response = await fetch('/execute_transfer', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          },
          body: JSON.stringify({
            mode: this.mode,
            from: this.fromChannel,
            to: this.toChannel,
            transfer_date: this.transferDate,
            note: this.transferNote,
            basket: this.basket.map(b => ({
              code: b.code,
              color: b.color,
              size: b.size,
              qty: Number(b.qty),
              type: b.type,
              available: Number(b.available),
              uid: b.uid
            }))
          })
        });

        const result = await response.json();

        if (result.status === 'success') {
          // Reload stats and history
          await this.loadStats();
          await this.loadHistory();
          
          // Clear channel stocks cache to force reload
          this.channelStocks = {};

          // reset
          this.basket = [];
          this.selectedUids = [];
          this.transferNote = '';
          this.transferDate = '';
          if (this.mode==='main_to_channel'){ this.fromChannel='main'; this.toChannel=''; }
          if (this.mode==='channel_to_channel'){ this.fromChannel=''; this.toChannel=''; }
          if (this.mode==='channel_to_main'){ this.fromChannel=''; this.toChannel='main'; }

          this.toast.msg = result.message || '{{ trans('messages.transfer_executed_successfully', [], session('locale')) }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 2000);
        } else {
          this.toast.msg = result.message || '{{ trans('messages.error_executing_transfer', [], session('locale')) }}';
          this.toast.show = true;
          setTimeout(()=> this.toast.show=false, 3000);
        }
      } catch (error) {
        console.error('Error executing transfer:', error);
        this.toast.msg = '{{ trans('messages.error_executing_transfer', [], session('locale')) }}';
        this.toast.show = true;
        setTimeout(()=> this.toast.show=false, 3000);
      }
    },

    // Load stats from backend
    async loadStats(){
      try {
        const response = await fetch('/get_stats');
        const data = await response.json();
        this.stats = data || { main: 0, website: 0, pos: 0, boutiques: 0 };
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },

    // Export to Excel
    exportToExcel(){
      const params = new URLSearchParams();
      if (this.historySearch) params.append('search', this.historySearch);
      if (this.dateFromH) params.append('date_from', this.dateFromH);
      if (this.dateToH) params.append('date_to', this.dateToH);
      
      const url = '/export_transfers_excel' + (params.toString() ? '?' + params.toString() : '');
      window.location.href = url;
    },

    async init(){
      // defaults
      this.fromChannel='main'; this.toChannel='';
      
      // Load data on page load
      await Promise.all([
        this.loadInventory(),
        this.loadStats(),
        this.loadHistory()
      ]);
      
      // Debug: Log items to console
      console.log('All items:', this.items);
      console.log('Channels:', this.channelsOnly);
      console.log('Boutiques:', this.boutiquesOnly);
    }
  }
}
</script> -->