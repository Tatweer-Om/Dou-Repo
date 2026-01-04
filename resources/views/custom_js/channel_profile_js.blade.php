<script>
function channelProfile() {
  return {
    tab: 'sales',
    sales: [],
    filteredSales: [],
    salesSearch: '',
    salesDateFrom: '',
    salesDateTo: '',
    transfers: [],
    transferItems: [],
    filteredTransfers: [],
    filteredTransferItems: [],
    transferSearch: '',
    transferDateFrom: '',
    transferDateTo: '',
    itemSearch: '',
    itemDateFrom: '',
    itemDateTo: '',
    itemStatus: [],
    filteredItemStatus: [],
    itemStatusSearch: '',
    itemStatusFilter: 'all',
    loading: false,

    async init() {
      await this.loadSales();
      await this.loadTransfers();
      await this.loadTransferItems();
      await this.loadItemStatus();
    },

    async loadSales() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/sales');
        const data = await response.json();
        this.sales = data || [];
        this.filteredSales = this.sales;
      } catch (error) {
        console.error('Error loading sales:', error);
        this.sales = [];
        this.filteredSales = [];
      } finally {
        this.loading = false;
      }
    },

    filterSales() {
      let filtered = [...this.sales];

      // Search filter
      if (this.salesSearch) {
        const search = this.salesSearch.toLowerCase();
        filtered = filtered.filter(sale => 
          (sale.order_no && sale.order_no.toLowerCase().includes(search)) ||
          (sale.abaya_code && sale.abaya_code.toLowerCase().includes(search)) ||
          (sale.design_name && sale.design_name.toLowerCase().includes(search)) ||
          (sale.color_name && sale.color_name.toLowerCase().includes(search)) ||
          (sale.size_name && sale.size_name.toLowerCase().includes(search))
        );
      }

      // Date filters
      if (this.salesDateFrom) {
        filtered = filtered.filter(sale => 
          sale.order_date && sale.order_date >= this.salesDateFrom
        );
      }

      if (this.salesDateTo) {
        filtered = filtered.filter(sale => 
          sale.order_date && sale.order_date <= this.salesDateTo
        );
      }

      this.filteredSales = filtered;
    },

    async loadTransfers() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/transfers');
        const data = await response.json();
        this.transfers = data || [];
        this.filteredTransfers = this.transfers;
      } catch (error) {
        console.error('Error loading transfers:', error);
        this.transfers = [];
        this.filteredTransfers = [];
      } finally {
        this.loading = false;
      }
    },

    async loadTransferItems() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/transfer-items');
        const data = await response.json();
        this.transferItems = data || [];
        this.filteredTransferItems = this.transferItems;
      } catch (error) {
        console.error('Error loading transfer items:', error);
        this.transferItems = [];
        this.filteredTransferItems = [];
      } finally {
        this.loading = false;
      }
    },

    filterTransfers() {
      let filtered = [...this.transfers];

      // Search filter
      if (this.transferSearch) {
        const search = this.transferSearch.toLowerCase();
        filtered = filtered.filter(transfer => 
          (transfer.transfer_code && transfer.transfer_code.toLowerCase().includes(search)) ||
          (transfer.from && transfer.from.toLowerCase().includes(search)) ||
          (transfer.to && transfer.to.toLowerCase().includes(search)) ||
          (transfer.notes && transfer.notes.toLowerCase().includes(search))
        );
      }

      // Date filters
      if (this.transferDateFrom) {
        filtered = filtered.filter(transfer => 
          transfer.date && transfer.date >= this.transferDateFrom
        );
      }

      if (this.transferDateTo) {
        filtered = filtered.filter(transfer => 
          transfer.date && transfer.date <= this.transferDateTo
        );
      }

      this.filteredTransfers = filtered;
    },

    filterTransferItems() {
      let filtered = [...this.transferItems];

      // Search filter
      if (this.itemSearch) {
        const search = this.itemSearch.toLowerCase();
        filtered = filtered.filter(item => 
          (item.transfer_code && item.transfer_code.toLowerCase().includes(search)) ||
          (item.abaya_code && item.abaya_code.toLowerCase().includes(search)) ||
          (item.color_name && item.color_name.toLowerCase().includes(search)) ||
          (item.size_name && item.size_name.toLowerCase().includes(search))
        );
      }

      // Date filters
      if (this.itemDateFrom) {
        filtered = filtered.filter(item => 
          item.transfer_date && item.transfer_date >= this.itemDateFrom
        );
      }

      if (this.itemDateTo) {
        filtered = filtered.filter(item => 
          item.transfer_date && item.transfer_date <= this.itemDateTo
        );
      }

      this.filteredTransferItems = filtered;
    },

    async loadItemStatus() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/item-status');
        const data = await response.json();
        this.itemStatus = data || [];
        this.filteredItemStatus = this.itemStatus;
      } catch (error) {
        console.error('Error loading item status:', error);
        this.itemStatus = [];
        this.filteredItemStatus = [];
      } finally {
        this.loading = false;
      }
    },

    filterItemStatus() {
      let filtered = [...this.itemStatus];

      // Search filter
      if (this.itemStatusSearch) {
        const search = this.itemStatusSearch.toLowerCase();
        filtered = filtered.filter(item => 
          (item.abaya_code && item.abaya_code.toLowerCase().includes(search)) ||
          (item.design_name && item.design_name.toLowerCase().includes(search)) ||
          (item.color_name && item.color_name.toLowerCase().includes(search)) ||
          (item.size_name && item.size_name.toLowerCase().includes(search))
        );
      }

      // Status filter
      if (this.itemStatusFilter !== 'all') {
        filtered = filtered.filter(item => item.status === this.itemStatusFilter);
      }

      this.filteredItemStatus = filtered;
    }
  }
}
</script>