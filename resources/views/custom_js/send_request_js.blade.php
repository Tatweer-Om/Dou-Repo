<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('assignTailorPage', () => ({

    /* Modes */
    mode: 'view',

    /* MODALS */
    showConfirmModal: false,
    showDetailsModal: false,
    showPrintModal: false,

    /* FILTERS */
    search: '',
    searchView: '',
    tailorViewFilter: '',
    phoneRegionViewFilter: 'all',

    tailorAssignFilter: '',
    phoneRegionAssignFilter: 'all',

    filter: {
      from: '',
      to: ''
    },

    statusFilter: '',

    /* Pagination (like view_stock) */
    perPage: 10,
    currentPageView: 1,
    currentPageAssign: 1,

    /* Sending summary number (required for Send Now / Print) */
    sendingSummaryNumber: '',

    /* Lists */
    selectedItems: [],
    receivedList: [],
    loading: false,
    isReceiving: false,

    selectedItem: {},

    /* Tailors list */
    tailors: [],

    /* Data from backend */
    newItems: [],
    processingItems: [],

    /* ======================================================================= */
    /* INITIALIZE - LOAD DATA FROM BACKEND */
    /* ======================================================================= */
    async init() {
      await this.loadData();
      
      // Clear selected items when switching to assign mode
      // This prevents items selected for printing in view mode from appearing in sending summary
      this.$watch('mode', (newMode) => {
        if (newMode === 'assign') {
          this.selectedItems = [];
          this.sendingSummaryNumber = '';
        }
      });
      // Reset to page 1 when view filters change
      this.$watch('searchView', () => { this.currentPageView = 1; });
      this.$watch('tailorViewFilter', () => { this.currentPageView = 1; });
      this.$watch('phoneRegionViewFilter', () => { this.currentPageView = 1; });
      // Reset to page 1 when assign filters change
      this.$watch('search', () => { this.currentPageAssign = 1; });
      this.$watch('filter.from', () => { this.currentPageAssign = 1; });
      this.$watch('filter.to', () => { this.currentPageAssign = 1; });
      this.$watch('statusFilter', () => { this.currentPageAssign = 1; });
      this.$watch('tailorAssignFilter', () => { this.currentPageAssign = 1; });
      this.$watch('phoneRegionAssignFilter', () => { this.currentPageAssign = 1; });
    },

    async loadData() {
    this.loading = true;
    try {
        const response = await fetch('{{ route("send_request.data") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',        // ← Very Important
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // First check if the response is OK
        if (!response.ok) {
            const errorText = await response.text();   // Read as text first
            console.error('Server Error Response:', errorText.substring(0, 500)); // Log part of it
            throw new Error(`Server error (${response.status})`);
        }

        const data = await response.json();

        if (data.success) {
            this.tailors = data.tailors || [];
            this.newItems = data.new || [];
            this.processingItems = data.processing || [];
            this.selectedItems = [];
            this.receivedList = [];
            this.currentPageView = 1;
            this.currentPageAssign = 1;

            // Auto-assign logic
            this.newItems.forEach(item => {
                if (!item.tailor_id && item.originalTailorId) {
                    item.tailor_id = item.originalTailorId;
                    item.tailor_name = this.tailorNameById(item.originalTailorId);
                }
            });
        } else {
            throw new Error(data.message || 'Failed to load data');
        }
    } catch (error) {
        console.error('Error loading data:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: '{{ trans("messages.error", [], session("locale")) }}',
                text: error.message || 'Something went wrong while loading data'
            });
        } else {
            alert('Error: ' + (error.message || 'Failed to load data'));
        }
    } finally {
        this.loading = false;
    }
},

    tailorNameById(id) {
      if (!id) return '';
      const tailor = this.tailors.find(t => String(t.id) === String(id));
      return tailor ? tailor.name : '';
    },

    /* Digits only; Oman = number starts with 968 (after stripping non-digits) */
    normalizePhoneDigits(raw) {
      if (raw == null || raw === '') return '';
      return String(raw).replace(/\D/g, '');
    },

    isOmanPhoneNumber(raw) {
      const d = this.normalizePhoneDigits(raw);
      return d.startsWith('968');
    },

    matchesPhoneRegionFilter(phone, region) {
      if (!region || region === 'all') return true;
      const oman = this.isOmanPhoneNumber(phone);
      if (region === 'oman') return oman;
      if (region === 'outside') return !oman;
      return true;
    },

    viewTailorTabClass(id) {
      const sel = String(this.tailorViewFilter ?? '');
      const cur = String(id);
      return sel === cur
        ? 'px-4 py-2 rounded-full bg-indigo-600 text-white font-medium shadow text-xs md:text-sm shrink-0'
        : 'px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm shrink-0';
    },

    viewPhoneTabClass(type) {
      return this.phoneRegionViewFilter === type
        ? 'px-4 py-2 rounded-full bg-purple-600 text-white font-medium shadow text-xs md:text-sm shrink-0'
        : 'px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm shrink-0';
    },

    assignTailorTabClass(id) {
      const sel = String(this.tailorAssignFilter ?? '');
      const cur = String(id);
      return sel === cur
        ? 'px-4 py-2 rounded-full bg-indigo-600 text-white font-medium shadow text-xs md:text-sm shrink-0'
        : 'px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm shrink-0';
    },

    assignPhoneTabClass(type) {
      return this.phoneRegionAssignFilter === type
        ? 'px-4 py-2 rounded-full bg-purple-600 text-white font-medium shadow text-xs md:text-sm shrink-0'
        : 'px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm shrink-0';
    },

    updateTailorSelection(item) {
      item.tailor_name = this.tailorNameById(item.tailor_id);
    },


    /* ======================================================================= */
    /* OPEN DETAILS */
    /* ======================================================================= */
    openDetails(item) {
      this.selectedItem = item;
      this.showDetailsModal = true;
    },


    /* ======================================================================= */
    /* PRINT SINGLE ITEM */
    /* ======================================================================= */
    printSingle(item) {
      let w = window.open('', '_blank');
      w.document.write(`
        <html>
        <head>
          <title>ورقة الخياط</title>
          <style>
            body { font-family: sans-serif; direction: rtl; padding: 20px; }
            h1 { margin-bottom: 15px; }
            .box { border: 1px solid #ccc; padding: 15px; border-radius: 12px; margin-bottom:20px; }
            img { width: 180px; border-radius: 10px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 8px; font-size: 14px; }
          </style>
        </head>
        <body>

          <h1>{{ trans('messages.abaya_details', [], session('locale')) }}</h1>

          <img src="${item.image}">

          <div class="box">
            <p><strong>{{ trans('messages.order_number', [], session('locale')) }}:</strong> ${item.orderId}</p>
            <p><strong>{{ trans('messages.customer', [], session('locale')) }}:</strong> ${item.customer}</p>
            <p><strong>{{ trans('messages.tailor', [], session('locale')) }}:</strong> ${item.tailor_name || item.tailor || ''}</p>
            <p><strong>{{ trans('messages.order_source', [], session('locale')) }}:</strong> ${item.source}</p>
          </div>

          <table>
            <tr><th>{{ trans('messages.abaya_length', [], session('locale')) }}</th><td>${item.length}</td></tr>
            <tr><th>{{ trans('messages.bust_one_side', [], session('locale')) }}</th><td>${item.bust}</td></tr>
            <tr><th>{{ trans('messages.sleeves_length', [], session('locale')) }}</th><td>${item.sleeves}</td></tr>
            <tr><th>{{ trans('messages.buttons', [], session('locale')) }}</th><td>${item.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'}</td></tr>
          </table>

          <h3>{{ trans('messages.notes', [], session('locale')) }}</h3>
          <p>${item.notes}</p>

        </body>
        </html>
      `);

      w.document.close();
      w.print();
    },


    /* ======================================================================= */
    /* PRINT MULTIPLE ITEMS */
    /* ======================================================================= */
    doPrintList() {
      if (this.selectedItems.length === 0) {
        alert('{{ trans('messages.no_items_selected', [], session('locale')) }}');
        return;
      }

      let w = window.open('', '_blank');

      // Create a single table with all items, including tailor name in each row
      let rows = this.selectedItems.map((i, idx) => {
        const tailorIdToUse = i.tailor_id || i.originalTailorId;
        const tailorName = i.tailor_name || this.tailorNameById(tailorIdToUse) || i.originalTailor || '{{ trans('messages.not_assigned', [], session('locale')) }}';
        return `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td>${i.order_no || ('#' + i.orderId)}</td>
            <td><strong>${tailorName}</strong></td>
            <td><strong>${i.abayaName || i.code || '—'}</strong><br><small style="color: #666;">{{ trans('messages.code', [], session('locale')) }}: ${i.code || '—'}</small></td>
            <td class="text-center">${i.quantity || 1}</td>
            <td>
              <strong>{{ trans('messages.abaya_length', [], session('locale')) }}:</strong> ${i.length || '—'}<br>
              <strong>{{ trans('messages.bust_one_side', [], session('locale')) }}:</strong> ${i.bust || '—'}<br>
              <strong>{{ trans('messages.sleeves_length', [], session('locale')) }}:</strong> ${i.sleeves || '—'}<br>
              <strong>{{ trans('messages.buttons', [], session('locale')) }}:</strong> ${i.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'}
            </td>
            <td>${i.notes || '—'}</td>
          </tr>
        `;
      }).join('');

      let content = `
        <table>
          <thead>
            <tr>
              <th style="width: 40px;">#</th>
              <th>{{ trans('messages.order_number', [], session('locale')) }}</th>
              <th>{{ trans('messages.tailor', [], session('locale')) }}</th>
              <th>{{ trans('messages.abaya', [], session('locale')) }}</th>
              <th style="width: 60px;">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th>{{ trans('messages.sizes', [], session('locale')) }}</th>
              <th>{{ trans('messages.notes', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      `;

      w.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>{{ trans('messages.tailor_sheet_orders', [], session('locale')) }}</title>
          <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
              padding: 20px; 
              direction: rtl; 
              background: #fff;
              color: #333;
            }
            .header {
              text-align: center;
              margin-bottom: 30px;
              padding-bottom: 20px;
              border-bottom: 3px solid #4f46e5;
            }
            .header h1 {
              color: #4f46e5;
              font-size: 24px;
              margin-bottom: 10px;
            }
            .header .info {
              color: #666;
              font-size: 14px;
            }
            table { 
              width: 100%; 
              border-collapse: collapse; 
              font-size: 13px;
              margin-bottom: 20px;
              box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            th { 
              background: linear-gradient(to bottom, #f3f4f6, #e5e7eb);
              color: #374151;
              font-weight: 600;
              padding: 12px 8px;
              border: 1px solid #d1d5db;
              text-align: right;
            }
            td { 
              border: 1px solid #e5e7eb;
              padding: 10px 8px;
              text-align: right;
            }
            tr:nth-child(even) {
              background-color: #f9fafb;
            }
            tr:hover {
              background-color: #f3f4f6;
            }
            .text-center {
              text-align: center;
            }
            @media print {
              body { padding: 10px; }
              table { page-break-inside: auto; }
              tr { page-break-inside: avoid; page-break-after: auto; }
              thead { display: table-header-group; }
              tfoot { display: table-footer-group; }
              @page { margin: 1cm; }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>{{ trans('messages.tailor_sheet', [], session('locale')) }}</h1>
            <div class="info">
              {{ trans('messages.total_items', [], session('locale')) }}: ${this.selectedItems.length} | 
              {{ trans('messages.printed_on', [], session('locale')) }}: ${new Date().toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
            </div>
          </div>
          ${content}
        </body>
        </html>
      `);

      w.document.close();
      setTimeout(() => {
        w.print();
      }, 250);
      this.showPrintModal = false;
    },


    /* ======================================================================= */
    /* SELECT ITEMS */
    /* ======================================================================= */
    toggleSelection(item) {
      let exists = this.selectedItems.find(i => i.rowId === item.rowId);
      if (exists) {
        this.selectedItems = this.selectedItems.filter(i => i.rowId !== item.rowId);
      } else {
        this.selectedItems.push(item);
      }
    },

    toggleReceive(item) {
      if (this.receivedList.includes(item.rowId)) {
        this.receivedList = this.receivedList.filter(id => id !== item.rowId);
      } else {
        this.receivedList.push(item.rowId);
      }
    },


    /* ======================================================================= */
    /* CONFIRM RECEIVE */
    /* ======================================================================= */
    async confirmReceive() {
      if (this.receivedList.length === 0 || this.isReceiving) return;

      this.isReceiving = true;

      try {
        const response = await fetch('{{ route('send_request.receive') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ item_ids: this.receivedList })
        });

        const data = await response.json();
        
        if (data.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: data.message || '{{ trans('messages.abayas_received', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert("✔ " + (data.message || '{{ trans('messages.abayas_received', [], session('locale')) }}'));
          }
          
          await this.loadData();
          this.showConfirmModal = false;
          this.receivedList = [];
        } else {
          throw new Error(data.message || 'Failed to mark items as received');
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
          alert('Error: ' + error.message);
        }
      } finally {
        this.isReceiving = false;
      }
    },


    /* ======================================================================= */
    /* FILTERING NEW ABAYAS (ASSIGN MODE) */
    /* ======================================================================= */
    filteredAbayas() {
      return this.newItems
        .filter(i => {
          const term = this.search.trim().toLowerCase();
          if (!term) return true;
          return (
            (i.customer || '').toLowerCase().includes(term) ||
            (i.customer_phone || '').toString().toLowerCase().includes(term) ||
            String(i.orderId || '').includes(term) ||
            (i.code || '').toLowerCase().includes(term)
          );
        })
        .filter(i => {
          if (!this.filter.from && !this.filter.to) return true;
          if (!i.date) return true;

          let d = new Date(i.date);
          let from = this.filter.from ? new Date(this.filter.from) : null;
          let to = this.filter.to ? new Date(this.filter.to) : null;

          if (from && d < from) return false;
          if (to && d > to) return false;

          return true;
        })
        .filter(i => {
          if (!this.tailorAssignFilter) return true;
          const tid = i.tailor_id || i.originalTailorId || '';
          return String(tid) === String(this.tailorAssignFilter);
        })
        .filter(i => this.matchesPhoneRegionFilter(i.customer_phone, this.phoneRegionAssignFilter));
    },


    /* ======================================================================= */
    /* SORT PROCESSING (VIEW MODE) */
    /* ======================================================================= */
    sortedProcessing() {
      return this.processingItems
        .filter(i => {
          const term = this.searchView.trim().toLowerCase();
          if (!term) return true;
          return (
            (i.customer || '').toLowerCase().includes(term) ||
            (i.customer_phone || '').toString().toLowerCase().includes(term) ||
            String(i.orderId || '').includes(term) ||
            (i.tailor_name || i.tailor || '').toLowerCase().includes(term) ||
            (i.code || '').toLowerCase().includes(term)
          );
        })
        .filter(i => {
          if (!this.tailorViewFilter) return true;
          return String(i.tailor_id) === String(this.tailorViewFilter);
        })
        .filter(i => this.matchesPhoneRegionFilter(i.customer_phone, this.phoneRegionViewFilter))
        .sort((a, b) => {
          const lateA = this.isLate(a.date) ? 1 : 0;
          const lateB = this.isLate(b.date) ? 1 : 0;
          return lateB - lateA;
        });
    },


    /* ======================================================================= */
    /* PAGINATION (VIEW MODE) */
    /* ======================================================================= */
    totalPagesView() {
      const total = this.sortedProcessing().length;
      return Math.max(1, Math.ceil(total / this.perPage));
    },
    paginatedProcessing() {
      const list = this.sortedProcessing();
      const page = Math.min(this.currentPageView, this.totalPagesView());
      const start = (page - 1) * this.perPage;
      return list.slice(start, start + this.perPage);
    },
    goToPageView(page) {
      const total = this.totalPagesView();
      if (page >= 1 && page <= total) this.currentPageView = page;
    },
    paginationPagesView() {
      const cur = this.currentPageView;
      const last = this.totalPagesView();
      const windowSize = 2;
      if (last <= 7) {
        return Array.from({ length: last }, (_, i) => ({ type: 'num', page: i + 1, label: String(i + 1) }));
      }
      const result = [];
      const showFirst = cur > windowSize + 2;
      const showLast = cur < last - windowSize - 1;
      const startP = Math.max(1, cur - windowSize);
      const endP = Math.min(last, cur + windowSize);
      if (showFirst) {
        result.push({ type: 'num', page: 1, label: '1' });
        result.push({ type: 'dots', page: null, label: '...' });
      }
      for (let j = startP; j <= endP; j++) {
        result.push({ type: 'num', page: j, label: String(j) });
      }
      if (showLast) {
        result.push({ type: 'dots', page: null, label: '...' });
        result.push({ type: 'num', page: last, label: String(last) });
      }
      return result;
    },


    /* ======================================================================= */
    /* PAGINATION (ASSIGN MODE) */
    /* ======================================================================= */
    totalPagesAssign() {
      const total = this.filteredAbayas().length;
      return Math.max(1, Math.ceil(total / this.perPage));
    },
    paginatedAbayas() {
      const list = this.filteredAbayas();
      const page = Math.min(this.currentPageAssign, this.totalPagesAssign());
      const start = (page - 1) * this.perPage;
      return list.slice(start, start + this.perPage);
    },
    goToPageAssign(page) {
      const total = this.totalPagesAssign();
      if (page >= 1 && page <= total) this.currentPageAssign = page;
    },
    paginationPagesAssign() {
      const cur = this.currentPageAssign;
      const last = this.totalPagesAssign();
      const windowSize = 2;
      if (last <= 7) {
        return Array.from({ length: last }, (_, i) => ({ type: 'num', page: i + 1, label: String(i + 1) }));
      }
      const result = [];
      const showFirst = cur > windowSize + 2;
      const showLast = cur < last - windowSize - 1;
      const startP = Math.max(1, cur - windowSize);
      const endP = Math.min(last, cur + windowSize);
      if (showFirst) {
        result.push({ type: 'num', page: 1, label: '1' });
        result.push({ type: 'dots', page: null, label: '...' });
      }
      for (let j = startP; j <= endP; j++) {
        result.push({ type: 'num', page: j, label: String(j) });
      }
      if (showLast) {
        result.push({ type: 'dots', page: null, label: '...' });
        result.push({ type: 'num', page: last, label: String(last) });
      }
      return result;
    },


    /* ======================================================================= */
    /* DATE HELPERS */
    /* ======================================================================= */
    isLate(date) {
      return ((new Date() - new Date(date)) / 86400000) >= 12;
    },

    daysAgo(date) {
      return "{{ trans('messages.ago', [], session('locale')) }} " + Math.floor((new Date() - new Date(date)) / 86400000) + " {{ trans('messages.days', [], session('locale')) }}";
    },


    /* ======================================================================= */
    /* GROUP BY TAILOR */
    /* ======================================================================= */
    groupByTailor() {
      let result = {};
      this.selectedItems.forEach(i => {
        const tailorName = this.tailorNameById(i.tailor_id) || '{{ trans('messages.not_assigned', [], session('locale')) }}';
        const quantity = i.quantity || 1;
        result[tailorName] = (result[tailorName] || 0) + quantity;
      });
      return result;
    },

    /* Can enable Send Now / Print only when sending summary number is entered */
    canSendOrPrint() {
      return (this.sendingSummaryNumber || '').trim() !== '';
    },

    /* Check if selected items are for a single tailor only */
    selectedItemsSingleTailor() {
      const ids = new Set();
      this.selectedItems.forEach(i => {
        const tid = i.tailor_id || i.originalTailorId;
        if (tid) ids.add(String(tid));
      });
      return ids.size <= 1;
    },


    /* ======================================================================= */
    /* PRINT SELECTED ITEMS (FOR SENDING TO TAILOR) */
    /* ======================================================================= */
    printSelectedItems() {
      if (this.selectedItems.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.no_items_selected', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_items_to_print', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.no_items_selected', [], session('locale')) }}');
        }
        return;
      }
      if (!this.selectedItemsSingleTailor()) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.one_tailor_at_a_time', [], session('locale')) ?: 'One tailor at a time' }}',
            text: '{{ trans('messages.one_tailor_at_a_time_text', [], session('locale')) ?: 'At a time you can send order to only one tailor.' }}'
          });
        } else {
          alert('{{ trans('messages.one_tailor_at_a_time_text', [], session('locale')) ?: 'At a time you can send order to only one tailor.' }}');
        }
        return;
      }

      let w = window.open('', '_blank');

      // Group items by tailor for better organization
      let itemsByTailor = {};
      this.selectedItems.forEach(item => {
        // Use tailor_id if set, otherwise use originalTailorId
        const tailorIdToUse = item.tailor_id || item.originalTailorId;
        const tailorName = this.tailorNameById(tailorIdToUse) || item.originalTailor || '{{ trans('messages.not_assigned', [], session('locale')) }}';
        if (!itemsByTailor[tailorName]) {
          itemsByTailor[tailorName] = [];
        }
        itemsByTailor[tailorName].push(item);
      });

      // Build content grouped by tailor
      let content = '';
      Object.keys(itemsByTailor).forEach(tailorName => {
        const items = itemsByTailor[tailorName];
        content += `
          <div style="margin-bottom: 30px; page-break-inside: avoid;">
            <h2 style="color: #4f46e5; font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #4f46e5;">
              {{ trans('messages.tailor', [], session('locale')) }}: ${tailorName}
            </h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
              <thead>
                <tr style="background: linear-gradient(to bottom, #f3f4f6, #e5e7eb);">
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: right; width: 40px;">#</th>
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: right;">{{ trans('messages.order_number', [], session('locale')) }}</th>
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: right;">{{ trans('messages.abaya', [], session('locale')) }}</th>
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: center; width: 80px;">{{ trans('messages.quantity', [], session('locale')) }}</th>
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: right;">{{ trans('messages.sizes', [], session('locale')) }}</th>
                  <th style="border: 1px solid #d1d5db; padding: 10px; text-align: right;">{{ trans('messages.notes', [], session('locale')) }}</th>
                </tr>
              </thead>
              <tbody>
        `;

        items.forEach((item, idx) => {
          content += `
            <tr style="border-bottom: 1px solid #e5e7eb;">
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: center;">${idx + 1}</td>
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: right; font-weight: 600; color: #4f46e5;">
                ${item.order_no || ('#' + item.orderId)}
              </td>
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: right;">
                <strong>${item.abayaName || item.code || '—'}</strong><br>
                <small style="color: #666;">{{ trans('messages.code', [], session('locale')) }}: ${item.code || '—'}</small>
              </td>
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: center; font-weight: 600; color: #4f46e5;">
                ${item.quantity || 1}
              </td>
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: right; font-size: 12px;">
                <strong>{{ trans('messages.abaya_length', [], session('locale')) }}:</strong> ${item.length || '—'}<br>
                <strong>{{ trans('messages.bust_one_side', [], session('locale')) }}:</strong> ${item.bust || '—'}<br>
                <strong>{{ trans('messages.sleeves_length', [], session('locale')) }}:</strong> ${item.sleeves || '—'}<br>
                <strong>{{ trans('messages.buttons', [], session('locale')) }}:</strong> ${item.buttons ? '{{ trans('messages.yes', [], session('locale')) }}' : '{{ trans('messages.no', [], session('locale')) }}'}
              </td>
              <td style="border: 1px solid #e5e7eb; padding: 10px; text-align: right;">
                ${item.notes || '—'}
              </td>
            </tr>
          `;
        });

        content += `
              </tbody>
            </table>
          </div>
        `;
      });

      w.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>{{ trans('messages.abayas_to_send_to_tailor', [], session('locale')) }}</title>
          <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
              padding: 20px; 
              direction: rtl; 
              background: #fff;
              color: #333;
            }
            .header {
              text-align: center;
              margin-bottom: 30px;
              padding-bottom: 20px;
              border-bottom: 3px solid #4f46e5;
            }
            .header h1 {
              color: #4f46e5;
              font-size: 24px;
              margin-bottom: 10px;
            }
            .header .info {
              color: #666;
              font-size: 14px;
            }
            table { 
              width: 100%; 
              border-collapse: collapse; 
              font-size: 13px;
              margin-bottom: 20px;
              box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            th { 
              background: linear-gradient(to bottom, #f3f4f6, #e5e7eb);
              color: #374151;
              font-weight: 600;
              padding: 12px 8px;
              border: 1px solid #d1d5db;
              text-align: right;
            }
            td { 
              border: 1px solid #e5e7eb;
              padding: 10px 8px;
              text-align: right;
            }
            tr:nth-child(even) {
              background-color: #f9fafb;
            }
            tr:hover {
              background-color: #f3f4f6;
            }
            .text-center {
              text-align: center;
            }
            @media print {
              body { padding: 10px; }
              table { page-break-inside: auto; }
              tr { page-break-inside: avoid; page-break-after: auto; }
              thead { display: table-header-group; }
              tfoot { display: table-footer-group; }
              @page { margin: 1cm; }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>{{ trans('messages.abayas_to_send_to_tailor', [], session('locale')) }}</h1>
            <div class="info">
              {{ trans('messages.total_items', [], session('locale')) }}: ${this.selectedItems.length} | 
              {{ trans('messages.total_quantity', [], session('locale')) }}: ${this.selectedItems.reduce((sum, item) => sum + (item.quantity || 1), 0)} | 
              {{ trans('messages.printed_on', [], session('locale')) }}: ${new Date().toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
            </div>
          </div>
          ${content}
        </body>
        </html>
      `);

      w.document.close();
      setTimeout(() => {
        w.print();
      }, 250);
    },


    /* ======================================================================= */
    /* SUBMIT SELECTED ITEMS TO TAILOR */
    /* ======================================================================= */
    async submitToTailor() {
      if (this.selectedItems.length === 0) return;
      if (!this.canSendOrPrint()) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.please_enter_sending_summary_number', [], session('locale')) ?: 'Sending Summary Number required' }}',
            text: '{{ trans('messages.enter_sending_summary_number', [], session('locale')) ?: 'Please enter the Sending Summary Number.' }}'
          });
        } else {
          alert('{{ trans('messages.please_enter_sending_summary_number', [], session('locale')) ?: 'Please enter Sending Summary Number.' }}');
        }
        return;
      }
      if (!this.selectedItemsSingleTailor()) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.one_tailor_at_a_time', [], session('locale')) ?: 'One tailor at a time' }}',
            text: '{{ trans('messages.one_tailor_at_a_time_text', [], session('locale')) ?: 'At a time you can send order to only one tailor.' }}'
          });
        } else {
          alert('{{ trans('messages.one_tailor_at_a_time_text', [], session('locale')) ?: 'At a time you can send order to only one tailor.' }}');
        }
        return;
      }

      // Auto-assign original tailor if not set
      this.selectedItems.forEach(item => {
        if (!item.tailor_id && item.originalTailorId) {
          item.tailor_id = item.originalTailorId;
          item.tailor_name = this.tailorNameById(item.originalTailorId);
        }
      });

      const assignments = this.selectedItems.map(item => ({
        item_id: item.rowId,
        tailor_id: item.tailor_id || item.originalTailorId
      }));

      // Validate all items have tailor selected (either assigned or original)
      if (assignments.some(a => !a.tailor_id)) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: '{{ trans('messages.select_tailor_first', [], session('locale')) }}',
            text: '{{ trans('messages.please_select_tailor', [], session('locale')) }}'
          });
        } else {
          alert('{{ trans('messages.select_tailor_first', [], session('locale')) }}');
        }
        return;
      }

      try {
        const response = await fetch('{{ route('send_request.assign') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            assignments,
            sending_summary_number: (this.sendingSummaryNumber || '').trim()
          })
        });

        const data = await response.json();
        
        if (data.success) {
          // Automatically open PDF in new tab with all abaya entries
          if (data.assigned_item_ids && data.assigned_item_ids.length > 0) {
            try {
              // Build query string with item IDs
              const itemIds = data.assigned_item_ids;
              const queryParams = itemIds.map(id => `item_ids[]=${encodeURIComponent(id)}`).join('&');
              const exportUrl = '{{ route('send_request.export_pdf') }}?' + queryParams;
              
              // Open PDF in new tab
              window.open(exportUrl, '_blank');
            } catch (pdfError) {
              console.error('Error opening PDF:', pdfError);
              // Continue even if PDF opening fails
            }
          }
          
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: data.message || '{{ trans('messages.abayas_sent_to_tailor', [], session('locale')) }}',
              text: '{{ trans('messages.pdf_opened', [], session('locale')) ?: 'PDF will be opened in a new tab' }}',
              timer: 3000,
              showConfirmButton: false
            });
          } else {
            alert("✔ " + (data.message || '{{ trans('messages.abayas_sent_to_tailor', [], session('locale')) }}'));
          }
          
          await this.loadData();
          this.mode = 'view';
          this.selectedItems = [];
          this.sendingSummaryNumber = '';
        } else {
          throw new Error(data.message || 'Failed to assign items to tailor');
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
          alert('Error: ' + error.message);
        }
      }
    }

  }));
});
</script>