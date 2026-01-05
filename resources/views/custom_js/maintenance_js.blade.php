<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('maintenanceApp', () => ({
    loading: false,
    loadingHistory: false,
    activeTab: 'current',
    search: '',
    items: [],
    repairHistory: [],
    tailors: [],
    showActionModal: false,
    showDeliverModal: false,
    showNotesModal: false,
    showOrderItemsModal: false,
    selectedItem: {},
    selectedDeliverItem: {},
    selectedNotesItem: {},
    selectedOrder: {},
    selectedTailorId: '',
    selectedQuantity: 1,
    deliveryCharges: 0,
    repairCost: 0,
    costBearer: '',
    maintenanceNotes: '',
    deliverAccountId: '',
    deliverPaymentAmount: 0,
    accounts: [],
    deliveredOrderSearch: '',
    deliveredOrderSearchResults: [],
    searchingDeliveredOrders: false,
    orderItems: [],
    selectedOrderItems: [],
    loadingOrderItems: false,
    paymentAmountError: '',

    // Pagination
    page: 1,
    perPage: 10,
    // History Pagination
    historyPage: 1,
    historyPerPage: 7,

    statistics: {
      delivered_to_tailor: 0,
      received_from_tailor: 0,
    },

    async init() {
      await this.loadData();
      await this.loadRepairHistory();
      await this.loadAccounts();
      // Reset to page 1 when search changes
      this.$watch('search', () => {
        this.page = 1;
      });
    },

    async loadAccounts() {
      try {
        const response = await fetch('{{ url('accounts/all') }}');
        const data = await response.json();
        if (Array.isArray(data)) {
          this.accounts = data;
        } else if (data.success && data.accounts) {
          this.accounts = data.accounts;
        } else {
          this.accounts = [];
        }
      } catch (error) {
        console.error('Error loading accounts:', error);
        this.accounts = [];
      }
    },

    async loadData() {
      this.loading = true;
      try {
        const response = await fetch('{{ route('maintenance.data') }}');
        const data = await response.json();
        
        if (data.success) {
          this.statistics = data.statistics || {};
          this.items = data.items || [];
          this.tailors = data.tailors || [];
          // Debug: log first item to check data structure
          if (this.items.length > 0) {
            console.log('First item data:', this.items[0]);
          }
        } else {
          throw new Error(data.message || '{{ trans('messages.failed_to_load_data', [], session('locale')) }}');
        }
      } catch (error) {
        console.error('Error loading data:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        } else {
          alert('{{ trans('messages.error', [], session('locale')) }}: ' + error.message);
        }
      } finally {
        this.loading = false;
      }
    },

    async loadRepairHistory() {
      this.loadingHistory = true;
      try {
        const response = await fetch('{{ route('maintenance.history') }}');
        const data = await response.json();
        
        if (data.success) {
          this.repairHistory = data.history || [];
          this.historyPage = 1; // Reset to first page when loading new data
        } else {
          throw new Error(data.message || '{{ trans('messages.failed_to_load_repair_history', [], session('locale')) }}');
        }
      } catch (error) {
        console.error('Error loading repair history:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        }
      } finally {
        this.loadingHistory = false;
      }
    },

    filteredItems() {
      if (!this.search) return this.items;

      const searchLower = this.search.toLowerCase();
      return this.items.filter(item => 
        item.design_name.toLowerCase().includes(searchLower) ||
        item.abaya_code.toLowerCase().includes(searchLower) ||
        item.customer_name.toLowerCase().includes(searchLower) ||
        item.customer_phone.includes(this.search) ||
        (item.order_no && item.order_no.toLowerCase().includes(searchLower))
      );
    },

    paginatedItems() {
      const filtered = this.filteredItems();
      const start = (this.page - 1) * this.perPage;
      return filtered.slice(start, start + this.perPage);
    },

    totalPages() {
      const total = this.filteredItems().length;
      return total === 0 ? 1 : Math.ceil(total / this.perPage);
    },

    pageNumbers() {
      const total = this.totalPages();
      const current = this.page;
      const pages = [];
      
      if (total <= 5) {
        // Show all pages if 5 or fewer
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        // Show pages around current page
        let start = Math.max(1, current - 2);
        let end = Math.min(total, start + 4);
        
        // Adjust if we're near the end
        if (end - start < 4) {
          start = Math.max(1, end - 4);
        }
        
        for (let i = start; i <= end; i++) {
          pages.push(i);
        }
      }
      
      return pages;
    },

    startItem() {
      if (this.filteredItems().length === 0) return 0;
      return (this.page - 1) * this.perPage + 1;
    },

    endItem() {
      return Math.min(this.page * this.perPage, this.filteredItems().length);
    },

    nextPage() {
      if (this.page < this.totalPages()) {
        this.page++;
        this.scrollToTop();
      }
    },

    prevPage() {
      if (this.page > 1) {
        this.page--;
        this.scrollToTop();
      }
    },

    goToPage(pageNum) {
      if (pageNum >= 1 && pageNum <= this.totalPages()) {
        this.page = pageNum;
        this.scrollToTop();
      }
    },

    scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // History Pagination Methods
    paginatedHistory() {
      if (!this.repairHistory || this.repairHistory.length === 0) return [];
      const start = (this.historyPage - 1) * this.historyPerPage;
      return this.repairHistory.slice(start, start + this.historyPerPage);
    },

    totalHistoryPages() {
      if (!this.repairHistory || this.repairHistory.length === 0) return 1;
      return Math.ceil(this.repairHistory.length / this.historyPerPage);
    },

    historyPageNumbers() {
      const total = this.totalHistoryPages();
      const current = this.historyPage;
      const pages = [];
      
      if (total <= 5) {
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        let start = Math.max(1, current - 2);
        let end = Math.min(total, start + 4);
        
        if (end - start < 4) {
          start = Math.max(1, end - 4);
        }
        
        for (let i = start; i <= end; i++) {
          pages.push(i);
        }
      }
      
      return pages;
    },

    historyStartItem() {
      if (!this.repairHistory || this.repairHistory.length === 0) return 0;
      return (this.historyPage - 1) * this.historyPerPage + 1;
    },

    historyEndItem() {
      if (!this.repairHistory || this.repairHistory.length === 0) return 0;
      return Math.min(this.historyPage * this.historyPerPage, this.repairHistory.length);
    },

    nextHistoryPage() {
      if (this.historyPage < this.totalHistoryPages()) {
        this.historyPage++;
        this.scrollToTop();
      }
    },

    prevHistoryPage() {
      if (this.historyPage > 1) {
        this.historyPage--;
        this.scrollToTop();
      }
    },

    goToHistoryPage(pageNum) {
      if (pageNum >= 1 && pageNum <= this.totalHistoryPages()) {
        this.historyPage = pageNum;
        this.scrollToTop();
      }
    },

    openActionModal(item) {
      this.selectedItem = item;
      this.selectedTailorId = '';
      this.selectedQuantity = item.quantity > 1 ? 1 : (item.quantity || 1); // Default to 1 if quantity > 1, otherwise use item quantity
      this.maintenanceNotes = item.maintenance_notes || '';
      this.showActionModal = true;
    },

    async openDeliverModal(item) {
      this.selectedDeliverItem = item;
      this.costBearer = '';
      this.deliveryCharges = 0;
      this.repairCost = 0;
      this.deliverAccountId = '';
      this.deliverPaymentAmount = 0;
      this.paymentAmountError = '';
      
      // Ensure accounts are loaded
      if (this.accounts.length === 0) {
        await this.loadAccounts();
      }
      
      this.showDeliverModal = true;
    },

    calculateDeliverTotalCost() {
      const delivery = parseFloat(this.deliveryCharges) || 0;
      const repair = parseFloat(this.repairCost) || 0;
      return delivery + repair;
    },

    formatCurrency(amount) {
      return (parseFloat(amount) || 0).toFixed(3) + ' ر.ع';
    },

    handleCostBearerChange() {
      if (this.costBearer === 'company') {
        this.deliveryCharges = 0;
        this.repairCost = 0;
        this.deliverPaymentAmount = 0;
        this.paymentAmountError = '';
      } else {
        // Auto-populate payment amount when customer is selected
        this.updatePaymentAmount();
      }
    },

    updatePaymentAmount() {
      // Auto-populate payment amount with total cost (only if customer is bearer)
      if (this.costBearer === 'customer') {
        const total = this.calculateDeliverTotalCost();
        this.deliverPaymentAmount = total;
        this.paymentAmountError = '';
      }
    },

    validatePaymentAmount() {
      if (this.costBearer === 'customer') {
        const total = this.calculateDeliverTotalCost();
        const payment = parseFloat(this.deliverPaymentAmount) || 0;
        
        if (total > 0) {
          if (payment !== total) {
            this.paymentAmountError = '{{ trans('messages.payment_must_equal_total', [], session('locale')) ?: 'Payment amount must equal total cost' }} (' + this.formatCurrency(total) + ')';
          } else {
            this.paymentAmountError = '';
          }
        }
      }
    },

    openNotesModal(item) {
      this.selectedNotesItem = item;
      this.showNotesModal = true;
    },

    async performAction() {
      if (this.selectedItem.maintenance_status === 'received_from_tailor') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: '{{ trans('messages.already_completed', [], session('locale')) }}',
            text: '{{ trans('messages.already_received_message', [], session('locale')) }}'
          });
        }
        return;
      }

      if (this.selectedItem.maintenance_status === 'delivered_to_tailor') {
        // Receive from tailor - just receive, no costs
        try {
          const response = await fetch('{{ route('maintenance.receive') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id
            })
          });

          const data = await response.json();

          if (data.success) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: '{{ trans('messages.success', [], session('locale')) }}',
                text: data.message
              });
            } else {
              alert(data.message);
            }
            this.showActionModal = false;
            await this.loadData();
          } else {
            throw new Error(data.message || '{{ trans('messages.failed_to_receive_item', [], session('locale')) }}');
          }
        } catch (error) {
          console.error('Error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: error.message
            });
          } else {
            alert('{{ trans('messages.error', [], session('locale')) }}: ' + error.message);
          }
        }
      } else {
        // Send to tailor
        if (!this.selectedTailorId) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: '{{ trans('messages.select_tailor_title', [], session('locale')) }}',
              text: '{{ trans('messages.please_select_tailor', [], session('locale')) }}'
            });
          } else {
            alert('{{ trans('messages.please_select_tailor', [], session('locale')) }}');
          }
          return;
        }

        // Validate quantity if quantity > 1
        if (this.selectedItem.quantity > 1) {
          const qty = parseInt(this.selectedQuantity) || 1;
          const maxQty = this.selectedItem.available_quantity || this.selectedItem.quantity;
          if (qty < 1 || qty > maxQty) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: '{{ trans('messages.invalid_quantity', [], session('locale')) ?: 'Invalid Quantity' }}',
                text: '{{ trans('messages.quantity_must_be_between', [], session('locale')) ?: 'Quantity must be between 1 and' }} ' + maxQty
              });
            } else {
              alert('{{ trans('messages.invalid_quantity', [], session('locale')) ?: 'Invalid Quantity' }}');
            }
            return;
          }
        }

        try {
          const response = await fetch('{{ route('maintenance.send_repair') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id,
              item_ids: this.selectedItem.item_ids || [this.selectedItem.id], // Send all item IDs in the group
              quantity: this.selectedItem.quantity > 1 ? parseInt(this.selectedQuantity) || 1 : null, // Send quantity if > 1
              tailor_id: this.selectedTailorId,
              maintenance_notes: this.maintenanceNotes || ''
            })
          });

          const data = await response.json();

          if (data.success) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: '{{ trans('messages.success', [], session('locale')) }}',
                text: data.message
              });
            } else {
              alert(data.message);
            }
            this.showActionModal = false;
            await this.loadData();
          } else {
            throw new Error(data.message || '{{ trans('messages.failed_to_send_item', [], session('locale')) }}');
          }
        } catch (error) {
          console.error('Error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: error.message
            });
          } else {
            alert('{{ trans('messages.error', [], session('locale')) }}: ' + error.message);
          }
        }
      }
    },

    getStatusLabel(status) {
      const labels = {
        'delivered_to_tailor': '{{ trans('messages.delivered_to_tailor', [], session('locale')) }}',
        'received_from_tailor': '{{ trans('messages.received_from_tailor', [], session('locale')) }}',
        'delivered': '{{ trans('messages.delivered', [], session('locale')) }}'
      };
      return labels[status] || '{{ trans('messages.not_in_maintenance', [], session('locale')) }}';
    },

    getStatusBadgeClass(status) {
      const classes = {
        'delivered_to_tailor': 'bg-orange-100 text-orange-800',
        'received_from_tailor': 'bg-blue-100 text-blue-800',
        'delivered': 'bg-emerald-100 text-emerald-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    },

    getMaintenanceStatusLabel(status) {
      const labels = {
        'delivered_to_tailor': '{{ trans('messages.delivered_to_tailor', [], session('locale')) }}',
        'received_from_tailor': '{{ trans('messages.received_from_tailor', [], session('locale')) }}',
        'delivered': '{{ trans('messages.delivered', [], session('locale')) }}'
      };
      return labels[status] || '{{ trans('messages.not_in_maintenance', [], session('locale')) }}';
    },

    async searchDeliveredOrders() {
      if (!this.deliveredOrderSearch || this.deliveredOrderSearch.length < 2) {
        this.deliveredOrderSearchResults = [];
        return;
      }

      this.searchingDeliveredOrders = true;
      try {
        const response = await fetch('{{ route('maintenance.search_delivered') }}?search=' + encodeURIComponent(this.deliveredOrderSearch));
        const data = await response.json();
        
        if (data.success) {
          this.deliveredOrderSearchResults = data.orders || [];
        } else {
          this.deliveredOrderSearchResults = [];
        }
      } catch (error) {
        console.error('Error searching delivered orders:', error);
        this.deliveredOrderSearchResults = [];
      } finally {
        this.searchingDeliveredOrders = false;
      }
    },

    async openOrderItemsModal(order) {
      this.selectedOrder = order;
      this.selectedOrderItems = [];
      this.selectedTailorId = '';
      this.maintenanceNotes = '';
      this.loadingOrderItems = true;
      this.showOrderItemsModal = true;
      this.deliveredOrderSearch = '';
      this.deliveredOrderSearchResults = [];

      try {
        const response = await fetch('{{ route('maintenance.order_items') }}?order_id=' + order.id);
        const data = await response.json();
        
        if (data.success) {
          this.orderItems = data.items || [];
          this.selectedOrder = data.order || order;
        } else {
          throw new Error(data.message || '{{ trans('messages.failed_to_load_items', [], session('locale')) ?: 'Failed to load items' }}');
        }
      } catch (error) {
        console.error('Error loading order items:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        }
        this.orderItems = [];
      } finally {
        this.loadingOrderItems = false;
      }
    },

    async sendSelectedItemsToMaintenance() {
      if (this.selectedOrderItems.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.required_field', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_items', [], session('locale')) ?: 'Please select at least one item' }}'
          });
        }
        return;
      }

      // Filter out items that are already in maintenance
      const availableItems = this.orderItems.filter(item => 
        this.selectedOrderItems.includes(item.id) && !item.maintenance_status
      );

      if (availableItems.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: '{{ trans('messages.info', [], session('locale')) ?: 'Info' }}',
            text: '{{ trans('messages.selected_items_already_in_maintenance', [], session('locale')) ?: 'Selected items are already in maintenance' }}'
          });
        }
        return;
      }

      // Check if tailor is selected
      if (!this.selectedTailorId) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.select_tailor_title', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_tailor', [], session('locale')) }}'
          });
        }
        return;
      }

      // Send all selected items to tailor
      try {
        const itemIds = availableItems.map(item => item.id);
        const response = await fetch('{{ route('maintenance.send_repair') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            item_id: itemIds[0], // First item ID as representative
            item_ids: itemIds, // All selected item IDs
            quantity: null, // Send all items
            tailor_id: this.selectedTailorId,
            maintenance_notes: this.maintenanceNotes || ''
          })
        });

        const data = await response.json();

        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.success', [], session('locale')) }}',
              text: data.message
            });
          }
          this.showOrderItemsModal = false;
          this.selectedOrderItems = [];
          this.selectedTailorId = '';
          this.maintenanceNotes = '';
          await this.loadData();
        } else {
          throw new Error(data.message || '{{ trans('messages.failed_to_send_item', [], session('locale')) }}');
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        }
      }
    },

    async performDeliver() {
      if (!this.costBearer) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.required_field', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_cost_bearer', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.please_select_cost_bearer', [], session('locale')) }}');
        }
        return;
      }

      // If customer is bearer and there are costs, validate account and payment
      const totalCost = this.calculateDeliverTotalCost();
      if (this.costBearer === 'customer' && totalCost > 0) {
        if (!this.deliverAccountId) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: '{{ trans('messages.required_field', [], session('locale')) }}',
              text: '{{ trans('messages.please_select_account', [], session('locale')) ?: 'Please select an account' }}'
            });
          } else {
            alert('{{ trans('messages.please_select_account', [], session('locale')) ?: 'Please select an account' }}');
          }
          return;
        }

        const paymentAmount = parseFloat(this.deliverPaymentAmount) || 0;
        
        if (paymentAmount <= 0) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: '{{ trans('messages.required_field', [], session('locale')) }}',
              text: '{{ trans('messages.please_enter_payment_amount', [], session('locale')) ?: 'Please enter payment amount' }}'
            });
          } else {
            alert('{{ trans('messages.please_enter_payment_amount', [], session('locale')) ?: 'Please enter payment amount' }}');
          }
          return;
        }

        // Validate payment amount equals total cost exactly
        if (Math.abs(paymentAmount - totalCost) > 0.001) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.invalid_payment_amount', [], session('locale')) ?: 'Invalid Payment Amount' }}',
              text: '{{ trans('messages.payment_must_equal_total', [], session('locale')) ?: 'Payment amount must equal the total cost' }} (' + this.formatCurrency(totalCost) + ')'
            });
          } else {
            alert('{{ trans('messages.payment_must_equal_total', [], session('locale')) ?: 'Payment amount must equal the total cost' }}');
          }
          return;
        }
      }

      try {
        const response = await fetch('{{ route('maintenance.deliver') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            item_id: this.selectedDeliverItem.id,
            delivery_charges: this.deliveryCharges,
            repair_cost: this.repairCost,
            cost_bearer: this.costBearer,
            account_id: this.costBearer === 'customer' && totalCost > 0 ? this.deliverAccountId : null,
            payment_amount: this.costBearer === 'customer' && totalCost > 0 ? this.deliverPaymentAmount : 0
          })
        });

        const data = await response.json();

        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.success', [], session('locale')) }}',
              text: data.message
            });
          } else {
            alert(data.message);
          }
          this.showDeliverModal = false;
          await this.loadData();
        } else {
          throw new Error(data.message || '{{ trans('messages.failed_to_deliver_item', [], session('locale')) ?: 'Failed to deliver item' }}');
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: error.message
          });
        } else {
          alert('{{ trans('messages.error', [], session('locale')) }}: ' + error.message);
        }
      }
    }
  }));
});
</script>