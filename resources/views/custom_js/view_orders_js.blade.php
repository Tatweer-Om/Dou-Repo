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
    selectedAccountId: '',
    accounts: [],

    deliverOrder: null,
    selectedReadyIds: [],
    loading: false,
    pageLoading: false,

    /* -------- بيانات الطلبات -------- */
    orders: [],

    /* -------- تحميل البيانات من الخادم -------- */
    async init() {
      this.loading = true;
      await this.loadAccounts();
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
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.error_loading_orders', [], session('locale')) ?: 'Error loading orders' }}'
            });
          } else {
            alert('{{ trans('messages.error_loading_orders', [], session('locale')) ?: 'Error loading orders' }}: ' + (data.message || ''));
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.error_loading_orders', [], session('locale')) ?: 'Error loading orders' }}'
          });
        } else {
          alert('{{ trans('messages.error_loading_orders', [], session('locale')) ?: 'Error loading orders' }}');
        }
      } finally {
        this.loading = false;
      }
      // Reset to page 1 when search or filters change (so search results show from first page)
      this.$watch('search', () => { this.page = 1; });
      this.$watch('filter', () => { this.page = 1; });
      this.$watch('sourceFilter', () => { this.page = 1; });
    },

    async loadAccounts() {
      try {
        const response = await fetch('{{ url('accounts/all') }}');
        const data = await response.json();
        if (Array.isArray(data)) {
          this.accounts = data;
        }
      } catch (error) {
        console.error('Error loading accounts:', error);
      }
    },

    /* -------- فلاتر عامة -------- */
//  filteredOrders() {
//   return this.orders
//     .filter(o => {
//       // Status filter
//       if (this.filter !== 'all' && o.status !== this.filter) {
//         return false;
//       }

//       // Source filter
//       if (this.sourceFilter !== 'all' && o.source !== this.sourceFilter) {
//         return false;
//       }

//       // Search filter
//       const q = this.search.trim().toLowerCase();
//       if (q) {
//         const customerMatch = (o.customer || '').toLowerCase().includes(q);
//         const customerNameMatch = (o.customer_name || '').toLowerCase().includes(q); // added
//         const customerPhoneMatch = (o.customer_phone || '')
//           .toString()
//           .replace(/\s/g, '')
//           .includes(q.replace(/\s/g, ''));

//         const idMatch = String(o.id).includes(q);
//         const orderNoMatch = (o.order_no || '').toLowerCase().includes(q);
//         const specialOrderNoMatch = (o.special_order_no || '').toLowerCase().includes(q);
//         const sourceMatch = this.sourceLabel(o.source).toLowerCase().includes(q);
//         const statusMatch = this.statusLabel(o.status).toLowerCase().includes(q);

//         if (
//           !customerMatch &&
//           !customerNameMatch &&   // added
//           !customerPhoneMatch &&
//           !idMatch &&
//           !orderNoMatch &&
//           !specialOrderNoMatch &&
//           !sourceMatch &&
//           !statusMatch
//         ) {
//           return false;
//         }
//       }

