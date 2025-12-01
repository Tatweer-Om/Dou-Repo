<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('ordersDashboard', () => ({

    /* -------- المتغيرات -------- */
    search: '',
    filter: 'all',
    sourceFilter: 'all',

    page: 1,
    perPage: 10,

    showViewModal: false,
    showPaymentModal: false,
    showDeliverModal: false,
    showBulkDeliverModal: false,

    viewOrder: null,
    paymentOrder: null,
    paymentAmount: '',
    paymentMethod: 'cash',

    deliverOrder: null,
    selectedReadyIds: [],
    loading: false,
    pageLoading: false,

    /* -------- بيانات الطلبات -------- */
    orders: [],

    /* -------- تحميل البيانات من الخادم -------- */
    async init() {
      this.loading = true;
      try {
        const response = await fetch('{{ url('get_orders_list') }}');
        const data = await response.json();
        if (data.success) {
          this.orders = data.orders || [];
          console.log('Loaded orders:', this.orders.length);
        } else {
          console.error('Error loading orders:', data.message);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'خطأ',
              text: data.message || 'حدث خطأ أثناء تحميل الطلبات'
            });
          } else {
            alert('حدث خطأ أثناء تحميل الطلبات: ' + (data.message || ''));
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'حدث خطأ أثناء تحميل الطلبات'
          });
        } else {
          alert('حدث خطأ أثناء تحميل الطلبات');
        }
      } finally {
        this.loading = false;
      }
    },

    /* -------- فلاتر عامة -------- */
    filteredOrders() {
      return this.orders
        .filter(o => {
          // Status filter
          if (this.filter !== 'all' && o.status !== this.filter) {
            return false;
          }
          // Source filter
          if (this.sourceFilter !== 'all' && o.source !== this.sourceFilter) {
            return false;
          }
          // Search filter
          const q = this.search.trim().toLowerCase();
          if (q) {
            const customerMatch = (o.customer || '').toLowerCase().includes(q);
            const idMatch = String(o.id).includes(q);
            const sourceMatch = this.sourceLabel(o.source).toLowerCase().includes(q);
            const statusMatch = this.statusLabel(o.status).toLowerCase().includes(q);
            
            if (!customerMatch && !idMatch && !sourceMatch && !statusMatch) {
              return false;
            }
          }
          return true;
        });
    },

    /* -------- Pagination -------- */
    paginatedOrders() {
      let start = (this.page - 1) * this.perPage;
      return this.filteredOrders().slice(start, start + this.perPage);
    },

    totalPages() {
      const total = this.filteredOrders().length;
      return total === 0 ? 1 : Math.ceil(total / this.perPage);
    },

    nextPage() {
      if (this.page < this.totalPages()) {
        this.pageLoading = true;
        setTimeout(() => {
          this.page++;
          this.scrollToTop();
          setTimeout(() => {
            this.pageLoading = false;
          }, 300);
        }, 200);
      }
    },

    prevPage() {
      if (this.page > 1) {
        this.pageLoading = true;
        setTimeout(() => {
          this.page--;
          this.scrollToTop();
          setTimeout(() => {
            this.pageLoading = false;
          }, 300);
        }, 200);
      }
    },

    goToPage(pageNum) {
      if (pageNum !== this.page && pageNum >= 1 && pageNum <= this.totalPages()) {
        this.pageLoading = true;
        setTimeout(() => {
          this.page = pageNum;
          this.scrollToTop();
          setTimeout(() => {
            this.pageLoading = false;
          }, 300);
        }, 200);
      }
    },

    scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    startItem() {
      if (this.filteredOrders().length === 0) return 0;
      return (this.page - 1) * this.perPage + 1;
    },

    endItem() {
      return Math.min(this.page * this.perPage, this.filteredOrders().length);
    },

    /* -------- Tabs Style -------- */
    tabClass(type) {
      return this.filter === type
        ? 'px-5 py-2 rounded-full bg-indigo-600 text-white font-medium shadow text-xs md:text-sm'
        : 'px-5 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm';
    },

    tabClass2(type) {
      return this.sourceFilter === type
        ? 'px-5 py-2 rounded-full bg-purple-600 text-white font-medium shadow text-xs md:text-sm'
        : 'px-5 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm';
    },

    /* -------- Badges & Labels -------- */
    sourceLabel(s) {
      const labels = {
        whatsapp: '{{ trans('messages.whatsapp', [], session('locale')) }}',
        walkin: '{{ trans('messages.walk_in', [], session('locale')) }}'
      };
      return labels[s] || s;
    },

    sourceIcon(s) {
      return {
        whatsapp: 'chat',
        walkin: 'storefront'
      }[s] || 'info';
    },

    sourceBadge(s) {
      return {
        whatsapp: 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        walkin: 'bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-[11px] font-semibold'
      }[s] || 'bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[11px] font-semibold';
    },

    countStatus(st) {
      return this.orders.filter(o => o.status === st).length;
    },

    statusLabel(s) {
      const labels = {
        new: '{{ trans('messages.new', [], session('locale')) }}',
        processing: '{{ trans('messages.in_progress', [], session('locale')) }}',
        ready: '{{ trans('messages.ready_for_delivery', [], session('locale')) }}',
        delivered: '{{ trans('messages.delivered', [], session('locale')) }}'
      };
      return labels[s] || '';
    },

    statusBadge(s) {
      return {
        new: 'bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        processing: 'bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        ready: 'bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        delivered: 'bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[11px] font-semibold'
      }[s] || 'bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[11px] font-semibold';
    },

    /* -------- Item Status (Tailor Status) -------- */
    itemStatusLabel(s) {
      const labels = {
        new: '{{ trans('messages.not_with_tailor', [], session('locale')) ?: 'Not with Tailor' }}',
        processing: '{{ trans('messages.with_tailor', [], session('locale')) ?: 'With Tailor' }}',
        received: '{{ trans('messages.ready', [], session('locale')) ?: 'Ready' }}'
      };
      return labels[s] || s;
    },

    itemStatusBadge(s) {
      return {
        new: 'bg-gray-100 text-gray-700',
        processing: 'bg-blue-100 text-blue-700',
        received: 'bg-emerald-100 text-emerald-700'
      }[s] || 'bg-gray-100 text-gray-600';
    },

    /* -------- التاريخ -------- */
    formatDate(d) { 
      return new Date(d).toLocaleDateString('ar-EG'); 
    },

    daysAgo(d) {
      const diff = (new Date() - new Date(d)) / 86400000;
      const days = Math.floor(diff);
      return `{{ trans('messages.ago', [], session('locale')) }} ${days} {{ trans('messages.days', [], session('locale')) }}`;
    },

    /* -------- VIEW -------- */
    openViewModal(order) {
      this.viewOrder = order;
      this.showViewModal = true;
    },

    /* -------- دفع -------- */
    openPaymentModal(order) {
      this.paymentOrder = order;
      const remaining = order.total - order.paid;
      this.paymentAmount = remaining > 0 ? remaining.toFixed(3) : '';
      this.paymentMethod = 'cash';
      this.showPaymentModal = true;
    },

    remainingAmount() {
      if (!this.paymentOrder) return 0;
      return this.paymentOrder.total - this.paymentOrder.paid;
    },

    async confirmPayment() {
      if (!this.paymentOrder) return;

      const amount = parseFloat(this.paymentAmount);
      if (isNaN(amount) || amount <= 0) {
        alert('{{ trans('messages.please_enter_valid_amount', [], session('locale')) }}');
        return;
      }

      const remaining = this.paymentOrder.total - this.paymentOrder.paid;
      if (amount > remaining + 0.0001) {
        alert('{{ trans('messages.amount_exceeds_remaining', [], session('locale')) }}');
        return;
      }

      try {
        const response = await fetch('{{ url('record_payment') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_id: this.paymentOrder.id,
            amount: amount,
            payment_method: this.paymentMethod
          })
        });

        const data = await response.json();

        if (data.success) {
          // Update local order data
          this.paymentOrder.paid = data.order.paid;
          this.paymentOrder.status = data.order.status;
          
          // Update in orders array
          const orderIndex = this.orders.findIndex(o => o.id === this.paymentOrder.id);
          if (orderIndex !== -1) {
            this.orders[orderIndex].paid = data.order.paid;
            this.orders[orderIndex].status = data.order.status;
          }

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.confirm_payment', [], session('locale')) }}',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(data.message);
          }

          this.showPaymentModal = false;
        } else {
          alert('Error: ' + (data.message || 'Failed to record payment'));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تسجيل الدفعة');
      }
    },

    /* -------- تسليم فردي -------- */
    openDeliverModal(order) {
      if (order.status !== 'ready') return;
      this.deliverOrder = order;
      this.showDeliverModal = true;
    },

    async confirmDeliverSingle() {
      if (!this.deliverOrder) return;

      try {
        const response = await fetch('{{ url('update_delivery_status') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_ids: [this.deliverOrder.id]
          })
        });

        const data = await response.json();

        if (data.success) {
          // Update local order data
          this.deliverOrder.status = 'delivered';
          
          // Update in orders array
          const orderIndex = this.orders.findIndex(o => o.id === this.deliverOrder.id);
          if (orderIndex !== -1) {
            this.orders[orderIndex].status = 'delivered';
          }

          // إزالة من التحديد إذا موجود
          const idx = this.selectedReadyIds.indexOf(this.deliverOrder.id);
          if (idx > -1) this.selectedReadyIds.splice(idx, 1);

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.confirm_delivery', [], session('locale')) }}',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(data.message);
          }

          this.showDeliverModal = false;
        } else {
          alert('Error: ' + (data.message || 'Failed to update delivery status'));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تحديث حالة التسليم');
      }
    },

    /* -------- تسليم جماعي -------- */
    openBulkDeliverModal() {
      if (this.selectedReadyIds.length === 0) return;
      this.showBulkDeliverModal = true;
    },

    async confirmBulkDeliver() {
      if (this.selectedReadyIds.length === 0) return;

      try {
        const response = await fetch('{{ url('update_delivery_status') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_ids: this.selectedReadyIds
          })
        });

        const data = await response.json();

        if (data.success) {
          // Update local orders data
          this.selectedReadyIds.forEach(id => {
            const orderIndex = this.orders.findIndex(o => o.id === id);
            if (orderIndex !== -1 && this.orders[orderIndex].status === 'ready') {
              this.orders[orderIndex].status = 'delivered';
            }
          });

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.bulk_delivery', [], session('locale')) }}',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(data.message);
          }

          this.selectedReadyIds = [];
          this.showBulkDeliverModal = false;
        } else {
          alert('Error: ' + (data.message || 'Failed to update delivery status'));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تحديث حالة التسليم');
      }
    },

    /* -------- تحديد جاهز للتسليم -------- */
    isReadySelected(id) {
      return this.selectedReadyIds.includes(id);
    },

    toggleReadySelection(order) {
      if (order.status !== 'ready') return;

      const idx = this.selectedReadyIds.indexOf(order.id);
      if (idx > -1) {
        this.selectedReadyIds.splice(idx, 1);
      } else {
        this.selectedReadyIds.push(order.id);
      }
    },

    /* -------- حذف -------- */
    async deleteOrder(id) {
      if (!confirm('{{ trans('messages.confirm_delete_order', [], session('locale')) }}')) {
        return;
      }

      try {
        const response = await fetch('{{ url('delete_order') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_id: id
          })
        });

        const data = await response.json();

        if (data.success) {
          // Remove from local orders array
          this.orders = this.orders.filter(o => o.id !== id);
          this.selectedReadyIds = this.selectedReadyIds.filter(x => x !== id);

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.deleted_success', [], session('locale')) }}',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(data.message);
          }
        } else {
          alert('Error: ' + (data.message || 'Failed to delete order'));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حذف الطلب');
      }
    }

  }));
});
</script>