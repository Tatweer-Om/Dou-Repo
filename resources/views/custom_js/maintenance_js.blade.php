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
    showNotesModal: false,
    selectedItem: {},
    selectedNotesItem: {},
    selectedTailorId: '',
    deliveryCharges: 0,
    repairCost: 0,
    costBearer: '',
    maintenanceNotes: '',

    // Pagination
    page: 1,
    perPage: 10,

    statistics: {
      delivered_to_tailor: 0,
      received_from_tailor: 0,
    },

    async init() {
      await this.loadData();
      await this.loadRepairHistory();
      // Reset to page 1 when search changes
      this.$watch('search', () => {
        this.page = 1;
      });
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

    openActionModal(item) {
      this.selectedItem = item;
      this.selectedTailorId = '';
      this.costBearer = item.cost_bearer || '';
      if (this.costBearer === 'company') {
        this.deliveryCharges = 0;
        this.repairCost = 0;
      } else {
        this.deliveryCharges = item.delivery_charges || 0;
        this.repairCost = item.repair_cost || 0;
      }
      this.maintenanceNotes = item.maintenance_notes || '';
      this.showActionModal = true;
    },

    handleCostBearerChange() {
      if (this.costBearer === 'company') {
        this.deliveryCharges = 0;
        this.repairCost = 0;
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
        // Receive from tailor
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

        try {
          const response = await fetch('{{ route('maintenance.receive') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id,
              delivery_charges: this.deliveryCharges,
              repair_cost: this.repairCost,
              cost_bearer: this.costBearer
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

        try {
          const response = await fetch('{{ route('maintenance.send_repair') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id,
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
        'received_from_tailor': '{{ trans('messages.received_from_tailor', [], session('locale')) }}'
      };
      return labels[status] || '{{ trans('messages.not_in_maintenance', [], session('locale')) }}';
    },

    getStatusBadgeClass(status) {
      const classes = {
        'delivered_to_tailor': 'bg-orange-100 text-orange-800',
        'received_from_tailor': 'bg-blue-100 text-blue-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    }
  }));
});
</script>