//       return true;
//     });
// },
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
        const customerNameMatch = (o.customer_name || '').toLowerCase().includes(q); // added
        const customerPhoneMatch = (o.customer_phone || '')
          .toString()
          .replace(/\s/g, '')
          .includes(q.replace(/\s/g, ''));

        const idMatch = String(o.id).includes(q);
        const orderNoMatch = (o.order_no || '').toLowerCase().includes(q);
        const specialOrderNoMatch = (o.special_order_no || '').toLowerCase().includes(q);
        const sourceMatch = this.sourceLabel(o.source).toLowerCase().includes(q);
        const statusMatch = this.statusLabel(o.status).toLowerCase().includes(q);

        if (
          !customerMatch &&
          !customerNameMatch &&   // added
          !customerPhoneMatch &&
          !idMatch &&
          !orderNoMatch &&
          !specialOrderNoMatch &&
          !sourceMatch &&
          !statusMatch
        ) {
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

    // Pagination buttons: same as view_stock — window of pages so we don’t show too many (e.g. not all 5 when there are 5 pages)
    pageNumbers() {
      const total = this.totalPages();
      const cur = this.page;
      if (total <= 3) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }
      // windowSize 1 = at most 3 numbers in middle (cur-1, cur, cur+1) so 5 pages don’t all show
      const windowSize = 1;
      const startP = Math.max(1, cur - windowSize);
      const endP = Math.min(total, cur + windowSize);
      const pages = [];
      if (startP > 1) {
        pages.push(1);
        if (startP > 2) pages.push('...');
      }
      for (let j = startP; j <= endP; j++) pages.push(j);
      if (endP < total) {
        if (endP < total - 1) pages.push('...');
        pages.push(total);
      }
      return pages;
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
        walkin: '{{ trans('messages.walk_in', [], session('locale')) }}',
        website: '{{ trans('messages.website', [], session('locale')) }}'
      };
      return labels[s] || s;
    },

    sourceIcon(s) {
      return {
        whatsapp: 'chat',
        walkin: 'storefront',
        website: 'language'
      }[s] || 'info';
    },

    sourceBadge(s) {
      return {
        whatsapp: 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        walkin: 'bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        website: 'bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-[11px] font-semibold'
      }[s] || 'bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[11px] font-semibold';
    },

    countStatus(st) {
      return this.orders.filter(o => o.status === st).length;
    },

    // Abaya quantity per order (sum of item.quantity)
    orderAbayaCount(order) {
      if (!order || !Array.isArray(order.items)) return 0;
      return order.items.reduce((sum, it) => sum + (parseInt(it.quantity, 10) || 0), 0);
    },

    // Total abayas for orders with given status
    countAbayasByStatus(st) {
      return this.orders
        .filter(o => o.status === st)
        .reduce((sum, o) => sum + this.orderAbayaCount(o), 0);
    },

    // Total abayas across all orders
    totalAbayas() {
      return this.orders.reduce((sum, o) => sum + this.orderAbayaCount(o), 0);
    },

    statusLabel(s) {
      const labels = {
        new: '{{ trans('messages.new', [], session('locale')) }}',
        processing: '{{ trans('messages.in_progress', [], session('locale')) }}',
        ready: '{{ trans('messages.ready_for_delivery', [], session('locale')) }}',
        partially_ready: '{{ trans('messages.partially_ready', [], session('locale')) }}',
        partially_processing: '{{ trans('messages.partially_processing', [], session('locale')) ?? 'Partially In Progress' }}',
        saved_in_stock: '{{ trans('messages.saved_in_stock', [], session('locale')) ?: 'Saved in Stock' }}',
        delivered: '{{ trans('messages.delivered', [], session('locale')) }}'
      };
      return labels[s] || '';
    },

    statusBadge(s) {
      return {
        new: 'bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        processing: 'bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        ready: 'bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        partially_ready: 'bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        partially_processing: 'bg-sky-100 text-sky-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        saved_in_stock: 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-[11px] font-semibold',
        delivered: 'bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[11px] font-semibold'
      }[s] || 'bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[11px] font-semibold';
    },

    /* -------- Item Status (Tailor Status) -------- */
    itemStatusLabel(s) {
      const labels = {
        new:              '{{ trans('messages.not_with_tailor', [], session('locale')) }}',
        processing:       '{{ trans('messages.with_tailor', [], session('locale')) }}',
        received:         '{{ trans('messages.ready', [], session('locale')) }}',
        'stock-received': '{{ trans('messages.taken_from_stock', [], session('locale')) }}'
      };
      return labels[s] || s;
    },

    itemStatusBadge(s) {
      return {
        new:            'bg-gray-100 text-gray-700',
        processing:     'bg-blue-100 text-blue-700',
        received:       'bg-emerald-100 text-emerald-700',
        'stock-received': 'bg-teal-100 text-teal-700'
      }[s] || 'bg-gray-100 text-gray-600';
    },

    /* -------- Take Item From Stock -------- */
    async takeItemFromStock(item, order) {
      // Step 1: fetch all color/size combinations available for this stock item
      let colorSizes = [];
      let maxQty = item.quantity;
      try {
        const res  = await fetch('{{ route('special_order.item_stock_availability') }}?item_id=' + item.id);
        const data = await res.json();
        if (!data.success) {
          Swal.fire({ icon: 'error', title: '{{ trans('messages.error', [], session('locale')) }}', text: data.message });
          return;
        }
        colorSizes = data.color_sizes || [];
        maxQty     = data.max_quantity || item.quantity;
        if (!data.has_stock || colorSizes.length === 0) {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.take_from_stock', [], session('locale')) }}',
            text: '{{ trans('messages.insufficient_stock', [], session('locale')) }}'
          });
          return;
        }
      } catch(e) {
        Swal.fire({ icon: 'error', title: '{{ trans('messages.error', [], session('locale')) }}', text: e.message });
        return;
      }

      // Step 2: build the color/size radio picker HTML
      const buildPickerHtml = (selectedId, qtyVal) => {
        const rows = colorSizes.map(cs => {
          const isSelected = cs.id === selectedId;
          const enough     = cs.qty >= 1;
          const rowBg      = isSelected ? 'background:#f0fdf4;border-color:#16a34a;' : 'background:#fff;border-color:#e5e7eb;';
          const qtyColor   = cs.qty === 0 ? '#dc2626' : cs.qty < maxQty ? '#d97706' : '#059669';
          return `
            <label for="cs_${cs.id}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:${enough ? 'pointer' : 'not-allowed'};margin-bottom:6px;${rowBg}transition:all .15s">
              <input type="radio" name="cs_pick" id="cs_${cs.id}" value="${cs.id}" ${isSelected ? 'checked' : ''} ${!enough ? 'disabled' : ''}
                style="width:16px;height:16px;accent-color:#059669;flex-shrink:0">
              <div style="flex:1;text-align:left">
                <span style="font-weight:600;font-size:13px">${cs.color_name || '—'}</span>
                <span style="color:#6b7280;font-size:12px;margin:0 6px">·</span>
                <span style="font-size:13px">${cs.size_name || '—'}</span>
              </div>
              <span style="font-weight:700;font-size:13px;color:${qtyColor};white-space:nowrap">
                {{ trans('messages.available_in_stock', [], session('locale')) }}: ${cs.qty}
              </span>
            </label>`;
        }).join('');

        const selectedCs = colorSizes.find(c => c.id === selectedId);
        const availForSelected = selectedCs ? selectedCs.qty : 0;
        const capForSelected   = Math.min(maxQty, availForSelected);

        return `
          <div style="font-size:13px;color:#374151;margin-bottom:10px">
            <strong>{{ trans('messages.design_name', [], session('locale')) }}:</strong>
            ${item.design_name || item.abaya_code}
            &nbsp;·&nbsp;
            <strong>{{ trans('messages.code', [], session('locale')) }}:</strong> ${item.abaya_code}
          </div>
          <p style="font-size:12px;color:#6b7280;margin-bottom:8px">
            {{ trans('messages.select_color_size', [], session('locale')) ?: 'Select a color / size combination:' }}
          </p>
          <div id="cs_list" style="max-height:220px;overflow-y:auto;padding-right:2px">
            ${rows}
          </div>
          <div style="margin-top:14px;display:flex;align-items:center;gap:10px">
            <label style="font-weight:600;font-size:13px;white-space:nowrap">
              {{ trans('messages.quantity', [], session('locale')) }}:
            </label>
            <input id="take_qty" type="number" min="1" max="${capForSelected}" value="${Math.min(qtyVal || maxQty, capForSelected)}"
              style="width:90px;border:1.5px solid #d1d5db;border-radius:8px;padding:6px 10px;font-size:14px;font-weight:600;text-align:center">
            <span style="font-size:12px;color:#6b7280">
              {{ trans('messages.max', [], session('locale')) ?: 'max' }}: <strong id="qty_max">${capForSelected}</strong>
            </span>
          </div>`;
      };

      // initial selection = first row
      let selectedId = colorSizes[0].id;
      let qtyVal     = Math.min(maxQty, colorSizes[0].qty);

      // Step 3: show Swal with dynamic picker
      const result = await Swal.fire({
        title: '{{ trans('messages.take_from_stock', [], session('locale')) }}',
        html: buildPickerHtml(selectedId, qtyVal),
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '{{ trans('messages.confirm', [], session('locale')) }}',
        cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}',
        focusConfirm: false,
        didOpen: () => {
          // Radio change → update selected + recalculate max qty
          document.querySelectorAll('input[name="cs_pick"]').forEach(radio => {
            radio.addEventListener('change', () => {
              selectedId = parseInt(radio.value);
              const cs   = colorSizes.find(c => c.id === selectedId);
              const cap  = cs ? Math.min(maxQty, cs.qty) : 1;
              const qtyInput = document.getElementById('take_qty');
              const maxLabel = document.getElementById('qty_max');
              if (qtyInput) { qtyInput.max = cap; if (parseInt(qtyInput.value) > cap) qtyInput.value = cap; }
              if (maxLabel) maxLabel.textContent = cap;
              // Highlight selected row
              document.querySelectorAll('label[for^="cs_"]').forEach(l => {
                l.style.background = '';
                l.style.borderColor = '#e5e7eb';
              });
              const lbl = document.querySelector(`label[for="cs_${selectedId}"]`);
              if (lbl) { lbl.style.background = '#f0fdf4'; lbl.style.borderColor = '#16a34a'; }
            });
          });
          // Qty input: enforce min=1 max=cap
          const qtyInput = document.getElementById('take_qty');
          if (qtyInput) {
            qtyInput.addEventListener('input', () => {
              const cap = parseInt(qtyInput.max) || maxQty;
              let v = parseInt(qtyInput.value) || 1;
              if (v < 1) v = 1;
              if (v > cap) v = cap;
              qtyInput.value = v;
            });
          }
        },
        preConfirm: () => {
          const radio = document.querySelector('input[name="cs_pick"]:checked');
          const qtyEl = document.getElementById('take_qty');
          if (!radio) { Swal.showValidationMessage('{{ trans('messages.select_color_size', [], session('locale')) ?: 'Please select a color/size' }}'); return false; }
          const qty = parseInt(qtyEl?.value) || 0;
          if (qty < 1) { Swal.showValidationMessage('{{ trans('messages.quantity_must_be_greater_than_zero', [], session('locale')) ?: 'Quantity must be at least 1' }}'); return false; }
          const cs  = colorSizes.find(c => c.id === parseInt(radio.value));
          if (!cs || qty > cs.qty) { Swal.showValidationMessage('{{ trans('messages.insufficient_stock', [], session('locale')) }}'); return false; }
          if (qty > maxQty) { Swal.showValidationMessage('{{ trans('messages.quantity_needed', [], session('locale')) }}: ' + maxQty); return false; }
          return { color_size_id: parseInt(radio.value), qty };
        }
      });

      if (!result.isConfirmed || !result.value) return;

      const { color_size_id, qty } = result.value;

      // Step 4: call the API
      try {
        const response = await fetch('{{ route('special_order.take_from_stock') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ item_id: item.id, color_size_id, qty })
        });
        const data = await response.json();

        if (data.success) {
          item.tailor_status = 'stock-received';
          const orderIdx = this.orders.findIndex(o => o.id === order.id);
          if (orderIdx !== -1 && data.new_status) {
            this.orders[orderIdx].status = data.new_status;
            if (this.viewOrder && this.viewOrder.id === order.id) {
              this.viewOrder.status = data.new_status;
            }
          }
          Swal.fire({
            icon: 'success',
            title: '{{ trans('messages.success', [], session('locale')) }}',
            text: data.message || '{{ trans('messages.item_taken_from_stock', [], session('locale')) }}',
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          Swal.fire({ icon: 'error', title: '{{ trans('messages.error', [], session('locale')) }}', text: data.message });
        }
      } catch(e) {
        Swal.fire({ icon: 'error', title: '{{ trans('messages.error', [], session('locale')) }}', text: e.message });
      }
    },

    /* -------- التاريخ -------- */
    formatDate(d) { 
      if (!d) return '—';
      const date = new Date(d);
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
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

    /* -------- PRINT BILL -------- */
    printBill(orderId) {
      const billUrl = '{{ url("special-order-bill") }}/' + orderId;
      window.open(billUrl, '_blank');
    },

    /* -------- دفع -------- */
    openPaymentModal(order) {
      this.paymentOrder = order;
      const remaining = order.total - order.paid;
      this.paymentAmount = remaining > 0 ? remaining.toFixed(3) : '';
      this.selectedAccountId = '';
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

      // Validate account selection
      if (!this.selectedAccountId) {
        show_notification('error', '<?= trans("messages.please_select_account", [], session("locale")) ?>');
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
            account_id: this.selectedAccountId
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
        alert('{{ trans('messages.error_recording_payment', [], session('locale')) ?: 'Error recording payment' }}');
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

      // For stock orders, show additional confirmation
      if (this.deliverOrder.is_stock_order) {
        let confirmed = false;
        
        if (typeof Swal !== 'undefined') {
          const result = await Swal.fire({
            title: '{{ trans('messages.confirm_save_to_stock_title', [], session('locale')) ?: 'Save to Stock?' }}',
            text: '{{ trans('messages.confirm_save_to_stock_message', [], session('locale')) ?: 'Are you sure you want to save this order as stock? Items will be added to inventory with their color and size.' }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ trans('messages.yes_save_to_stock', [], session('locale')) ?: 'Yes, Save to Stock' }}',
            cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
          });
          confirmed = result.isConfirmed;
        } else {
          // Fallback to standard confirm
          confirmed = confirm('{{ trans('messages.confirm_save_to_stock_message', [], session('locale')) ?: 'Are you sure you want to save this order as stock? Items will be added to inventory with their color and size.' }}');
        }

        if (!confirmed) {
          return; // User cancelled
        }
      }

      try {
        const response = await fetch('{{ url('update_delivery_status') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_ids: [this.deliverOrder.id],
            add_to_stock: this.deliverOrder.is_stock_order ? true : false
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
        alert('{{ trans('messages.error_updating_delivery', [], session('locale')) ?: 'Error updating delivery status' }}');
      }
    },

    /* -------- تسليم جماعي -------- */
    openBulkDeliverModal() {
      if (this.selectedReadyIds.length === 0) return;
      this.showBulkDeliverModal = true;
    },

    async confirmBulkDeliver() {
      if (this.selectedReadyIds.length === 0) return;

      // Check if any selected orders are stock orders
      const selectedOrders = this.orders.filter(o => this.selectedReadyIds.includes(o.id));
      const hasStockOrders = selectedOrders.some(o => o.is_stock_order);
      
      // Validate that all non-stock orders are fully paid
      const notFullyPaidOrders = selectedOrders.filter(o => {
        if (o.is_stock_order) return false; // Stock orders don't need payment
        return Math.abs(o.total - o.paid) >= 0.001; // Check if not fully paid
      });
      
      if (notFullyPaidOrders.length > 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.cannot_deliver_orders', [], session('locale')) ?: 'Cannot Deliver Orders' }}',
            html: `${notFullyPaidOrders.length} {{ trans('messages.order_not_fully_paid_plural', [], session('locale')) ?: 'order(s) are not fully paid' }}. <br><br>{{ trans('messages.only_fully_paid_deliver', [], session('locale')) ?: 'Only fully paid orders can be delivered. Please pay the remaining amounts or remove these orders from selection.' }}`,
            confirmButtonText: '{{ trans('messages.ok', [], session('locale')) ?: 'OK' }}'
          });
        } else {
          alert(`${notFullyPaidOrders.length} order(s) are not fully paid. Only fully paid orders can be delivered.`);
        }
        
        // Remove non-fully-paid orders from selection
        notFullyPaidOrders.forEach(order => {
          const idx = this.selectedReadyIds.indexOf(order.id);
          if (idx > -1) {
            this.selectedReadyIds.splice(idx, 1);
          }
        });
        
        return; // Stop the delivery process
      }
      
      // For stock orders, show additional confirmation
      if (hasStockOrders) {
        const stockOrdersCount = selectedOrders.filter(o => o.is_stock_order).length;
        let confirmed = false;
        
        if (typeof Swal !== 'undefined') {
          const result = await Swal.fire({
            title: '{{ trans('messages.confirm_save_to_stock_title', [], session('locale')) ?: 'Save to Stock?' }}',
            text: `{{ trans('messages.confirm_save_to_stock_bulk_message', [], session('locale')) ?: 'Are you sure you want to save' }} ${stockOrdersCount} {{ trans('messages.stock_orders_to_inventory', [], session('locale')) ?: 'stock order(s) to inventory? Items will be added with their color and size.' }}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ trans('messages.yes_save_to_stock', [], session('locale')) ?: 'Yes, Save to Stock' }}',
            cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
          });
          confirmed = result.isConfirmed;
        } else {
          // Fallback to standard confirm
          confirmed = confirm(`{{ trans('messages.confirm_save_to_stock_bulk_message', [], session('locale')) ?: 'Are you sure you want to save' }} ${stockOrdersCount} {{ trans('messages.stock_orders_to_inventory', [], session('locale')) ?: 'stock order(s) to inventory? Items will be added with their color and size.' }}`);
        }

        if (!confirmed) {
          return; // User cancelled
        }
      }

      try {
        const response = await fetch('{{ url('update_delivery_status') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_ids: this.selectedReadyIds,
            add_to_stock: hasStockOrders ? true : false
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
          // If some orders were not fully paid, remove them from selection
          if (data.not_fully_paid_order_ids && data.not_fully_paid_order_ids.length > 0) {
            data.not_fully_paid_order_ids.forEach(orderId => {
              const idx = this.selectedReadyIds.indexOf(orderId);
              if (idx > -1) {
                this.selectedReadyIds.splice(idx, 1);
              }
            });
          }

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.failed_to_update_delivery', [], session('locale')) ?: 'Failed to update delivery status' }}',
              confirmButtonText: '{{ trans('messages.ok', [], session('locale')) ?: 'OK' }}'
            });
          } else {
            alert('Error: ' + (data.message || 'Failed to update delivery status'));
          }
        }
      } catch (error) {
        console.error('Error:', error);
        alert('{{ trans('messages.error_updating_delivery', [], session('locale')) ?: 'Error updating delivery status' }}');
      }
    },

    /* -------- تحديد جاهز للتسليم -------- */
    isReadySelected(id) {
      return this.selectedReadyIds.includes(id);
    },

    toggleReadySelection(order) {
      // Only allow selection if: status is ready, not a stock order, AND fully paid
      if (order.status !== 'ready' || order.is_stock_order) return;
      
      // Check if order is fully paid (with tolerance for floating point comparison)
      const isFullyPaid = Math.abs(order.total - order.paid) < 0.001;
      if (!isFullyPaid) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.cannot_select_order', [], session('locale')) ?: 'Cannot Select Order' }}',
            text: '{{ trans('messages.order_not_fully_paid', [], session('locale')) ?: 'This order is not fully paid. Only fully paid orders can be delivered.' }}'
          });
        } else {
          alert('{{ trans('messages.order_not_fully_paid', [], session('locale')) ?: 'This order is not fully paid. Only fully paid orders can be delivered.' }}');
        }
        return;
      }

      const idx = this.selectedReadyIds.indexOf(order.id);
      if (idx > -1) {
        this.selectedReadyIds.splice(idx, 1);
      } else {
        this.selectedReadyIds.push(order.id);
      }
    },

    /* -------- حذف -------- */
    async deleteOrder(id) {
      if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
          title: '{{ trans('messages.confirm_delete_title', [], session('locale')) ?: 'Are you sure?' }}',
          text: '{{ trans('messages.confirm_delete_order', [], session('locale')) ?: 'Do you want to delete this order? This action cannot be undone.' }}',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#ef4444',
          cancelButtonColor: '#6b7280',
          confirmButtonText: '{{ trans('messages.yes_delete', [], session('locale')) ?: 'Yes, Delete' }}',
          cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
        });
        if (!result.isConfirmed) return;
      } else {
        if (!confirm('{{ trans('messages.confirm_delete_order', [], session('locale')) }}')) return;
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
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: '{{ trans('messages.error', [], session('locale')) }}',
              text: data.message || '{{ trans('messages.failed_to_delete_order', [], session('locale')) ?: 'Failed to delete order' }}'
            });
          } else {
            alert('Error: ' + (data.message || 'Failed to delete order'));
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: '{{ trans('messages.error', [], session('locale')) }}',
            text: '{{ trans('messages.error_deleting_order', [], session('locale')) ?: 'Error deleting order' }}'
          });
        } else {
          alert('{{ trans('messages.error_deleting_order', [], session('locale')) ?: 'Error deleting order' }}');
        }
      }
    }

  }));
});
</script>